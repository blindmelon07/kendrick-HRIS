<?php

namespace Database\Factories;

use App\Enums\FilingStatus;
use App\Enums\FilingType;
use App\Models\Employee;
use App\Models\Filing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Filing>
 */
class FilingFactory extends Factory
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
            'type' => FilingType::Leave,
            'date_from' => '2026-10-05',
            'date_to' => '2026-10-05',
            'with_pay' => true,
            'reason' => fake()->sentence(),
            'status' => FilingStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FilingStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }

    public function endorsed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FilingStatus::EndorsedByDeptHead,
            'endorsed_at' => now(),
        ]);
    }
}
