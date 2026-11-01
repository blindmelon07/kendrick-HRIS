<?php

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->hr = User::factory()->role(UserRole::HrTimekeeper)->create();
});

function employeePayload(array $overrides = []): array
{
    return [
        'employee_number' => 'TS-01234',
        'first_name' => 'Ana',
        'last_name' => 'Garcia',
        'branch_id' => Branch::factory()->create()->id,
        'department_id' => Department::factory()->create()->id,
        'shift_id' => Shift::factory()->create()->id,
        'level' => 'rank_and_file',
        'job_title' => 'Sales Associate',
        'employee_status' => 'active',
        'employment_status' => 'probationary',
        'date_hired' => '2026-10-01',
        'pay_type' => 'daily',
        'rate' => '600',
        'payment_method' => 'cash',
        'has_statutory_deductions' => false,
        'sss_contribution' => '0',
        'philhealth_contribution' => '0',
        'pagibig_contribution' => '0',
        ...$overrides,
    ];
}

test('HR can list employees', function () {
    Employee::factory()->count(2)->create();
    Employee::factory()->resigned()->create();

    $this->actingAs($this->hr)
        ->get(route('employees.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('employees/index')
            ->has('employees.data', 2));
});

test('HR can create an employee with a kiosk login', function () {
    $this->actingAs($this->hr)
        ->post(route('employees.store'), employeePayload([
            'portal_email' => 'ana@example.com',
            'portal_password' => 'secret-pass',
        ]))
        ->assertRedirect();

    $employee = Employee::where('employee_number', 'TS-01234')->sole();

    expect($employee->user->email)->toBe('ana@example.com')
        ->and($employee->user->role)->toBe(UserRole::Employee)
        ->and(Hash::check('secret-pass', $employee->user->password))->toBeTrue();
});

test('employee validation enforces required and conditional fields', function () {
    $this->actingAs($this->hr)
        ->post(route('employees.store'), employeePayload([
            'employee_number' => '',
            'payment_method' => 'atm',
            'employee_status' => 'resigned',
        ]))
        ->assertSessionHasErrors(['employee_number', 'bank_account_number', 'date_separated']);
});

test('HR can update an employee without changing the existing login', function () {
    $employee = Employee::factory()->withUser()->create();
    $originalPassword = $employee->user->password;

    $this->actingAs($this->hr)
        ->put(route('employees.update', $employee), employeePayload([
            'employee_number' => $employee->employee_number,
            'job_title' => 'Senior Sales Associate',
            'portal_email' => $employee->user->email,
        ]))
        ->assertRedirect(route('employees.show', $employee));

    expect($employee->fresh()->job_title)->toBe('Senior Sales Associate')
        ->and($employee->user->fresh()->password)->toBe($originalPassword);
});

test('contribution amounts are only required and saved when custom amounts are chosen', function () {
    $this->actingAs($this->hr)
        ->post(route('employees.store'), employeePayload([
            'custom_contributions' => true,
            'sss_contribution' => '',
        ]))
        ->assertSessionHasErrors('sss_contribution');

    $this->actingAs($this->hr)
        ->post(route('employees.store'), employeePayload([
            'custom_contributions' => false,
            'sss_contribution' => '999',
        ]))
        ->assertSessionHasNoErrors();

    $employee = Employee::where('employee_number', 'TS-01234')->sole();

    expect($employee->custom_contributions)->toBeFalse()
        ->and((float) $employee->sss_contribution)->toBe(0.0);
});

test('the profile shows contributions computed from the tables', function () {
    $employee = Employee::factory()->create(['rate' => 600]);

    $this->actingAs($this->hr)
        ->get(route('employees.show', $employee))
        ->assertInertia(fn (Assert $page) => $page
            ->where('contributions.sss_contribution', 775)
            ->where('contributions.philhealth_contribution', 391.25)
            ->where('contributions.pagibig_contribution', 200)
            ->where('contributions.monthly_salary', 15650));
});

test('a department with employees cannot be deleted', function () {
    $employee = Employee::factory()->create();

    $this->actingAs($this->hr)->delete(route('departments.destroy', $employee->department_id));

    expect(Department::find($employee->department_id))->not->toBeNull();
});
