<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $userId = $this->route('user') instanceof User
            ? $this->route('user')->id
            : $this->route('user');

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['nullable', 'string'],
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
