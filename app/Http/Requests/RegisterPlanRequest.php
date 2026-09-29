<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterPlanRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'plan' => ['required', 'string', 'in:essential,professional,enterprise'],
            'billing_cycle' => ['required', 'string', 'in:monthly,annual'],
        ];
    }

    /**
     * Custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'plan.required' => 'Veuillez sélectionner une formule.',
            'plan.in' => 'La formule sélectionnée est invalide.',
            'billing_cycle.required' => 'Veuillez sélectionner un cycle de facturation.',
            'billing_cycle.in' => 'Le cycle de facturation doit être mensuel ou annuel.',
        ];
    }
}
