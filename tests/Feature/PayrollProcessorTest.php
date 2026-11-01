<?php

use App\Enums\AdjustmentCategory;
use App\Enums\HolidayType;
use App\Enums\PayrollPeriodStatus;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Loan;
use App\Models\PayrollAdjustment;
use App\Models\PayrollPeriod;
use App\Models\Shift;
use App\Models\TimeLog;
use App\Models\User;
use App\Services\Payroll\PayrollProcessor;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

beforeEach(function () {
    $this->shift = Shift::factory()->create([
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'break_minutes' => 60,
        'work_days' => [1, 2, 3, 4, 5, 6],
    ]);
    $this->processor = app(PayrollProcessor::class);
});

/**
 * Log a full 08:00–17:00 day for every work day in the range, except the given dates.
 */
function attendFullPeriod(Employee $employee, string $from, string $to, array $except = []): void
{
    foreach (CarbonPeriod::create($from, $to) as $day) {
        $date = CarbonImmutable::instance($day);

        if ($date->isSunday() || in_array($date->toDateString(), $except, true)) {
            continue;
        }

        TimeLog::factory()->for($employee)->create(['logged_at' => $date->setTime(8, 0)]);
        TimeLog::factory()->for($employee)->create(['logged_at' => $date->setTime(17, 0)]);
    }
}

test('daily-rated pay is days worked times rate, with contributions on the 1st half', function () {
    $employee = Employee::factory()->for($this->shift)->create(['rate' => 600]);
    $period = PayrollPeriod::factory()->create();
    attendFullPeriod($employee, '2026-10-01', '2026-10-15', except: ['2026-10-05']);

    $this->processor->process($period);
    $payslip = $period->payslips()->sole();

    expect((float) $payslip->days_worked)->toBe(12.0)
        ->and((float) $payslip->days_absent)->toBe(1.0)
        ->and((float) $payslip->basic_pay)->toBe(7200.0)
        ->and((float) $payslip->absence_deduction)->toBe(0.0)
        // Monthly salary 600 × 313 ÷ 12 = 15,650 → SSS 775 (MSC 15,500), PhilHealth 391.25, Pag-IBIG 200.
        ->and((float) $payslip->sss_contribution)->toBe(775.0)
        ->and((float) $payslip->philhealth_contribution)->toBe(391.25)
        ->and((float) $payslip->pagibig_contribution)->toBe(200.0)
        ->and((float) $payslip->total_deductions)->toBe(1366.25)
        ->and((float) $payslip->net_pay)->toBe(7200.0 - 1366.25)
        ->and($period->fresh()->status)->toBe(PayrollPeriodStatus::Processed);
});

test('monthly-rated pay is half the salary less absences, late and undertime', function () {
    $employee = Employee::factory()->for($this->shift)->monthly(31300)->create(['has_statutory_deductions' => false]);
    $period = PayrollPeriod::factory()->create();
    attendFullPeriod($employee, '2026-10-01', '2026-10-15', except: ['2026-10-05', '2026-10-06']);
    TimeLog::query()->whereBelongsTo($employee)->where('logged_at', '2026-10-07 08:00:00')->update(['logged_at' => '2026-10-07 08:30:00']);

    $this->processor->process($period);
    $payslip = $period->payslips()->sole();

    // Daily rate: 31,300 × 12 ÷ 313 = 1,200; per minute: 1,200 ÷ 480 = 2.50.
    expect((float) $payslip->basic_pay)->toBe(15650.0)
        ->and((float) $payslip->absence_deduction)->toBe(2400.0)
        ->and($payslip->late_minutes)->toBe(30)
        ->and((float) $payslip->late_undertime_deduction)->toBe(75.0)
        ->and((float) $payslip->gross_pay)->toBe(15650.0 - 2400.0 - 75.0)
        ->and((float) $payslip->total_deductions)->toBe(0.0);
});

