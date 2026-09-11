Excellent. On démarre donc par le **MCD complet de la GED**. L'idée est de poser une base suffisamment solide pour le SaaS, sans surcharger la V1.

# 🗂️ MCD — GED SaaS

## 1. Vue globale

```text
ORGANISATION
│
├──< UTILISATEURS
│       │
│       ├──< GROUPES >──< GROUP_USER
│       │
│       └──< USER_ROLES
│
├──< DOSSIERS
│      │
│      ├──< SOUS-DOSSIERS
│      │
│      └──< DOCUMENTS
│              │
│              ├──< DOCUMENT_VERSIONS
│              ├──< DOCUMENT_TAGS >── TAGS
│              ├──< DOCUMENT_METADATA
│              ├──< DOCUMENT_SHARES
│              ├──< DOCUMENT_COMMENTS
│              ├──< DOCUMENT_FAVORITES
│              └──< WORKFLOW_INSTANCES
│
├──< CATEGORIES
│
└──< AUDIT_LOGS
```

---

# 2. Organisation

La table `organizations` représente chaque entreprise utilisant la GED.

### `organizations`

| Champ         | Type      | Description           |
| ------------- | --------- | --------------------- |
| id            | BIGINT    | Identifiant           |
| name          | VARCHAR   | Nom de l'organisation |
| slug          | VARCHAR   | Identifiant URL       |
| logo          | VARCHAR   | Logo                  |
| email         | VARCHAR   | Email                 |
| phone         | VARCHAR   | Téléphone             |
| address       | TEXT      | Adresse               |
| storage_limit | BIGINT    | Limite de stockage    |
| status        | ENUM      | active/inactive       |
| created_at    | TIMESTAMP | Création              |
| updated_at    | TIMESTAMP | Modification          |

Relation :

```text
Organization 1 ─────── N Users
Organization 1 ─────── N Groups
Organization 1 ─────── N Folders
Organization 1 ─────── N Documents
```

---

# 3. Utilisateurs

### `users`

| Champ           | Type      |
| --------------- | --------- |
| id              | BIGINT    |
| organization_id | FK        |
| first_name      | VARCHAR   |
| last_name       | VARCHAR   |
| email           | VARCHAR   |
| phone           | VARCHAR   |
| password        | VARCHAR   |
| avatar          | VARCHAR   |
| job_title       | VARCHAR   |
| status          | ENUM      |
| last_login_at   | TIMESTAMP |
| created_at      | TIMESTAMP |
| updated_at      | TIMESTAMP |

Un utilisateur appartient à une organisation.

```text
Organization
     │
     └──────< Users
```

---

# 4. Groupes

### `groups`

| Champ           | Type      |
| --------------- | --------- |
| id              | BIGINT    |
| organization_id | FK        |
| name            | VARCHAR   |
| description     | TEXT      |
| status          | BOOLEAN   |
| created_at      | TIMESTAMP |
| updated_at      | TIMESTAMP |

### `group_user`

Table pivot :

| Champ    |
| -------- |
| group_id |
| user_id  |

Relation :

```text
Users N ───── N Groups
```

Exemple :

```text
Jean
 ├── RH
 └── Projet A
```

---

# 5. Rôles

Nous utiliserons un système de rôles/permissions.

Exemple :

```text
Super Admin
Administrateur
Manager
Utilisateur
Lecteur
```

Les rôles seront associés aux utilisateurs.

---

# 6. Permissions

Exemples :

```text
documents.view
documents.create
documents.update
documents.delete
documents.download
documents.share

folders.view
folders.create
folders.update
folders.delete

users.view
users.create
users.update
users.delete

groups.view
groups.create
groups.update
groups.delete
```

Nous pourrons utiliser **Spatie Laravel Permission** pour cette partie.

---

# 7. Dossiers

C'est une table fondamentale.

### `folders`

