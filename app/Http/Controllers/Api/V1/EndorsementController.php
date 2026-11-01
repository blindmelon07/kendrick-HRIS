<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\FilingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\FilingResource;
use App\Models\Filing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * Dept. Head endorsement of their team's filings.
 */
class EndorsementController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filings = $request->user()->employee->endorsableFilings()
            ->with(['employee:id,employee_number,first_name,last_name,middle_name,suffix', 'shift:id,name'])
            ->where('status', FilingStatus::Pending)
            ->oldest()
            ->paginate(30);

        return FilingResource::collection($filings);
    }

    public function update(Request $request, Filing $filing): FilingResource|JsonResponse
    {
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['endorse', 'deny'])],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        abort_unless($request->user()->employee->canEndorse($filing), 403);

        if ($filing->status !== FilingStatus::Pending) {
            return response()->json(['message' => 'This filing has already been reviewed.'], 422);
        }

        $validated['decision'] === 'endorse'
            ? $filing->endorse($request->user(), $validated['remarks'] ?? null)
            : $filing->deny($request->user(), $validated['remarks'] ?? null);

        return FilingResource::make($filing->fresh(['employee', 'shift']));
    }
}
