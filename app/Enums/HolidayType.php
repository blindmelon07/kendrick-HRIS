<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum HolidayType: string
{
    use HasOptions;

    case Regular = 'regular';
    case SpecialNonWorking = 'special';

    public function label(): string
    {
        return match ($this) {
            self::Regular => 'Regular Holiday',
            self::SpecialNonWorking => 'Special Non-Working',
        };
    }
}
