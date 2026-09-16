# GEDAPP — Spécification de l'API REST V1 (Laravel Sanctum)

## 1. Vue d'ensemble

L'API REST V1 de la solution GED multi-tenant offre une interface standardisée, sécurisée et performante conçue pour :
- Les applications mobiles (**React Native / Expo**).
- Les intégrations de systèmes tiers autorisés.
- Des frontends alternatifs ou portails partenaires.

Toutes les routes sont préfixées par `/api/v1`.

---

## 2. Principes Fondamentaux & Sécurité

### Authentification & Tokens
- L'authentification repose sur **Laravel Sanctum** (Personal Access Tokens).
- Chaque requête protégée doit transmettre l'en-tête :
  ```http
  Authorization: Bearer <personal_access_token>
  Accept: application/json
  ```
- Les tokens sont délivrés lors du `login` et peuvent être révoqués unitairement (`logout`) ou globalement (`logout-all`).

### Isolation Multi-Tenant Stricte
```text
API Request (Bearer Token)
          ↓
  Authenticated User
          ↓
  Organization ID (verrouillé par le contexte du token)
          ↓
  AccessControlService & Policies
          ↓
  Ressource demandée
```
- **Règle absolue :** Aucun paramètre `organization_id` transmis dans le payload ou les requêtes ne peut altérer l'organisation de l'utilisateur. Le tenant est dérivé à 100% de l'utilisateur authentifié.
- Toute tentative d'accès cross-tenant (ex: consultation d'un document d'une autre organisation) est rejetée avec un code HTTP **403 Forbidden** ou **404 Not Found**.

---

## 3. Endpoints d'Authentification (`/api/v1/auth`)

### `POST /api/v1/auth/login`
Authentification avec identifiant et mot de passe. Supporte indifféremment l'adresse email ou le numéro de téléphone.

- **Request Body :**
  ```json
  {
    "email": "alice@acme.test",
    "password": "password123",
    "device_name": "ged-mobile"
  }
  ```
- **Response (200 OK) :**
  ```json
  {
    "data": {
      "token": "1|qW4e...token_string",
      "token_type": "Bearer",
      "user": {
        "id": 1,
        "first_name": "Alice",
        "last_name": "Smith",
        "name": "Alice Smith",
        "email": "alice@acme.test",
        "phone": "+33600000000",
        "avatar": null,
        "job_title": "Responsable Achats",
        "status": "active",
        "organization_id": 1,
        "organization": {
          "id": 1,
          "name": "Acme Corporation"
        },
        "roles": ["admin"],
        "permissions": ["documents.view", "documents.create", "..."]
      }
    }
  }
  ```

### `POST /api/v1/auth/logout`
Révoque le token d'accès utilisé pour la requête courante.
- **Headers :** `Authorization: Bearer <token>`
- **Response (200 OK) :**
  ```json
  { "message": "Successfully logged out." }
  ```

### `POST /api/v1/auth/logout-all`
Révoque l'ensemble des tokens d'accès de l'utilisateur (déconnexion de tous les terminaux).
- **Headers :** `Authorization: Bearer <token>`
- **Response (200 OK) :**
  ```json
  { "message": "Successfully logged out from all devices." }
  ```

### `GET /api/v1/auth/me`
Retourne le profil complet de l'utilisateur connecté avec son organisation, ses rôles et ses permissions calculées.
- **Response (200 OK) :** Structure `UserResource` sans mots de passe ni hash.

---

## 4. Organisation (`/api/v1/organizations`)

### `GET /api/v1/organizations/current`
Retourne les détails et quotas de l'organisation courante.
- **Response (200 OK) :**
  ```json
  {
    "data": {
      "id": 1,
      "name": "Acme Corporation",
      "slug": "acme-corp",
      "logo": null,
      "email": "contact@acme.com",
      "storage_limit": 5368709120,
      "status": "active"
    }
  }
  ```

---

## 5. Dossiers (`/api/v1/folders`)

- `GET /api/v1/folders` : Liste les dossiers accessibles (racines par défaut, ou selon `?parent_id=X`, ou tous si `?all=1`).
- `POST /api/v1/folders` : Crée un dossier (`name`, `description`, optionnel `parent_id`).
- `GET /api/v1/folders/{id}` : Détails d'un dossier avec enfants et créateur.
- `PUT /api/v1/folders/{id}` : Mise à jour du dossier (renommage, déplacement avec détection de cycles).
- `DELETE /api/v1/folders/{id}` : Suppression logique (mise en corbeille).

---

## 6. Documents & Versions (`/api/v1/documents`)

### Documents
- `GET /api/v1/documents` : Liste paginée des documents actifs accessibles (filtrable par `folder_id`, `category_id`, `tag_id`, `status`).
- `POST /api/v1/documents` : Téléversement multipart d'un nouveau document (`file`, optionnels `name`, `description`, `folder_id`, `category_id`, `tag_ids`).
- `GET /api/v1/documents/{id}` : Détails complets du document et de sa version courante.
- `PUT /api/v1/documents/{id}` : Mise à jour des métadonnées (`name`, `description`, `folder_id`, `category_id`, `tag_ids`).
- `DELETE /api/v1/documents/{id}` : Mise en corbeille du document.
- `GET /api/v1/documents/{id}/download` : Téléchargement du fichier binaire.
- `GET /api/v1/documents/{id}/preview` : Prévisualisation binaire/métadonnées du document.
- `GET /api/v1/documents/{id}/history` : Historique d'audit du document.
- `POST /api/v1/documents/{id}/favorite` : Ajout / retrait des favoris (toggle).

