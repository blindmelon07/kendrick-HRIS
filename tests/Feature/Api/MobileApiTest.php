<?php

use App\Enums\FilingStatus;
use App\Enums\PayrollPeriodStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Filing;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->employee = Employee::factory()->withUser()->create();
});

test('employees exchange credentials for a token', function () {
    $response = $this->postJson(route('api.v1.auth.token.store'), [
        'email' => $this->employee->user->email,
        'password' => 'password',
        'device_name' => 'Juan\'s iPhone',
    ]);

    $response->assertCreated()->assertJsonStructure(['token']);

    $this->withToken($response->json('token'))
        ->getJson(route('api.v1.me'))
        ->assertOk()
        ->assertJsonPath('data.employee.employee_number', $this->employee->employee_number);
});

test('token login rejects bad credentials and accounts without an employee record', function () {
    $this->postJson(route('api.v1.auth.token.store'), [
        'email' => $this->employee->user->email,
        'password' => 'wrong',
        'device_name' => 'Phone',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');

    $hrUser = User::factory()->role(UserRole::HrPayroll)->create();

    $this->postJson(route('api.v1.auth.token.store'), [
        'email' => $hrUser->email,
        'password' => 'password',
        'device_name' => 'Phone',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

test('the profile includes a photo the app can load with its token', function () {
    Storage::fake('local');
    Storage::disk('local')->put('employee-photos/juan.jpg', 'image-bytes');
    $this->employee->update(['photo_path' => 'employee-photos/juan.jpg']);
    $token = $this->employee->user->createToken('Phone')->plainTextToken;

    $photoUrl = $this->withToken($token)->getJson(route('api.v1.me'))->assertOk()->json('data.photo_url');

    expect($photoUrl)->toContain('/api/v1/me/photo');

    $this->withToken($token)->get($photoUrl)->assertOk();
});

test('the profile photo is null and 404 when the employee has none', function () {
    Sanctum::actingAs($this->employee->user);

    $this->getJson(route('api.v1.me'))->assertJsonPath('data.photo_url', null);
    $this->getJson(route('api.v1.me.photo'))->assertNotFound();
});

test('signing out revokes the current token', function () {
    $token = $this->employee->user->createToken('Phone')->plainTextToken;

    $this->withToken($token)->deleteJson(route('api.v1.auth.token.destroy'))->assertNoContent();

    expect($this->employee->user->tokens()->count())->toBe(0);
});

test('unauthenticated requests get a JSON 401', function () {
    $this->getJson(route('api.v1.home'))->assertUnauthorized();
});

test('the home screen summarizes today', function () {
    $this->travelTo('2026-10-05 12:00');
    TimeLog::factory()->for($this->employee)->create(['logged_at' => '2026-10-05 06:58:00']);
    Filing::factory()->for($this->employee)->create();
    Sanctum::actingAs($this->employee->user);

    $this->getJson(route('api.v1.home'))
        ->assertOk()
        ->assertJsonPath('data.today_logs', ['06:58'])
        ->assertJsonPath('data.pending_filings', 1)
        ->assertJsonPath('data.endorsements_waiting', null)
        ->assertJsonPath('data.latest_payslip', null);
});

test('attendance returns daily records and a summary for the range', function () {
    TimeLog::factory()->for($this->employee)->create(['logged_at' => '2026-10-05 08:10:00']);
    TimeLog::factory()->for($this->employee)->create(['logged_at' => '2026-10-05 17:00:00']);
    Sanctum::actingAs($this->employee->user);

    $this->getJson(route('api.v1.attendance', ['from' => '2026-10-05', 'to' => '2026-10-06']))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.time_in', '08:10')
        ->assertJsonPath('data.0.late_minutes', 10)
        ->assertJsonPath('meta.summary.days_absent', 1);
});

test('employees file and withdraw their own filings', function () {
    Sanctum::actingAs($this->employee->user);

    $id = $this->postJson(route('api.v1.filings.store'), [
        'type' => 'overtime',
        'date_from' => '2026-10-05',
        'time_from' => '17:00',
        'time_to' => '19:00',
        'reason' => 'Month-end inventory.',
    ])->assertCreated()->assertJsonPath('data.status', 'pending')->json('data.id');

    $this->getJson(route('api.v1.filings.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonStructure(['meta' => ['types', 'shifts']]);

    $this->deleteJson(route('api.v1.filings.destroy', $id))->assertNoContent();

    $someoneElses = Filing::factory()->create();
    $this->deleteJson(route('api.v1.filings.destroy', $someoneElses))->assertForbidden();
});

test('filing validation errors come back as JSON', function () {
    Sanctum::actingAs($this->employee->user);

    $this->postJson(route('api.v1.filings.store'), ['type' => 'overtime', 'date_from' => '2026-10-05', 'reason' => 'x'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['time_from', 'time_to']);
});

test('only payslips from finalized periods are visible', function () {
    $draft = Payslip::factory()->for($this->employee)->for(PayrollPeriod::factory())->create();
    $released = Payslip::factory()->for($this->employee)
        ->for(PayrollPeriod::factory()->secondHalf()->state(['status' => PayrollPeriodStatus::Finalized]))
        ->create();
    Sanctum::actingAs($this->employee->user);

    $this->getJson(route('api.v1.payslips.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $released->id);

    $this->getJson(route('api.v1.payslips.show', $released))
        ->assertOk()
        ->assertJsonPath('data.net_pay', 6475)
        ->assertJsonStructure(['data' => ['details' => ['earnings', 'deductions', 'attendance']]]);

    $this->getJson(route('api.v1.payslips.show', $draft))->assertNotFound();
});

test('dept heads endorse their team filings through the API', function () {
    $department = Department::factory()->create();
    $head = Employee::factory()->withUser()->create(['department_id' => $department->id]);
    $department->update(['head_employee_id' => $head->id]);
    $member = Employee::factory()->create(['department_id' => $department->id]);
    $filing = Filing::factory()->for($member)->create();
    $outsider = Filing::factory()->create();

    Sanctum::actingAs($head->user);

    $this->getJson(route('api.v1.endorsements.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.employee.employee_number', $member->employee_number);

    $this->putJson(route('api.v1.endorsements.update', $filing), ['decision' => 'endorse'])
        ->assertOk()
        ->assertJsonPath('data.status', FilingStatus::EndorsedByDeptHead->value);

    $this->putJson(route('api.v1.endorsements.update', $outsider), ['decision' => 'endorse'])->assertForbidden();
});

test('regular employees cannot reach endorsements', function () {
    Sanctum::actingAs($this->employee->user);

    $this->getJson(route('api.v1.endorsements.index'))->assertForbidden();
});
