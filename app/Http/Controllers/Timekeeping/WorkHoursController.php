<?php

namespace App\Http\Controllers\Timekeeping;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Services\Timekeeping\DailyRecord;
use App\Services\Timekeeping\WorkHoursCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Work Hours Summary (WHS) per cut-off, for checking and Dept. Head confirmation.
 */
class WorkHoursController extends Controller
{
    public function index(Request $request, WorkHoursCalculator $calculator): Response
    {
        [$from, $to] = $this->cutoffRange($request);
        $departmentId = $request->integer('department_id') ?: null;

        $employees = Employee::query()
            ->with('department:id,name')
            ->whereDate('date_hired', '<=', $to)
            ->where(fn (Builder $query) => $query->whereNull('date_separated')->orWhereDate('date_separated', '>=', $from))
            ->when($departmentId, fn (Builder $query) => $query->where('department_id', $departmentId))
            ->orderBy('last_name')
            ->get();

        $records = $calculator->forEmployees($employees, $from, $to);

        return Inertia::render('timekeeping/work-hours', [
            'rows' => $employees->map(fn (Employee $employee): array => [
                'employee' => $employee->only('id', 'employee_number', 'full_name'),
                'department' => $employee->department->name,
                ...$calculator->summarize($records[$employee->id]),
                'incomplete_days' => collect($records[$employee->id])->where('status', DailyRecord::Incomplete)->count(),
            ]),
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'department_id' => $departmentId],
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Request $request, Employee $employee, WorkHoursCalculator $calculator): Response
    {
        [$from, $to] = $this->cutoffRange($request);
        $records = $calculator->forEmployee($employee->load('shift'), $from, $to);

        return Inertia::render('timekeeping/work-hours-show', [
            'employee' => $employee->only('id', 'employee_number', 'full_name'),
            'records' => array_map(fn (DailyRecord $record): array => $record->toArray(), $records),
            'summary' => $calculator->summarize($records),
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
        ]);
    }
}
