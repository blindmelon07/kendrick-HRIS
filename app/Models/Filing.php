<?php

namespace App\Models;

use App\Enums\FilingStatus;
use App\Enums\FilingType;
use Carbon\CarbonImmutable;
use Database\Factories\FilingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An employee timekeeping request: Unaccounted, Leave, Overtime, Early Out, or Shift Change.
 *
 * @property int $id
 * @property int $employee_id
 * @property FilingType $type
 * @property CarbonImmutable $date_from
 * @property CarbonImmutable $date_to
 * @property string|null $time_from
 * @property string|null $time_to
 * @property bool $is_half_day
 * @property bool $with_pay
 * @property int|null $shift_id
 * @property string $reason
 * @property FilingStatus $status
 * @property int|null $endorsed_by
 * @property CarbonImmutable|null $endorsed_at
 * @property int|null $reviewed_by
 * @property CarbonImmutable|null $reviewed_at
 * @property string|null $remarks
 * @property-read Employee $employee
 * @property-read Shift|null $shift
 */
#[Fillable([
    'employee_id', 'type', 'date_from', 'date_to', 'time_from', 'time_to',
    'is_half_day', 'with_pay', 'shift_id', 'reason', 'status',
    'endorsed_by', 'endorsed_at', 'reviewed_by', 'reviewed_at', 'remarks',
])]
class Filing extends Model
{
    /** @use HasFactory<FilingFactory> */
    use HasFactory;

    /**
     * Mirrors the column defaults so a newly filed request reads as pending before it is reloaded.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
        'is_half_day' => false,
        'with_pay' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => FilingType::class,
            'status' => FilingStatus::class,
            'date_from' => 'date:Y-m-d',
            'date_to' => 'date:Y-m-d',
            'is_half_day' => 'boolean',
            'with_pay' => 'boolean',
            'endorsed_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Filing>  $query
     */
    #[Scope]
    protected function approved(Builder $query): void
    {
        $query->where('status', FilingStatus::Approved);
    }

    /**
     * Filings that touch the given date range.
     *
     * @param  Builder<Filing>  $query
     */
    #[Scope]
    protected function overlapping(Builder $query, CarbonImmutable $from, CarbonImmutable $to): void
    {
        $query->whereDate('date_from', '<=', $to)->whereDate('date_to', '>=', $from);
    }

    public function covers(CarbonImmutable $date): bool
    {
        return $date->betweenIncluded($this->date_from, $this->date_to);
    }

    /**
     * Filings need a Dept. Head endorsement first, unless the department has no
     * head or the employee is the head.
     */
    public function requiresEndorsement(): bool
    {
        $headId = $this->employee->department->head_employee_id;

        return $headId !== null && $headId !== $this->employee_id;
    }

    /**
     * Whether HR (the Time Keeper) may now give the final decision.
     */
    public function isReadyForReview(): bool
    {
        return $this->status === FilingStatus::EndorsedByDeptHead
            || ($this->status === FilingStatus::Pending && ! $this->requiresEndorsement());
    }

    public function endorse(User $user, ?string $remarks = null): void
    {
        $this->update([
            'status' => FilingStatus::EndorsedByDeptHead,
            'endorsed_by' => $user->id,
            'endorsed_at' => now(),
            'remarks' => $remarks ?? $this->remarks,
        ]);
    }

    public function approve(User $user, ?string $remarks = null): void
    {
        $this->update([
            'status' => FilingStatus::Approved,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'remarks' => $remarks ?? $this->remarks,
        ]);
    }

    public function deny(User $user, ?string $remarks = null): void
    {
        $this->update([
            'status' => FilingStatus::Denied,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'remarks' => $remarks ?? $this->remarks,
        ]);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<Shift, $this>
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function endorser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'endorsed_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
