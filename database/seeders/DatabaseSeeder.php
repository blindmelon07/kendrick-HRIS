<?php

namespace Database\Seeders;

use App\Enums\EmployeeLevel;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with demo data. Every account's password is "password".
     */
    public function run(): void
    {
        $this->call([OrganizationSeeder::class, HolidaySeeder::class]);

        User::factory()->admin()->create(['name' => 'System Administrator', 'email' => 'admin@tristellar.test']);
        User::factory()->role(UserRole::HrTimekeeper)->create(['name' => 'HR Time Keeper', 'email' => 'timekeeper@tristellar.test']);
        User::factory()->role(UserRole::HrPayroll)->create(['name' => 'HR Payroll Officer', 'email' => 'payroll@tristellar.test']);
        User::factory()->role(UserRole::Finance)->create(['name' => 'Finance', 'email' => 'finance@tristellar.test']);

        $trece = Branch::where('code', 'TRECE')->sole();
        $tanza = Branch::where('code', 'TANZA')->sole();
        $rankAndFile = Shift::where('start_time', '07:00:00')->sole();
        $supervisor = Shift::where('start_time', '08:00:00')->sole();
        $operations = Department::where('code', 'OPS')->sole();

        $head = Employee::factory()->monthly(35000)->create([
            'employee_number' => 'TS-00001',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'branch_id' => $trece->id,
            'department_id' => $operations->id,
            'shift_id' => $supervisor->id,
            'level' => EmployeeLevel::Supervisor,
            'job_title' => 'Operations Supervisor',
            'payment_method' => PaymentMethod::Atm,
            'bank_account_number' => '0012-3456-7890',
            'user_id' => User::factory()->create(['name' => 'Maria Santos', 'email' => 'head@tristellar.test'])->id,
        ]);
        $operations->update(['head_employee_id' => $head->id]);

        Employee::factory()->create([
            'employee_number' => 'TS-00002',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'branch_id' => $trece->id,
            'department_id' => $operations->id,
            'shift_id' => $rankAndFile->id,
            'job_title' => 'Warehouse Staff',
            'user_id' => User::factory()->create(['name' => 'Juan Dela Cruz', 'email' => 'employee@tristellar.test'])->id,
        ]);

        Employee::factory()->perBiyahe()->create([
            'employee_number' => 'TS-00003',
            'first_name' => 'Pedro',
            'last_name' => 'Reyes',
            'branch_id' => $tanza->id,
            'department_id' => $operations->id,
            'shift_id' => $rankAndFile->id,
            'rate' => 550,
        ]);

        Department::all()->each(fn (Department $department) => Employee::factory()
            ->count(3)
            ->sequence(fn ($sequence) => ['employee_number' => sprintf('TS-%05d', 100 + $department->id * 10 + $sequence->index)])
            ->create([
                'department_id' => $department->id,
                'branch_id' => fake()->randomElement([$trece->id, $tanza->id]),
                'shift_id' => $rankAndFile->id,
                'rate' => fake()->randomElement([550, 600, 650]),
            ]));

        $this->seedOctoberLogs();
    }

    /**
     * Biometric punches for October 2026 with some lates, undertime and missing logs.
     */
    private function seedOctoberLogs(): void
    {
        $rows = [];

        foreach (Employee::with('shift')->get() as $employee) {
            foreach (CarbonPeriod::create('2026-10-01', '2026-10-30') as $day) {
                $date = CarbonImmutable::instance($day);

                if (! $employee->shift->isWorkDay($date) || fake()->boolean(4)) {
                    continue;
                }

                $timeIn = $employee->shift->startsAt($date)->addMinutes(fake()->randomElement([-15, -10, -5, -2, 0, 3, 12]));
                $timeOut = $employee->shift->endsAt($date)->addMinutes(fake()->randomElement([-20, 0, 2, 5, 10, 15]));

                $rows[] = ['employee_id' => $employee->id, 'logged_at' => $timeIn, 'source' => 'biometric', 'created_at' => now(), 'updated_at' => now()];

                if (! fake()->boolean(3)) {
                    $rows[] = ['employee_id' => $employee->id, 'logged_at' => $timeOut, 'source' => 'biometric', 'created_at' => now(), 'updated_at' => now()];
                }
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            TimeLog::insert($chunk);
        }
    }
}
