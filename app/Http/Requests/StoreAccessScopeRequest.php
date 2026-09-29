<?php

namespace App\Http\Requests;

use App\Enums\AccessScopeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccessScopeRequest extends FormRequest
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
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'scope_type' => ['required', 'string', Rule::enum(AccessScopeType::class)],
            'direction_id' => ['nullable', 'integer', 'exists:directions,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'folder_id' => ['nullable', 'integer', 'exists:folders,id'],
            'document_id' => ['nullable', 'integer', 'exists:documents,id'],
        ];
    }
}
