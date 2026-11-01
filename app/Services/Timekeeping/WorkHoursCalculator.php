<?php

namespace App\Services\Timekeeping;

use App\Enums\FilingType;
use App\Enums\HolidayType;
use App\Models\Employee;
use App\Models\Filing;
use App\Models\Holiday;
use App\Models\Shift;
use App\Models\TimeLog;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Builds the Work Hours Summary (WHS): turns raw time logs plus approved
 * filings into per-day attendance records for a cut-off period.
 */
class WorkHoursCalculator
{
    /**
     * Compute daily records for many employees at once, keyed by employee ID.
     *
     * @param  Collection<int, Employee>  $employees
     * @return array<int, list<DailyRecord>>
     */
    public function forEmployees(Collection $employees, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $employeeIds = $employees->pluck('id')->all();

        $logs = TimeLog::query()
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('logged_at', [$from->startOfDay(), $to->endOfDay()])
            ->orderBy('logged_at')
            ->get()
            ->groupBy(['employee_id', fn (TimeLog $log): string => $log->logged_at->toDateString()]);

        $filings = Filing::query()
            ->approved()
            ->overlapping($from, $to)
            ->whereIn('employee_id', $employeeIds)
            ->get()
            ->groupBy('employee_id');

        $holidays = Holiday::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->keyBy(fn (Holiday $holiday): string => $holiday->date->toDateString());

        $shifts = Shift::all()->keyBy('id');

        $records = [];

        foreach ($employees as $employee) {
            $records[$employee->id] = $this->calculate(
                $employee,
                $from,
                $to,
                $logs->get($employee->id, collect()),
                $filings->get($employee->id, collect()),
                $holidays,
                $shifts,
            );
        }

        return $records;
    }

    /**
     * @return list<DailyRecord>
     */
    public function forEmployee(Employee $employee, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return $this->forEmployees(collect([$employee]), $from, $to)[$employee->id];
    }

    /**
     * Sum daily records into cut-off totals.
     *
     * @param  list<DailyRecord>  $records
     * @return array{days_worked: float, paid_leave_days: float, unpaid_leave_days: float, days_absent: float, late_minutes: int, undertime_minutes: int, overtime_hours: float}
     */
    public function summarize(array $records): array
    {
        $records = collect($records);

        return [
            'days_worked' => (float) $records->sum('daysWorked'),
            'paid_leave_days' => (float) $records->sum('paidLeaveDays'),
            'unpaid_leave_days' => (float) $records->sum('unpaidLeaveDays'),
            'days_absent' => (float) $records->sum(fn (DailyRecord $record): float => $record->absentDays()),
            'late_minutes' => (int) $records->sum('lateMinutes'),
            'undertime_minutes' => (int) $records->sum('undertimeMinutes'),
            'overtime_hours' => (float) $records->sum('overtimeHours'),
        ];
    }

    /**
     * @param  Collection<string, Collection<int, TimeLog>>  $logsByDate
     * @param  Collection<int, Filing>  $filings
     * @param  Collection<string, Holiday>  $holidays
     * @param  Collection<int, Shift>  $shifts
     * @return list<DailyRecord>
     */
    private function calculate(
        Employee $employee,
        CarbonImmutable $from,
        CarbonImmutable $to,
        Collection $logsByDate,
        Collection $filings,
        Collection $holidays,
        Collection $shifts,
    ): array {
        $records = [];

        foreach (CarbonPeriod::create($from, $to) as $day) {
            $date = CarbonImmutable::instance($day)->startOfDay();
            $dayFilings = $filings->filter(fn (Filing $filing): bool => $filing->covers($date));

            $records[] = $this->calculateDay(
                $employee,
                $date,
                $logsByDate->get($date->toDateString(), collect()),
                $dayFilings,
                $holidays->get($date->toDateString()),
                $shifts,
            );
        }

        return $records;
    }

