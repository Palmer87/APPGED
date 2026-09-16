<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
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
            'q' => ['nullable', 'string', 'max:255'],
            'organization_id' => ['nullable', 'integer'],
            'folder_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'tag_id' => ['nullable', 'integer'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer'],
            'metadata_key' => ['nullable', 'string', 'regex:/^[a-z][a-z0-9_]*$/'],
            'metadata_value' => ['nullable'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date'],
            'updated_from' => ['nullable', 'date'],
            'updated_to' => ['nullable', 'date'],
            'extension' => ['nullable', 'string', 'max:10'],
            'status' => ['nullable', 'in:active,archived'],
            'sort' => ['nullable', 'in:created_at,updated_at,name,size'],
            'direction' => ['nullable', 'in:asc,desc,ASC,DESC'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
