<?php

namespace App\Models;

use App\Enums\PayrollHalf;
use App\Enums\PayrollPeriodStatus;
use Carbon\CarbonImmutable;
use Database\Factories\PayrollPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A semi-monthly payroll cut-off.
 *
 * @property int $id
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable $end_date
 * @property CarbonImmutable $pay_date
 * @property PayrollHalf $half
 * @property PayrollPeriodStatus $status
 * @property CarbonImmutable|null $processed_at
 * @property int|null $finalized_by
 * @property CarbonImmutable|null $finalized_at
 * @property-read string $label
 */
#[Fillable(['start_date', 'end_date', 'pay_date', 'half', 'status', 'processed_at', 'finalized_by', 'finalized_at'])]
#[Appends(['label'])]
class PayrollPeriod extends Model
{
    /** @use HasFactory<PayrollPeriodFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'pay_date' => 'date:Y-m-d',
            'half' => PayrollHalf::class,
            'status' => PayrollPeriodStatus::class,
            'processed_at' => 'datetime',
            'finalized_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute<non-falsy-string, never>
     */
    protected function label(): Attribute
    {
        return Attribute::get(fn (): string => $this->start_date->format('M j').' – '.$this->end_date->format('M j, Y'));
    }

    /**
     * The cut-off containing the given date: a defined payroll period when one
     * exists, otherwise the 1st–15th or 16th–end of month.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function rangeContaining(CarbonImmutable $date): array
    {
        $period = static::query()
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();

        if ($period) {
            return [$period->start_date, $period->end_date];
        }

        return $date->day <= 15
            ? [$date->startOfMonth(), $date->setDay(15)]
            : [$date->setDay(16), $date->endOfMonth()->startOfDay()];
    }

    public function isFinalized(): bool
    {
        return $this->status === PayrollPeriodStatus::Finalized;
    }

    /**
     * @return HasMany<Payslip, $this>
     */
    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    /**
     * @return HasMany<PayrollAdjustment, $this>
     */
    public function adjustments(): HasMany
    {
        return $this->hasMany(PayrollAdjustment::class);
    }
}
