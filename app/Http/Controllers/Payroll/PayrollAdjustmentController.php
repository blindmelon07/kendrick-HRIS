<?php

namespace App\Http\Controllers\Payroll;

use App\Enums\AdjustmentCategory;
use App\Http\Controllers\Controller;
use App\Models\PayrollAdjustment;
use App\Models\PayrollPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PayrollAdjustmentController extends Controller
{
    public function store(Request $request, PayrollPeriod $period): RedirectResponse
    {
        if ($period->isFinalized()) {
            return Inertia::flash('error', 'This payroll period is already finalized.')->back();
        }

        $validated = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')],
            'category' => ['required', Rule::enum(AdjustmentCategory::class)],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999'],
        ]);

        $period->adjustments()->create($validated);

        return Inertia::flash('success', 'Adjustment added. Reprocess the payroll to apply it.')->back();
    }

    public function destroy(PayrollPeriod $period, PayrollAdjustment $adjustment): RedirectResponse
    {
        if ($period->isFinalized()) {
            return Inertia::flash('error', 'This payroll period is already finalized.')->back();
        }

        $adjustment->delete();

        return Inertia::flash('success', 'Adjustment removed. Reprocess the payroll to apply it.')->back();
    }
}
