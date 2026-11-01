<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\FilingStatus;
use App\Models\Filing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Filing
 */
class FilingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'date_from' => $this->date_from->toDateString(),
            'date_to' => $this->date_to->toDateString(),
            'time_from' => $this->time_from ? substr($this->time_from, 0, 5) : null,
            'time_to' => $this->time_to ? substr($this->time_to, 0, 5) : null,
            'is_half_day' => $this->is_half_day,
            'with_pay' => $this->with_pay,
            'shift' => $this->whenLoaded('shift', fn () => $this->shift?->name),
            'reason' => $this->reason,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'remarks' => $this->remarks,
            'can_withdraw' => $this->status === FilingStatus::Pending,
            'employee' => EmployeeResource::make($this->whenLoaded('employee')),
            'filed_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
