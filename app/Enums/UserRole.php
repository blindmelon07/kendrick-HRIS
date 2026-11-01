<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum UserRole: string
{
    use HasOptions;

    case Admin = 'admin';
    case HrTimekeeper = 'hr_timekeeper';
    case HrPayroll = 'hr_payroll';
    case Finance = 'finance';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::HrTimekeeper => 'HR Time Keeper',
            self::HrPayroll => 'HR Payroll Officer',
            self::Finance => 'Finance',
            self::Employee => 'Employee',
        };
    }
}
