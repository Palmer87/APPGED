<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organization = Organization::query()->where('slug', 'ged-demo')->firstOrFail();

        $user = User::query()->firstOrCreate(
            ['email' => 'admin@ged-demo.test'],
            [
                'organization_id' => $organization->id,
                'first_name' => 'GED',
                'last_name' => 'Administrator',
                'password' => Hash::make('password'),
                'status' => 'active',
            ],
        );

        $group = Group::query()
            ->whereBelongsTo($organization)
            ->where('name', 'Administrateurs')
            ->firstOrFail();

        $user->groups()->syncWithoutDetaching([$group->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);
        $user->syncRoles(['admin', 'super-admin']);
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }
}
