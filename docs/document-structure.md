# Évolution de la Structure Documentaire GEDAPP V1 & V2

## 1. Vue d'ensemble

GEDAPP est structuré autour d'une hiérarchie métier documentaire :

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

### Évolution V2 : Refonte Expérience Utilisateur (UX Métier)
Dans la V2, **l'arborescence des dossiers n'est plus le point d'entrée principal des utilisateurs**.
L'application adopte une approche **100% orientée métier et formulaires** :
1. **L'utilisateur ne navigue plus manuellement** dans des dossiers et sous-dossiers pour stocker ou chercher un document.
2. Le flux principal repose sur le triptyque : **Direction → Type documentaire → Formulaire dynamique de métadonnées**.
3. Les dossiers internes (`folders`) restent le socle technique sous le capot pour le stockage physique, les ACL et l'organisation, mais leur gestion directe est reléguée dans la section **Administration** (`/folders`).

---

## 2. Parcours Utilisateurs Métier (V2)

### 2.1. Importer un Document (`/documents/create`)
- **Étape 1 : Sélection de la Direction** (ex. *Direction Ressources Humaines*).
- **Étape 2 : Sélection du Type Documentaire** (ex. *Contrat de travail*).
- **Étape 3 : Renseignement du Formulaire de Métadonnées Dynamique** : Les champs configurés pour ce type documentaire s'affichent automatiquement avec validation instantanée (ex. `numero_contrat`, `nom_salarie`, `date_signature`).
- **Étape 4 : Sélection / Glisser-déposer du Fichier** avec aperçu et contrôle de taille/format.
- **Étape 5 : Enregistrement et Dérivation Automatique** :
  - Le backend assigne automatiquement le document au dossier technique correspondant au type documentaire (`folder_id = document_type_id`).
  - Les valeurs de métadonnées sont persistées dans `document_metadata_values`.
  - Le fichier initial constitue la version 1 (`DocumentVersion::v1`).
  - La file d'attente d'OCR et d'indexation plein texte est déclenchée.

### 2.2. Rechercher un Document (`/search`)
- Recherche par mot-clé global (titre, nom de fichier, texte OCR).
- Filtrage hiérarchique : **Direction** puis **Type Documentaire**.
- Dès la sélection d'un type documentaire, les critères de **recherche par métadonnées métiers** s'affichent dynamiquement (ex. recherche par numéro de contrat, nom du salarié, date, etc.).
- Affichage des résultats en vue grille/cartes métier ou tableau avec badges de Direction et de Type documentaire, affichage des métadonnées clés et actions directes (Consulter, Télécharger, Modifier).

### 2.3. Modifier un Document & Remplacement de Fichier (`/documents/{id}/edit`)
- **Modification des métadonnées** : Les champs du formulaire dynamique sont pré-remplis avec les valeurs actuelles.
- **Remplacement de fichier non destructif** :
  - L'utilisateur peut facultativement importer un nouveau fichier pour remplacer le document courant.
  - **Règle absolue d'intégrité** : Le fichier existant n'est **JAMAIS** écrasé physiquement sur le disque.
  - Un nouveau numéro de version est créé (V2, V3...) via `DocumentService::uploadNewVersion()`.
  - L'historique complet, les versions antérieures, les journaux d'audit et les textes OCR de chaque version sont préservés.

### 2.4. Consultation Enrichie d'un Document (`/documents/{id}`)
- **Présentation Métier Immédiate** :
  - Aperçu direct (visionneuse PDF / Image) dans le panneau principal.
  - Carte latérale/synthétique **Informations Métier** affichant la Direction, le Type documentaire et toutes les métadonnées renseignées.
  - Raccourcis d'actions immédiates : **Modifier** (ouvre le formulaire métier), **Télécharger**, **Nouvelle version**, **Partager**, et bascule vers l'Historique d'audit ou les Commentaires.

---

## 3. Modèle de données & Migrations

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

## 4. Backend & Services

### 4.1. Enums & Modèles
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

### 4.2. Services
- **`DocumentWebController`** :
   - `create()` : Prépare la hiérarchie active des directions, types documentaires et métadonnées associées pour le composant d'importation Inertia.
   - `store()` : Valide le fichier, dérive `folder_id` depuis `document_type_id`, pré-valide les métadonnées obligatoires et les types de données avec retours d'erreurs granulaires sous les champs (`metadata.{key}`), persiste les métadonnées via `DocumentMetadataService`, configure les droits ACL et retourne vers le document créé.
   - `edit()` : Récupère les métadonnées actuelles et les définitions de métadonnées du type documentaire pour affichage dans le formulaire d'édition.
   - `update()` : Met à jour les informations de base, valide et synchronise les métadonnées, et si un fichier de remplacement est transmis, déclenche `DocumentService::uploadNewVersion()` sans perte de l'historique physique.
