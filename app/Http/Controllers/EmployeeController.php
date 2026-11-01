<?php

namespace App\Http\Controllers;

use App\Enums\EmployeeLevel;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PayType;
use App\Enums\UserRole;
use App\Http\Requests\EmployeeRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\User;
use App\Services\Payroll\ContributionCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only('search', 'department_id', 'employee_status');

        $employees = Employee::query()
            ->with(['department:id,name', 'branch:id,name'])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query
                ->where('employee_number', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")))
            ->when($filters['department_id'] ?? null, fn (Builder $query, string $departmentId) => $query->where('department_id', $departmentId))
            ->where('employee_status', $filters['employee_status'] ?? EmployeeStatus::Active->value)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('employees/index', [
            'employees' => $employees,
            'filters' => $filters,
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => EmployeeStatus::options(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('employees/form', [
            'employee' => null,
            ...$this->formOptions(),
        ]);
    }

    public function store(EmployeeRequest $request): RedirectResponse
    {
        $employee = DB::transaction(function () use ($request): Employee {
            $employee = Employee::create($request->employeeAttributes());
            $this->syncPortalAccount($employee, $request);
            $this->syncPhoto($employee, $request);

            return $employee;
        });

        Inertia::flash('success', "Employee {$employee->full_name} created.");

        return redirect()->route('employees.show', $employee);
    }

    public function show(Employee $employee, ContributionCalculator $contributions): Response
    {
        $employee->load(['department', 'branch', 'shift', 'user:id,email,role']);

        return Inertia::render('employees/show', [
            'employee' => $employee,
            'contributions' => [
                ...$contributions->forEmployee($employee),
                'monthly_salary' => $employee->monthlySalary(),
                'sss_salary_credit' => $contributions->sssSalaryCredit($employee->monthlySalary()),
            ],
            'filings' => $employee->filings()->latest()->limit(10)->get(),
            'loans' => $employee->loans()->latest('start_date')->get(),
            'payslips' => $employee->payslips()->with('payrollPeriod')->latest('id')->limit(6)->get(),
        ]);
    }

    public function edit(Employee $employee): Response
    {
        $employee->load('user:id,email');

        return Inertia::render('employees/form', [
            'employee' => $employee,
            ...$this->formOptions(),
        ]);
    }

    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        DB::transaction(function () use ($request, $employee): void {
            $employee->update($request->employeeAttributes());
            $this->syncPortalAccount($employee, $request);
            $this->syncPhoto($employee, $request);
        });

        Inertia::flash('success', 'Employee record updated.');

        return redirect()->route('employees.show', $employee);
    }

    /**
     * Store, replace or remove the profile photo on the private disk.
     */
    private function syncPhoto(Employee $employee, EmployeeRequest $request): void
    {
        $previous = $employee->photo_path;

        if ($request->hasFile('photo')) {
            $employee->update(['photo_path' => $request->file('photo')->store('employee-photos', 'local')]);
        } elseif ($request->boolean('remove_photo')) {
            $employee->update(['photo_path' => null]);
        }

        if ($previous && $previous !== $employee->photo_path) {
            Storage::disk('local')->delete($previous);
        }
    }

    /**
     * Create or update the employee's self-service (kiosk) login.
     */
    private function syncPortalAccount(Employee $employee, EmployeeRequest $request): void
    {
        $email = $request->validated('portal_email');

        if (! $email) {
            return;
        }

        $attributes = array_filter([
            'name' => trim("{$employee->first_name} {$employee->last_name}"),
            'email' => $email,
            'password' => $request->validated('portal_password'),
        ]);

        if ($employee->user) {
            $employee->user->update($attributes);

            return;
        }

        $user = User::create([...$attributes, 'role' => UserRole::Employee]);
        $employee->update(['user_id' => $user->id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'shifts' => Shift::query()->orderBy('name')->get(['id', 'name', 'start_time', 'end_time']),
            'levels' => EmployeeLevel::options(),
            'employeeStatuses' => EmployeeStatus::options(),
            'employmentStatuses' => EmploymentStatus::options(),
            'payTypes' => PayType::options(),
            'paymentMethods' => PaymentMethod::options(),
        ];
    }
}
