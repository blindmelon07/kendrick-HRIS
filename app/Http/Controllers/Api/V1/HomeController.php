<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\FilingStatus;
use App\Enums\PayrollPeriodStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PayslipResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Summary for the app's home screen.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        $latestPayslip = $employee->payslips()
            ->with('payrollPeriod')
            ->whereHas('payrollPeriod', fn (Builder $query) => $query->where('status', PayrollPeriodStatus::Finalized))
            ->latest('id')
            ->first();

        return response()->json([
            'data' => [
                'today_logs' => $employee->timeLogs()
                    ->whereDate('logged_at', today())
                    ->orderBy('logged_at')
                    ->get()
                    ->map(fn ($log): string => $log->logged_at->format('H:i')),
                'pending_filings' => $employee->filings()
                    ->whereIn('status', [FilingStatus::Pending, FilingStatus::EndorsedByDeptHead])
                    ->count(),
                'endorsements_waiting' => $user->can('endorse-filings')
                    ? $employee->endorsableFilings()->where('status', FilingStatus::Pending)->count()
                    : null,
                'latest_payslip' => $latestPayslip ? PayslipResource::make($latestPayslip) : null,
            ],
        ]);
    }
}