    /**
     * @param  Collection<int, TimeLog>  $logs
     * @param  Collection<int, Filing>  $filings
     * @param  Collection<int, Shift>  $shifts
     */
    private function calculateDay(
        Employee $employee,
        CarbonImmutable $date,
        Collection $logs,
        Collection $filings,
        ?Holiday $holiday,
        Collection $shifts,
    ): DailyRecord {
        $shiftChange = $filings->first(fn (Filing $filing): bool => $filing->type === FilingType::ShiftChange);
        $shift = $shifts->get($shiftChange->shift_id ?? $employee->shift_id);
        $isWorkDay = $shift->isWorkDay($date);

        $base = [
            'date' => $date,
            'shiftName' => $shift->name,
            'isWorkDay' => $isWorkDay,
            'holidayType' => $holiday?->type,
            'holidayName' => $holiday?->name,
        ];

        if ($date->lt($employee->date_hired) || ($employee->date_separated && $date->gt($employee->date_separated))) {
            return new DailyRecord(...$base, status: DailyRecord::NotEmployed);
        }

        [$timeIn, $timeOut] = $this->resolvePunches($date, $logs, $filings);
        $overtimeHours = $this->overtimeHours($filings);

        if (! $isWorkDay) {
            return new DailyRecord(...$base, status: DailyRecord::RestDay, timeIn: $timeIn, timeOut: $timeOut, overtimeHours: $overtimeHours);
        }

        $leave = $filings->first(fn (Filing $filing): bool => $filing->type === FilingType::Leave);

        if ($leave && ! $leave->is_half_day) {
            return new DailyRecord(
                ...$base,
                status: DailyRecord::OnLeave,
                paidLeaveDays: $leave->with_pay ? 1.0 : 0.0,
                unpaidLeaveDays: $leave->with_pay ? 0.0 : 1.0,
            );
        }

        if (! $timeIn && ! $timeOut) {
            return new DailyRecord(...$base, status: $holiday ? DailyRecord::Holiday : DailyRecord::Absent);
        }

        if (! $timeIn || ! $timeOut) {
            return new DailyRecord(...$base, status: DailyRecord::Incomplete, timeIn: $timeIn, timeOut: $timeOut);
        }

        if ($leave) {
            return $this->halfDay($base, $shift, $leave, $timeIn, $timeOut, $overtimeHours);
        }

        $shiftStart = $shift->startsAt($date);
        $graceEnd = $shiftStart->addMinutes(config('hris.timekeeping.late_grace_minutes'));

        return new DailyRecord(
            ...$base,
            status: DailyRecord::Present,
            timeIn: $timeIn,
            timeOut: $timeOut,
            lateMinutes: $timeIn->gt($graceEnd) ? (int) $shiftStart->diffInMinutes($timeIn) : 0,
            undertimeMinutes: max(0, (int) $timeOut->diffInMinutes($shift->endsAt($date), false)),
            overtimeHours: $overtimeHours,
            daysWorked: 1.0,
        );
    }

    /**
     * Half-day leave: the day is credited as 0.5 worked + 0.5 leave. Work beyond
     * the half-day requirement is not paid; a shortfall counts as undertime.
     *
     * @param  array{date: CarbonImmutable, shiftName: string, isWorkDay: bool, holidayType: HolidayType|null, holidayName: string|null}  $base
     */
    private function halfDay(array $base, Shift $shift, Filing $leave, CarbonImmutable $timeIn, CarbonImmutable $timeOut, float $overtimeHours): DailyRecord
    {
        $requiredMinutes = intdiv($shift->workMinutes(), 2);
        $renderedMinutes = (int) $timeIn->diffInMinutes($timeOut);

        return new DailyRecord(
            ...$base,
            status: DailyRecord::Present,
            timeIn: $timeIn,
            timeOut: $timeOut,
            undertimeMinutes: max(0, $requiredMinutes - $renderedMinutes),
            overtimeHours: $overtimeHours,
            daysWorked: 0.5,
            paidLeaveDays: $leave->with_pay ? 0.5 : 0.0,
            unpaidLeaveDays: $leave->with_pay ? 0.0 : 0.5,
        );
    }

    /**
     * First punch is time-in, last punch is time-out. An approved Unaccounted
     * filing supplies or corrects missing punches.
     *
     * @param  Collection<int, TimeLog>  $logs
     * @param  Collection<int, Filing>  $filings
     * @return array{0: CarbonImmutable|null, 1: CarbonImmutable|null}
     */
    private function resolvePunches(CarbonImmutable $date, Collection $logs, Collection $filings): array
    {
        $timeIn = $logs->first()?->logged_at;
        $timeOut = $logs->count() > 1 ? $logs->last()->logged_at : null;

        $unaccounted = $filings->first(fn (Filing $filing): bool => $filing->type === FilingType::Unaccounted);

        if ($unaccounted) {
            $timeIn = $unaccounted->time_from ? $date->setTimeFromTimeString($unaccounted->time_from) : $timeIn;
            $timeOut = $unaccounted->time_to ? $date->setTimeFromTimeString($unaccounted->time_to) : $timeOut;
        }

        return [$timeIn, $timeOut];
    }

    /**
     * @param  Collection<int, Filing>  $filings
     */
    private function overtimeHours(Collection $filings): float
    {
        return round($filings
            ->filter(fn (Filing $filing): bool => $filing->type === FilingType::Overtime && $filing->time_from && $filing->time_to)
            ->sum(function (Filing $filing): float {
                $start = strtotime($filing->time_from);
                $end = strtotime($filing->time_to);

                if ($end <= $start) {
                    $end += 86400;
                }

                return ($end - $start) / 3600;
            }), 2);
    }
}
