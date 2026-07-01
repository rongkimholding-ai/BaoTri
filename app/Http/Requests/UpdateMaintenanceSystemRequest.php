<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMaintenanceSystemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'issue_code' => ['required', 'string', 'max:100'],
            'issue_name' => ['required', 'string', 'max:255'],
            'issue_description' => ['nullable', 'string'],
            'solution_description' => ['nullable', 'string'],

            'branch_code' => ['nullable', 'string', 'max:50'],
            'branch_name' => ['required', 'string', 'max:255'],
            'branch_email' => ['nullable', 'email', 'max:255'],

            'technician_name' => ['nullable', 'string', 'max:255'],
            'technician_email' => ['nullable', 'email', 'max:255'],
            'technician_mobile' => ['nullable', 'string', 'max:30'],

            'actual_completion_date' => ['required', 'string'],
            'status' => ['nullable', 'string'],
            'delay_reason' => ['nullable', 'string'],
        ];
    }
}
