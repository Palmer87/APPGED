# Évolution de la Structure Documentaire GEDAPP V1

## 1. Vue d'ensemble

Cette évolution aligne GEDAPP sur la hiérarchie métier documentaire :

```
ORGANISATION (Multi-tenant)
│
├── DIRECTION (`folders.folder_type = 'department'`)
│   │
│   ├── TYPE DOCUMENTAIRE (`folders.folder_type = 'document_type'`)
│   │   │   [Métadonnées associées via `folder_metadata_definition`]
│   │   │
│   │   ├── DOCUMENT (`documents.document_type_id`, `documents.folder_id`)
│   │   ├── DOCUMENT
│   │   └── DOCUMENT
│   │
│   └── TYPE DOCUMENTAIRE
│
└── DIRECTION
```

---

## 2. Modèle de données & Migrations

Les migrations sont **purement additives** et garantissent l'intégrité des données existantes sans aucune perte :

1. **`2026_09_18_170001_add_folder_type_to_folders_table.php`** :
   - Ajout de `folder_type` (`string(30)`, indexé, valeur par défaut `'standard'`).
   - Ajout de `is_active` (`boolean`, valeur par défaut `true`).
2. **`2026_09_18_170002_add_document_type_id_to_documents_table.php`** :
   - Ajout de `document_type_id` (clé étrangère nullable référençant `folders.id`, indexée).
3. **`2026_09_18_170003_create_folder_metadata_definition_table.php`** :
   - Table pivot `folder_metadata_definition` liant un type documentaire (`folder_id`) à une définition de métadonnée (`metadata_definition_id`).
   - Colonnes : `is_required` (`boolean`, nullable), `order` (`integer`, défaut 0), contrainte d'unicité `unique(['folder_id', 'metadata_definition_id'])`.

---

## 3. Backend & Services

### 3.1. Enums & Modèles
- **`App\Enums\FolderType`** : `Standard = 'standard'`, `Department = 'department'`, `DocumentType = 'document_type'`.
- **`App\Models\Folder`** :
  - Relations : `metadataDefinitions()`, `typedDocuments()`, `department()` (pour un type documentaire), `documentTypes()` (pour une direction).
  - Méthodes utilitaires : `isDepartment()`, `isDocumentType()`, `getDocumentType()`, `getDepartment()`.
  - Scopes Eloquent : `departments()`, `documentTypes()`, `active()`.
- **`App\Models\Document`** :
  - Relation : `documentType()` (`belongsTo(Folder::class, 'document_type_id')`).
  - Méthode utilitaire : `getDepartment()`.
- **`App\Models\MetadataDefinition`** :
  - Relations : `folders()` et `documentTypes()` via la table pivot `folder_metadata_definition`.

### 3.2. Services
- **`FolderService`** :
  - `createDepartment(array $data, User $user): Folder` : Crée une direction au niveau racine (`parent_id = null`).
  - `createDocumentType(array $data, User $user): Folder` : Crée un type documentaire rattaché obligatoirement à une direction existante.
  - `syncMetadataDefinitions(Folder $folder, array $definitions, User $user): void` : Configure les métadonnées requises et leur ordre d'affichage.
  - `hasDocuments(Folder $folder): bool` : Vérifie récursivement la présence de documents actifs pour sécuriser les suppressions.
  - `delete(Folder $folder, bool $force = false): void` : Bloque la suppression si des documents sont présents (sauf si `force = true`).
- **`DocumentService`** :
  - Lors de l'upload (`upload()`), dérive et assigne automatiquement `document_type_id` à partir du dossier sélectionné.
  - Lors du déplacement (`move()`), met à jour `document_type_id` tout en conservant l'historique des valeurs de métadonnées.
- **`DocumentMetadataService`** :
  - `getRequiredDefinitionsForDocument(Document $document)` : Filtre les métadonnées obligatoires selon le type documentaire rattaché au document.
- **`SearchService`** :
  - Filtres par direction (`department_id`) et par type documentaire (`document_type_id`).
  - Recherche textuelle étendue sur les valeurs de métadonnées (`metadataValues`).

---

## 4. Sécurité & Droits d'accès

- **`App\Policies\FolderPolicy`** :
  - `createDepartment` : Réservé aux administrateurs ou aux utilisateurs dotés du droit `folders.create`.
  - `createDocumentType` : Réservé aux administrateurs ou aux gestionnaires documentaires.
  - `manageMetadata` : Permet la configuration des métadonnées du type documentaire.
