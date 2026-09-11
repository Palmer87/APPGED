<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Organization::query()->firstOrCreate(
            ['slug' => 'ged-demo'],
            [
                'name' => 'GED Demo',
                'email' => 'contact@ged-demo.test',
                'storage_limit' => 5_368_709_120,
                'status' => 'active',
            ],
        );
    }
}
