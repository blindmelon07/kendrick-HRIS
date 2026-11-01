<?php

namespace App\Http\Requests;

use App\Enums\FilingType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFilingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->employee !== null;
    }

    /**
     * Only leave may span several days; everything else is a single-day filing.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('type') !== FilingType::Leave->value || $this->boolean('is_half_day')) {
            $this->merge(['date_to' => $this->input('date_from')]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $type = FilingType::tryFrom((string) $this->input('type'));

        return [
            'type' => ['required', Rule::enum(FilingType::class)],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from', 'before_or_equal:'.now()->addYear()->toDateString()],
            'time_from' => [
                Rule::requiredIf(in_array($type, [FilingType::Overtime, FilingType::EarlyOut], true)),
                Rule::when($type === FilingType::Unaccounted, ['required_without:time_to']),
                'nullable', 'date_format:H:i',
            ],
            'time_to' => [
                Rule::requiredIf($type === FilingType::Overtime),
                'nullable', 'date_format:H:i',
            ],
            'is_half_day' => ['boolean'],
            'with_pay' => ['boolean'],
            'shift_id' => [Rule::requiredIf($type === FilingType::ShiftChange), 'nullable', Rule::exists('shifts', 'id')],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'time_from' => $this->input('type') === FilingType::EarlyOut->value ? 'time out' : 'start time',
            'time_to' => 'end time',
            'shift_id' => 'shift',
        ];
    }
}
