<?php

namespace App\Http\Controllers\SelfService;

use App\Http\Controllers\Controller;
use App\Services\Timekeeping\DailyRecord;
use App\Services\Timekeeping\WorkHoursCalculator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Employee kiosk: review own logs for the cut-off.
 */
class MyAttendanceController extends Controller
{
    public function __invoke(Request $request, WorkHoursCalculator $calculator): Response
    {
        $employee = $request->user()->employee;

        abort_unless($employee !== null, 404);

        [$from, $to] = $this->cutoffRange($request);
        $records = $calculator->forEmployee($employee->load('shift'), $from, $to);

        return Inertia::render('self-service/attendance', [
            'records' => array_map(fn (DailyRecord $record): array => $record->toArray(), $records),
            'summary' => $calculator->summarize($records),
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
        ]);
    }
}
