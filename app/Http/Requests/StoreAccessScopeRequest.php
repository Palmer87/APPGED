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
        $orgId = $this->user()?->organization_id;

        $userRule = Rule::exists('users', 'id');
        $directionRule = Rule::exists('directions', 'id');
        $serviceRule = Rule::exists('services', 'id');
        $folderRule = Rule::exists('folders', 'id');
        $documentRule = Rule::exists('documents', 'id');

        if ($orgId) {
            $userRule->where('organization_id', $orgId);
            $directionRule->where('organization_id', $orgId);
            $serviceRule->where('organization_id', $orgId);
            $folderRule->where('organization_id', $orgId);
            $documentRule->where('organization_id', $orgId);
        }

        return [
            'user_id' => ['required', 'integer', $userRule],
            'scope_type' => ['required', 'string', Rule::enum(AccessScopeType::class)],
            'scope_id' => ['nullable', 'integer'],

            // Direction scope: direction_id is required, service_id/folder_id/document_id prohibited
            'direction_id' => [
                'nullable',
                'integer',
                $directionRule,
                'required_if:scope_type,direction',
                'prohibited_if:scope_type,organization,service,document_type,folder,document',
            ],

            // Service scope: service_id is required, direction_id/folder_id/document_id prohibited
            'service_id' => [
                'nullable',
                'integer',
                $serviceRule,
                'required_if:scope_type,service',
                'prohibited_if:scope_type,organization,direction,document_type,folder,document',
            ],

            // Folder & DocumentType scope: folder_id is required, others prohibited
            'folder_id' => [
                'nullable',
                'integer',
                $folderRule,
                'required_if:scope_type,folder,document_type',
                'prohibited_if:scope_type,organization,direction,service,document',
            ],

            // Document scope: document_id is required, others prohibited
            'document_id' => [
                'nullable',
                'integer',
                $documentRule,
                'required_if:scope_type,document',
                'prohibited_if:scope_type,organization,direction,service,document_type,folder',
            ],

            'expires_at' => ['nullable', 'date'],
        ];
    }
}
