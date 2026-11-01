<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum EmployeeLevel: string
{
    use HasOptions;

    case RankAndFile = 'rank_and_file';
    case Supervisor = 'supervisor';
    case Manager = 'manager';

    public function label(): string
    {
        return match ($this) {
            self::RankAndFile => 'Rank & File',
            self::Supervisor => 'Supervisor',
            self::Manager => 'Manager',
        };
    }
}
