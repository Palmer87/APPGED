<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'documents.view', 'documents.create', 'documents.update', 'documents.delete', 'documents.download', 'documents.share', 'documents.archive', 'documents.restore',
            'folders.view', 'folders.create', 'folders.update', 'folders.delete', 'folders.share',
            'users.view', 'users.create', 'users.update', 'users.delete',
            'groups.view', 'groups.create', 'groups.update', 'groups.delete',
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'tags.view', 'tags.create', 'tags.update', 'tags.delete',
            'metadata.view', 'metadata.create', 'metadata.update', 'metadata.delete',
            'settings.view', 'settings.update',
            'audit.view',
            'workflows.view', 'workflows.create', 'workflows.update', 'workflows.delete', 'workflows.execute', 'workflows.approve', 'workflows.reject', 'workflows.cancel',
            'comments.view', 'comments.create', 'comments.update', 'comments.delete', 'comments.moderate',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $rolePermissions = [
            'super-admin' => $permissions,
            'admin' => $permissions,
            'manager' => ['documents.view', 'documents.create', 'documents.update', 'documents.download', 'documents.share', 'folders.view', 'folders.create', 'folders.update', 'folders.share', 'users.view', 'groups.view', 'audit.view', 'workflows.view', 'workflows.create', 'workflows.update', 'workflows.execute', 'workflows.approve', 'workflows.reject', 'workflows.cancel', 'comments.view', 'comments.create', 'comments.update', 'comments.delete', 'comments.moderate'],
            'utilisateur' => ['documents.view', 'documents.create', 'documents.download', 'folders.view', 'workflows.view', 'workflows.execute', 'workflows.approve', 'workflows.reject', 'workflows.cancel', 'comments.view', 'comments.create', 'comments.update', 'comments.delete'],
            'lecteur' => ['documents.view', 'documents.download', 'folders.view', 'comments.view'],
        ];

        foreach (Organization::query()->cursor() as $organization) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

            foreach ($rolePermissions as $roleName => $assignedPermissions) {
                $role = Role::findOrCreate($roleName, 'web');
                $role->syncPermissions($assignedPermissions);
            }
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }
}
