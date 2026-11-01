<?php

namespace App\Models;

use App\Enums\AdjustmentCategory;
use Database\Factories\PayrollAdjustmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A Per Biyahe earning, other earning, or other deduction for one payroll period.
 *
 * @property int $id
 * @property int $payroll_period_id
 * @property int $employee_id
 * @property AdjustmentCategory $category
 * @property string $description
 * @property string $amount
 * @property-read Employee $employee
 */
#[Fillable(['payroll_period_id', 'employee_id', 'category', 'description', 'amount'])]
class PayrollAdjustment extends Model
{
    /** @use HasFactory<PayrollAdjustmentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => AdjustmentCategory::class,
            'amount' => 'decimal:2',
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
