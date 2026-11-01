<?php

namespace App\Services\Timekeeping;

use App\Enums\HolidayType;
use Carbon\CarbonImmutable;

/**
 * One employee's computed attendance for one calendar day (a WHS row).
 */
final readonly class DailyRecord
{
    public const string Present = 'present';

    public const string Absent = 'absent';

    public const string Incomplete = 'incomplete';

    public const string OnLeave = 'leave';

    public const string Holiday = 'holiday';

    public const string RestDay = 'rest_day';

    public const string NotEmployed = 'not_employed';

    public function __construct(
        public CarbonImmutable $date,
        public string $status,
        public string $shiftName,
        public bool $isWorkDay,
        public ?CarbonImmutable $timeIn = null,
        public ?CarbonImmutable $timeOut = null,
        public int $lateMinutes = 0,
        public int $undertimeMinutes = 0,
        public float $overtimeHours = 0,
        public float $daysWorked = 0,
        public float $paidLeaveDays = 0,
        public float $unpaidLeaveDays = 0,
        public ?HolidayType $holidayType = null,
        public ?string $holidayName = null,
    ) {}

    /**
     * Days the employee was expected to work but has no pay credit for.
     */
    public function absentDays(): float
    {
        return $this->status === self::Absent || $this->status === self::Incomplete ? 1.0 : 0.0;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'date' => $this->date->toDateString(),
            'day' => $this->date->format('D'),
            'status' => $this->status,
            'shift' => $this->shiftName,
            'is_work_day' => $this->isWorkDay,
            'time_in' => $this->timeIn?->format('H:i'),
            'time_out' => $this->timeOut?->format('H:i'),
            'late_minutes' => $this->lateMinutes,
            'undertime_minutes' => $this->undertimeMinutes,
            'overtime_hours' => $this->overtimeHours,
            'days_worked' => $this->daysWorked,
            'paid_leave_days' => $this->paidLeaveDays,
            'unpaid_leave_days' => $this->unpaidLeaveDays,
            'holiday' => $this->holidayName,
            'holiday_type' => $this->holidayType?->value,
        ];
    }
}
