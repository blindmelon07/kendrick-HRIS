<?php

namespace App\Http\Requests;

use App\Enums\EmployeeLevel;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PayType;
use App\Models\Employee;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class EmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage-employees');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Employee|null $employee */
        $employee = $this->route('employee');

        return [
            'employee_number' => ['required', 'string', 'max:30', Rule::unique('employees')->ignore($employee)],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:10'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['Male', 'Female'])],
            'civil_status' => ['nullable', Rule::in(['Single', 'Married', 'Widowed', 'Separated'])],
            'mobile_number' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],

            'branch_id' => ['required', Rule::exists('branches', 'id')],
            'department_id' => ['required', Rule::exists('departments', 'id')],
            'shift_id' => ['required', Rule::exists('shifts', 'id')],
            'level' => ['required', Rule::enum(EmployeeLevel::class)],
            'job_title' => ['required', 'string', 'max:255'],
            'employee_status' => ['required', Rule::enum(EmployeeStatus::class)],
            'employment_status' => ['required', Rule::enum(EmploymentStatus::class)],
            'date_hired' => ['required', 'date'],
            'date_separated' => [
                Rule::requiredIf(fn (): bool => $this->input('employee_status') !== EmployeeStatus::Active->value),
                'nullable', 'date', 'after_or_equal:date_hired',
            ],

            'pay_type' => ['required', Rule::enum(PayType::class)],
            'rate' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'bank_account_number' => ['nullable', 'required_if:payment_method,'.PaymentMethod::Atm->value, 'string', 'max:40'],

            'sss_number' => ['nullable', 'string', 'max:30'],
            'philhealth_number' => ['nullable', 'string', 'max:30'],
            'pagibig_number' => ['nullable', 'string', 'max:30'],
            'tin' => ['nullable', 'string', 'max:30'],
            'has_statutory_deductions' => ['boolean'],
            'custom_contributions' => ['boolean'],
            // Only stored when HR overrides the contribution tables.
            'sss_contribution' => ['exclude_unless:custom_contributions,true', 'required', 'numeric', 'min:0'],
            'philhealth_contribution' => ['exclude_unless:custom_contributions,true', 'required', 'numeric', 'min:0'],
            'pagibig_contribution' => ['exclude_unless:custom_contributions,true', 'required', 'numeric', 'min:0'],

            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:50'],
            'emergency_contact_number' => ['nullable', 'string', 'max:30'],

            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=5000,max_height=5000'],
            'remove_photo' => ['boolean'],

            'portal_email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($employee?->user_id)],
            'portal_password' => [
                Rule::requiredIf(fn (): bool => filled($this->input('portal_email')) && ! $employee?->user_id),
                'nullable', Password::defaults(),
            ],
        ];
    }

    /**
     * Employee attributes, without the portal account and photo fields.
     *
     * @return array<string, mixed>
     */
    public function employeeAttributes(): array
    {
        return $this->safe()->except(['portal_email', 'portal_password', 'photo', 'remove_photo']);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo.max' => 'The photo must be 2 MB or smaller.',
            'photo.mimes' => 'The photo must be a JPG, PNG or WebP image.',
        ];
    }
}
