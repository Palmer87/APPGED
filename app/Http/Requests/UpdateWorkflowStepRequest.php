<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkflowStepRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'position' => ['sometimes', 'required', 'integer', 'min:1'],
            'approver_type' => ['sometimes', 'required', 'string', 'in:user,group'],
            'approver_user_id' => ['nullable', 'integer'],
            'approver_group_id' => ['nullable', 'integer'],
            'is_required' => ['sometimes', 'boolean'],
        ];
    }
}
