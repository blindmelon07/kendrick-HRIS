<?php

use App\Models\Employee;
use App\Models\TimeLog;
use App\Services\Timekeeping\TimeLogImporter;
use Illuminate\Http\UploadedFile;

test('imports punches by employee number and skips duplicates, unknown employees and bad rows', function () {
    $employee = Employee::factory()->create(['employee_number' => 'TS-00001']);
    TimeLog::factory()->for($employee)->create(['logged_at' => '2026-10-05 07:58:00']);

    $csv = UploadedFile::fake()->createWithContent('logs.csv', implode("\n", [
        'Employee_Number,Date,Time',
        'TS-00001,2026-10-05,07:58',
        'TS-00001,2026-10-05,16:02',
        'TS-99999,2026-10-05,08:00',
        'TS-00001,not-a-date,',
    ]));

    $result = app(TimeLogImporter::class)->import($csv);

    expect($result['imported'])->toBe(1)
        ->and($result['duplicates'])->toBe(1)
        ->and($result['unknown_employees'])->toBe(['TS-99999'])
        ->and($result['invalid_rows'])->toBe([5])
        ->and($employee->timeLogs()->count())->toBe(2);
});

test('accepts a single logged_at column', function () {
    $employee = Employee::factory()->create(['employee_number' => 'TS-00002']);
    $csv = UploadedFile::fake()->createWithContent('logs.csv', "employee_number,logged_at\nTS-00002,2026-10-05 07:00:00\n");

    app(TimeLogImporter::class)->import($csv);

    expect($employee->timeLogs()->sole()->logged_at->toDateTimeString())->toBe('2026-10-05 07:00:00');
});
