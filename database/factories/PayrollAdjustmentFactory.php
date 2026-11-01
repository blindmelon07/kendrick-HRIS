<?php

namespace Database\Factories;

use App\Enums\AdjustmentCategory;
use App\Models\Employee;
use App\Models\PayrollAdjustment;
use App\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollAdjustment>
 */
class PayrollAdjustmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payroll_period_id' => PayrollPeriod::factory(),
            'employee_id' => Employee::factory(),
            'category' => AdjustmentCategory::OtherEarning,
            'description' => fake()->words(2, true),
            'amount' => fake()->randomFloat(2, 100, 2000),
        ];
    }
}
