<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class BillingPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'billing.view',
            'billing.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (Organization::query()->cursor() as $organization) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

            $superAdmin = Role::findOrCreate('super-admin', 'web');
            $superAdmin->givePermissionTo($permissions);

            $admin = Role::findOrCreate('admin', 'web');
            $admin->givePermissionTo($permissions);

            $manager = Role::findOrCreate('manager', 'web');
            $manager->givePermissionTo('billing.view');
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }
}