| Champ           | Type        |
| --------------- | ----------- |
| id              | BIGINT      |
| organization_id | FK          |
| parent_id       | FK nullable |
| name            | VARCHAR     |
| description     | TEXT        |
| path            | VARCHAR     |
| created_by      | FK users    |
| is_archived     | BOOLEAN     |
| created_at      | TIMESTAMP   |
| updated_at      | TIMESTAMP   |

### Relation récursive

```text
Dossier
   │
   ├── Dossier
   │    ├── Dossier
   │    └── Dossier
   │
   └── Dossier
```

Exemple :

```text
RH
│
├── Contrats
│   ├── CDI
│   └── CDD
│
├── Congés
└── Dossiers employés
```

`parent_id` permet cette organisation.

---

# 8. Documents

### `documents`

| Champ              | Type               |
| ------------------ | ------------------ |
| id                 | BIGINT             |
| organization_id    | FK                 |
| folder_id          | FK                 |
| category_id        | FK nullable        |
| uploaded_by        | FK users           |
| name               | VARCHAR            |
| description        | TEXT               |
| file_name          | VARCHAR            |
| mime_type          | VARCHAR            |
| extension          | VARCHAR            |
| size               | BIGINT             |
| storage_disk       | VARCHAR            |
| storage_path       | TEXT               |
| status             | ENUM               |
| current_version_id | FK nullable        |
| deleted_at         | TIMESTAMP nullable |
| created_at         | TIMESTAMP          |
| updated_at         | TIMESTAMP          |

Statuts possibles :

```text
active
archived
deleted
pending_validation
validated
rejected
```

---

# 9. Versions

### `document_versions`

| Champ          | Type      |
| -------------- | --------- |
| id             | BIGINT    |
| document_id    | FK        |
| version_number | VARCHAR   |
| file_name      | VARCHAR   |
| storage_path   | TEXT      |
| size           | BIGINT    |
| uploaded_by    | FK        |
| comment        | TEXT      |
| created_at     | TIMESTAMP |

Exemple :

```text
Contrat.pdf

v1.0
v1.1
v2.0
v2.1
```

---

# 10. Catégories

### `categories`

```text
id
organization_id
name
description
parent_id
status
created_at
updated_at
```

Exemple :

```text
Finance
 ├── Factures
 ├── Reçus
 └── Rapports

RH
 ├── Contrats
 ├── Congés
 └── Personnel
```

---

# 11. Tags

### `tags`

```text
id
organization_id
name
color
created_at
updated_at
```

### `document_tag`

```text
document_id
tag_id
```

Un document peut avoir plusieurs tags.

---

# 12. Métadonnées

Pour rendre la GED flexible, nous allons prévoir des champs personnalisables.

### `metadata_definitions`

```text
id
organization_id
name
key
type
is_required
created_at
updated_at
```

Types :

```text
text
number
date
boolean
select
```

Puis :

### `document_metadata`

```text
id
document_id
metadata_definition_id
value
```

Exemple :

```text
Contrat.pdf

Numéro contrat : CTR-2026-001
Employé : Jean Kouassi
Date début : 01/01/2026
Type : CDI
```

---

# 13. Permissions des dossiers

Nous devons pouvoir dire :

> Le groupe RH peut accéder au dossier RH.

### `folder_permissions`

```text
id
folder_id
user_id nullable
group_id nullable
permission
created_at
```

Exemple :

```text
Dossier RH

Groupe RH
    ↓
view
create
update
download
```

---

# 14. Permissions des documents

### `document_permissions`

```text
id
document_id
user_id nullable
group_id nullable
permission
created_at
```

Cela permet de protéger un document particulier.

---

# 15. Partage

### `document_shares`

```text
id
document_id
shared_by
user_id nullable
group_id nullable
token nullable
password nullable
expires_at nullable
can_download
can_view
created_at
```

On pourra ainsi créer :

