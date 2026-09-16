<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            'Important',
            'Urgent',
            'Juridique',
            'Comptabilité',
            'RH',
            'Archive',
            '2026',
        ];

        $organizations = Organization::all();

        foreach ($organizations as $organization) {
            foreach ($tags as $tagName) {
                Tag::firstOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'name' => $tagName,
                    ],
                    [
                        'description' => "Tag $tagName",
                    ]
                );
            }
        }
    }
}
