<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterOrganizationRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:2', 'max:100', 'unique:organizations,name'],
            'activity' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
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
            'name.required' => "Le nom de l'organisation est obligatoire.",
            'name.min' => "Le nom de l'organisation doit contenir au moins :min caractères.",
            'name.max' => "Le nom de l'organisation ne peut pas dépasser :max caractères.",
            'name.unique' => 'Cette organisation est déjà enregistrée. Veuillez choisir un autre nom.',
        ];
    }
}