```text
Lien de partage

Expiration : 20/09/2026
Téléchargement : ❌
Mot de passe : ✅
```

---

# 16. Favoris

### `favorites`

```text
id
user_id
document_id nullable
folder_id nullable
created_at
```

Un utilisateur peut ajouter :

```text
⭐ Contrat.pdf
⭐ Dossier RH
```

---

# 17. Commentaires

### `document_comments`

```text
id
document_id
user_id
parent_id nullable
content
created_at
updated_at
```

On pourra même avoir des réponses :

```text
Marie :
"Merci de vérifier la page 4."

Jean :
"Correction effectuée."
```

---

# 18. Workflow

Pour les documents nécessitant une validation.

### `workflows`

```text
id
organization_id
name
description
status
created_at
updated_at
```

### `workflow_steps`

```text
id
workflow_id
name
order
approver_type
group_id nullable
user_id nullable
```

### `workflow_instances`

```text
id
workflow_id
document_id
started_by
status
started_at
completed_at
```

### `workflow_actions`

```text
id
workflow_instance_id
workflow_step_id
user_id
action
comment
created_at
```

Actions :

```text
pending
approved
rejected
returned
```

---

# 19. Notifications

### `notifications`

```text
id
user_id
type
title
message
data
read_at
created_at
```

Exemple :

> 🔔 Votre document `Contrat.pdf` a été validé.

---

# 20. Audit / historique

### `audit_logs`

```text
id
organization_id
user_id
action
entity_type
entity_id
old_values
new_values
ip_address
user_agent
created_at
```

Exemple :

```text
10/09/2026 14:30

Jean Kouassi
→ téléchargement
→ Contrat.pdf
→ IP : xxx.xxx.xxx.xxx
```

---

# 21. Corbeille

Grâce à `deleted_at`, nous pouvons utiliser le système **Soft Delete** de Laravel.

```text
Document
   ↓
deleted_at
   ↓
🗑️ Corbeille
```

Puis :

```text
Restaurer
```

ou

```text
Supprimer définitivement
```

---

# 22. Architecture finale

Notre modèle fonctionnel devient :

```text
                    ORGANIZATION
                         │
       ┌─────────────────┼─────────────────┐
       │                 │                 │
       ▼                 ▼                 ▼
     USERS             GROUPS           FOLDERS
       │                 │                 │
       │                 │                 ├── parent_id
       │                 │                 │
       └──── GROUP ──────┘                 ▼
                                    DOCUMENTS
                                         │
             ┌───────────────────────────┼──────────────────────┐
             │             │             │          │           │
             ▼             ▼             ▼          ▼           ▼
         VERSIONS        TAGS       METADATA    SHARES      COMMENTS
                                         │
                                         ▼
                                     WORKFLOW
                                         │
                                         ▼
                                   NOTIFICATIONS
                                         │
                                         ▼
                                    AUDIT LOGS
```

## 🔥 Point important pour notre SaaS

Je recommande de **ne pas faire uniquement `users → documents`**.

Notre architecture sera plutôt :

```text
Organisation
      ↓
Utilisateur
      ↓
Groupe
      ↓
Rôle
      ↓
Permission
      ↓
Dossier
      ↓
Document
      ↓
Version
```

Cela nous permettra plus tard d'avoir des règles très puissantes comme :

> **Le groupe "Comptabilité" peut consulter et télécharger tous les documents du dossier "Finance", mais ne peut pas supprimer les documents.**

ou :

> **Le groupe "Direction" peut consulter tous les documents, y compris les documents confidentiels.**

---

### Étape suivante

Maintenant que le **MCD fonctionnel** est posé, je te propose de passer au **MLD PostgreSQL complet**, avec **toutes les tables, colonnes, types de données, PK, FK, indexes, contraintes UNIQUE et règles `ON DELETE`**.

Ensuite, nous pourrons transformer directement ce MLD en **migrations Laravel 12**.
