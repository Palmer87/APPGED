<?php

namespace App\Services;

use App\Models\Tag;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class TagService
{
    /**
     * Create a new tag for the authenticated user's organization.
     */
    public function create(array $data): Tag
    {
        $user = auth()->user();

        Gate::authorize('create', Tag::class);

        Validator::make($data, [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        // Enforce organization_id from the authenticated user
        $orgId = $user->organization_id;
        $data['organization_id'] = $orgId;

        // Ensure name uniqueness within the organization
        $existing = Tag::where('organization_id', $orgId)
            ->where('name', $data['name'])
            ->first();

        if ($existing) {
            abort(422, 'A tag with this name already exists in your organization.');
        }

        return Tag::create($data);
    }

    /**
     * Update an existing tag.
     */
    public function update(Tag $tag, array $data): Tag
    {
        Gate::authorize('update', $tag);

        Validator::make($data, [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        if (isset($data['name']) && $data['name'] !== $tag->name) {
            $existing = Tag::where('organization_id', $tag->organization_id)
                ->where('name', $data['name'])
                ->where('id', '!=', $tag->id)
                ->first();

            if ($existing) {
                abort(422, 'A tag with this name already exists in your organization.');
            }
        }

        // Prevent modification of organization_id
        unset($data['organization_id']);

        $tag->update($data);

        return $tag;
    }

    /**
     * Delete a tag (soft delete).
     */
    public function delete(Tag $tag): void
    {
        Gate::authorize('delete', $tag);

        $tag->delete();
    }

    /**
     * Restore a deleted tag.
     */
    public function restore(Tag $tag): void
    {
        Gate::authorize('restore', $tag);

        $tag->restore();
    }
}
