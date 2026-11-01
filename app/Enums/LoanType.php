<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum LoanType: string
{
    use HasOptions;

    case Sss = 'sss';
    case Pagibig = 'pagibig';
    case Company = 'company';

    public function label(): string
    {
        return match ($this) {
            self::Sss => 'SSS Loan',
            self::Pagibig => 'Pag-IBIG Loan',
            self::Company => 'Company Loan',
        };
    }
}
