<?php

namespace Database\Seeders;

use App\Enums\FolderType;
use App\Models\Folder;
use App\Models\MetadataDefinition;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class DocumentStructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizations = Organization::all();

        foreach ($organizations as $org) {
            $user = User::where('organization_id', $org->id)->first() ?? User::first();
            // 1. Assurer les métadonnées requises
            $defs = [
                'client' => MetadataDefinition::firstOrCreate(
                    ['organization_id' => $org->id, 'key' => 'client'],
                    [
                        'name' => 'Client',
                        'type' => 'string',
                        'description' => 'Nom du client ou de la société cliente',
                        'is_required' => false,
                        'is_active' => true,
                    ]
                ),
                'nom_salarie' => MetadataDefinition::firstOrCreate(
                    ['organization_id' => $org->id, 'key' => 'nom_salarie'],
                    [
                        'name' => 'Nom du salarié',
                        'type' => 'string',
                        'description' => 'Nom et prénom du collaborateur',
                        'is_required' => false,
                        'is_active' => true,
                    ]
                ),
                'periode_paie' => MetadataDefinition::firstOrCreate(
                    ['organization_id' => $org->id, 'key' => 'periode_paie'],
                    [
                        'name' => 'Période de paie',
                        'type' => 'string',
                        'description' => 'Ex: Mars 2026',
                        'is_required' => false,
                        'is_active' => true,
                    ]
                ),
                'montant_net' => MetadataDefinition::firstOrCreate(
                    ['organization_id' => $org->id, 'key' => 'montant_net'],
                    [
                        'name' => 'Net à payer',
                        'type' => 'decimal',
                        'description' => 'Montant net payé au salarié',
                        'is_required' => false,
                        'is_active' => true,
                    ]
                ),
            ];

            $existingDefs = MetadataDefinition::where('organization_id', $org->id)->get()->keyBy('key');

            // 2. Création des Directions (Niveau 1)
            $directionsData = [
                [
                    'name' => 'Comptabilité & Finance',
                    'code' => 'COMPTA',
                    'description' => 'Direction financière et comptable : factures, décharges, règlements',
                    'document_types' => [
                        [
                            'name' => 'Facture client',
                            'code' => 'FACT-CLI',
                            'description' => 'Factures émises à destination des clients',
                            'metadata' => [
                                ['key' => 'numero_facture', 'required' => true, 'order' => 1],
                                ['key' => 'date_facture', 'required' => true, 'order' => 2],
                                ['key' => 'client', 'required' => true, 'order' => 3],
                                ['key' => 'montant_ttc', 'required' => true, 'order' => 4],
                            ],
                        ],
                        [
                            'name' => 'Facture fournisseur',
                            'code' => 'FACT-FOURN',
                            'description' => 'Factures de charges et prestataires externes',
                            'metadata' => [
                                ['key' => 'numero_facture', 'required' => true, 'order' => 1],
                                ['key' => 'date_facture', 'required' => true, 'order' => 2],
                                ['key' => 'fournisseur', 'required' => true, 'order' => 3],
                                ['key' => 'montant_ttc', 'required' => true, 'order' => 4],
                                ['key' => 'date_echeance', 'required' => false, 'order' => 5],
                            ],
                        ],
                        [
                            'name' => 'Décharge client',
                            'code' => 'DECH-CLI',
                            'description' => 'Bons d’enlèvement et reçus de remise signés',
                            'metadata' => [
                                ['key' => 'client', 'required' => true, 'order' => 1],
                                ['key' => 'date_signature', 'required' => true, 'order' => 2],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Ressources Humaines',
                    'code' => 'RH',
                    'description' => 'Direction RH : contrats, fiches de paie, attestations',
                    'document_types' => [
                        [
                            'name' => 'Contrat de travail',
                            'code' => 'CONTRAT-RH',
                            'description' => 'Contrats CDI, CDD, avenants salariaux',
                            'metadata' => [
                                ['key' => 'numero_contrat', 'required' => true, 'order' => 1],
                                ['key' => 'nom_salarie', 'required' => true, 'order' => 2],
                                ['key' => 'date_signature', 'required' => true, 'order' => 3],
                            ],
                        ],
                        [
                            'name' => 'Bulletin de paie',
                            'code' => 'PAIE',
                            'description' => 'Bulletins mensuels de salaire',
                            'metadata' => [
                                ['key' => 'nom_salarie', 'required' => true, 'order' => 1],
                                ['key' => 'periode_paie', 'required' => true, 'order' => 2],
                                ['key' => 'montant_net', 'required' => true, 'order' => 3],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Direction Commerciale',
                    'code' => 'COMMERCIAL',
                    'description' => 'Développement commercial, devis, propositions et contrats clients',
                    'document_types' => [
                        [
                            'name' => 'Proposition commerciale',
                            'code' => 'PROP-COMM',
                            'description' => 'Offres commerciales et devis présentés aux prospects',
                            'metadata' => [
                                ['key' => 'client', 'required' => true, 'order' => 1],
                                ['key' => 'montant', 'required' => true, 'order' => 2],
                                ['key' => 'date_signature', 'required' => false, 'order' => 3],
                            ],
                        ],
                    ],
                ],
            ];

            foreach ($directionsData as $dirData) {
                // Créer ou retrouver la Direction
                $direction = Folder::firstOrCreate(
                    [
                        'organization_id' => $org->id,
                        'name' => $dirData['name'],
                        'parent_id' => null,
                    ],
                    [
                        'folder_type' => FolderType::Department,
                        'description' => $dirData['description'],
                        'is_active' => true,
                        'path' => '/'.$dirData['name'],
                        'created_by' => $user?->id,
                    ]
                );

                if ($direction->folder_type !== FolderType::Department) {
                    $direction->update([
                        'folder_type' => FolderType::Department,
                        'is_active' => true,
                    ]);
                }

                // Créer les Types Documentaires rattachés à la Direction
                foreach ($dirData['document_types'] as $docTypeData) {
                    $docType = Folder::firstOrCreate(
                        [
                            'organization_id' => $org->id,
                            'parent_id' => $direction->id,
                            'name' => $docTypeData['name'],
                        ],
                        [
                            'folder_type' => FolderType::DocumentType,
                            'description' => $docTypeData['description'],
                            'is_active' => true,
                            'path' => $direction->path.'/'.$docTypeData['name'],
                            'created_by' => $user?->id,
                        ]
                    );

                    if ($docType->folder_type !== FolderType::DocumentType) {
                        $docType->update([
                            'folder_type' => FolderType::DocumentType,
                            'is_active' => true,
                        ]);
                    }

                    // Associer les métadonnées avec ordre et caractère obligatoire
                    $syncData = [];
                    foreach ($docTypeData['metadata'] as $m) {
                        $def = $existingDefs->get($m['key']) ?? $defs[$m['key']] ?? null;
                        if ($def) {
                            $syncData[$def->id] = [
                                'is_required' => $m['required'],
                                'order' => $m['order'],
                            ];
                        }
                    }

                    if (! empty($syncData)) {
                        $docType->metadataDefinitions()->syncWithoutDetaching($syncData);
                    }
                }
            }
        }
    }
}
