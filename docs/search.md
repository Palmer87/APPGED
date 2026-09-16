# Module 9 — Moteur de Recherche Documentaire V1

Ce document détaille l'architecture, la sécurité, les filtres et les choix techniques du module de recherche documentaire.

---

## 1. Vue d'Ensemble & Architecture

Le service principal est `App\Services\SearchService`.
Il fournit la méthode :

```php
public function search(User $user, array $filters = []): LengthAwarePaginator
```

### Principes Clés
1. **Sécurité SQL native** : Aucun document n'est chargé en mémoire pour être filtré en PHP. L'ensemble des contraintes (Tenant, ACL, filtres) est appliqué directement dans la clause SQL `WHERE`.
2. **Anti-Fuite & Isolation Tenant** :
   - Pour un utilisateur normal : `documents.organization_id = user.organization_id` est imposé systématiquement.
   - Les filtres `folder_id`, `category_id`, `tag_id`, `metadata_key` sont validés par rapport au tenant de l'utilisateur. Si un ID cross-tenant est fourni, la requête est isolée (`1 = 0`), garantissant zéro résultat sans révéler l'existence de l'ID.
3. **Super-Admin** :
   - Accès global sans filtre d'organisation forcé par défaut.
   - Possibilité de filtrer par `organization_id` si souhaité.

---

## 2. Contrôle d'Accès (ACL) au Niveau SQL

La méthode `AccessControlService::applyAccessScope(Builder $query, User $user)` injecte les règles d'habilitation directement dans l'arbre d'expression Eloquent :

```sql
WHERE documents.organization_id = ?
  AND (
    -- 1. ACL directe utilisateur
    EXISTS (
      SELECT 1 FROM document_permissions
      WHERE document_id = documents.id
        AND user_id = ?
        AND permission = 'view'
    )
    OR
    -- 2. ACL via groupe
    EXISTS (
      SELECT 1 FROM document_permissions
      WHERE document_id = documents.id
        AND group_id IN (?)
        AND permission = 'view'
    )
    OR
    -- 3. Héritage du dossier parent (si non soft-deleted)
    (
      documents.folder_id IS NOT NULL AND EXISTS (
        SELECT 1 FROM folder_permissions
        JOIN folders ON folders.id = folder_permissions.folder_id
        WHERE folder_permissions.folder_id = documents.folder_id
          AND folders.deleted_at IS NULL
          AND folder_permissions.permission = 'view'
          AND (folder_permissions.user_id = ? OR folder_permissions.group_id IN (?))
      )
    )
  )
```

> [!NOTE]
> **View vs Download** : La recherche filtre sur la permission `view`. Un document trouvé n'autorise pas automatiquement son téléchargement ; l'action `download` reste soumise à la vérification séparée via `DocumentPolicy::download` et l'ACL `download`.

---

## 3. Stratégie de Recherche Textuelle & PostgreSQL FTS

### Recherche Insensible à la Casse et Partielle
Le paramètre `q` recherche simultanément dans :
- `documents.name`
- `documents.file_name`
- `documents.description`

### Implémentation Multi-Driver
- **PostgreSQL (Production)** :
  - Utilise un index GIN d'expression :
    ```sql
    CREATE INDEX documents_fts_idx ON documents USING GIN (
        to_tsvector('simple', coalesce(name, '') || ' ' || coalesce(file_name, '') || ' ' || coalesce(description, ''))
    );
    ```
  - Combiné avec des clauses `ILIKE %terme%` pour garantir la correspondance partielle (ex: `"contr"` trouve `"Contrat Fournisseur.pdf"`).
- **SQLite (Environnement de test)** :
  - Prédicats `LOWER(...) LIKE ?` garantissant un comportement strictement identique, sans nécessiter d'extension PostgreSQL externe dans la suite de tests en mémoire.

---

## 4. Filtres Supportés

| Filtre | Type | Comportement |
|---|---|---|
| `q` | `string` | Recherche textuelle partielle et insensible à la casse sur nom, nom de fichier et description |
| `folder_id` | `int` | Documents rattachés au dossier. Vérification tenant stricte |
| `category_id` | `int` | Documents rattachés à la catégorie. Vérification tenant stricte |
| `tag_id` | `int` | Documents possédant le tag |
| `tag_ids` | `array<int>` | Documents possédant **TOUS** les tags indiqués (logique cumulative `AND`) |
| `metadata_key` | `string` | Clé de la définition de métadonnée (vérification tenant) |
| `metadata_value` | `mixed` | Valeur recherchée dans la colonne typée correspondante (`string`, `text`, `integer`, `decimal`, `boolean`, `date`, `datetime`) |
| `created_from` | `date` | `documents.created_at >= created_from` |
| `created_to` | `date` | `documents.created_at <= created_to` |
| `updated_from` | `date` | `documents.updated_at >= updated_from` |
| `updated_to` | `date` | `documents.updated_at <= updated_to` |
| `extension` | `string` | Extension normalisée (ex: `pdf`, `png`) |
| `status` | `string` | `active` ou `archived` |
| `sort` | `string` | Whitelist : `created_at` (défaut), `updated_at`, `name`, `size` |
| `direction` | `string` | `asc` ou `desc` (défaut) |
| `per_page` | `int` | Entre 1 et 100 (défaut 15) |

---

## 5. Prévention des Problèmes N+1

Le service précharge systématiquement les relations via Eloquent `with()` :
- `categories`
- `tags`
- `metadataValues.definition`
- `folder`

Le nombre de requêtes SQL reste ainsi strictement constant, quel que soit le nombre de documents retournés par page.

---

## 6. Limites V1 & Stratégie d'Évolution

### Limites V1
- Pas d'OCR ni de recherche dans le contenu textuel brut extrait des fichiers binaires (réservé aux phases ultérieures).
- Pas de recherche phonétique ou de distance Levenshtein (fuzzy matching).
- Stockage et indexation gérés exclusivement par la base relationnelle PostgreSQL.

### Stratégie d'Évolution (Meilisearch / Elasticsearch)
Pour migrer ultérieurement vers Meilisearch ou Elasticsearch :
1. Conserver l'interface du `SearchService` identique.
2. Utiliser Laravel Scout avec un index pré-scopé par `organization_id` et filtrage par ACL post-requête ou via des filtres d'attributs Scout (`scopedIndex`).
3. Les consommateurs de `SearchService` n'auront aucune modification à apporter.
