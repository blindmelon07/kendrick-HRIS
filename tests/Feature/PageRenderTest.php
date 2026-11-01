<?php

use App\Models\Employee;
use App\Models\Filing;
use App\Models\Loan;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\TimeLog;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->employee = Employee::factory()->withUser()->create();
    $this->employee->department->update(['head_employee_id' => $this->employee->id]);
    TimeLog::factory()->for($this->employee)->create(['logged_at' => '2026-10-05 07:58:00']);
    Filing::factory()->for($this->employee)->create();
    Loan::factory()->for($this->employee)->create();
    $this->period = PayrollPeriod::factory()->create();
    $this->payslip = Payslip::factory()->for($this->period)->for($this->employee)->create();
});

test('admin pages render', function (string $route, string $component, ?Closure $parameters = null) {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route($route, $parameters ? $parameters->call($this) : []))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with([
    ['dashboard', 'dashboard'],
    ['employees.index', 'employees/index'],
    ['employees.create', 'employees/form'],
    ['employees.show', 'employees/show', fn () => $this->employee],
    ['employees.edit', 'employees/form', fn () => $this->employee],
    ['departments.index', 'departments/index'],
    ['branches.index', 'branches/index'],
    ['shifts.index', 'shifts/index'],
    ['timekeeping.holidays.index', 'holidays/index'],
    ['timekeeping.time-logs.index', 'timekeeping/time-logs', fn () => ['from' => '2026-10-01', 'to' => '2026-10-15']],
    ['timekeeping.filings.index', 'timekeeping/filings'],
    ['timekeeping.work-hours.index', 'timekeeping/work-hours', fn () => ['from' => '2026-10-01', 'to' => '2026-10-15']],
    ['timekeeping.work-hours.show', 'timekeeping/work-hours-show', fn () => ['employee' => $this->employee, 'from' => '2026-10-01', 'to' => '2026-10-15']],
    ['payroll.periods.index', 'payroll/periods/index'],
    ['payroll.periods.show', 'payroll/periods/show', fn () => $this->period],
    ['payroll.loans.index', 'payroll/loans'],
    ['payslips.show', 'payroll/payslip', fn () => $this->payslip],
    ['users.index', 'users/index'],
]);

test('employee kiosk pages render', function (string $route, string $component) {
    $this->actingAs($this->employee->user)
        ->get(route($route))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with([
    ['dashboard', 'dashboard'],
    ['my.attendance', 'self-service/attendance'],
    ['my.filings.index', 'self-service/filings'],
    ['my.payslips', 'self-service/payslips'],
    ['endorsements.index', 'timekeeping/endorsements'],
]);

test('the work hours summary lists computed totals', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('timekeeping.work-hours.index', ['from' => '2026-10-05', 'to' => '2026-10-05']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('rows', 1)
            ->where('rows.0.incomplete_days', 1));
});
