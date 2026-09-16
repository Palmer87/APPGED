# Documentation — Module 15 : Workflow de Validation V1

## 1. Vue d'ensemble & Architecture

Le module de workflow documentaire V1 de notre GED SaaS permet de faire circuler un document à travers un circuit séquentiel d'étapes de validation avant son approbation définitive.

```text
Document (v1)
     │
     ▼
Workflow (Validation de contrat)
     │
     ├── Étape 1 : Responsable (User)
     │        │ [Approve]
     │        ▼
     ├── Étape 2 : Service Juridique (Group)
     │        │ [Request Correction]
     │        ▼
     │   Correction par l'utilisateur -> Nouvelle Version (v2)
     │        │ [Resubmit]
     │        ▼
     ├── Étape 2 : Service Juridique (Group)
     │        │ [Approve]
     │        ▼
     └── Étape 3 : Direction (User)
              │ [Approve]
              ▼
         Statut Final : Approved
```

### Principes Directeurs
1. **Cloisonnement multi-tenant absolu** : Aucun workflow, étape, instance, action ou notification ne peut traverser les frontières d'un tenant (`organization_id`). L'identifiant d'organisation provient obligatoirement de l'utilisateur ou de l'entité parente authentifiée.
2. **Respect strict des ACL** : Être notifié ou approbateur d'une étape n'octroie pas de droit de visionnage implicite sur le document. La consultation demeure régie par `DocumentPolicy` et `AccessControlService`.
3. **Double journalisation** :
   - `workflow_actions` : Historique métier complet (soumissions, approbations, rejets, demandes de correction, résoumissions, annulations) avec lien direct vers la version documentaire évaluée.
   - `audit_logs` : Journal d'audit de sécurité global traçant toutes les opérations via `AuditService`.
4. **Intégrité du cycle de vie** : Interdiction absolue d'archiver ou de placer à la corbeille un document dont le workflow est en cours (`in_progress` ou `correction_requested`).
5. **Concurrence & Idempotence** : Utilisation de `DB::transaction()` et `lockForUpdate()` garantissant qu'aucune validation concourante ne puisse corrompre l'état de l'instance.

---

## 2. Modèle de Données & Tables

### A. Table `workflows`
* `id` : Identifiant unique (BigInt)
* `organization_id` : Tenant propriétaire (Cascade on delete)
* `name` : Nom du circuit de validation
* `description` : Description facultative
* `is_active` : Statut actif/inactif (Boolean, défaut `true`)
* `created_by` : Utilisateur créateur (Restreint / Cascade)
* `timestamps()`, `softDeletes()`
* **Contrainte d'unicité** : `[organization_id, name]`

### B. Table `workflow_steps`
* `id` : Identifiant unique de l'étape
* `organization_id` : Tenant propriétaire
* `workflow_id` : Workflow parent (Cascade on delete)
* `name` : Intitulé de l'étape (ex: "Validation Juridique")
* `description` : Description / consignes pour l'approbateur
* `position` : Ordre d'exécution séquentiel (1, 2, 3...)
* `approver_type` : Type d'approbateur (`user` ou `group`)
* `approver_user_id` : Utilisateur désigné (Nullable, XOR avec group)
* `approver_group_id` : Groupe désigné (Nullable, XOR avec user)
* `is_required` : Indique si l'étape est obligatoire (Défaut `true`)
* `timestamps()`
* **Contrainte d'unicité** : `[workflow_id, position]`

### C. Table `workflow_instances`
* `id` : Identifiant unique de l'instance d'exécution
* `organization_id` : Tenant propriétaire
* `workflow_id` : Workflow suivi
* `document_id` : Document concerné
* `document_version_id` : Version couramment évaluée
* `started_by` : Utilisateur initiateur
* `current_step_id` : Étape courante en attente d'approbation (Nullable une fois terminé)
* `status` : Statut du workflow (`pending`, `in_progress`, `approved`, `rejected`, `correction_requested`, `cancelled`)
* `started_at` : Date et heure de début
* `completed_at` : Date et heure de fin (Nullable)
* `timestamps()`
* **Contrainte d'unicité partielle (PostgreSQL)** :
  `CREATE UNIQUE INDEX unique_active_workflow_per_document ON workflow_instances (document_id) WHERE status IN ('pending', 'in_progress', 'correction_requested');`
  Garantit qu'un document n'a qu'un seul workflow actif simultanément.

### D. Table `workflow_actions`
* `id` : Identifiant de l'action
* `organization_id` : Tenant propriétaire
* `workflow_instance_id` : Instance rattachée
* `workflow_step_id` : Étape sur laquelle l'action a eu lieu (Nullable)
* `user_id` : Utilisateur auteur de l'action
* `document_version_id` : Version du document au moment de l'action
* `action` : Type d'action (`submitted`, `approved`, `rejected`, `correction_requested`, `cancelled`)
* `comment` : Commentaire explicatif (Obligatoire pour reject et correction)
* `created_at` : Date de l'action (Immuable, pas de `updated_at`)

---

## 3. Rôles, Permissions & Sécurité

