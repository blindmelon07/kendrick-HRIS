<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ShiftController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('shifts/index', [
            'shifts' => Shift::query()->withCount('employees')->orderBy('start_time')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Shift::create($this->validated($request));

        return Inertia::flash('success', 'Shift created.')->back();
    }

    public function update(Request $request, Shift $shift): RedirectResponse
    {
        $shift->update($this->validated($request, $shift));

        return Inertia::flash('success', 'Shift updated.')->back();
    }

    public function destroy(Shift $shift): RedirectResponse
    {
        if ($shift->employees()->exists()) {
            return Inertia::flash('error', 'This shift is still assigned to employees.')->back();
        }

        $shift->delete();

        return Inertia::flash('success', 'Shift deleted.')->back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Shift $shift = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('shifts')->ignore($shift)],
            'start_time' => ['required', 'date_format:H:i,H:i:s'],
            'end_time' => ['required', 'date_format:H:i,H:i:s', 'after:start_time'],
            'break_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'work_days' => ['required', 'array', 'min:1'],
            'work_days.*' => ['integer', 'between:1,7', 'distinct'],
        ]);
    }
}
