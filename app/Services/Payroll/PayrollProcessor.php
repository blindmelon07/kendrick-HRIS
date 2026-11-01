<?php

namespace App\Services\Payroll;

use App\Enums\AdjustmentCategory;
use App\Enums\EmployeeStatus;
use App\Enums\HolidayType;
use App\Enums\PayrollHalf;
use App\Enums\PayrollPeriodStatus;
use App\Enums\PayType;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\PayrollAdjustment;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\User;
use App\Services\Timekeeping\DailyRecord;
use App\Services\Timekeeping\WorkHoursCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Computes payslips for a semi-monthly cut-off from the WHS, Per Biyahe
 * and other adjustments, statutory contributions (1st half) and loan
 * amortizations (2nd half).
 */
class PayrollProcessor
{
    public function __construct(
        private WorkHoursCalculator $calculator,
        private ContributionCalculator $contributionCalculator,
    ) {}

    /**
     * (Re)compute every payslip in a draft or processed period.
     */
    public function process(PayrollPeriod $period): int
    {
        if ($period->isFinalized()) {
            throw new LogicException('A finalized payroll period cannot be reprocessed.');
        }

        $employees = $this->payableEmployees($period);
        $records = $this->calculator->forEmployees($employees, $period->start_date, $period->end_date);

        $adjustments = PayrollAdjustment::query()
            ->whereBelongsTo($period)
            ->get()
            ->groupBy('employee_id');

        $loans = $period->half === PayrollHalf::Second
            ? Loan::query()
                ->outstanding()
                ->whereIn('employee_id', $employees->modelKeys())
                ->whereDate('start_date', '<=', $period->end_date)
                ->get()
                ->groupBy('employee_id')
            : collect();

        DB::transaction(function () use ($period, $employees, $records, $adjustments, $loans): void {
            $period->payslips()->delete();

            foreach ($employees as $employee) {
                $period->payslips()->create($this->compute(
                    $period,
                    $employee,
                    $records[$employee->id],
                    $adjustments->get($employee->id, collect()),
                    $loans->get($employee->id, collect()),
                ));
            }

            $period->update(['status' => PayrollPeriodStatus::Processed, 'processed_at' => now()]);
        });

        return $employees->count();
    }

    /**
     * Lock the period and apply loan payments to outstanding balances.
     */
    public function finalize(PayrollPeriod $period, User $user): void
    {
        if ($period->status !== PayrollPeriodStatus::Processed) {
            throw new LogicException('Only a processed payroll period can be finalized.');
        }

        DB::transaction(function () use ($period, $user): void {
            $period->payslips()->each(function (Payslip $payslip): void {
                foreach ($payslip->loan_breakdown ?? [] as $payment) {
                    Loan::query()->whereKey($payment['loan_id'])->decrement('balance', $payment['amount']);
                }
            });

            $period->update([
                'status' => PayrollPeriodStatus::Finalized,
                'finalized_by' => $user->id,
                'finalized_at' => now(),
            ]);
        });
    }

    /**
     * Active employees, plus anyone separated during the cut-off (for final pay days).
     *
     * @return EloquentCollection<int, Employee>
     */
    private function payableEmployees(PayrollPeriod $period): EloquentCollection
    {
        return Employee::query()
            ->with('shift')
            ->whereDate('date_hired', '<=', $period->end_date)
            ->where(fn (Builder $query) => $query
                ->where('employee_status', EmployeeStatus::Active)
                ->orWhereDate('date_separated', '>=', $period->start_date))
            ->orderBy('last_name')
            ->get();
    }

