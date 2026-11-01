<?php

namespace App\Http\Controllers;

use App\Enums\FilingStatus;
use App\Models\Employee;
use App\Models\Filing;
use App\Models\PayrollPeriod;
use App\Models\TimeLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $employee = $user->employee;

        return Inertia::render('dashboard', [
            'stats' => $user->can('manage-employees') || $user->can('manage-payroll') ? [
                'activeEmployees' => Employee::query()->active()->count(),
                'presentToday' => TimeLog::query()->whereDate('logged_at', today())->distinct()->count('employee_id'),
                'filingsForReview' => Filing::query()->whereIn('status', [FilingStatus::Pending, FilingStatus::EndorsedByDeptHead])->count(),
                'latestPeriod' => PayrollPeriod::query()->latest('start_date')->first(),
            ] : null,
            'me' => $employee ? [
                'todayLogs' => $employee->timeLogs()->whereDate('logged_at', today())->orderBy('logged_at')->pluck('logged_at')
                    ->map(fn ($loggedAt): string => $loggedAt->format('H:i')),
                'pendingFilings' => $employee->filings()->whereIn('status', [FilingStatus::Pending, FilingStatus::EndorsedByDeptHead])->count(),
                'latestPayslip' => $employee->payslips()->with('payrollPeriod')->latest('id')
                    ->whereHas('payrollPeriod', fn ($query) => $query->whereNotNull('finalized_at'))
                    ->first(['id', 'payroll_period_id', 'net_pay']),
            ] : null,
        ]);
    }
}
