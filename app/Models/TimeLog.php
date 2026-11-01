<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\TimeLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A raw punch from the biometric ID-card system (or a manual Bundy Clock entry).
 *
 * @property int $id
 * @property int $employee_id
 * @property CarbonImmutable $logged_at
 * @property string $source
 */
#[Fillable(['employee_id', 'logged_at', 'source'])]
class TimeLog extends Model
{
    /** @use HasFactory<TimeLogFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'logged_at' => 'datetime:Y-m-d H:i:s',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
