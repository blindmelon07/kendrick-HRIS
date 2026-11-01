<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payroll
    |--------------------------------------------------------------------------
    |
    | Monthly-rated employees are converted to a daily rate using
    | (monthly rate × 12) ÷ working days per year. Use 261 for a 5-day
    | work week or 313 for a 6-day work week.
    |
    | Multipliers follow the Philippine Labor Code defaults.
    |
    */

    'payroll' => [
        'working_days_per_year' => (int) env('HRIS_WORKING_DAYS_PER_YEAR', 313),
        'overtime_multiplier' => 1.25,
        'regular_holiday_worked_multiplier' => 2.0,
        'special_holiday_worked_multiplier' => 1.3,

        // Statutory deductions start one month after the date hired.
        'statutory_grace_months' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Government contributions (employee share, per month)
    |--------------------------------------------------------------------------
    |
    | 2026 rates. Update these when SSS, PhilHealth or Pag-IBIG publish new
    | circulars. Daily-rated employees use daily rate × (working days ÷ 12)
    | as their monthly salary.
    |
    */

    'contributions' => [
        // 15% of the Monthly Salary Credit; employee pays 5%. MSC steps of ₱500.
        'sss' => [
            'employee_rate' => 0.05,
            'min_msc' => 5000,
            'max_msc' => 35000,
            'msc_step' => 500,
        ],

        // 5% premium shared equally; employee pays 2.5% of salary within the floor and ceiling.
        'philhealth' => [
            'employee_rate' => 0.025,
            'salary_floor' => 10000,
            'salary_ceiling' => 100000,
        ],

        // 2% of salary up to the maximum fund salary (1% when salary is ₱1,500 or less).
        'pagibig' => [
            'employee_rate' => 0.02,
            'low_salary_rate' => 0.01,
            'low_salary_threshold' => 1500,
            'max_fund_salary' => 10000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Timekeeping
    |--------------------------------------------------------------------------
    |
    | Grace period before a time-in counts as late, in minutes.
    |
    */

    'timekeeping' => [
        'late_grace_minutes' => (int) env('HRIS_LATE_GRACE_MINUTES', 0),
    ],

];
