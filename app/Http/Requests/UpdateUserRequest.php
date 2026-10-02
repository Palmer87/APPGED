<?php

namespace App\Http\Requests;

use App\Models\Direction;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
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
        $userId = $this->route('user') instanceof User
            ? $this->route('user')->id
            : $this->route('user');

        $orgId = $this->user()?->organization_id;

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['nullable', 'string'],
            'direction_id' => [
                'nullable',
                'integer',
                Rule::exists('directions', 'id')->where(fn ($q) => $q->where('organization_id', $orgId)),
            ],
            'primary_service_id' => [
                'nullable',
                'integer',
                Rule::exists('services', 'id')->where(fn ($q) => $q->where('organization_id', $orgId)),
            ],
            'associated_service_ids' => ['nullable', 'array'],
            'associated_service_ids.*' => [
                'integer',
                Rule::exists('services', 'id')->where(fn ($q) => $q->where('organization_id', $orgId)),
            ],
            'access_scope_type' => ['nullable', 'string'],
            'access_scope_direction_id' => [
                'nullable',
                'integer',
                Rule::exists('directions', 'id')->where(fn ($q) => $q->where('organization_id', $orgId)),
            ],
            'access_scope_service_id' => [
                'nullable',
                'integer',
                Rule::exists('services', 'id')->where(fn ($q) => $q->where('organization_id', $orgId)),
            ],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => [
                'integer',
                Rule::exists('groups', 'id')->where(fn ($q) => $q->where('organization_id', $orgId)),
            ],
            'status' => ['required', 'in:active,inactive'],
        ];
    }

    /**
     * Configure after validation hooks to enforce Direction/Service consistency.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $directionId = $this->input('direction_id');
                $primaryServiceId = $this->input('primary_service_id');
                $associatedServiceIds = $this->input('associated_service_ids', []);

                if ($directionId && $primaryServiceId) {
                    $service = Service::find($primaryServiceId);
                    if ($service && (int) $service->direction_id !== (int) $directionId) {
                        $validator->errors()->add(
                            'primary_service_id',
                            'Le service principal sélectionné n\'appartient pas à la direction choisie.'
                        );
                    }
                }

                if ($directionId && ! empty($associatedServiceIds)) {
                    $invalidServices = Service::whereIn('id', $associatedServiceIds)
                        ->where('direction_id', '!=', $directionId)
                        ->count();

                    if ($invalidServices > 0) {
                        $validator->errors()->add(
                            'associated_service_ids',
                            'Un ou plusieurs services associés n\'appartiennent pas à la direction choisie.'
                        );
                    }
                }
            },
        ];
    }
}
