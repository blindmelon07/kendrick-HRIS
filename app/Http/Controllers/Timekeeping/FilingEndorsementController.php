<?php

namespace App\Http\Controllers\Timekeeping;

use App\Enums\FilingStatus;
use App\Http\Controllers\Controller;
use App\Models\Filing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dept. Head review of filings from their department's employees.
 */
class FilingEndorsementController extends Controller
{
    public function index(Request $request): Response
    {
        $showAll = $request->boolean('all');

        $filings = $request->user()->employee->endorsableFilings()
            ->with(['employee:id,employee_number,first_name,last_name,middle_name,suffix', 'shift:id,name'])
            ->unless($showAll, fn (Builder $query) => $query->where('status', FilingStatus::Pending))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('timekeeping/endorsements', [
            'filings' => $filings,
            'showAll' => $showAll,
        ]);
    }

    public function update(Request $request, Filing $filing): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['endorse', 'deny'])],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        abort_unless($request->user()->employee->canEndorse($filing), 403);

        if ($filing->status !== FilingStatus::Pending) {
            return Inertia::flash('error', 'This filing has already been reviewed.')->back();
        }

        $validated['decision'] === 'endorse'
            ? $filing->endorse($request->user(), $validated['remarks'] ?? null)
            : $filing->deny($request->user(), $validated['remarks'] ?? null);

        return Inertia::flash('success', 'Filing '.($validated['decision'] === 'endorse' ? 'endorsed to HR' : 'denied').'.')->back();
    }
}
