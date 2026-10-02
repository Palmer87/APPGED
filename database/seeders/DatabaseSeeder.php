<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            OrganizationSeeder::class,
            GroupSeeder::class,
            RolePermissionSeeder::class,
            BillingPermissionSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            MetadataDefinitionSeeder::class,
            DocumentStructureSeeder::class,
        ]);
    }
}
