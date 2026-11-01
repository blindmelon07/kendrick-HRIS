<?php

namespace App\Http\Controllers\Payroll;

use App\Enums\LoanType;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Loan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Government Loans Monitoring: SSS, Pag-IBIG and company loans.
 */
class LoanController extends Controller
{
    public function index(Request $request): Response
    {
        $showPaid = $request->boolean('paid');

        return Inertia::render('payroll/loans', [
            'loans' => Loan::query()
                ->with('employee:id,employee_number,first_name,last_name,middle_name,suffix')
                ->when(! $showPaid, fn (Builder $query) => $query->outstanding())
                ->latest('start_date')
                ->paginate(50)
                ->withQueryString(),
            'showPaid' => $showPaid,
            'types' => LoanType::options(),
            'employees' => Employee::query()->active()->orderBy('last_name')->get(['id', 'employee_number', 'first_name', 'last_name', 'middle_name', 'suffix']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        Loan::create([...$validated, 'balance' => $validated['balance'] ?? $validated['principal']]);

        return Inertia::flash('success', 'Loan added.')->back();
    }

    public function update(Request $request, Loan $loan): RedirectResponse
    {
        $loan->update($this->validated($request));

        return Inertia::flash('success', 'Loan updated.')->back();
    }

    public function destroy(Loan $loan): RedirectResponse
    {
        $loan->delete();

        return Inertia::flash('success', 'Loan removed.')->back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')],
            'type' => ['required', Rule::enum(LoanType::class)],
            'reference_number' => ['nullable', 'string', 'max:50'],
            'principal' => ['required', 'numeric', 'gt:0'],
            'amortization' => ['required', 'numeric', 'gt:0', 'lte:principal'],
            'balance' => ['nullable', 'numeric', 'min:0', 'lte:principal'],
            'start_date' => ['required', 'date'],
        ]);
    }
}