### Versions de Documents
- `GET /api/v1/documents/{id}/versions` : Liste chronologique des versions du document.
- `POST /api/v1/documents/{id}/versions` : Téléversement d'une nouvelle version (`file`, `comment`).
- `GET /api/v1/documents/{id}/versions/{version}/download` : Téléchargement d'une version spécifique.
- `POST /api/v1/documents/{id}/versions/{version}/restore` : Restauration d'une version antérieure en tant que nouvelle version courante.

---

## 7. Recherche Avancée (`/api/v1/search`)

- `GET /api/v1/search?q=Contrat&category_id=1&from=2026-01-01`
  - Recherche plein texte sur nom, description et nom de fichier.
  - Filtrage multi-critères : catégorie, tags, dossier, extensions, dates de création.
  - Résultat paginé conforme à `DocumentResource`.

---

## 8. Catégories & Tags (`/api/v1/categories`, `/api/v1/tags`)

- `GET /api/v1/categories` : Liste des catégories avec comptage de documents.
- `POST /api/v1/categories` : Création de catégorie (unicité par organisation).
- `GET /api/v1/categories/{id}` : Consultation.
- `PUT /api/v1/categories/{id}` : Modification.
- `DELETE /api/v1/categories/{id}` : Suppression.
- Mêmes opérations standards pour `/api/v1/tags`.

---

## 9. Métadonnées Personnalisées (`/api/v1/metadata`)

- `GET /api/v1/metadata/definitions` : Définitions de champs personnalisés du tenant.
- `POST /api/v1/metadata/definitions` : Création de définition (`name`, `key`, `type`, `is_required`).
- `GET /api/v1/metadata/definitions/{id}` : Consultation.
- `PUT /api/v1/metadata/definitions/{id}` : Modification.
- `DELETE /api/v1/metadata/definitions/{id}` : Suppression.
- `GET /api/v1/metadata/documents/{document}` : Valeurs typées des métadonnées du document.
- `PUT /api/v1/metadata/documents/{document}` : Mise à jour en lot des valeurs de métadonnées.

---

## 10. Favoris & Récents (`/api/v1/favorites`, `/api/v1/recent`)

- `GET /api/v1/favorites` : Liste des documents favoris de l'utilisateur.
- `POST /api/v1/favorites/documents/{id}` : Toggle favori.
- `GET /api/v1/recent` : Liste des 5 à 50 documents récemment consultés ou modifiés par l'utilisateur.

---

## 11. Partages Internes (`/api/v1/shares`)

- `GET /api/v1/shares/documents/{document}` : Liste des partages actifs et passés.
- `POST /api/v1/shares/documents/{document}/user` : Partage avec un utilisateur (`user_id`, `permission`, optionnel `expires_at`).
- `POST /api/v1/shares/documents/{document}/group` : Partage avec un groupe (`group_id`, `permission`, optionnel `expires_at`).
- `DELETE /api/v1/shares/documents/{document}/{share}` : Révocation du partage.

---

## 12. Moteur de Workflows (`/api/v1/workflows`)

### Définitions
- `GET /api/v1/workflows` : Liste des définitions de workflow actives.
- `POST /api/v1/workflows` : Création d'un circuit avec étapes (`name`, `steps`: `approver_type`, `approver_user_id` / `approver_group_id`).
- `GET /api/v1/workflows/{id}` : Détail du workflow et ordonnancement des étapes.

### Exécution & Instances
- `POST /api/v1/workflows/documents/{document}/start/{workflow}` : Démarrage du circuit de validation sur le document.
- `GET /api/v1/workflows/instances` : Liste des instances de workflow en cours / terminées.
- `GET /api/v1/workflows/instances/{id}` : Détail de l'instance, étape courante et historique des actions.
- `POST /api/v1/workflows/instances/{id}/approve` : Validation de l'étape courante par l'approbateur désigné.
- `POST /api/v1/workflows/instances/{id}/reject` : Rejet de l'instance avec motif obligatoire.
- `POST /api/v1/workflows/instances/{id}/cancel` : Annulation de l'instance par son initiateur ou un administrateur.

---

## 13. Commentaires & Collaboration (`/api/v1/comments`)

- `GET /api/v1/comments/documents/{document}` : Liste paginée des discussions et réponses imbriquées.
- `POST /api/v1/comments/documents/{document}` : Dépôt d'un commentaire racine (optionnellement lié à une version).
- `POST /api/v1/comments/{id}/reply` : Réponse à un commentaire existant.
- `PUT /api/v1/comments/{id}` : Modification d'un commentaire (par son auteur ou modérateur).
- `DELETE /api/v1/comments/{id}` : Suppression d'un commentaire.

---

## 14. Notifications (`/api/v1/notifications`)

- `GET /api/v1/notifications` : Liste paginée des notifications avec compteur de non-lues (`unread_count`).
- `GET /api/v1/notifications/unread` : Filtrage exclusif des non-lues.
- `POST /api/v1/notifications/{id}/read` : Marquer une notification comme lue.
- `POST /api/v1/notifications/read-all` : Tout marquer comme lu.
- `DELETE /api/v1/notifications/{id}` : Suppression d'une notification.

---

## 15. Codes d'Erreur & Réponses Types

| Code HTTP | Description | Format de réponse |
| :--- | :--- | :--- |
| **200 OK** | Requête traitée avec succès | `{"data": ...}` |
| **201 Created** | Ressource créée | `{"data": ..., "message": "..."}` |
| **401 Unauthorized** | Token absent, invalide ou révoqué | `{"message": "Unauthenticated."}` |
| **403 Forbidden** | Accès non autorisé (cross-tenant, permission manquante) | `{"message": "This action is unauthorized."}` |
| **404 Not Found** | Ressource introuvable | `{"message": "Resource not found."}` |
| **422 Unprocessable** | Erreur de validation de formulaire | `{"message": "...", "errors": {...}}` |
