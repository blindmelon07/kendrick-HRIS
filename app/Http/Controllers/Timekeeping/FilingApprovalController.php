<?php

namespace App\Http\Controllers\Timekeeping;

use App\Enums\FilingStatus;
use App\Enums\FilingType;
use App\Http\Controllers\Controller;
use App\Models\Filing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * HR Time Keeper's final review of employee filings.
 */
class FilingApprovalController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status', 'for_review')->toString();
        $type = $request->string('type')->toString();

        $filings = Filing::query()
            ->with(['employee:id,employee_number,first_name,last_name,middle_name,suffix,department_id', 'employee.department:id,name,head_employee_id', 'shift:id,name', 'endorser:id,name'])
            ->when($status === 'for_review', fn (Builder $query) => $query->whereIn('status', [FilingStatus::Pending, FilingStatus::EndorsedByDeptHead]))
            ->when($status !== 'for_review' && $status !== 'all', fn (Builder $query) => $query->where('status', $status))
            ->when($type, fn (Builder $query) => $query->where('type', $type))
            ->latest()
            ->paginate(30)
            ->withQueryString()
            ->through(fn (Filing $filing): array => [
                ...$filing->toArray(),
                'ready_for_review' => $filing->isReadyForReview(),
            ]);

        return Inertia::render('timekeeping/filings', [
            'filings' => $filings,
            'filters' => ['status' => $status, 'type' => $type],
            'statuses' => FilingStatus::options(),
            'types' => FilingType::options(),
        ]);
    }

    public function update(Request $request, Filing $filing): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'deny'])],
            'with_pay' => ['boolean'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $filing->isReadyForReview()) {
            return Inertia::flash('error', 'This filing still needs the Dept. Head\'s endorsement.')->back();
        }

        if ($filing->type === FilingType::Leave && array_key_exists('with_pay', $validated)) {
            $filing->with_pay = $validated['with_pay'];
        }

        $validated['decision'] === 'approve'
            ? $filing->approve($request->user(), $validated['remarks'] ?? null)
            : $filing->deny($request->user(), $validated['remarks'] ?? null);

        return Inertia::flash('success', 'Filing '.($validated['decision'] === 'approve' ? 'approved' : 'denied').'.')->back();
    }
}
