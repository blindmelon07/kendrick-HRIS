<?php

namespace App\Services\Timekeeping;

use App\Models\Employee;
use App\Models\TimeLog;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Throwable;

/**
 * Imports raw punches exported from the biometric (BIS) timekeeping system.
 *
 * Expected CSV header: `employee_number` plus either `logged_at`
 * (e.g. 2026-10-31 07:58) or separate `date` and `time` columns.
 */
class TimeLogImporter
{
    /**
     * @return array{imported: int, duplicates: int, unknown_employees: list<string>, invalid_rows: list<int>}
     */
    public function import(UploadedFile $file, string $source = 'biometric'): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw new RuntimeException('The uploaded time log file could not be read.');
        }

        $header = array_map(fn (?string $column): string => strtolower(trim((string) $column, " \t\n\r\0\x0B\u{FEFF}")), fgetcsv($handle, escape: '') ?: []);

        $employeeIds = Employee::query()->pluck('id', 'employee_number');

        $result = ['imported' => 0, 'duplicates' => 0, 'unknown_employees' => [], 'invalid_rows' => []];
        $rows = [];
        $line = 1;

        while (($values = fgetcsv($handle, escape: '')) !== false) {
            $line++;

            if ($values === [null] || count($values) !== count($header)) {
                $result['invalid_rows'][] = $line;

                continue;
            }

            $row = array_combine($header, array_map(fn (?string $value): string => trim((string) $value), $values));
            $employeeNumber = $row['employee_number'] ?? '';
            $employeeId = $employeeIds->get($employeeNumber);

            if (! $employeeId) {
                $result['unknown_employees'][] = $employeeNumber;

                continue;
            }

            $loggedAt = $this->parseTimestamp($row);

            if (! $loggedAt) {
                $result['invalid_rows'][] = $line;

                continue;
            }

            $rows[] = [
                'employee_id' => $employeeId,
                'logged_at' => $loggedAt->format('Y-m-d H:i:s'),
                'source' => $source,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        fclose($handle);

        foreach (array_chunk($rows, 500) as $chunk) {
            $result['imported'] += TimeLog::query()->insertOrIgnore($chunk);
        }

        $result['duplicates'] = count($rows) - $result['imported'];
        $result['unknown_employees'] = array_values(array_unique($result['unknown_employees']));

        return $result;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function parseTimestamp(array $row): ?CarbonImmutable
    {
        $value = $row['logged_at'] ?? trim(($row['date'] ?? '').' '.($row['time'] ?? ''));

        if ($value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->startOfMinute();
        } catch (Throwable) {
            return null;
        }
    }
}
