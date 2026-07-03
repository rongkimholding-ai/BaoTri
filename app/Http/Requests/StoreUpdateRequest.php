<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'integer'],

            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('stores', 'email')->ignore($this->store),
            ],

            'area' => [
                'required',
                'in:north,south',
            ],

            'region' => ['nullable', 'string', 'max:100'],

            'am_name' => ['nullable', 'string', 'max:255'],
            'am_email' => ['nullable', 'email', 'max:255'],

            'om_name' => ['nullable', 'string', 'max:255'],
            'om_email' => ['nullable', 'email', 'max:255'],

            'technician_name' => ['nullable', 'string', 'max:255'],

            'muasam_email' => ['nullable', 'email', 'max:255'],
        ];
    }
}