- **`SearchService` & `SearchWebController`** :
   - `SearchRequest` : Valide la structure `metadata` (tableau de valeurs).
   - `SearchService::search()` : Prise en charge des filtres multi-critères sur les métadonnées avec correspondance `like` pour les champs texte tout en respectant un budget strict de requêtes SQL.
   - `SearchWebController::index()` : Injecte les directions et types avec leurs métadonnées pour la construction dynamique des filtres de recherche côté client.
- **`DocumentMetadataService`** :
   - `getRequiredDefinitionsForDocument(Document $document)` : Filtre les métadonnées obligatoires selon le type documentaire rattaché au document.
   - `validateAndFormat()` & `setValues()` : Contrôle la validité des formats (date, entier, décimal, booléen, texte) et persiste les valeurs.
- **`TesseractEngine` & Traitement OCR** :
   - Décompression native des flux PDF `FlateDecode` pour extraire instantanément le texte vectoriel des documents PDF.
   - Gestion robuste des erreurs Leptonica/Windows pour éviter les échecs intempestifs sur les PDF sans texte scanné.

---

## 5. Sécurité & Droits d'accès

- **`App\Policies\DocumentPolicy` & `FolderPolicy`** :
   - Vérification des permissions granulaires (`documents.create`, `documents.update`, `folders.create`, etc.).
   - Protection IDOR : contrôle de l'appartenance à la même organisation.
- **Multi-Tenant Strict** :
   - Toutes les requêtes filtrent impérativement par `organization_id`.
   - Impossible de rattacher un type documentaire à une direction d'une autre organisation.
   - Impossible d'accéder ou de modifier un document d'une autre organisation.

---

## 6. Routes Web & Endpoints

### Navigation Métier Principale (V2)
| Méthode | Route | Nom de route | Description |
|---|---|---|---|
| `GET` | `/dashboard` | `dashboard` | Tableau de bord synthétique |
| `GET` | `/documents/create` | `documents.create` | Formulaire d'importation métier (Direction → Type → Métadonnées) |
| `GET` | `/search` | `search` | Moteur de recherche avancé (Filtres métadonnées dynamiques + OCR) |
| `GET` | `/documents` | `documents.index` | Tous les documents avec badges et filtres |
| `GET` | `/documents/{document}` | `documents.show` | Consultation complète d'un document, versions et métadonnées |
| `GET` | `/documents/{document}/edit` | `documents.edit` | Formulaire de modification métier & remplacement de fichier |
| `PUT` | `/documents/{document}` | `documents.update` | Enregistrement des modifications / nouvelle version |

### Administration & Structure Documentaire
| Méthode | Route | Nom de route | Description |
|---|---|---|---|
| `GET` | `/departments` | `departments.index` | Gestion des directions |
| `GET` | `/document-types` | `document-types.index` | Gestion des types documentaires |
| `GET` | `/document-types/{documentType}/metadata` | `document-types.metadata` | Configuration des métadonnées du type |
| `GET` | `/folders` | `folders.index` | Explorateur de dossiers internes (niveau technique/admin) |

---

## 7. Composants Frontend (Inertia + React + TailwindCSS)

1. **`AuthenticatedLayout.jsx`** :
   - Navigation principale épurée : "Importer un document" mis en valeur, "Recherche", "Tous les documents", "Favoris", "Récents", "Partagés", "Workflows", "Notifications", "Corbeille".
   - "Dossiers internes" déplacé sous la section **ADMINISTRATION**.
2. **`Components/MetadataForm.jsx`** :
   - Composant réutilisable pour la saisie dynamique de métadonnées (`string`, `text`, `integer`, `decimal`, `date`, `datetime`, `boolean`).
   - Support d'un mode édition standard et d'un mode filtre (`isSearchMode`).
   - Gestion des erreurs champ par champ avec messages sous les champs.
3. **`Pages/Documents/Create.jsx`** :
   - Assistant d'importation sans navigation par dossiers : Direction → Type → Métadonnées dynamiques → Fichier (drag & drop avec aperçu) → Résumé en temps réel.
4. **`Pages/Documents/Edit.jsx`** :
   - Modification complète : métadonnées dynamiques pré-remplies, informations sur la version actuelle, zone optionnelle de remplacement de fichier (création de version N+1).
5. **`Pages/Search/Index.jsx`** :
   - Sélection hiérarchique Direction / Type documentaire avec affichage dynamique des filtres de métadonnées correspondants, recherche globale et résultats avec badges.
6. **`Pages/Documents/Show.jsx` & `Index.jsx`** :
   - Affichage valorisé des badges de Direction et de Type documentaire, bloc Informations Métier intégré à la consultation et raccourcis d'édition et d'import.

---

## 8. Données de démonstration & Seeder

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
