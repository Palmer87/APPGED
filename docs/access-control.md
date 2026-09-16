# Modèle de Contrôle d'Accès (Access Control — ACL) V1

Ce document détaille l'architecture et les règles du système de permissions et d'accès effectif aux dossiers et documents dans la GED SaaS multi-tenant.

---

## 1. Vue d'Ensemble à Deux Niveaux

La sécurité documentaire repose sur deux niveaux de contrôle successifs :

### Niveau 1 : Isolation Multi-Tenant (Stricte)
- Tout utilisateur, groupe ou ressource appartient à une organisation unique (`organization_id`).
- Une ressource (dossier ou document) d'une organisation A est **strictement inaccessible** aux utilisateurs ou groupes d'une organisation B, même si les identifiants sont connus.
- Une tentative d'accès cross-tenant retourne systématiquement un refus immédiat (`403 Forbidden` ou `false`).

### Niveau 2 : Permissions Globales Spatie & ACL Granulaires
- **Permissions globales (Spatie)** : permettent de vérifier les habilitations générales d'un rôle dans l'organisation (ex. `documents.view`, `folders.update`).
- **Listes de Contrôle d'Accès (ACL)** : permettent d'affiner ou de restreindre les droits sur un document ou un dossier précis.

---

## 2. Permissions Disponibles

### Dossiers
- `folders.view` (ou `view`) : consultation du dossier et listing de son contenu.
- `folders.create` (ou `create`) : création de sous-dossiers ou documents.
- `folders.update` (ou `update`) : modification des métadonnées du dossier.
- `folders.delete` (ou `delete`) : suppression (soft delete) du dossier.
- `folders.share` (ou `share`) : attribution ou révocation des droits ACL sur le dossier.

### Documents
- `documents.view` (ou `view`) : consultation des détails du document.
- `documents.create` (ou `create`) : ajout de nouvelles versions ou téléversement.
- `documents.update` (ou `update`) : mise à jour des métadonnées du document.
- `documents.delete` (ou `delete`) : suppression (soft delete) du document.
- `documents.download` (ou `download`) : téléchargement du fichier ou d'une version.
- `documents.share` (ou `share`) : gestion des ACL du document.
- `documents.archive` (ou `archive`) : archivage du document.
- `documents.restore` (ou `restore`) : restauration du document supprimé.

---

## 3. Structure des ACL (Tables & Contraintes)

Deux tables stockent les ACL :
- `folder_permissions` (`folder_id`, `user_id`, `group_id`, `permission`)
- `document_permissions` (`document_id`, `user_id`, `group_id`, `permission`)

### Règle Exclusivité Sujet (User XOR Group)
Au niveau PostgreSQL, une contrainte CHECK garantit qu'une ACL vise **soit** un utilisateur, **soit** un groupe, jamais les deux simultanément :
```sql
CHECK ((user_id IS NOT NULL AND group_id IS NULL) OR (user_id IS NULL AND group_id IS NOT NULL))
```

### Unicité
Des index uniques partiels empêchent tout doublon :
- `(folder_id, user_id, permission)` WHERE `user_id IS NOT NULL`
- `(folder_id, group_id, permission)` WHERE `group_id IS NOT NULL`
- `(document_id, user_id, permission)` WHERE `user_id IS NOT NULL`
- `(document_id, group_id, permission)` WHERE `group_id IS NOT NULL`

---

## 4. Calcul de l'Accès Effectif (`canAccessDocument`)

L'évaluation de l'accès effectif d'un utilisateur à un document respecte l'ordre suivant :

1. **Super-Admin** : accès global accordé immédiatement.
2. **Isolation Tenant** : si `user.organization_id !== document.organization_id`, accès refusé.
3. **Cycle de vie** : si le document est soft-deleted, accès refusé.
4. **ACL Directe Utilisateur** : si une ACL sur le document associe l'utilisateur à la permission demandée, accès accordé.
5. **ACL Groupe** : si l'un des groupes de l'utilisateur dispose de l'ACL sur le document, accès accordé.
6. **Héritage Dossier Parent** : si le document est classé dans un dossier non supprimé :
   - L'utilisateur possède une ACL utilisateur sur le dossier pour l'action équivalente (ex. `view` sur dossier pour `view` ou `download` sur document) -> accès accordé.
   - L'un des groupes de l'utilisateur possède cette ACL sur le dossier -> accès accordé.
7. **Refus** : par défaut, en l'absence de droit accordé.

---

## 5. Règle de Priorité (Pas de Deny en V1)

En V1, le modèle suit le principe du **moindre privilège positif** :
- Il n'existe **pas de permission explicite 'deny'**.
- Une permission accordée (utilisateur OU groupe OU héritage dossier) accorde l'accès.
- L'absence de permission explicite vaut refus.
- Le propriétaire/uploader (`uploaded_by`) est soumis aux mêmes règles d'habilitation et d'ACL que les autres membres du tenant.

---

## 6. Exemples d'Utilisation

### Exemple 1 : Attribution d'un accès direct à un utilisateur
```php
$aclService = app(\App\Services\AccessControlService::class);

// Accorder la vue sur un document confidentiel
$aclService->grantDocumentPermission($document, $userMarie, 'view');

// Vérification
$canView = $aclService->canAccessDocument($userMarie, $document, 'view'); // true
```

### Exemple 2 : Attribution à un groupe
```php
// Accorder le droit de téléchargement à tout le groupe Comptabilité
$aclService->grantDocumentPermissionToGroup($factureDoc, $groupCompta, 'download');

// Un membre du groupe Compta a automatiquement accès
$canDownload = $aclService->canAccessDocument($userComptable, $factureDoc, 'download'); // true
```

### Exemple 3 : Héritage dossier direct
```php
// Accorder l'accès au dossier RH
$aclService->grantFolderPermission($folderRH, $userJean, 'view');

// Tout document directement contenu dans le dossier RH hérite du droit de consultation
$canViewContrat = $aclService->canAccessDocument($userJean, $contratDoc, 'view'); // true

// En révoquant l'accès au dossier, l'accès hérité est automatiquement révoqué
$aclService->revokeFolderPermission($folderRH, $userJean, 'view');
$canViewContrat = $aclService->canAccessDocument($userJean, $contratDoc, 'view'); // false
```
