# MODULE 12 — Corbeille & Archivage V1

Ce document détaille l'architecture, les états, les permissions et le comportement du cycle de vie documentaire de la GED.

## 1. États du document

Le cycle de vie documentaire s'appuie sur deux attributs complémentaires :
* `status` (`'active'` | `'archived'`)
* `deleted_at` (`NULL` | `timestamp` via `SoftDeletes`)

| État | `status` | `deleted_at` | Description |
|---|---|---|---|
| **Actif** | `active` | `NULL` | Document vivant, modifiable, consultable, prévisualisable. |
| **Archivé** | `archived` | `NULL` | Document figé, non modifiable, consultable en lecture seule. |
| **Corbeille (Trash)** | `active` ou `archived` | Non `NULL` | Document supprimé logiquement, masqué des requêtes standard. |
| **Suppression définitive** | N/A | N/A | Ligne en BDD effacée en cascade, tous les fichiers physiques purgés du stockage privé. |

---

## 2. Transitions autorisées et interdites

```text
                    DOCUMENT
                       │
              ┌────────┴────────┐
              ↓                 ↓
            ACTIF            ARCHIVÉ
              │                 │
              ↓                 ↓
          CORBEILLE         DÉSARCHIVAGE
              │
        ┌─────┴─────┐
        ↓           ↓
    RESTAURATION  SUPPRESSION DÉFINITIVE
```

* **Archivage** : Actif -> Archivé (`status = 'archived'`).
* **Désarchivage** : Archivé -> Actif (`status = 'active'`).
* **Mise en corbeille** : Actif ou Archivé -> Corbeille (`deleted_at = now()`).
* **Restauration** : Corbeille -> État d'origine (conserve son statut `active` ou `archived` initial).
* **Suppression définitive** : **Uniquement depuis la Corbeille**.
  * Tenter de supprimer définitivement un document actif ou archivé sans passage préalable par la corbeille renvoie une erreur HTTP 422.

---

## 3. Gestion du stockage physique et des versions

* **Mise en corbeille (Soft Delete)** :
  * Le fichier principal et toutes les versions sur le stockage privé sont **strictement conservés**.
  * Aucune suppression de fichier physique n'a lieu.
* **Suppression définitive (Force Delete)** :
  * Tous les chemins physiques (`storage_path` du document et de chacune de ses versions) sont collectés.
  * Les fichiers physiques correspondants sont purgés du disque de stockage privé.
  * Les enregistrements de base de données associés (`document_versions`, `document_permissions`, `document_shares`, `document_metadata`, `document_category`, `document_tag`) sont supprimés en cascade.

---

## 4. Permissions requises

| Action | Route | Méthode HTTP | Permission / Règle |
|---|---|---|---|
| **Consulter la corbeille** | `/documents/trash` | `GET` | Authentifié (isolé au tenant courant) |
| **Consulter les archives** | `/documents/archived` | `GET` | Authentifié + respect de la portée ACL/Permissions |
| **Archiver** | `/documents/{document}/archive` | `POST` | `documents.update` |
| **Désarchiver** | `/documents/{document}/unarchive` | `POST` | `documents.update` |
| **Mettre en corbeille** | `/documents/{document}` | `DELETE` | `documents.delete` |
| **Restaurer** | `/documents/{document}/restore` | `POST` | `documents.restore` (ou `documents.delete`) |
| **Suppression définitive** | `/documents/{document}/force` | `DELETE` | `documents.delete` |
| **Vider la corbeille** | `/documents/trash/empty` | `POST` | `documents.delete` |

---

## 5. Isolation Multi-Tenant et Anti-Fuite

* **Strict cloisonnement organisationnel** : Les opérations sur un document vérifient obligatoirement `$user->organization_id === $document->organization_id`.
* **Pas de fuite d'existence** : L'accès à un document d'une autre organisation renvoie une erreur `403 Forbidden` ou `404 Not Found`.
* **Corbeille isolée** : `/documents/trash` et `/documents/trash/empty` ne ciblent strictement que les documents appartenant au `organization_id` de l'utilisateur connecté.
