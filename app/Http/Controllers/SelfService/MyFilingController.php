<?php

namespace App\Http\Controllers\SelfService;

use App\Enums\FilingStatus;
use App\Enums\FilingType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFilingRequest;
use App\Models\Filing;
use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Employee kiosk: filing of Unaccounted, Leave, OT, Early Out and Shift Change.
 */
class MyFilingController extends Controller
{
    public function index(Request $request): Response
    {
        $employee = $request->user()->employee;

        return Inertia::render('self-service/filings', [
            'filings' => $employee->filings()->with(['shift:id,name', 'endorser:id,name', 'reviewer:id,name'])->latest()->paginate(20),
            'types' => FilingType::options(),
            'shifts' => Shift::query()->orderBy('name')->get(['id', 'name', 'start_time', 'end_time']),
        ]);
    }

    public function store(StoreFilingRequest $request): RedirectResponse
    {
        $request->user()->employee->filings()->create($request->validated());

        return Inertia::flash('success', 'Filing submitted for approval.')->back();
    }

    public function destroy(Request $request, Filing $filing): RedirectResponse
    {
        abort_unless($filing->employee_id === $request->user()->employee?->id, 403);

        if ($filing->status !== FilingStatus::Pending) {
            return Inertia::flash('error', 'Only pending filings can be withdrawn.')->back();
        }

        $filing->delete();

        return Inertia::flash('success', 'Filing withdrawn.')->back();
    }
}
