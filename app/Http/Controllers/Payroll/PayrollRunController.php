<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Services\Payroll\PayrollProcessor;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Process (or reprocess) payroll for a cut-off from the current WHS.
 */
class PayrollRunController extends Controller
{
    public function store(PayrollPeriod $period, PayrollProcessor $processor): RedirectResponse
    {
        if ($period->isFinalized()) {
            return Inertia::flash('error', 'This payroll period is already finalized.')->back();
        }

        $count = $processor->process($period);

        return Inertia::flash('success', "Payroll processed for {$count} employees.")->back();
    }
}
