# MODULE 13 — Audit & Historique V1

## 1. Objectif & Vue d'ensemble

Le module d'audit conserve de manière inviolable et traçable l'ensemble des actions critiques effectuées au sein de la GED SaaS.
Il répond précisément à la question :
> **Qui a fait quoi, sur quelle ressource, dans quelle organisation, quand et avec quel résultat ?**

---

## 2. Schéma de la table `audit_logs`

```sql
CREATE TABLE audit_logs (
    id BIGSERIAL PRIMARY KEY,
    organization_id BIGINT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    user_id BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(100) NOT NULL,
    auditable_type VARCHAR(255) NULL,
    auditable_id BIGINT NULL,
    target_type VARCHAR(255) NULL,
    target_id BIGINT NULL,
    description TEXT NULL,
    old_values JSONB NULL,
    new_values JSONB NULL,
    metadata JSONB NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    result VARCHAR(20) NOT NULL DEFAULT 'success',
    created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
);
```

### Pourquoi `organization_id` est-il nullable ?
La colonne `organization_id` est nullable pour permettre l'enregistrement d'événements globaux du système ou des super-administrateurs hors du contexte d'un tenant spécifique (ex. authentification globale, opérations de maintenance). En revanche, pour toute opération concernant une ressource tenant, `organization_id` est impérativement déterminé côté serveur depuis la ressource ou le profil de l'utilisateur authentifié.

---

## 3. Immuabilité & Sécurité Anti-Altération

* **Append-only** : La table `audit_logs` ne contient pas de colonne `updated_at` (`AuditLog::UPDATED_AT = null`).
* **Verrouillage Eloquent** : Le modèle `AuditLog` intercepte et bloque les événements `updating` et `deleting`.
* **Policy stricte** : `AuditLogPolicy` interdit systématiquement les méthodes `create`, `update` et `delete` aux requêtes clientes.
* **Séparation des privilèges** :
  * La consultation des logs (`/audit-logs`) requiert la permission `audit.view` (attribuée par défaut à `super-admin`, `admin`, `manager`).
  * Les utilisateurs d'une organisation ne peuvent sous aucun prétexte consulter les logs d'une autre organisation.
  * Les super-administrateurs peuvent auditer globalement ou filtrer par organisation.
  * La consultation de l'historique d'un document (`/documents/{document}/history`) est conditionnée par l'autorisation de consultation sur le document (`DocumentPolicy::view`).

---

## 4. Redaction des Données Sensibles

Avant toute persistance dans `old_values`, `new_values` ou `metadata`, un filtre récursif masque automatiquement les valeurs dont la clé correspond à un champ sensible :
* `password`
* `password_confirmation`
* `remember_token`
* `token`
* `access_token`
* `refresh_token`
* `secret`
* `api_key`
* `authorization`

Toute occurrence est remplacée par la valeur `"[REDACTED]"`. Le contenu binaire des fichiers physiques n'est jamais consigné en base.

---

## 5. Actions Normalisées

| Catégorie | Action | Description |
|---|---|---|
| **Documents** | `document.created` | Création d'un document avec sa version initiale. |
| | `document.updated` | Mise à jour des métadonnées du document. |
| | `document.deleted` | Mise en corbeille (soft delete). |
| | `document.restored` | Restauration depuis la corbeille. |
| | `document.archived` | Archivage du document (`status = 'archived'`). |
| | `document.unarchived` | Désarchivage du document (`status = 'active'`). |
| | `document.force_deleted` | Suppression définitive avec purge physique des fichiers. |
| **Versions** | `document.version_created` | Dépôt d'une nouvelle version incrémentale. |
| | `document.version_restored` | Restauration d'une version antérieure sous un nouveau numéro. |
| **Partages** | `document.shared` | Partage du document avec un utilisateur ou un groupe. |
| | `document.share_revoked` | Révocation d'un partage de document. |
| **Permissions** | `document.permission_granted` | Attribution d'une permission granulaire ACL sur un document. |
| | `document.permission_revoked` | Révocation d'une permission granulaire ACL sur un document. |
| | `folder.permission_granted` | Attribution d'une permission granulaire ACL sur un dossier. |
| | `folder.permission_revoked` | Révocation d'une permission granulaire ACL sur un dossier. |
| **Dossiers** | `folder.created` | Création d'un dossier. |
| | `folder.updated` | Déplacement d'un dossier (changement de parent). |
| | `folder.deleted` | Mise en corbeille d'un dossier. |
| | `folder.restored` | Restauration d'un dossier depuis la corbeille. |
| **Catégories & Tags** | `document.category_updated` | Attribution ou suppression de catégorie sur un document. |
| | `document.tags_updated` | Synchronisation des étiquettes (tags) d'un document. |
| **Métadonnées** | `document.metadata_updated` | Mise à jour de valeurs de métadonnées personnalisées. |
| **Prévisualisation** | `document.previewed` | Consultation in-browser d'un document ou d'une version. |

---

## 6. Endpoints & Consultation de l'Historique

* `GET /audit-logs` (`audit.logs.index`) :
  * Filtres supportés : `organization_id` (super-admin uniquement), `user_id`, `action`, `result`, `auditable_type`, `auditable_id`, `target_type`, `target_id`, `date_from`, `date_to`.
  * Pagination sécurisée : `per_page` compris entre 1 et 100 (valeur par défaut : 25).
* `GET /documents/{document}/history` (`documents.history`) :
  * Historique chronologique complet (du plus récent au plus ancien) pour le document spécifié.

---

## 7. Performances & Stratégie de Rétention Future

* **Index composites** :
  * `[organization_id, created_at]` pour accélérer le tri et le partitionnement logique par tenant.
  * `[auditable_type, auditable_id]` et `[target_type, target_id]` pour les jointures polymorphiques.
  * `[action]`, `[result]`, `[user_id]` pour les filtres courants.
* **Rétention future** :
  * Prévoir une commande console de nettoyage/archivage froid (ex: 90 jours pour les `document.previewed`, 1 an à 5 ans pour les actions d'écriture selon la politique légale et l'abonnement du tenant).
