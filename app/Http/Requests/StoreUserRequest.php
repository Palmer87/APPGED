<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string'],
            'direction_id' => ['nullable', 'integer', 'exists:directions,id'],
            'primary_service_id' => ['nullable', 'integer', 'exists:services,id'],
            'associated_service_ids' => ['nullable', 'array'],
            'associated_service_ids.*' => ['integer', 'exists:services,id'],
            'access_scope_type' => ['nullable', 'string'],
            'access_scope_direction_id' => ['nullable', 'integer', 'exists:directions,id'],
            'access_scope_service_id' => ['nullable', 'integer', 'exists:services,id'],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
