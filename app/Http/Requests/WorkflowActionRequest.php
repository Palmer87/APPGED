<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class WorkflowActionRequest extends FormRequest
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
        $path = $this->decodedPath();
        $isCommentRequired = str_contains($path, 'reject') || str_contains($path, 'correction');

        return [
            'comment' => [$isCommentRequired ? 'required' : 'nullable', 'string', 'max:2000'],
        ];
    }
}
