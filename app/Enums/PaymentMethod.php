<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PaymentMethod: string
{
    use HasOptions;

    case Atm = 'atm';
    case Cash = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::Atm => 'ATM (BDO)',
            self::Cash => 'Cash',
        };
    }
}