- **Multi-Tenant Strict** :
  - Toutes les requêtes filtrent impérativement par `organization_id`.
  - Impossible de rattacher un type documentaire à une direction d'une autre organisation.
  - Impossible pour un utilisateur d'une organisation d'accéder aux directions, types documentaires ou documents d'une autre organisation.

---

## 5. Routes & Endpoints API

### Routes Web
| Méthode | Route | Description |
|---|---|---|
| `GET` | `/departments` | Liste des directions avec compteurs de types documentaires et documents |
| `POST` | `/departments` | Création d'une direction |
| `PUT` | `/departments/{department}` | Modification d'une direction |
| `DELETE` | `/departments/{department}` | Suppression sécurisée d'une direction |
| `GET` | `/document-types` | Liste des types documentaires groupés ou filtrés par direction |
| `POST` | `/document-types` | Création d'un type documentaire |
| `PUT` | `/document-types/{documentType}` | Modification d'un type documentaire |
| `DELETE` | `/document-types/{documentType}` | Suppression sécurisée d'un type documentaire |
| `GET` | `/document-types/{documentType}/metadata` | Interface de configuration des métadonnées associées |
| `POST` | `/document-types/{documentType}/metadata` | Enregistrement de la configuration des métadonnées |

### Routes API (V1 - JSON)
| Méthode | Route | Description |
|---|---|---|
| `GET` | `/api/v1/document-types` | Liste des types documentaires (avec filtre optionnel `?department_id=`) |
| `GET` | `/api/v1/document-types/{id}` | Détail d'un type documentaire avec métadonnées associées |
| `POST` | `/api/v1/document-types/{id}/metadata` | Configuration des métadonnées via l'API |

---

## 6. Interface Utilisateur (Inertia + React + TailwindCSS)

1. **Explorateur de dossiers (`Folders/Index.jsx`)** :
   - Icônes et badges distincts :
     - Direction : Icône `Building2`, badge bleu ciel `Direction`.
     - Type documentaire : Icône `FileStack`, badge violet `Type doc.`.
     - Dossier standard : Icône `FolderIcon`.
   - **Bandeau Direction** : Titre, description et bouton rapide pour ajouter un nouveau type documentaire.
   - **Bandeau Type documentaire** : Titre, direction parente, compteurs de documents et de métadonnées, lien direct vers « Gérer les métadonnées ».
   - **Modale d'import de document** : Génère dynamiquement les champs de saisie pour chaque métadonnée configurée sur le type documentaire (texte, date, nombre, booléen) avec indicateur obligatoire `*`.
2. **Gestion des Directions (`Departments/Index.jsx`)** :
   - Table et cartes avec statut actif/inactif, compteurs et actions contextuelles.
3. **Gestion des Types Documentaires (`DocumentTypes/Index.jsx`)** :
   - Filtrage par direction, lien rapide vers la configuration des métadonnées.
4. **Configuration des Métadonnées (`DocumentTypes/Metadata.jsx`)** :
   - Assignation dynamique des définitions existantes avec toggle "Obligatoire" et définition de l'ordre d'affichage.

---

## 7. Données de démonstration & Seeder

Le seeder idempotent `Database\Seeders\DocumentStructureSeeder` initialise la structure métier :
- **Direction Comptabilité & Finance** :
  - *Facture client* : `numero_facture` (obligatoire), `date_facture` (obligatoire), `client` (obligatoire), `montant_ttc` (obligatoire).
  - *Facture fournisseur* : `numero_facture` (obligatoire), `date_facture` (obligatoire), `fournisseur` (obligatoire), `montant_ttc` (obligatoire), `date_echeance` (facultatif).
  - *Décharge client* : `client` (obligatoire), `date_signature` (obligatoire).
- **Direction Ressources Humaines** :
  - *Contrat de travail* : `numero_contrat` (obligatoire), `nom_salarie` (obligatoire), `date_signature` (obligatoire).
  - *Bulletin de paie* : `nom_salarie` (obligatoire), `periode_paie` (obligatoire), `montant_net` (obligatoire).
- **Direction Commerciale** :
  - *Proposition commerciale* : `client` (obligatoire), `montant` (obligatoire), `date_signature` (facultatif).
