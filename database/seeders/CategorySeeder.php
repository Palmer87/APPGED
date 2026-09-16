<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Contrats',
            'Factures',
            'Ressources humaines',
            'Documents administratifs',
            'Rapports',
            'Courriers',
            'Comptabilité',
        ];

        $organizations = Organization::all();

        foreach ($organizations as $organization) {
            foreach ($categories as $categoryName) {
                Category::firstOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'name' => $categoryName,
                    ],
                    [
                        'description' => "Catégorie $categoryName",
                    ]
                );
            }
        }
    }
}
