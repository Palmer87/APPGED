<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWorkflowStepRequest extends FormRequest
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
            'position' => ['required', 'integer', 'min:1'],
            'approver_type' => ['required', 'string', 'in:user,group'],
            'approver_user_id' => ['nullable', 'integer', 'required_if:approver_type,user'],
            'approver_group_id' => ['nullable', 'integer', 'required_if:approver_type,group'],
            'is_required' => ['nullable', 'boolean'],
        ];
    }
}
