<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PayrollPeriodStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PayslipResource;
use App\Models\Payslip;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The employee's payslips from finalized payroll periods.
 */
class PayslipController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $payslips = $request->user()->employee->payslips()
            ->with('payrollPeriod')
            ->whereHas('payrollPeriod', fn (Builder $query) => $query->where('status', PayrollPeriodStatus::Finalized))
            ->latest('id')
            ->paginate(24);

        return PayslipResource::collection($payslips);
    }

    public function show(Request $request, Payslip $payslip): PayslipResource
    {
        abort_unless(
            $payslip->employee_id === $request->user()->employee->id && $payslip->payrollPeriod->isFinalized(),
            404,
        );

        return PayslipResource::make($payslip->load('payrollPeriod'));
    }
}
