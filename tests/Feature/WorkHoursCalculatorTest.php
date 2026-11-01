<?php

use App\Enums\FilingType;
use App\Enums\HolidayType;
use App\Models\Employee;
use App\Models\Filing;
use App\Models\Holiday;
use App\Models\Shift;
use App\Models\TimeLog;
use App\Services\Timekeeping\DailyRecord;
use App\Services\Timekeeping\WorkHoursCalculator;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->shift = Shift::factory()->create([
        'name' => 'Rank & File',
        'start_time' => '07:00:00',
        'end_time' => '16:00:00',
        'break_minutes' => 60,
        'work_days' => [1, 2, 3, 4, 5, 6],
    ]);
    $this->employee = Employee::factory()->for($this->shift)->create();
    $this->calculator = app(WorkHoursCalculator::class);
});

function punch(Employee $employee, string ...$timestamps): void
{
    foreach ($timestamps as $timestamp) {
        TimeLog::factory()->for($employee)->create(['logged_at' => $timestamp]);
    }
}

function recordFor(Employee $employee, string $date): DailyRecord
{
    $day = CarbonImmutable::parse($date);

    return app(WorkHoursCalculator::class)->forEmployee($employee, $day, $day)[0];
}

test('computes late and undertime against the shift', function () {
    punch($this->employee, '2026-10-05 07:12', '2026-10-05 12:01', '2026-10-05 15:40');

    $record = recordFor($this->employee, '2026-10-05');

    expect($record->status)->toBe(DailyRecord::Present)
        ->and($record->timeIn->format('H:i'))->toBe('07:12')
        ->and($record->timeOut->format('H:i'))->toBe('15:40')
        ->and($record->lateMinutes)->toBe(12)
        ->and($record->undertimeMinutes)->toBe(20)
        ->and($record->daysWorked)->toBe(1.0);
});

test('a day with a single punch is incomplete until an unaccounted filing is approved', function () {
    punch($this->employee, '2026-10-05 06:55');

    expect(recordFor($this->employee, '2026-10-05')->status)->toBe(DailyRecord::Incomplete);

    Filing::factory()->for($this->employee)->approved()->create([
        'type' => FilingType::Unaccounted,
        'date_from' => '2026-10-05',
        'date_to' => '2026-10-05',
        'time_to' => '16:05',
    ]);

    $record = recordFor($this->employee, '2026-10-05');

    expect($record->status)->toBe(DailyRecord::Present)
        ->and($record->timeOut->format('H:i'))->toBe('16:05')
        ->and($record->undertimeMinutes)->toBe(0);
});

test('a work day without logs is absent and a rest day is not', function () {
    expect(recordFor($this->employee, '2026-10-05')->absentDays())->toBe(1.0)
        ->and(recordFor($this->employee, '2026-10-04')->status)->toBe(DailyRecord::RestDay);
});

test('pending filings are ignored', function () {
    Filing::factory()->for($this->employee)->create(['date_from' => '2026-10-05', 'date_to' => '2026-10-05']);

    expect(recordFor($this->employee, '2026-10-05')->status)->toBe(DailyRecord::Absent);
});

test('an approved full-day leave is credited as paid or unpaid leave', function () {
    Filing::factory()->for($this->employee)->approved()->create([
        'date_from' => '2026-10-05',
        'date_to' => '2026-10-06',
        'with_pay' => true,
    ]);
    Filing::factory()->for($this->employee)->approved()->create([
        'date_from' => '2026-10-07',
        'date_to' => '2026-10-07',
        'with_pay' => false,
    ]);

    $records = $this->calculator->forEmployee($this->employee, CarbonImmutable::parse('2026-10-05'), CarbonImmutable::parse('2026-10-07'));
    $summary = $this->calculator->summarize($records);

    expect($summary['paid_leave_days'])->toBe(2.0)
        ->and($summary['unpaid_leave_days'])->toBe(1.0)
        ->and($summary['days_absent'])->toBe(0.0);
});

test('half-day leave credits half a day and only flags a shortfall as undertime', function (string $timeOut, int $expectedUndertime) {
    Filing::factory()->for($this->employee)->approved()->create([
        'date_from' => '2026-10-05',
        'date_to' => '2026-10-05',
        'is_half_day' => true,
        'with_pay' => true,
    ]);
    punch($this->employee, '2026-10-05 07:00', "2026-10-05 {$timeOut}");

    $record = recordFor($this->employee, '2026-10-05');

    expect($record->daysWorked)->toBe(0.5)
        ->and($record->paidLeaveDays)->toBe(0.5)
        ->and($record->undertimeMinutes)->toBe($expectedUndertime);
})->with([
    'left after the 4-hour half day' => ['11:10', 0],
    'left exactly at 11:00' => ['11:00', 0],
    'left early' => ['10:30', 30],
]);

test('an unworked holiday is not an absence and a worked holiday keeps its type', function () {
    Holiday::factory()->create(['date' => '2026-10-05', 'type' => HolidayType::Regular]);
    Holiday::factory()->create(['date' => '2026-10-06', 'type' => HolidayType::SpecialNonWorking]);
    punch($this->employee, '2026-10-06 07:00', '2026-10-06 16:00');

    $holiday = recordFor($this->employee, '2026-10-05');
    $worked = recordFor($this->employee, '2026-10-06');

    expect($holiday->status)->toBe(DailyRecord::Holiday)
        ->and($holiday->absentDays())->toBe(0.0)
        ->and($worked->status)->toBe(DailyRecord::Present)
        ->and($worked->holidayType)->toBe(HolidayType::SpecialNonWorking);
});

test('an approved shift change uses the new schedule', function () {
    $supervisorShift = Shift::factory()->create(['start_time' => '08:00:00', 'end_time' => '18:00:00']);
    Filing::factory()->for($this->employee)->approved()->create([
        'type' => FilingType::ShiftChange,
        'date_from' => '2026-10-05',
        'date_to' => '2026-10-05',
        'shift_id' => $supervisorShift->id,
    ]);
    punch($this->employee, '2026-10-05 08:00', '2026-10-05 18:00');

    $record = recordFor($this->employee, '2026-10-05');

    expect($record->lateMinutes)->toBe(0)
        ->and($record->undertimeMinutes)->toBe(0)
        ->and($record->shiftName)->toBe($supervisorShift->name);
});

test('approved overtime adds filed hours', function () {
    punch($this->employee, '2026-10-05 07:00', '2026-10-05 19:00');
    Filing::factory()->for($this->employee)->approved()->create([
        'type' => FilingType::Overtime,
        'date_from' => '2026-10-05',
        'date_to' => '2026-10-05',
        'time_from' => '16:00',
        'time_to' => '18:30',
    ]);

    expect(recordFor($this->employee, '2026-10-05')->overtimeHours)->toBe(2.5);
});

test('days before the hire date are not counted', function () {
    $employee = Employee::factory()->for($this->shift)->create(['date_hired' => '2026-10-10']);

    expect(recordFor($employee, '2026-10-05')->status)->toBe(DailyRecord::NotEmployed)
        ->and(recordFor($employee, '2026-10-05')->absentDays())->toBe(0.0);
});
