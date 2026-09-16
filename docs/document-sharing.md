# Module 11 — Partage de Documents V1

Ce document détaille l'architecture, la sécurité, le modèle de données et les règles de gestion du partage de documents au sein de la GED SaaS multi-tenant.

---

## 1. Objectif & Vue d'Ensemble

Permettre à un utilisateur autorisé (`documents.share`) de partager un document avec :
1. Un autre utilisateur de la même organisation.
2. Un groupe d'utilisateurs de la même organisation.

Le partage permet de définir :
- Le niveau d'accès : consultation (`view`) ou téléchargement (`download`).
- Une date d'expiration facultative (`expires_at`).
- La révocation sans suppression physique (`revoked_at`).

---

## 2. Modèle de Données (`document_shares`)

```text
document_shares
-------------------------
id              : bigint (PK)
organization_id : bigint (FK -> organizations)
document_id     : bigint (FK -> documents)
user_id         : bigint (nullable, FK -> users)
group_id        : bigint (nullable, FK -> groups)
permission      : varchar ('view', 'download')
expires_at      : timestamp (nullable)
shared_by       : bigint (FK -> users)
revoked_at      : timestamp (nullable)
revoked_by      : bigint (nullable, FK -> users)
created_at      : timestamp
updated_at      : timestamp
```

### Contrainte XOR
Un partage s'adresse **exclusivement** à un utilisateur OU à un groupe :
`(user_id IS NOT NULL AND group_id IS NULL) OR (user_id IS NULL AND group_id IS NOT NULL)`
Cette règle est garantie au niveau SQL par une contrainte `CHECK` sous PostgreSQL et des `TRIGGERS` sous SQLite.

---

## 3. Matrice des Permissions (`view` vs `download`)

| Permission | Prévisualisation (`preview`) | Recherche (`SearchService`) | Téléchargement (`download`) |
|---|---|---|---|
| `view` | Autorisée | Visible dans les résultats | **Refusé (403)** |
| `download` | Autorisée | Visible dans les résultats | **Autorisé (200)** |

> [!NOTE]
> Le niveau `download` inclut logiquement `view`. Un partage ne peut jamais accorder `download` sans permettre la consultation.

---

## 4. Gestion de l'Expiration et de la Révocation

### Expiration
- Si `expires_at` est `NULL` : le partage est **permanent**.
- Si `expires_at` est renseigné : le partage est **temporaire**.
- Un partage est considéré comme **actif** si et seulement si :
  `revoked_at IS NULL AND (expires_at IS NULL OR expires_at > now())`
- Dès que `expires_at <= now()`, l'accès est instantanément révoqué au niveau SQL, et le document disparaît de la recherche et de la prévisualisation.

### Révocation
- La révocation ne supprime pas la ligne en base : elle horodate `revoked_at = now()` et renseigne `revoked_by = auth()->id()`.
- Cela préserve l'historique pour les futurs modules d'audit.
- Un partage révoqué ne permet plus aucun accès.

---

## 5. Gestion Idempotente des Doublons

Lorsqu'un document est à nouveau partagé avec le même utilisateur ou groupe :
- Aucune ligne active en double n'est créée.
- Le partage existant est mis à jour (ex: passage de `view` à `download`, mise à jour d'`expires_at`, réactivation si précédemment révoqué).

---

## 6. Sécurité Multi-Tenant & Rôles

1. **Isolation Stricte** :
   - Un utilisateur ne peut **JAMAIS** partager un document avec un utilisateur ou groupe d'une autre organisation (rejet HTTP 403 immédiat).
   - Même un `super-admin` doit respecter l'appartenance du bénéficiaire à l'organisation du document.
2. **Permission d'Acteur** :
   - L'utilisateur qui partage doit disposer de la permission `documents.share`.
   - Dans la matrice Spatie par défaut, cette permission est accordée aux rôles :
     - `super-admin`
     - `admin`
     - `manager`
   - Les rôles `utilisateur` et `lecteur` ne peuvent pas initier de partage sans droit explicite.

---

## 7. Intégration dans les Moteurs Existants

### AccessControlService & DocumentPolicy
- `canAccessDocument(User $user, Document $document, string $permission)` :
  - Vérifie les partages actifs directs utilisateur et via groupe.
- `applyAccessScope(Builder $query, User $user)` :
  - Intègre nativement en sous-requêtes SQL `EXISTS` les partages actifs sur `document_shares`.

### SearchService
- Un document non accessible via les ACL standard devient visible pour un utilisateur dès qu'il lui est partagé (ou partagé à son groupe).
- Dès que le partage expire ou est révoqué, le document disparaît immédiatement des résultats de recherche.

### PreviewService & Téléchargement
- `documents.preview` fonctionne directement pour un bénéficiaire d'un partage `view` ou `download`.
- Le téléchargement requiert un partage avec permission explicite `download`.

---

## 8. Endpoints API / Web

| Méthode | Route | Description |
|---|---|---|
| `GET` | `/documents/{document}/shares` | Liste tous les partages actifs du document |
| `POST` | `/documents/{document}/shares/user` | Partage le document avec un utilisateur (`user_id`, `permission`, `expires_at`) |
| `POST` | `/documents/{document}/shares/group` | Partage le document avec un groupe (`group_id`, `permission`, `expires_at`) |
| `DELETE` | `/documents/{document}/shares/{share}` | Révoque un partage existant |
