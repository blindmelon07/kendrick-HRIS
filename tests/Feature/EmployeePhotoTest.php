<?php

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->hr = User::factory()->role(UserRole::HrTimekeeper)->create();
});

function photoPayload(Employee $employee, array $overrides = []): array
{
    return [
        ...$employee->only([
            'employee_number', 'first_name', 'last_name', 'branch_id', 'department_id', 'shift_id', 'job_title', 'rate',
        ]),
        'level' => $employee->level->value,
        'employee_status' => $employee->employee_status->value,
        'employment_status' => $employee->employment_status->value,
        'pay_type' => $employee->pay_type->value,
        'payment_method' => $employee->payment_method->value,
        'date_hired' => $employee->date_hired->toDateString(),
        'has_statutory_deductions' => true,
        'custom_contributions' => false,
        ...$overrides,
    ];
}

test('HR can upload a profile photo', function () {
    $employee = Employee::factory()->create();

    $this->actingAs($this->hr)
        ->put(route('employees.update', $employee), photoPayload($employee, [
            'photo' => UploadedFile::fake()->image('juan.jpg', 400, 400),
        ]))
        ->assertSessionHasNoErrors();

    $employee->refresh();

    expect($employee->photo_path)->toStartWith('employee-photos/')
        ->and($employee->photo_url)->toContain("/employees/{$employee->id}/photo");
    Storage::disk('local')->assertExists($employee->photo_path);
});

test('replacing or removing a photo deletes the old file', function () {
    $employee = Employee::factory()->create();

    $this->actingAs($this->hr)->put(route('employees.update', $employee), photoPayload($employee, [
        'photo' => UploadedFile::fake()->image('first.jpg'),
    ]));
    $first = $employee->refresh()->photo_path;

    $this->actingAs($this->hr)->put(route('employees.update', $employee), photoPayload($employee, [
        'photo' => UploadedFile::fake()->image('second.png'),
    ]));
    $second = $employee->refresh()->photo_path;

    Storage::disk('local')->assertMissing($first);
    Storage::disk('local')->assertExists($second);

    $this->actingAs($this->hr)->put(route('employees.update', $employee), photoPayload($employee, ['remove_photo' => true]));

    expect($employee->refresh()->photo_path)->toBeNull();
    Storage::disk('local')->assertMissing($second);
});

test('saving without a new photo keeps the current one', function () {
    $employee = Employee::factory()->create(['photo_path' => 'employee-photos/kept.jpg']);
    Storage::disk('local')->put('employee-photos/kept.jpg', 'image');

    $this->actingAs($this->hr)->put(route('employees.update', $employee), photoPayload($employee, ['job_title' => 'Team Lead']));

    expect($employee->refresh()->photo_path)->toBe('employee-photos/kept.jpg');
});

test('only images up to 2 MB are accepted', function (UploadedFile $file) {
    $employee = Employee::factory()->create();

    $this->actingAs($this->hr)
        ->put(route('employees.update', $employee), photoPayload($employee, ['photo' => $file]))
        ->assertSessionHasErrors('photo');
})->with([
    'a PDF' => fn () => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
    'too large' => fn () => UploadedFile::fake()->image('huge.jpg')->size(3000),
]);

test('a photo can be added when creating an employee', function () {
    $this->actingAs($this->hr)->post(route('employees.store'), [
        ...photoPayload(Employee::factory()->make([
            'employee_number' => 'TS-04321',
            'branch_id' => Branch::factory()->create()->id,
            'department_id' => Department::factory()->create()->id,
            'shift_id' => Shift::factory()->create()->id,
        ])),
        'photo' => UploadedFile::fake()->image('new.jpg'),
    ])->assertSessionHasNoErrors();

    expect(Employee::where('employee_number', 'TS-04321')->sole()->photo_path)->not->toBeNull();
});

test('photos are only shown to HR and to the employee themselves', function () {
    $employee = Employee::factory()->withUser()->create(['photo_path' => 'employee-photos/juan.jpg']);
    Storage::disk('local')->put('employee-photos/juan.jpg', 'image-bytes');
    $colleague = Employee::factory()->withUser()->create();

    $this->actingAs($this->hr)->get(route('employees.photo', $employee))->assertOk();
    $this->actingAs($employee->user)->get(route('employees.photo', $employee))->assertOk();
    $this->actingAs($colleague->user)->get(route('employees.photo', $employee))->assertForbidden();

    auth()->logout();
    $this->get(route('employees.photo', $employee))->assertRedirect(route('login'));
});

test('an employee without a photo returns 404', function () {
    $employee = Employee::factory()->create();

    $this->actingAs($this->hr)->get(route('employees.photo', $employee))->assertNotFound();
});
