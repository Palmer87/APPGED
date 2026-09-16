<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class CategoryService
{
    /**
     * Create a new category for the authenticated user's organization.
     */
    public function create(array $data): Category
    {
        $user = auth()->user();

        Gate::authorize('create', Category::class);

        Validator::make($data, [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        // Enforce organization_id from the authenticated user
        $orgId = $user->organization_id;
        $data['organization_id'] = $orgId;

        // Ensure name uniqueness within the organization
        $existing = Category::where('organization_id', $orgId)
            ->where('name', $data['name'])
            ->first();

        if ($existing) {
            abort(422, 'A category with this name already exists in your organization.');
        }

        return Category::create($data);
    }

    /**
     * Update an existing category.
     */
    public function update(Category $category, array $data): Category
    {
        Gate::authorize('update', $category);

        Validator::make($data, [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        if (isset($data['name']) && $data['name'] !== $category->name) {
            $existing = Category::where('organization_id', $category->organization_id)
                ->where('name', $data['name'])
                ->where('id', '!=', $category->id)
                ->first();

            if ($existing) {
                abort(422, 'A category with this name already exists in your organization.');
            }
        }

        // Prevent modification of organization_id
        unset($data['organization_id']);

        $category->update($data);

        return $category;
    }

    /**
     * Delete a category (soft delete) and cleanly remove pivot relations.
     */
    public function delete(Category $category): void
    {
        Gate::authorize('delete', $category);

        DB::transaction(function () use ($category) {
            // Cleanly detach pivot relations without deleting documents
            $category->documents()->detach();
            $category->delete();
        });
    }

    /**
     * Restore a deleted category.
     */
    public function restore(Category $category): void
    {
        Gate::authorize('restore', $category);

        $category->restore();
    }
}