test('regular holidays are paid to daily-rated employees, and work on them earns a premium', function () {
    $employee = Employee::factory()->for($this->shift)->create(['rate' => 600, 'has_statutory_deductions' => false]);
    $period = PayrollPeriod::factory()->create();
    Holiday::factory()->create(['date' => '2026-10-05', 'type' => HolidayType::Regular]);
    Holiday::factory()->create(['date' => '2026-10-06', 'type' => HolidayType::SpecialNonWorking]);
    attendFullPeriod($employee, '2026-10-01', '2026-10-15', except: ['2026-10-05']);

    $this->processor->process($period);
    $payslip = $period->payslips()->sole();

    // Unworked regular holiday: 600. Worked special holiday premium: 600 × 0.3 = 180.
    expect((float) $payslip->holiday_pay)->toBe(780.0)
        ->and((float) $payslip->days_absent)->toBe(0.0);
});

test('per biyahe drivers are paid from uploaded trips only', function () {
    $driver = Employee::factory()->for($this->shift)->perBiyahe()->create(['has_statutory_deductions' => false]);
    $period = PayrollPeriod::factory()->create();
    PayrollAdjustment::factory()->for($period)->for($driver)->create(['category' => AdjustmentCategory::PerBiyahe, 'amount' => 4500]);
    PayrollAdjustment::factory()->for($period)->for($driver)->create(['category' => AdjustmentCategory::OtherDeduction, 'amount' => 500]);

    $this->processor->process($period);
    $payslip = $period->payslips()->sole();

    expect((float) $payslip->basic_pay)->toBe(0.0)
        ->and((float) $payslip->per_biyahe_pay)->toBe(4500.0)
        ->and((float) $payslip->absence_deduction)->toBe(0.0)
        ->and((float) $payslip->other_deductions)->toBe(500.0)
        ->and((float) $payslip->net_pay)->toBe(4000.0);
});

test('custom contribution amounts replace the tables on the payslip', function () {
    $employee = Employee::factory()->for($this->shift)->customContributions(1000, 500, 100)->create();
    $period = PayrollPeriod::factory()->create();

    $this->processor->process($period);
    $payslip = $period->payslips()->sole();

    expect((float) $payslip->sss_contribution)->toBe(1000.0)
        ->and((float) $payslip->philhealth_contribution)->toBe(500.0)
        ->and((float) $payslip->pagibig_contribution)->toBe(100.0);
});

test('statutory contributions wait until one month after hire', function () {
    $employee = Employee::factory()->for($this->shift)->create(['date_hired' => '2026-09-20']);
    $period = PayrollPeriod::factory()->create();

    $this->processor->process($period);

    expect((float) $period->payslips()->sole()->sss_contribution)->toBe(0.0);
});

test('loans are deducted on the 2nd half and balances drop when finalized', function () {
    $employee = Employee::factory()->for($this->shift)->create();
    $loan = Loan::factory()->for($employee)->create(['amortization' => 1000, 'balance' => 600]);
    $first = PayrollPeriod::factory()->create();
    $second = PayrollPeriod::factory()->secondHalf()->create();

    $this->processor->process($first);
    $this->processor->process($second);
    $payslip = $second->payslips()->sole();

    expect((float) $first->payslips()->sole()->loan_deductions)->toBe(0.0)
        ->and((float) $payslip->loan_deductions)->toBe(600.0)
        ->and((float) $payslip->sss_contribution)->toBe(0.0);

    $this->processor->finalize($second->fresh(), User::factory()->create());

    expect((float) $loan->fresh()->balance)->toBe(0.0)
        ->and($second->fresh()->status)->toBe(PayrollPeriodStatus::Finalized);
});

test('resigned employees are excluded after their separation date', function () {
    Employee::factory()->for($this->shift)->resigned('2026-09-30')->create();
    $leaver = Employee::factory()->for($this->shift)->resigned('2026-10-07')->create(['rate' => 600, 'has_statutory_deductions' => false]);
    $period = PayrollPeriod::factory()->create();
    attendFullPeriod($leaver, '2026-10-01', '2026-10-07');

    $this->processor->process($period);

    expect($period->payslips()->count())->toBe(1)
        ->and((float) $period->payslips()->sole()->basic_pay)->toBe(6 * 600.0)
        ->and((float) $period->payslips()->sole()->days_absent)->toBe(0.0);
});

test('a finalized period cannot be reprocessed', function () {
    $period = PayrollPeriod::factory()->create(['status' => PayrollPeriodStatus::Finalized]);

    $this->processor->process($period);
})->throws(LogicException::class);
