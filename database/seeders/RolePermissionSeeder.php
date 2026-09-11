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
            'settings.view', 'settings.update',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $rolePermissions = [
            'super-admin' => $permissions,
            'admin' => $permissions,
            'manager' => ['documents.view', 'documents.create', 'documents.update', 'documents.download', 'documents.share', 'folders.view', 'folders.create', 'folders.update', 'folders.share', 'users.view', 'groups.view'],
            'utilisateur' => ['documents.view', 'documents.create', 'documents.download', 'folders.view'],
            'lecteur' => ['documents.view', 'documents.download', 'folders.view'],
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
