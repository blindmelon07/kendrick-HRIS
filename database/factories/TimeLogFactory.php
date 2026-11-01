<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\TimeLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeLog>
 */
class TimeLogFactory extends Factory
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
            'logged_at' => fake()->dateTimeThisMonth(),
            'source' => 'biometric',
        ];
    }
}
