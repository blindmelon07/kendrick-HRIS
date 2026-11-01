<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PayType: string
{
    use HasOptions;

    case Daily = 'daily';
    case Monthly = 'monthly';
    case PerBiyahe = 'per_biyahe';

    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Daily Rate',
            self::Monthly => 'Monthly Rate',
            self::PerBiyahe => 'Per Biyahe',
        };
    }
}
