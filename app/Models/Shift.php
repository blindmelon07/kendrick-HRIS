<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\ShiftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $start_time
 * @property string $end_time
 * @property int $break_minutes
 * @property list<int> $work_days ISO-8601 day numbers (1 = Monday, 7 = Sunday).
 */
#[Fillable(['name', 'start_time', 'end_time', 'break_minutes', 'work_days'])]
class Shift extends Model
{
    /** @use HasFactory<ShiftFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'break_minutes' => 'integer',
            'work_days' => 'array',
        ];
    }

    /**
     * @return HasMany<Employee, $this>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Paid working minutes in a full shift (excluding break).
     */
    public function workMinutes(): int
    {
        $start = strtotime($this->start_time);
        $end = strtotime($this->end_time);

        return intdiv($end - $start, 60) - $this->break_minutes;
    }

    public function isWorkDay(CarbonInterface $date): bool
    {
        return in_array($date->isoWeekday(), $this->work_days, true);
    }

    public function startsAt(CarbonInterface $date): CarbonInterface
    {
        return $date->setTimeFromTimeString($this->start_time);
    }

    public function endsAt(CarbonInterface $date): CarbonInterface
    {
        return $date->setTimeFromTimeString($this->end_time);
    }
}
