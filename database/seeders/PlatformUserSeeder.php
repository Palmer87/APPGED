<?php

namespace Database\Seeders;

use App\Models\PlatformUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PlatformUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $platformUsers = [
            [
                'name' => 'Propriétaire GEDAPP',
                'email' => 'owner@gedapp.com',
                'password' => Hash::make('Password123!'),
                'role' => 'platform_owner',
                'is_active' => true,
            ],
            [
                'name' => 'Admin SaaS Global',
                'email' => 'admin@gedapp.com',
                'password' => Hash::make('Password123!'),
                'role' => 'platform_admin',
                'is_active' => true,
            ],
            [
                'name' => 'Support Client SaaS',
                'email' => 'support@gedapp.com',
                'password' => Hash::make('Password123!'),
                'role' => 'platform_support',
                'is_active' => true,
            ],
            [
                'name' => 'Comptabilité & Facturation',
                'email' => 'billing@gedapp.com',
                'password' => Hash::make('Password123!'),
                'role' => 'platform_billing',
                'is_active' => true,
            ],
        ];

        foreach ($platformUsers as $data) {
            PlatformUser::firstOrCreate(
                ['email' => $data['email']],
                $data
            );
        }
    }
}
