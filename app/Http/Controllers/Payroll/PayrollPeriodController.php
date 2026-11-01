<?php

namespace App\Http\Controllers\Payroll;

use App\Enums\AdjustmentCategory;
use App\Enums\PaymentMethod;
use App\Enums\PayrollHalf;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PayrollPeriodController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('payroll/periods/index', [
            'periods' => PayrollPeriod::query()
                ->withCount('payslips')
                ->withSum('payslips', 'net_pay')
                ->latest('start_date')
                ->paginate(24),
            'halves' => PayrollHalf::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date', 'before_or_equal:'.$request->date('start_date')?->addDays(31)->toDateString()],
            'pay_date' => ['required', 'date', 'after_or_equal:end_date'],
            'half' => ['required', Rule::enum(PayrollHalf::class)],
        ]);

        $overlaps = PayrollPeriod::query()
            ->whereDate('start_date', '<=', $validated['end_date'])
            ->whereDate('end_date', '>=', $validated['start_date'])
            ->exists();

        if ($overlaps) {
            return back()->withErrors(['start_date' => 'This cut-off overlaps an existing payroll period.']);
        }

        $period = PayrollPeriod::create($validated);

        Inertia::flash('success', 'Payroll period created.');

        return redirect()->route('payroll.periods.show', $period);
    }

    public function show(PayrollPeriod $period): Response
    {
        $payslips = $period->payslips()
            ->with('employee:id,employee_number,first_name,last_name,middle_name,suffix,bank_account_number')
            ->get()
            ->sortBy('employee.last_name')
            ->values();

        return Inertia::render('payroll/periods/show', [
            'period' => $period,
            'payslips' => $payslips,
            'totals' => [
                'gross' => round((float) $payslips->sum('gross_pay'), 2),
                'deductions' => round((float) $payslips->sum('total_deductions'), 2),
                'net' => round((float) $payslips->sum('net_pay'), 2),
                'atm' => round((float) $payslips->where('payment_method', PaymentMethod::Atm)->sum('net_pay'), 2),
                'cash' => round((float) $payslips->where('payment_method', PaymentMethod::Cash)->sum('net_pay'), 2),
            ],
            'adjustments' => $period->adjustments()
                ->with('employee:id,employee_number,first_name,last_name,middle_name,suffix')
                ->latest()
                ->get(),
            'categories' => AdjustmentCategory::options(),
            'employees' => Employee::query()->active()->orderBy('last_name')->get(['id', 'employee_number', 'first_name', 'last_name', 'middle_name', 'suffix']),
        ]);
    }

    public function destroy(PayrollPeriod $period): RedirectResponse
    {
        if ($period->isFinalized()) {
            return Inertia::flash('error', 'A finalized payroll period cannot be deleted.')->back();
        }

        $period->delete();

        Inertia::flash('success', 'Payroll period deleted.');

        return redirect()->route('payroll.periods.index');
    }
}
