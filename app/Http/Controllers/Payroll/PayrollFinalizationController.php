<?php

namespace App\Http\Controllers\Payroll;

use App\Enums\PayrollPeriodStatus;
use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Services\Payroll\PayrollProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Lock a processed payroll, apply loan payments and release payslips to employees.
 */
class PayrollFinalizationController extends Controller
{
    public function store(Request $request, PayrollPeriod $period, PayrollProcessor $processor): RedirectResponse
    {
        if ($period->status !== PayrollPeriodStatus::Processed) {
            return Inertia::flash('error', 'Process the payroll before finalizing it.')->back();
        }

        $processor->finalize($period, $request->user());

        return Inertia::flash('success', 'Payroll finalized. Payslips are now visible to employees.')->back();
    }
}
