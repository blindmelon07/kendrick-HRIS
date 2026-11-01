<?php

use App\Enums\AdjustmentCategory;
use App\Enums\PayrollPeriodStatus;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->officer = User::factory()->role(UserRole::HrPayroll)->create();
});

test('a payroll officer can create, process and finalize a period', function () {
    $employee = Employee::factory()->withUser()->create();

    $this->actingAs($this->officer)
        ->post(route('payroll.periods.store'), [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-15',
            'pay_date' => '2026-10-25',
            'half' => 'first',
        ])
        ->assertRedirect();

    $period = PayrollPeriod::sole();

    $this->actingAs($this->officer)->post(route('payroll.periods.process', $period));
    expect($period->fresh()->status)->toBe(PayrollPeriodStatus::Processed)
        ->and($period->payslips()->count())->toBe(1);

    $payslip = $period->payslips()->sole();

    $this->actingAs($employee->user)->get(route('payslips.show', $payslip))->assertForbidden();

    $this->actingAs($this->officer)->post(route('payroll.periods.finalize', $period));
    expect($period->fresh()->status)->toBe(PayrollPeriodStatus::Finalized);

    $this->actingAs($employee->user)
        ->get(route('payslips.show', $payslip))
        ->assertInertia(fn (Assert $page) => $page->component('payroll/payslip')->where('payslip.id', $payslip->id));

    $this->actingAs($this->officer)->post(route('payroll.periods.process', $period));
    expect($period->fresh()->status)->toBe(PayrollPeriodStatus::Finalized);
});

test('overlapping cut-offs are rejected', function () {
    PayrollPeriod::factory()->create();

    $this->actingAs($this->officer)
        ->post(route('payroll.periods.store'), [
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-20',
            'pay_date' => '2026-10-25',
            'half' => 'first',
        ])
        ->assertSessionHasErrors('start_date');
});

test('the per biyahe uploader file is imported as adjustments', function () {
    $period = PayrollPeriod::factory()->create();
    $driver = Employee::factory()->perBiyahe()->create(['employee_number' => 'TS-00003']);

    $file = UploadedFile::fake()->createWithContent('per-biyahe.csv', implode("\n", [
        'employee_number,amount,description',
        'TS-00003,"1,500.00",Trip 10/02 Trece–Manila',
        'TS-00003,800,Trip 10/03',
        'TS-99999,500,Unknown driver',
    ]));

    $this->actingAs($this->officer)
        ->post(route('payroll.periods.adjustments.import', $period), [
            'category' => AdjustmentCategory::PerBiyahe->value,
            'file' => $file,
        ])
        ->assertSessionHasNoErrors();

    expect($period->adjustments()->whereBelongsTo($driver)->sum('amount'))->toEqual(2300);
});

test('adjustments cannot be changed once a period is finalized', function () {
    $period = PayrollPeriod::factory()->create(['status' => PayrollPeriodStatus::Finalized]);

    $this->actingAs($this->officer)->post(route('payroll.periods.adjustments.store', $period), [
        'employee_id' => Employee::factory()->create()->id,
        'category' => AdjustmentCategory::OtherEarning->value,
        'description' => 'Incentive',
        'amount' => 500,
    ]);

    expect($period->adjustments()->count())->toBe(0);
});
