<?php

namespace Database\Seeders;

use App\Models\MetadataDefinition;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class MetadataDefinitionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $definitions = [
            [
                'name' => 'Numéro du contrat',
                'key' => 'numero_contrat',
                'type' => 'string',
                'description' => 'Référence unique du contrat',
                'is_required' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Date de signature',
                'key' => 'date_signature',
                'type' => 'date',
                'description' => 'Date de signature formelle',
                'is_required' => false,
                'is_active' => true,
            ],
            [
                'name' => "Date d'expiration",
                'key' => 'date_expiration',
                'type' => 'date',
                'description' => 'Date de fin de validité',
                'is_required' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Fournisseur',
                'key' => 'fournisseur',
                'type' => 'string',
                'description' => 'Nom du prestataire ou tiers',
                'is_required' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Montant',
                'key' => 'montant',
                'type' => 'decimal',
                'description' => 'Montant contractuel global',
                'is_required' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Numéro de facture',
                'key' => 'numero_facture',
                'type' => 'string',
                'description' => 'Numéro légal de la facture',
                'is_required' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Date de facture',
                'key' => 'date_facture',
                'type' => 'date',
                'description' => "Date d'émission de la facture",
                'is_required' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Montant TTC',
                'key' => 'montant_ttc',
                'type' => 'decimal',
                'description' => 'Montant total toutes taxes comprises',
                'is_required' => false,
                'is_active' => true,
            ],
            [
                'name' => "Date d'échéance",
                'key' => 'date_echeance',
                'type' => 'date',
                'description' => 'Date limite de paiement',
                'is_required' => false,
                'is_active' => true,
            ],
        ];

        $organizations = Organization::all();

        foreach ($organizations as $organization) {
            foreach ($definitions as $def) {
                MetadataDefinition::firstOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'key' => $def['key'],
                    ],
                    [
                        'name' => $def['name'],
                        'type' => $def['type'],
                        'description' => $def['description'],
                        'is_required' => $def['is_required'],
                        'is_active' => $def['is_active'],
                    ]
                );
            }
        }
    }
}
