<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum EmploymentStatus: string
{
    use HasOptions;

    case Probationary = 'probationary';
    case Regular = 'regular';
    case Contractual = 'contractual';
    case ProjectBased = 'project_based';

    public function label(): string
    {
        return match ($this) {
            self::Probationary => 'Probationary',
            self::Regular => 'Regular',
            self::Contractual => 'Contractual',
            self::ProjectBased => 'Project-Based',
        };
    }
}
