<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\FilingStatus;
use App\Enums\FilingType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFilingRequest;
use App\Http\Resources\Api\V1\FilingResource;
use App\Models\Filing;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * The employee's own filings (Unaccounted, Leave, OT, Early Out, Shift Change).
 */
class FilingController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filings = $request->user()->employee->filings()
            ->with('shift:id,name')
            ->latest()
            ->paginate(20);

        return FilingResource::collection($filings)->additional([
            'meta' => [
                'types' => FilingType::options(),
                'shifts' => Shift::query()->orderBy('name')->get(['id', 'name', 'start_time', 'end_time'])
                    ->map(fn (Shift $shift): array => [
                        'value' => (string) $shift->id,
                        'label' => $shift->name.' ('.substr($shift->start_time, 0, 5).'–'.substr($shift->end_time, 0, 5).')',
                    ]),
            ],
        ]);
    }

    public function store(StoreFilingRequest $request): JsonResponse
    {
        $filing = $request->user()->employee->filings()->create($request->validated());

        return FilingResource::make($filing->load('shift:id,name'))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, Filing $filing): Response|JsonResponse
    {
        abort_unless($filing->employee_id === $request->user()->employee->id, 403);

        if ($filing->status !== FilingStatus::Pending) {
            return response()->json(['message' => 'Only pending filings can be withdrawn.'], 422);
        }

        $filing->delete();

        return response()->noContent();
    }
}
