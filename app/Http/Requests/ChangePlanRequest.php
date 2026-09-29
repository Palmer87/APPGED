<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'plan_id' => ['required_without:plan_slug', 'nullable', 'integer', 'exists:plans,id'],
            'plan_slug' => ['required_without:plan_id', 'nullable', 'string', 'exists:plans,slug'],
            'billing_cycle' => ['required', 'string', 'in:monthly,annual'],
        ];
    }
}
