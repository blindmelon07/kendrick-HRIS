<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Database\Factories\PayslipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $payroll_period_id
 * @property int $employee_id
 * @property PaymentMethod $payment_method
 * @property string $gross_pay
 * @property string $total_deductions
 * @property string $net_pay
 * @property list<array{loan_id: int, type: string, amount: float}>|null $loan_breakdown
 * @property-read Employee $employee
 * @property-read PayrollPeriod $payrollPeriod
 */
#[Fillable([
    'payroll_period_id', 'employee_id', 'payment_method',
    'days_worked', 'days_absent', 'late_minutes', 'undertime_minutes', 'overtime_hours',
    'basic_pay', 'holiday_pay', 'overtime_pay', 'per_biyahe_pay', 'other_earnings', 'gross_pay',
    'absence_deduction', 'late_undertime_deduction', 'sss_contribution', 'philhealth_contribution',
    'pagibig_contribution', 'loan_deductions', 'other_deductions', 'total_deductions', 'net_pay',
    'loan_breakdown',
])]
class Payslip extends Model
{
    /** @use HasFactory<PayslipFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        $money = array_fill_keys([
            'basic_pay', 'holiday_pay', 'overtime_pay', 'per_biyahe_pay', 'other_earnings', 'gross_pay',
            'absence_deduction', 'late_undertime_deduction', 'sss_contribution', 'philhealth_contribution',
            'pagibig_contribution', 'loan_deductions', 'other_deductions', 'total_deductions', 'net_pay',
        ], 'decimal:2');

        return [
            ...$money,
            'payment_method' => PaymentMethod::class,
            'days_worked' => 'decimal:2',
            'days_absent' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'late_minutes' => 'integer',
            'undertime_minutes' => 'integer',
            'loan_breakdown' => 'array',
        ];
    }

    /**
     * @return BelongsTo<PayrollPeriod, $this>
     */
    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
