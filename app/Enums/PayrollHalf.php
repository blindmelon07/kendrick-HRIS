<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PayrollHalf: string
{
    use HasOptions;

    case First = 'first';
    case Second = 'second';

    public function label(): string
    {
        return match ($this) {
            self::First => '1st Half',
            self::Second => '2nd Half',
        };
    }
}
