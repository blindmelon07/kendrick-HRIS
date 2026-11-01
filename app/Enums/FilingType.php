<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum FilingType: string
{
    use HasOptions;

    case Unaccounted = 'unaccounted';
    case Leave = 'leave';
    case Overtime = 'overtime';
    case EarlyOut = 'early_out';
    case ShiftChange = 'shift_change';

    public function label(): string
    {
        return match ($this) {
            self::Unaccounted => 'Unaccounted',
            self::Leave => 'Leave',
            self::Overtime => 'Overtime',
            self::EarlyOut => 'Early Out',
            self::ShiftChange => 'Shift Change',
        };
    }
}
