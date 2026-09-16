<?php

namespace Database\Seeders;

use App\Enums\WorkflowApproverType;
use App\Models\Group;
use App\Models\Organization;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Database\Seeder;

class WorkflowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Organization::query()->cursor() as $organization) {
            $admin = User::where('organization_id', $organization->id)->first();
            if (! $admin) {
                continue;
            }

            $users = User::where('organization_id', $organization->id)->take(3)->get();
            $group = Group::where('organization_id', $organization->id)->first();

            // 1. Workflow: Validation de contrat (3 étapes)
            $contractWorkflow = Workflow::firstOrCreate(
                [
                    'organization_id' => $organization->id,
                    'name' => 'Validation de contrat',
                ],
                [
                    'description' => 'Circuit de validation standard pour tous les contrats commerciaux et fournisseurs.',
                    'is_active' => true,
                    'created_by' => $admin->id,
                ]
            );

            // Step 1: Responsable opérationnel
            WorkflowStep::updateOrCreate(
                [
                    'workflow_id' => $contractWorkflow->id,
                    'position' => 1,
                ],
                [
                    'organization_id' => $organization->id,
                    'name' => 'Revue par le responsable',
                    'description' => 'Vérification initiale des termes et conditions opérationnelles.',
                    'approver_type' => WorkflowApproverType::User,
                    'approver_user_id' => $users->first()?->id ?? $admin->id,
                    'approver_group_id' => null,
                    'is_required' => true,
                ]
            );

            // Step 2: Service Juridique / Groupe
            if ($group) {
                WorkflowStep::updateOrCreate(
                    [
                        'workflow_id' => $contractWorkflow->id,
                        'position' => 2,
                    ],
                    [
                        'organization_id' => $organization->id,
                        'name' => 'Validation Juridique',
                        'description' => 'Contrôle de conformité légale et réglementaire.',
                        'approver_type' => WorkflowApproverType::Group,
                        'approver_user_id' => null,
                        'approver_group_id' => $group->id,
                        'is_required' => true,
                    ]
                );
            } else {
                WorkflowStep::updateOrCreate(
                    [
                        'workflow_id' => $contractWorkflow->id,
                        'position' => 2,
                    ],
                    [
                        'organization_id' => $organization->id,
                        'name' => 'Validation Juridique',
                        'description' => 'Contrôle de conformité légale et réglementaire.',
                        'approver_type' => WorkflowApproverType::User,
                        'approver_user_id' => $users->get(1)?->id ?? $admin->id,
                        'approver_group_id' => null,
                        'is_required' => true,
                    ]
                );
            }

            // Step 3: Direction
            WorkflowStep::updateOrCreate(
                [
                    'workflow_id' => $contractWorkflow->id,
                    'position' => 3,
                ],
                [
                    'organization_id' => $organization->id,
                    'name' => 'Approbation Direction',
                    'description' => 'Validation finale pour engagement de l\'entreprise.',
                    'approver_type' => WorkflowApproverType::User,
                    'approver_user_id' => $users->last()?->id ?? $admin->id,
                    'approver_group_id' => null,
                    'is_required' => true,
                ]
            );

            // 2. Workflow: Validation de facture (2 étapes)
            $invoiceWorkflow = Workflow::firstOrCreate(
                [
                    'organization_id' => $organization->id,
                    'name' => 'Validation de facture',
                ],
                [
                    'description' => 'Circuit de validation pour mise en paiement des factures d\'achats.',
                    'is_active' => true,
                    'created_by' => $admin->id,
                ]
            );

            WorkflowStep::updateOrCreate(
                [
                    'workflow_id' => $invoiceWorkflow->id,
                    'position' => 1,
                ],
                [
                    'organization_id' => $organization->id,
                    'name' => 'Comptabilité fournisseur',
                    'description' => 'Rapprochement bon de commande et facture.',
                    'approver_type' => WorkflowApproverType::User,
                    'approver_user_id' => $users->first()?->id ?? $admin->id,
                    'approver_group_id' => null,
                    'is_required' => true,
                ]
            );

            WorkflowStep::updateOrCreate(
                [
                    'workflow_id' => $invoiceWorkflow->id,
                    'position' => 2,
                ],
                [
                    'organization_id' => $organization->id,
                    'name' => 'Direction financière',
                    'description' => 'Bon à payer.',
                    'approver_type' => WorkflowApproverType::User,
                    'approver_user_id' => $users->last()?->id ?? $admin->id,
                    'approver_group_id' => null,
                    'is_required' => true,
                ]
            );

            // 3. Workflow: Validation de document administratif (1 étape)
            $adminDocWorkflow = Workflow::firstOrCreate(
                [
                    'organization_id' => $organization->id,
                    'name' => 'Validation de document administratif',
                ],
                [
                    'description' => 'Revue administrative rapide.',
                    'is_active' => true,
                    'created_by' => $admin->id,
                ]
            );

            WorkflowStep::updateOrCreate(
                [
                    'workflow_id' => $adminDocWorkflow->id,
                    'position' => 1,
                ],
                [
                    'organization_id' => $organization->id,
                    'name' => 'Validation Manager',
                    'description' => 'Validation hiérarchique directe.',
                    'approver_type' => WorkflowApproverType::User,
                    'approver_user_id' => $admin->id,
                    'approver_group_id' => null,
                    'is_required' => true,
                ]
            );
        }
    }
}
