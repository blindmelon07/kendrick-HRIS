<?php

namespace Database\Factories;

use App\Enums\LoanType;
use App\Models\Employee;
use App\Models\Loan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Loan>
 */
class LoanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'type' => LoanType::Sss,
            'reference_number' => fake()->numerify('LN-######'),
            'principal' => 20000,
            'amortization' => 1000,
            'balance' => 20000,
            'start_date' => '2026-01-01',
        ];
    }
}
