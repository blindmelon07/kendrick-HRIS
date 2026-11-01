<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Employee
 */
class EmployeeResource extends JsonResource
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
            'employee_number' => $this->employee_number,
            'full_name' => $this->full_name,
            'first_name' => $this->first_name,
            'job_title' => $this->whenHas('job_title'),
            'department' => $this->whenLoaded('department', fn () => $this->department->name),
            'branch' => $this->whenLoaded('branch', fn () => $this->branch->name),
            'shift' => $this->whenLoaded('shift', fn () => [
                'name' => $this->shift->name,
                'start_time' => substr($this->shift->start_time, 0, 5),
                'end_time' => substr($this->shift->end_time, 0, 5),
            ]),
        ];
    }
}
