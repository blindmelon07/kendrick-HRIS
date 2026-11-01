<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payslip>
 */
class PayslipFactory extends Factory
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
            'payment_method' => PaymentMethod::Cash,
            'days_worked' => 13,
            'basic_pay' => 7800,
            'gross_pay' => 7800,
            'total_deductions' => 1325,
            'net_pay' => 6475,
        ];
    }
}
