<?php

use App\Models\Employee;
use App\Services\Payroll\ContributionCalculator;

beforeEach(function () {
    $this->calculator = app(ContributionCalculator::class);
});

test('SSS uses the monthly salary credit bracket', function (float $salary, float $credit, float $employeeShare) {
    expect($this->calculator->sssSalaryCredit($salary))->toBe($credit)
        ->and($this->calculator->forMonthlySalary($salary)['sss_contribution'])->toBe($employeeShare);
})->with([
    'below the minimum' => [3000, 5000.0, 250.0],
    'top of the first bracket' => [5249.99, 5000.0, 250.0],
    'start of the next bracket' => [5250, 5500.0, 275.0],
    'daily ₱600 worker' => [15650, 15500.0, 775.0],
    'bracket edge' => [15750, 16000.0, 800.0],
    'at the maximum' => [34750, 35000.0, 1750.0],
    'above the maximum' => [80000, 35000.0, 1750.0],
]);

test('PhilHealth is 2.5% of salary between the floor and ceiling', function (float $salary, float $employeeShare) {
    expect($this->calculator->forMonthlySalary($salary)['philhealth_contribution'])->toBe($employeeShare);
})->with([
    'below the floor' => [8000, 250.0],
    'daily ₱600 worker' => [15650, 391.25],
    'monthly ₱35,000' => [35000, 875.0],
    'above the ceiling' => [150000, 2500.0],
]);

test('Pag-IBIG is 2% up to the maximum fund salary, 1% for very low salaries', function (float $salary, float $employeeShare) {
    expect($this->calculator->forMonthlySalary($salary)['pagibig_contribution'])->toBe($employeeShare);
})->with([
    'low salary' => [1500, 15.0],
    'below the cap' => [5000, 100.0],
    'at the cap' => [10000, 200.0],
    'above the cap' => [35000, 200.0],
]);

test('daily-rated employees use daily rate × average working days per month', function () {
    $daily = Employee::factory()->make(['rate' => 600]);
    $monthly = Employee::factory()->monthly(35000)->make();

    expect($daily->monthlySalary())->toBe(15650.0)
        ->and($monthly->monthlySalary())->toBe(35000.0);
});

test('custom amounts override the tables', function () {
    $employee = Employee::factory()->customContributions(1000, 500, 100)->make(['rate' => 600]);

    expect($this->calculator->forEmployee($employee))->toBe([
        'sss_contribution' => 1000.0,
        'philhealth_contribution' => 500.0,
        'pagibig_contribution' => 100.0,
    ]);
});
