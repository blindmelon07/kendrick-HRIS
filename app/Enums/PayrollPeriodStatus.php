<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PayrollPeriodStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Processed = 'processed';
    case Finalized = 'finalized';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Processed => 'Processed',
            self::Finalized => 'Finalized',
        };
    }
}
