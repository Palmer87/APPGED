<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organization = Organization::query()->where('slug', 'ged-demo')->firstOrFail();

        Group::query()->firstOrCreate(
            [
                'organization_id' => $organization->id,
                'name' => 'Administrateurs',
            ],
            [
                'description' => 'Groupe des administrateurs de l’organisation de démonstration.',
                'status' => 'active',
            ],
        );
    }
}
