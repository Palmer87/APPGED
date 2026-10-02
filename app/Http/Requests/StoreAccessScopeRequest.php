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
            'scope_id' => ['nullable', 'integer'],

            // Direction scope: direction_id is required, service_id/folder_id/document_id prohibited
            'direction_id' => [
                'nullable',
                'integer',
                'exists:directions,id',
                'required_if:scope_type,direction',
                'prohibited_if:scope_type,organization,service,document_type,folder,document',
            ],

            // Service scope: service_id is required, direction_id/folder_id/document_id prohibited
            'service_id' => [
                'nullable',
                'integer',
                'exists:services,id',
                'required_if:scope_type,service',
                'prohibited_if:scope_type,organization,direction,document_type,folder,document',
            ],

            // Folder & DocumentType scope: folder_id is required, others prohibited
            'folder_id' => [
                'nullable',
                'integer',
                'exists:folders,id',
                'required_if:scope_type,folder,document_type',
                'prohibited_if:scope_type,organization,direction,service,document',
            ],

            // Document scope: document_id is required, others prohibited
            'document_id' => [
                'nullable',
                'integer',
                'exists:documents,id',
                'required_if:scope_type,document',
                'prohibited_if:scope_type,organization,direction,service,document_type,folder',
            ],

            'expires_at' => ['nullable', 'date'],
        ];
    }
}
