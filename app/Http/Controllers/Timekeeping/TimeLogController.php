<?php

namespace App\Http\Controllers\Timekeeping;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\TimeLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TimeLogController extends Controller
{
    public function index(Request $request): Response
    {
        [$from, $to] = $this->cutoffRange($request);
        $search = $request->string('search')->trim()->toString();

        $logs = TimeLog::query()
            ->with('employee:id,employee_number,first_name,last_name,middle_name,suffix')
            ->whereBetween('logged_at', [$from->startOfDay(), $to->endOfDay()])
            ->when($search, fn (Builder $query) => $query->whereHas('employee', fn (Builder $query) => $query
                ->where('employee_number', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")))
            ->orderByDesc('logged_at')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('timekeeping/time-logs', [
            'logs' => $logs,
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'search' => $search],
            'employees' => Employee::query()->active()->orderBy('last_name')->get(['id', 'employee_number', 'first_name', 'last_name', 'middle_name', 'suffix']),
        ]);
    }

    /**
     * Manual entry for punches recorded on the Bundy Clock.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')],
            'logged_at' => ['required', 'date', Rule::unique('time_logs')->where('employee_id', $request->input('employee_id'))],
        ], ['logged_at.unique' => 'This punch is already recorded.']);

        TimeLog::create([...$validated, 'source' => 'bundy_clock']);

        return Inertia::flash('success', 'Punch recorded.')->back();
    }

    public function destroy(TimeLog $timeLog): RedirectResponse
    {
        $timeLog->delete();

        return Inertia::flash('success', 'Punch removed.')->back();
    }
}
