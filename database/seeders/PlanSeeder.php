<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'essential',
                'name' => 'Essentiel',
                'description' => 'Idéal pour les petites équipes et les entreprises qui démarrent la gestion électronique de documents.',
                'monthly_price' => 19000,
                'annual_price' => 190000,
                'currency' => 'XOF',
                'max_users' => 5,
                'max_storage_bytes' => 20 * 1024 * 1024 * 1024, // 20 Go
                'max_directions' => 3,
                'max_document_types' => 15,
                'max_ocr_pages_month' => 100,
                'has_api' => false,
                'has_workflows' => false,
                'has_advanced_audit' => false,
                'has_priority_support' => false,
                'has_dedicated_support' => false,
                'has_sla' => false,
                'has_custom_migration' => false,
                'has_custom_integrations' => false,
                'is_custom' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'professional',
                'name' => 'Professionnel',
                'description' => 'Pour les PME en pleine croissance exigeant des workflows, de l\'audit avancé et une haute capacité.',
                'monthly_price' => 39000,
                'annual_price' => 390000,
                'currency' => 'XOF',
                'max_users' => 20,
                'max_storage_bytes' => 100 * 1024 * 1024 * 1024, // 100 Go
                'max_directions' => 10,
                'max_document_types' => 50,
                'max_ocr_pages_month' => 1000,
                'has_api' => true,
                'has_workflows' => true,
                'has_advanced_audit' => true,
                'has_priority_support' => true,
                'has_dedicated_support' => false,
                'has_sla' => false,
                'has_custom_migration' => false,
                'has_custom_integrations' => false,
                'is_custom' => false,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'slug' => 'enterprise',
                'name' => 'Entreprise',
                'description' => 'Offre sur mesure pour les grandes entreprises, avec volumes illimités, SLA garanti et support dédié.',
                'monthly_price' => null,
                'annual_price' => null,
                'currency' => 'XOF',
                'max_users' => null,
                'max_storage_bytes' => null,
                'max_directions' => null,
                'max_document_types' => null,
                'max_ocr_pages_month' => null,
                'has_api' => true,
                'has_workflows' => true,
                'has_advanced_audit' => true,
                'has_priority_support' => true,
                'has_dedicated_support' => true,
                'has_sla' => true,
                'has_custom_migration' => true,
                'has_custom_integrations' => true,
                'is_custom' => true,
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }
    }
}
