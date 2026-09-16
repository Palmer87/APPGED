<?php

namespace App\Services;

use App\Models\MetadataDefinition;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class MetadataDefinitionService
{
    /**
     * Allowed metadata definition types.
     */
    public const ALLOWED_TYPES = ['string', 'text', 'integer', 'decimal', 'boolean', 'date', 'datetime'];

    /**
     * Create a new metadata definition for the authenticated user's organization.
     */
    public function create(array $data): MetadataDefinition
    {
        $user = auth()->user();

        Gate::authorize('create', MetadataDefinition::class);

        Validator::make($data, [
            'name' => ['required', 'string', 'max:150'],
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/'],
            'type' => ['required', 'string', 'in:'.implode(',', self::ALLOWED_TYPES)],
            'description' => ['nullable', 'string'],
            'is_required' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ])->validate();

        $orgId = $user->organization_id;
        $data['organization_id'] = $orgId;

        // Ensure key uniqueness within the organization
        $existing = MetadataDefinition::where('organization_id', $orgId)
            ->where('key', $data['key'])
            ->first();

        if ($existing) {
            abort(422, 'A metadata definition with this key already exists in your organization.');
        }

        return MetadataDefinition::create($data);
    }

    /**
     * Update an existing metadata definition.
     */
    public function update(MetadataDefinition $definition, array $data): MetadataDefinition
    {
        Gate::authorize('update', $definition);

        Validator::make($data, [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'key' => ['sometimes', 'required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/'],
            'type' => ['sometimes', 'required', 'string', 'in:'.implode(',', self::ALLOWED_TYPES)],
            'description' => ['nullable', 'string'],
            'is_required' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ])->validate();

        if (isset($data['key']) && $data['key'] !== $definition->key) {
            $existing = MetadataDefinition::where('organization_id', $definition->organization_id)
                ->where('key', $data['key'])
                ->where('id', '!=', $definition->id)
                ->first();

            if ($existing) {
                abort(422, 'A metadata definition with this key already exists in your organization.');
            }
        }

        // Prevent modification of organization_id
        unset($data['organization_id']);

        $definition->update($data);

        return $definition;
    }

    /**
     * Delete a metadata definition (soft delete).
     * Values in DB are preserved for future restoration.
     */
    public function delete(MetadataDefinition $definition): void
    {
        Gate::authorize('delete', $definition);

        $definition->delete();
    }

    /**
     * Restore a soft-deleted metadata definition.
     */
    public function restore(MetadataDefinition $definition): void
    {
        Gate::authorize('restore', $definition);

        $definition->restore();
    }
}
