<?php

namespace App\Http\Controllers\SelfService;

use App\Enums\PayrollPeriodStatus;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Employee kiosk: payslips from finalized payroll periods.
 */
class MyPayslipController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $employee = $request->user()->employee;

        abort_unless($employee !== null, 404);

        return Inertia::render('self-service/payslips', [
            'payslips' => $employee->payslips()
                ->with('payrollPeriod')
                ->whereHas('payrollPeriod', fn (Builder $query) => $query->where('status', PayrollPeriodStatus::Finalized))
                ->latest('id')
                ->paginate(24),
        ]);
    }
}
