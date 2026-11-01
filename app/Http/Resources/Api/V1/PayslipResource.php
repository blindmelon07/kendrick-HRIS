<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\AdjustmentCategory;
use App\Models\PayrollAdjustment;
use App\Models\Payslip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payslip
 */
class PayslipResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $period = $this->payrollPeriod;

        return [
            'id' => $this->id,
            'period' => [
                'label' => $period->label,
                'start_date' => $period->start_date->toDateString(),
                'end_date' => $period->end_date->toDateString(),
                'pay_date' => $period->pay_date->toDateString(),
            ],
            'payment_method' => $this->payment_method->value,
            'payment_method_label' => $this->payment_method->label(),
            'gross_pay' => (float) $this->gross_pay,
            'total_deductions' => (float) $this->total_deductions,
            'net_pay' => (float) $this->net_pay,
            'details' => $this->when($request->routeIs('api.v1.payslips.show'), fn (): array => $this->details()),
        ];
    }

    /**
     * Full earnings and deductions breakdown for the payslip screen.
     *
     * @return array<string, mixed>
     */
    private function details(): array
    {
        $adjustments = PayrollAdjustment::query()
            ->where('payroll_period_id', $this->payroll_period_id)
            ->where('employee_id', $this->employee_id)
            ->get();

        $lines = fn (array $items): array => array_values(array_filter($items, fn (array $line): bool => $line['amount'] != 0));

        return [
            'attendance' => [
                'days_worked' => (float) $this->days_worked,
                'days_absent' => (float) $this->days_absent,
                'late_undertime_minutes' => $this->late_minutes + $this->undertime_minutes,
                'overtime_hours' => (float) $this->overtime_hours,
            ],
            'earnings' => $lines([
                ['label' => 'Basic pay', 'amount' => (float) $this->basic_pay],
                ['label' => 'Holiday pay', 'amount' => (float) $this->holiday_pay],
                ['label' => 'Overtime pay', 'amount' => (float) $this->overtime_pay],
                ['label' => 'Per Biyahe', 'amount' => (float) $this->per_biyahe_pay],
                ...$adjustments->where('category', AdjustmentCategory::OtherEarning)
                    ->map(fn (PayrollAdjustment $adjustment): array => ['label' => $adjustment->description, 'amount' => (float) $adjustment->amount])
                    ->values()->all(),
                ['label' => 'Absences / unpaid leave', 'amount' => -(float) $this->absence_deduction],
                ['label' => 'Late / undertime', 'amount' => -(float) $this->late_undertime_deduction],
            ]),
            'deductions' => $lines([
                ['label' => 'SSS', 'amount' => (float) $this->sss_contribution],
                ['label' => 'PhilHealth', 'amount' => (float) $this->philhealth_contribution],
                ['label' => 'Pag-IBIG', 'amount' => (float) $this->pagibig_contribution],
                ...array_map(fn (array $loan): array => ['label' => $loan['type'], 'amount' => (float) $loan['amount']], $this->loan_breakdown ?? []),
                ...$adjustments->where('category', AdjustmentCategory::OtherDeduction)
                    ->map(fn (PayrollAdjustment $adjustment): array => ['label' => $adjustment->description, 'amount' => (float) $adjustment->amount])
                    ->values()->all(),
            ]),
        ];
    }
}
