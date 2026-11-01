<?php

namespace App\Services\Payroll;

use App\Models\Employee;

/**
 * Employee share of SSS, PhilHealth and Pag-IBIG contributions per month,
 * from the rates in config/hris.php.
 */
class ContributionCalculator
{
    /**
     * Monthly contributions for an employee: the custom amounts when HR set them,
     * otherwise computed from the contribution tables.
     *
     * @return array{sss_contribution: float, philhealth_contribution: float, pagibig_contribution: float}
     */
    public function forEmployee(Employee $employee): array
    {
        if ($employee->custom_contributions) {
            return [
                'sss_contribution' => (float) $employee->sss_contribution,
                'philhealth_contribution' => (float) $employee->philhealth_contribution,
                'pagibig_contribution' => (float) $employee->pagibig_contribution,
            ];
        }

        return $this->forMonthlySalary($employee->monthlySalary());
    }

    /**
     * @return array{sss_contribution: float, philhealth_contribution: float, pagibig_contribution: float}
     */
    public function forMonthlySalary(float $salary): array
    {
        return [
            'sss_contribution' => $this->sss($salary),
            'philhealth_contribution' => $this->philhealth($salary),
            'pagibig_contribution' => $this->pagibig($salary),
        ];
    }

    /**
     * Monthly Salary Credit: salaries are grouped into ₱500 brackets centred on
     * each credit (e.g. ₱15,250–₱15,749.99 → ₱15,500), within the minimum and maximum.
     */
    public function sssSalaryCredit(float $salary): float
    {
        $config = config('hris.contributions.sss');
        $step = $config['msc_step'];
        $credit = floor(($salary + $step / 2) / $step) * $step;

        return (float) max($config['min_msc'], min($config['max_msc'], $credit));
    }

    private function sss(float $salary): float
    {
        return round($this->sssSalaryCredit($salary) * config('hris.contributions.sss.employee_rate'), 2);
    }

    private function philhealth(float $salary): float
    {
        $config = config('hris.contributions.philhealth');
        $base = max($config['salary_floor'], min($config['salary_ceiling'], $salary));

        return round($base * $config['employee_rate'], 2);
    }

    private function pagibig(float $salary): float
    {
        $config = config('hris.contributions.pagibig');
        $rate = $salary <= $config['low_salary_threshold'] ? $config['low_salary_rate'] : $config['employee_rate'];

        return round(min($salary, $config['max_fund_salary']) * $rate, 2);
    }
}