### Permissions Spatie Définies
* `workflows.view` : Consulter les workflows et instances de son organisation
* `workflows.create` : Créer un nouveau workflow
* `workflows.update` : Modifier un workflow et ses étapes
* `workflows.delete` : Supprimer un workflow (interdit si instance active)
* `workflows.execute` : Démarrer un workflow sur un document accessible
* `workflows.approve` : Valider ou demander une correction (soumis à être désigné sur l'étape)
* `workflows.reject` : Rejeter un workflow (soumis à être désigné sur l'étape)
* `workflows.cancel` : Annuler un workflow actif (initiateur ou manager/admin)

### Matrice d'Attribution
| Permission | super-admin | admin | manager | utilisateur | lecteur |
|---|:---:|:---:|:---:|:---:|:---:|
| `workflows.view` | ✅ | ✅ | ✅ | ✅ | ❌ |
| `workflows.create` | ✅ | ✅ | ✅ | ❌ | ❌ |
| `workflows.update` | ✅ | ✅ | ✅ | ❌ | ❌ |
| `workflows.delete` | ✅ | ✅ | ✅ | ❌ | ❌ |
| `workflows.execute` | ✅ | ✅ | ✅ | ✅ | ❌ |
| `workflows.approve` | ✅ | ✅ | ✅ | ✅* | ❌ |
| `workflows.reject` | ✅ | ✅ | ✅ | ✅* | ❌ |
| `workflows.cancel` | ✅ | ✅ | ✅ | ✅** | ❌ |

*\* L'utilisateur ne peut approuver/rejeter que s'il est nommément l'approbateur désigné ou membre du groupe assigné à l'étape courante.*
*\*\* L'utilisateur standard ne peut annuler que les instances qu'il a lui-même initiées.*

---

## 4. Règles Métier Détaillées

### A. Règle XOR des Approbateurs
Une étape doit cibler **exclusivement** :
* Un utilisateur précis (`approver_type = 'user'` ET `approver_user_id` non nul ET `approver_group_id` nul), OU
* Un groupe de validation (`approver_type = 'group'` ET `approver_group_id` non nul ET `approver_user_id` nul).
L'entité désignée doit impérativement appartenir au même tenant.

### B. Validation par Groupe (Règle V1)
Lorsqu'une étape est attribuée à un groupe, l'approbation du **premier membre valide** fait avancer le workflow à l'étape suivante.

### C. Gestion des Demandes de Correction & Résoumission
1. Lorsqu'un approbateur demande une correction, le statut devient `correction_requested` et un commentaire explicatif est obligatoire.
2. L'instance conserve la référence à `current_step_id` pour mémoriser l'étape émettrice.
3. L'utilisateur modifie le document (téléversement d'une nouvelle version v2 via `DocumentService::uploadNewVersion`).
4. L'utilisateur résoumet l'instance (`POST /workflow-instances/{instance}/resubmit`).
5. Le workflow reprend **à l'étape ayant demandé la correction**, avec la nouvelle version attachée. L'historique complet des actions précédentes est intégralement conservé.

### D. Interdiction d'Archivage ou de Suppression en Workflow Actif
* `DocumentLifecycleService::archive()` rejette la demande avec une erreur 422 si le document possède un workflow en cours (`in_progress` ou `correction_requested`).
* `DocumentLifecycleService::moveToTrash()` rejette également la mise en corbeille avec une erreur 422.
* Pour archiver ou supprimer le document, le workflow actif doit préalablement être approuvé, rejeté ou annulé explicitement.

---

## 5. Concurrence & Idempotence

Pour contrer le risque de double validation ou de passage d'étape corrompu en cas de requêtes concurrentes :
```php
DB::transaction(function () use ($instance, ...) {
    $lockedInstance = WorkflowInstance::where('id', $instance->id)
        ->lockForUpdate()
        ->firstOrFail();

    if ($lockedInstance->status !== WorkflowStatus::InProgress) {
        throw new HttpException(422, 'Workflow instance is not in progress.');
    }
    // ...
});
```
Toute tentative d'approbation sur une instance déjà validée, rejetée ou modifiée par un tiers est immédiatement interceptée et rejetée proprement (HTTP 422 / 403).

---

## 6. Endpoints de l'API

### Configuration des Workflows & Étapes
* `GET /workflows` : Liste paginée des workflows du tenant.
* `POST /workflows` : Création d'un workflow.
* `GET /workflows/{workflow}` : Détails d'un workflow avec ses étapes.
* `PUT /workflows/{workflow}` : Modification d'un workflow.
* `DELETE /workflows/{workflow}` : Suppression logique (bloquée si instance active).
* `POST /workflows/{workflow}/steps` : Ajout d'une étape.
* `PUT /workflow-steps/{step}` : Modification d'une étape.
* `DELETE /workflow-steps/{step}` : Suppression d'une étape (bloquée si instance active).
* `POST /workflows/{workflow}/steps/reorder` : Réordonnancement ordonné des étapes.

### Exécution & Actions du Workflow
* `POST /documents/{document}/workflows/{workflow}/start` : Démarrer un circuit de validation.
* `GET /workflow-instances` : Lister les instances du tenant (filtres `status`, `document_id`).
* `GET /workflow-instances/{instance}` : Consulter l'état et l'étape courante d'une instance.
* `GET /workflow-instances/{instance}/history` : Consulter l'historique chronologique inverse des actions métier.
* `POST /workflow-instances/{instance}/approve` : Valider l'étape courante.
* `POST /workflow-instances/{instance}/reject` : Rejeter le document (commentaire obligatoire).
* `POST /workflow-instances/{instance}/correction` : Demander des corrections (commentaire obligatoire).
* `POST /workflow-instances/{instance}/resubmit` : Résoumettre après correction.
* `POST /workflow-instances/{instance}/cancel` : Annuler le workflow.
