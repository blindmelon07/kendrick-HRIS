<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BranchController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('branches/index', [
            'branches' => Branch::query()->withCount('employees')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Branch::create($this->validated($request));

        return Inertia::flash('success', 'Branch created.')->back();
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $branch->update($this->validated($request, $branch));

        return Inertia::flash('success', 'Branch updated.')->back();
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        if ($branch->employees()->exists()) {
            return Inertia::flash('error', 'This branch still has employees.')->back();
        }

        $branch->delete();

        return Inertia::flash('success', 'Branch deleted.')->back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Branch $branch = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('branches')->ignore($branch)],
            'code' => ['required', 'string', 'max:20', Rule::unique('branches')->ignore($branch)],
        ]);
    }
}
