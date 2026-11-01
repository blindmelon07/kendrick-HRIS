<?php

namespace App\Http\Controllers;

use App\Enums\HolidayType;
use App\Models\Holiday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class HolidayController extends Controller
{
    public function index(Request $request): Response
    {
        $year = $request->integer('year', now()->year);

        return Inertia::render('holidays/index', [
            'holidays' => Holiday::query()->whereYear('date', $year)->orderBy('date')->get(),
            'year' => $year,
            'types' => HolidayType::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Holiday::create($this->validated($request));

        return Inertia::flash('success', 'Holiday added.')->back();
    }

    public function update(Request $request, Holiday $holiday): RedirectResponse
    {
        $holiday->update($this->validated($request, $holiday));

        return Inertia::flash('success', 'Holiday updated.')->back();
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $holiday->delete();

        return Inertia::flash('success', 'Holiday removed.')->back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Holiday $holiday = null): array
    {
        return $request->validate([
            'date' => ['required', 'date', Rule::unique('holidays')->ignore($holiday)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(HolidayType::class)],
        ]);
    }
}
