<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('departments/index', [
            'departments' => Department::query()->with('head:id,first_name,last_name')->withCount('employees')->orderBy('name')->get(),
            'employees' => Employee::query()->active()->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'middle_name', 'suffix']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Department::create($this->validated($request));

        return Inertia::flash('success', 'Department created.')->back();
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $department->update($this->validated($request, $department));

        return Inertia::flash('success', 'Department updated.')->back();
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->employees()->exists()) {
            return Inertia::flash('error', 'Move this department\'s employees before deleting it.')->back();
        }

        $department->delete();

        return Inertia::flash('success', 'Department deleted.')->back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Department $department = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments')->ignore($department)],
            'code' => ['required', 'string', 'max:20', Rule::unique('departments')->ignore($department)],
            'email' => ['nullable', 'email', 'max:255'],
            'head_employee_id' => ['nullable', Rule::exists('employees', 'id')],
        ]);
    }
}
