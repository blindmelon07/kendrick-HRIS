<?php

namespace App\Http\Controllers\Payroll;

use App\Enums\AdjustmentCategory;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Bulk upload of the Per Biyahe uploader file, other earnings or other deductions.
 *
 * Expected CSV header: `employee_number,amount` and an optional `description`.
 */
class PayrollAdjustmentImportController extends Controller
{
    public function store(Request $request, PayrollPeriod $period): RedirectResponse
    {
        if ($period->isFinalized()) {
            return Inertia::flash('error', 'This payroll period is already finalized.')->back();
        }

        $validated = $request->validate([
            'category' => ['required', Rule::enum(AdjustmentCategory::class)],
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $category = AdjustmentCategory::from($validated['category']);
        $employeeIds = Employee::query()->pluck('id', 'employee_number');
        $handle = fopen($request->file('file')->getRealPath(), 'r');

        if ($handle === false) {
            return back()->withErrors(['file' => 'The uploaded file could not be read.']);
        }

        $header = array_map(fn (?string $column): string => strtolower(trim((string) $column, " \t\n\r\0\x0B\u{FEFF}")), fgetcsv($handle, escape: '') ?: []);

        if (! in_array('employee_number', $header, true) || ! in_array('amount', $header, true)) {
            fclose($handle);

            return back()->withErrors(['file' => 'The file needs employee_number and amount columns.']);
        }

        $rows = [];
        $skipped = [];
        $line = 1;

        while (($values = fgetcsv($handle, escape: '')) !== false) {
            $line++;

            if (count($values) !== count($header)) {
                continue;
            }

            $row = array_combine($header, array_map(fn (?string $value): string => trim((string) $value), $values));
            $employeeId = $employeeIds->get($row['employee_number']);
            $amount = (float) str_replace(',', '', $row['amount']);

            if (! $employeeId || $amount <= 0) {
                $skipped[] = $line;

                continue;
            }

            $rows[] = [
                'employee_id' => $employeeId,
                'category' => $category,
                'description' => ($row['description'] ?? '') ?: $category->label(),
                'amount' => $amount,
            ];
        }

        fclose($handle);

        DB::transaction(fn () => $period->adjustments()->createMany($rows));

        $message = count($rows).' '.$category->label().' entries uploaded.';

        if ($skipped) {
            $message .= ' Skipped line(s) '.implode(', ', $skipped).' (unknown employee number or invalid amount).';
        }

        return Inertia::flash('success', $message)->back();
    }
}
