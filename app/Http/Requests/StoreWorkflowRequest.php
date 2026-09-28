<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'steps' => ['nullable', 'array'],
            'steps.*.name' => ['required_with:steps', 'string', 'max:255'],
            'steps.*.approver_type' => ['required_with:steps', 'string', 'in:user,group'],
            'steps.*.approver_user_id' => ['nullable', 'integer'],
            'steps.*.approver_group_id' => ['nullable', 'integer'],
            'steps.*.approver_id' => ['nullable', 'integer'],
            'steps.*.position' => ['nullable', 'integer'],
            'steps.*.is_required' => ['nullable', 'boolean'],
        ];
    }
}