    /**
     * @param  list<DailyRecord>  $records
     * @param  Collection<int, PayrollAdjustment>  $adjustments
     * @param  Collection<int, Loan>  $loans
     * @return array<string, mixed>
     */
    private function compute(PayrollPeriod $period, Employee $employee, array $records, Collection $adjustments, Collection $loans): array
    {
        $summary = $this->calculator->summarize($records);
        $dailyRate = $employee->dailyRate();
        $minuteRate = $dailyRate / max(1, $employee->shift->workMinutes());
        $isPerBiyahe = $employee->pay_type === PayType::PerBiyahe;

        $basicPay = 0.0;
        $holidayPay = 0.0;
        $absenceDeduction = 0.0;
        $lateUndertimeDeduction = 0.0;
        $overtimePay = 0.0;

        if (! $isPerBiyahe) {
            $holidayPay = $this->holidayPay($records, $dailyRate, $employee->pay_type);
            $lateUndertimeDeduction = ($summary['late_minutes'] + $summary['undertime_minutes']) * $minuteRate;
            $overtimePay = $summary['overtime_hours'] * 60 * $minuteRate * config('hris.payroll.overtime_multiplier');

            if ($employee->pay_type === PayType::Monthly) {
                $basicPay = (float) $employee->rate / 2;
                $unpaidDays = $summary['days_absent'] + $summary['unpaid_leave_days'] + $this->daysNotEmployed($records);
                $absenceDeduction = $unpaidDays * $dailyRate;
            } else {
                $basicPay = ($summary['days_worked'] + $summary['paid_leave_days']) * $dailyRate;
            }
        }

        $perBiyahePay = (float) $adjustments->where('category', AdjustmentCategory::PerBiyahe)->sum('amount');
        $otherEarnings = (float) $adjustments->where('category', AdjustmentCategory::OtherEarning)->sum('amount');
        $otherDeductions = (float) $adjustments->where('category', AdjustmentCategory::OtherDeduction)->sum('amount');

        $contributions = $this->contributions($period, $employee);

        $loanBreakdown = $loans->map(fn (Loan $loan): array => [
            'loan_id' => $loan->id,
            'type' => $loan->type->label(),
            'amount' => round($loan->dueAmount(), 2),
        ])->values()->all();
        $loanDeductions = array_sum(array_column($loanBreakdown, 'amount'));

        $grossPay = $basicPay + $holidayPay + $overtimePay + $perBiyahePay + $otherEarnings
            - $absenceDeduction - $lateUndertimeDeduction;
        $totalDeductions = array_sum($contributions) + $loanDeductions + $otherDeductions;

        return [
            'employee_id' => $employee->id,
            'payment_method' => $employee->payment_method,
            'days_worked' => $summary['days_worked'],
            'days_absent' => $summary['days_absent'] + $summary['unpaid_leave_days'],
            'late_minutes' => $summary['late_minutes'],
            'undertime_minutes' => $summary['undertime_minutes'],
            'overtime_hours' => $summary['overtime_hours'],
            'basic_pay' => round($basicPay, 2),
            'holiday_pay' => round($holidayPay, 2),
            'overtime_pay' => round($overtimePay, 2),
            'per_biyahe_pay' => round($perBiyahePay, 2),
            'other_earnings' => round($otherEarnings, 2),
            'gross_pay' => round($grossPay, 2),
            'absence_deduction' => round($absenceDeduction, 2),
            'late_undertime_deduction' => round($lateUndertimeDeduction, 2),
            ...$contributions,
            'loan_deductions' => round($loanDeductions, 2),
            'other_deductions' => round($otherDeductions, 2),
            'total_deductions' => round($totalDeductions, 2),
            'net_pay' => round($grossPay - $totalDeductions, 2),
            'loan_breakdown' => $loanBreakdown,
        ];
    }

    /**
     * Unworked regular holidays are paid at 100% for daily-rated employees
     * (monthly-rated already include them). Work on a holiday earns the
     * premium on top of the day's basic pay.
     *
     * @param  list<DailyRecord>  $records
     */
    private function holidayPay(array $records, float $dailyRate, PayType $payType): float
    {
        $pay = 0.0;

        foreach ($records as $record) {
            if ($record->holidayType === null) {
                continue;
            }

            if ($record->status === DailyRecord::Holiday && $record->holidayType === HolidayType::Regular && $payType === PayType::Daily) {
                $pay += $dailyRate;
            }

            if ($record->daysWorked > 0) {
                $multiplier = $record->holidayType === HolidayType::Regular
                    ? config('hris.payroll.regular_holiday_worked_multiplier')
                    : config('hris.payroll.special_holiday_worked_multiplier');

                $pay += $record->daysWorked * $dailyRate * ($multiplier - 1);
            }
        }

        return $pay;
    }

    /**
     * Work days in the cut-off before hire or after separation (monthly-rated proration).
     *
     * @param  list<DailyRecord>  $records
     */
    private function daysNotEmployed(array $records): float
    {
        return (float) collect($records)
            ->filter(fn (DailyRecord $record): bool => $record->status === DailyRecord::NotEmployed && $record->isWorkDay)
            ->count();
    }

    /**
     * Monthly SSS / PhilHealth / Pag-IBIG employee shares (from the contribution tables
     * or the employee's custom amounts), deducted on the 1st-half payroll once the
     * employee has completed the statutory grace period.
     *
     * @return array{sss_contribution: float, philhealth_contribution: float, pagibig_contribution: float}
     */
    private function contributions(PayrollPeriod $period, Employee $employee): array
    {
        $eligible = $period->half === PayrollHalf::First
            && $employee->has_statutory_deductions
            && $employee->date_hired->addMonths(config('hris.payroll.statutory_grace_months'))->lte($period->end_date);

        if (! $eligible) {
            return ['sss_contribution' => 0.0, 'philhealth_contribution' => 0.0, 'pagibig_contribution' => 0.0];
        }

        return $this->contributionCalculator->forEmployee($employee);
    }
}
