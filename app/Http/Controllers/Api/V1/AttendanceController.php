<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Timekeeping\DailyRecord;
use App\Services\Timekeeping\WorkHoursCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The employee's own Work Hours Summary for a cut-off.
 */
class AttendanceController extends Controller
{
    public function __invoke(Request $request, WorkHoursCalculator $calculator): JsonResponse
    {
        [$from, $to] = $this->cutoffRange($request);
        $records = $calculator->forEmployee($request->user()->employee->load('shift'), $from, $to);

        return response()->json([
            'data' => array_map(fn (DailyRecord $record): array => $record->toArray(), $records),
            'meta' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'summary' => $calculator->summarize($records),
            ],
        ]);
    }
}
