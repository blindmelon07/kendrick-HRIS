<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum EmployeeStatus: string
{
    use HasOptions;

    case Active = 'active';
    case Resigned = 'resigned';
    case Terminated = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Resigned => 'Resigned',
            self::Terminated => 'Terminated',
        };
    }
}
