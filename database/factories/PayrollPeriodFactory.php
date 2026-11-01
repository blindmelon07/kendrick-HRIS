<?php

namespace Database\Factories;

use App\Enums\PayrollHalf;
use App\Enums\PayrollPeriodStatus;
use App\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollPeriod>
 */
class PayrollPeriodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-15',
            'pay_date' => '2026-10-25',
            'half' => PayrollHalf::First,
            'status' => PayrollPeriodStatus::Draft,
        ];
    }

    public function secondHalf(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => '2026-10-16',
            'end_date' => '2026-10-31',
            'pay_date' => '2026-11-10',
            'half' => PayrollHalf::Second,
        ]);
    }
}
