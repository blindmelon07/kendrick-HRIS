<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AdjustmentCategory: string
{
    use HasOptions;

    case PerBiyahe = 'per_biyahe';
    case OtherEarning = 'other_earning';
    case OtherDeduction = 'other_deduction';

    public function label(): string
    {
        return match ($this) {
            self::PerBiyahe => 'Per Biyahe',
            self::OtherEarning => 'Other Earning',
            self::OtherDeduction => 'Other Deduction',
        };
    }
}
