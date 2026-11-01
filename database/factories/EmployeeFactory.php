<?php

namespace Database\Factories;

use App\Enums\EmployeeLevel;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PayType;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_number' => fake()->unique()->numerify('TS-#####'),
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->lastName(),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->dateTimeBetween('-50 years', '-20 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['Male', 'Female']),
            'civil_status' => fake()->randomElement(['Single', 'Married']),
            'mobile_number' => fake()->numerify('09#########'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->address(),
            'branch_id' => Branch::factory(),
            'department_id' => Department::factory(),
            'shift_id' => Shift::factory(),
            'level' => EmployeeLevel::RankAndFile,
            'job_title' => fake()->jobTitle(),
            'employee_status' => EmployeeStatus::Active,
            'employment_status' => EmploymentStatus::Regular,
            'date_hired' => '2025-01-06',
            'pay_type' => PayType::Daily,
            'rate' => 600,
            'payment_method' => PaymentMethod::Cash,
            'has_statutory_deductions' => true,
            'custom_contributions' => false,
            'sss_contribution' => 750,
            'philhealth_contribution' => 375,
            'pagibig_contribution' => 200,
        ];
    }

    public function monthly(float $rate = 30000): static
    {
        return $this->state(fn (array $attributes) => [
            'pay_type' => PayType::Monthly,
            'rate' => $rate,
        ]);
    }

    public function perBiyahe(): static
    {
        return $this->state(fn (array $attributes) => [
            'pay_type' => PayType::PerBiyahe,
            'job_title' => 'Driver',
        ]);
    }

    /**
     * Use hand-entered contribution amounts instead of the contribution tables.
     */
    public function customContributions(float $sss = 750, float $philhealth = 375, float $pagibig = 200): static
    {
        return $this->state(fn (array $attributes) => [
            'custom_contributions' => true,
            'sss_contribution' => $sss,
            'philhealth_contribution' => $philhealth,
            'pagibig_contribution' => $pagibig,
        ]);
    }

    public function withUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory(),
        ]);
    }

    public function resigned(string $dateSeparated = '2026-01-15'): static
    {
        return $this->state(fn (array $attributes) => [
            'employee_status' => EmployeeStatus::Resigned,
            'date_separated' => $dateSeparated,
        ]);
    }
}
