<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum FilingStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case EndorsedByDeptHead = 'endorsed';
    case Approved = 'approved';
    case Denied = 'denied';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::EndorsedByDeptHead => 'Endorsed by Dept. Head',
            self::Approved => 'Approved',
            self::Denied => 'Denied',
        };
    }
}
