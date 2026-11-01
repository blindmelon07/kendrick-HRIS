<?php

use App\Enums\FilingStatus;
use App\Enums\FilingType;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Filing;
use App\Models\User;

beforeEach(function () {
    $this->department = Department::factory()->create();
    $this->head = Employee::factory()->withUser()->create(['department_id' => $this->department->id]);
    $this->department->update(['head_employee_id' => $this->head->id]);
    $this->employee = Employee::factory()->withUser()->create(['department_id' => $this->department->id]);
    $this->timekeeper = User::factory()->role(UserRole::HrTimekeeper)->create();
});

test('an employee can file an unaccounted log', function () {
    $this->actingAs($this->employee->user)
        ->post(route('my.filings.store'), [
            'type' => FilingType::Unaccounted->value,
            'date_from' => '2026-10-05',
            'time_to' => '16:00',
            'reason' => 'Forgot to tap out.',
        ])
        ->assertSessionHasNoErrors();

    $filing = $this->employee->filings()->sole();

    expect($filing->status)->toBe(FilingStatus::Pending)
        ->and($filing->date_to->toDateString())->toBe('2026-10-05');
});

test('filing validation depends on the type', function (array $payload, array $errors) {
    $this->actingAs($this->employee->user)
        ->post(route('my.filings.store'), [...$payload, 'date_from' => '2026-10-05', 'reason' => 'Test'])
        ->assertSessionHasErrors($errors);
})->with([
    'overtime needs start and end' => [['type' => 'overtime'], ['time_from', 'time_to']],
    'unaccounted needs a time' => [['type' => 'unaccounted'], ['time_from']],
    'shift change needs a shift' => [['type' => 'shift_change'], ['shift_id']],
    'leave cannot end before it starts' => [['type' => 'leave', 'date_to' => '2026-10-01'], ['date_to']],
]);

test('the dept head endorses, then the time keeper approves', function () {
    $filing = Filing::factory()->for($this->employee)->create();

    $this->actingAs($this->timekeeper)->put(route('timekeeping.filings.update', $filing), ['decision' => 'approve']);
    expect($filing->fresh()->status)->toBe(FilingStatus::Pending);

    $this->actingAs($this->head->user)
        ->put(route('endorsements.update', $filing), ['decision' => 'endorse'])
        ->assertSessionHasNoErrors();
    expect($filing->fresh()->status)->toBe(FilingStatus::EndorsedByDeptHead);

    $this->actingAs($this->timekeeper)
        ->put(route('timekeeping.filings.update', $filing), ['decision' => 'approve', 'with_pay' => false, 'remarks' => 'Approved 10/06'])
        ->assertSessionHasNoErrors();

    $filing->refresh();

    expect($filing->status)->toBe(FilingStatus::Approved)
        ->and($filing->with_pay)->toBeFalse()
        ->and($filing->reviewed_by)->toBe($this->timekeeper->id)
        ->and($filing->remarks)->toBe('Approved 10/06');
});

test('filings from a department without a head go straight to HR', function () {
    $employee = Employee::factory()->create();
    $filing = Filing::factory()->for($employee)->create();

    $this->actingAs($this->timekeeper)->put(route('timekeeping.filings.update', $filing), ['decision' => 'approve']);

    expect($filing->fresh()->status)->toBe(FilingStatus::Approved);
});

test('a dept head cannot endorse filings outside their department or their own', function () {
    $outsider = Filing::factory()->create();
    $own = Filing::factory()->for($this->head)->create();

    $this->actingAs($this->head->user)->put(route('endorsements.update', $outsider), ['decision' => 'endorse'])->assertForbidden();
    $this->actingAs($this->head->user)->put(route('endorsements.update', $own), ['decision' => 'endorse'])->assertForbidden();
});

test('employees can withdraw only their own pending filings', function () {
    $pending = Filing::factory()->for($this->employee)->create();
    $approved = Filing::factory()->for($this->employee)->approved()->create();
    $someoneElses = Filing::factory()->create();

    $this->actingAs($this->employee->user)->delete(route('my.filings.destroy', $pending));
    $this->actingAs($this->employee->user)->delete(route('my.filings.destroy', $approved));
    $this->actingAs($this->employee->user)->delete(route('my.filings.destroy', $someoneElses))->assertForbidden();

    expect(Filing::find($pending->id))->toBeNull()
        ->and(Filing::find($approved->id))->not->toBeNull()
        ->and(Filing::find($someoneElses->id))->not->toBeNull();
});
