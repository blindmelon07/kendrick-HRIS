<?php

namespace App\Models;

use App\Enums\LoanType;
use Carbon\CarbonImmutable;
use Database\Factories\LoanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A government or company loan, amortized on the 2nd-half payroll.
 *
 * @property int $id
 * @property int $employee_id
 * @property LoanType $type
 * @property string|null $reference_number
 * @property string $principal
 * @property string $amortization
 * @property string $balance
 * @property CarbonImmutable $start_date
 * @property-read Employee $employee
 */
#[Fillable(['employee_id', 'type', 'reference_number', 'principal', 'amortization', 'balance', 'start_date'])]
class Loan extends Model
{
    /** @use HasFactory<LoanFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LoanType::class,
            'principal' => 'decimal:2',
            'amortization' => 'decimal:2',
            'balance' => 'decimal:2',
            'start_date' => 'date:Y-m-d',
        ];
    }

    /**
     * @param  Builder<Loan>  $query
     */
    #[Scope]
    protected function outstanding(Builder $query): void
    {
        $query->where('balance', '>', 0);
    }

    /**
     * The amount to deduct this cut-off, capped at the remaining balance.
     */
    public function dueAmount(): float
    {
        return min((float) $this->amortization, (float) $this->balance);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
