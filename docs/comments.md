# Documentation — Module 16 : Commentaires & Collaboration V1

## 1. Vue d'ensemble & Architecture

Le module de commentaires et collaboration V1 permet aux utilisateurs autorisés d'échanger et de collaborer directement sur les documents de la GED ainsi que sur des versions spécifiques de ces documents.

```text
Document (Contrat.pdf)
   │
   ├── Commentaire Document-level (par User A)
   │      └── Réponse (par User B)
   │
   └── Version 2
          ├── Commentaire Version-level (par User C)
          └── Réponse (par User A)
```

### Principes Directeurs
1. **Cloisonnement multi-tenant absolu** : Aucun commentaire, réponse, notification ou entrée d'audit ne peut franchir les frontières de l'organisation (`organization_id`). L'identifiant d'organisation est invariablement dérivé du document ou de l'utilisateur authentifié.
2. **Dépendance stricte aux ACL** : Un commentaire ne confère jamais de droit d'accès au document. Pour lire, créer, répondre ou modifier, l'utilisateur doit posséder `documents.view` sur le document concerné via `DocumentPolicy` et `AccessControlService`.
3. **Hiérarchie à 1 niveau (Profondeur contrôlée)** :
   - Un commentaire racine possède `parent_id = null`.
   - Une réponse possède `parent_id = X`.
   - Une réponse ne peut jamais recevoir de sous-réponses (`parent.parent_id === null` obligatoire).
4. **Conservation de l'arborescence et masquage** : La suppression d'un commentaire parent utilise `SoftDeletes`. Ses réponses actives restent visibles et ordonnées, tandis que le contenu parent est masqué (`[Commentaire supprimé]`).
5. **Texte brut strict** : Sanitation systématique (`strip_tags`) pour interdire toute injection XSS ou HTML non sécurisé.

---

## 2. Modèle de Données & Table `document_comments`

### Colonnes
* `id` : Identifiant unique (BigInt)
* `organization_id` : Tenant propriétaire (Cascade on delete)
* `document_id` : Document rattaché (Cascade on delete)
* `document_version_id` : Version ciblée (Nullable, Null on delete)
* `user_id` : Auteur du commentaire (Cascade on delete)
* `parent_id` : Commentaire parent pour les réponses (Nullable, Cascade on delete)
* `content` : Contenu du commentaire (Text brut, max 10 000 caractères)
* `created_at`, `updated_at` : Horodatages standards
* `deleted_at` : SoftDeletes (Nullable)

### Index & Optimisation PostgreSQL
* Index individuels : `organization_id`, `document_id`, `document_version_id`, `user_id`, `parent_id`, `created_at`.
* Index composé : `['document_id', 'parent_id', 'created_at']` pour optimiser le listing paginé des commentaires racines ordonnés chronologiquement.

---

## 3. Rôles, Permissions & Sécurité

### Permissions Spatie Définies
* `comments.view` : Consulter les commentaires des documents accessibles.
* `comments.create` : Poster un commentaire ou répondre sur un document actif.
* `comments.update` : Modifier ses propres commentaires.
* `comments.delete` : Supprimer ses propres commentaires.
* `comments.moderate` : Modérer (modifier, supprimer, restaurer) les commentaires de tiers.

### Matrice d'Attribution
| Permission | super-admin | admin | manager | utilisateur | lecteur |
|---|:---:|:---:|:---:|:---:|:---:|
| `comments.view` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `comments.create` | ✅ | ✅ | ✅ | ✅ | ❌ |
| `comments.update` | ✅ | ✅ | ✅ | ✅* | ❌ |
| `comments.delete` | ✅ | ✅ | ✅ | ✅* | ❌ |
| `comments.moderate` | ✅ | ✅ | ✅ | ❌ | ❌ |

*\* L'utilisateur standard ne peut modifier ou supprimer que ses propres commentaires (`comment.user_id === actor.id`).*

---

## 4. Règles Métier & Cycle de Vie

### A. Cycle de vie du Document
| Statut du Document | Consultation des commentaires | Création / Réponses | Modification / Suppression |
|---|:---:|:---:|:---:|
| **Actif** (`active`) | ✅ Autorisée | ✅ Autorisée | ✅ Autorisée |
| **Archivé** (`archived`) | ✅ Autorisée (lecture seule) | ❌ Refusée (HTTP 422) | ❌ Refusée (HTTP 422) |
| **Corbeille** (`trashed`) | ❌ Refusée (HTTP 404 / 422) | ❌ Refusée (HTTP 422) | ❌ Refusée (HTTP 422) |

*Après restauration d'un document depuis la corbeille, tous ses commentaires redeviennent immédiatement consultables selon les permissions d'origine.*

### B. Commentaires au Niveau Version
* Un commentaire peut être créé globalement sur le document (`document_version_id = null`) ou lié à une version précise (`POST /documents/{document}/versions/{version}/comments`).
* La version doit obligatoirement appartenir au document et au même tenant (`version.document_id === document.id`).

### C. Réponses & Anti-imbrication
* Pour répondre à un commentaire : `POST /comments/{comment}/reply`.
* L'étape parent ne doit pas être une réponse (`parent.parent_id === null`).
* Si le parent est supprimé (`trashed()`), aucune nouvelle réponse ne peut lui être ajoutée.

### D. Suppression & Masquage de Contenu
* La suppression logique (`DELETE /comments/{comment}`) applique `SoftDeletes`.
* Si un commentaire parent est supprimé mais possède des réponses actives, il reste présent dans le flux paginé avec son contenu masqué par la mention `[Commentaire supprimé]`.

---

## 5. Endpoints de l'API

* `GET    /documents/{document}/comments` : Liste paginée des commentaires racines et de leurs réponses (support du filtre `?version_id=X` et du paramètre `per_page` borné entre 1 et 100).
* `POST   /documents/{document}/comments` : Création d'un commentaire racine.
* `POST   /documents/{document}/versions/{version}/comments` : Création d'un commentaire lié à une version.
* `POST   /comments/{comment}/reply` : Répondre à un commentaire racine existant.
* `PUT    /comments/{comment}` : Modifier son commentaire ou modérer.
* `DELETE /comments/{comment}` : Suppression logique d'un commentaire.
* `POST   /comments/{comment}/restore` : Restauration d'un commentaire supprimé.

---

## 6. Notifications & Audit

### Notifications
* Déclenchées via `NotificationService::notifyDocumentCommented()`.
* Commentaire racine : notification des participants intéressés du document (partages actifs + créateur).
* Réponse : notification prioritaire de l'auteur du commentaire parent et des participants au fil de discussion.
* Déduplication automatique et exclusion systématique de l'auteur.

### Journalisation d'Audit
* Événements enregistrés par `AuditService` :
  - `document.comment_created`
  - `document.comment_replied`
  - `document.comment_updated`
  - `document.comment_deleted`
  - `document.comment_restored`
* Métadonnées enregistrées : `document_id`, `comment_id`, `version_id`, `parent_id`. Aucun contenu sensible ou volumineux n'est stocké dans l'audit.
