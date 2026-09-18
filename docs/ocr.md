# Module 19 — OCR V1 (Reconnaissance et Indexation du Texte)

Ce document décrit l'architecture, la configuration, la sécurité et le fonctionnement du module OCR (**Optical Character Recognition**) dans GEDAPP.

---

## 1. Objectif du Module

Le module OCR permet d'extraire automatiquement le contenu textuel des documents scannés ou images déposés dans GEDAPP, afin de :
- Rendre ces documents interrogeables via la **recherche textuelle plein texte PostgreSQL**.
- Permettre aux utilisateurs de consulter et copier le texte extrait dans l'interface web.
- Conserver une traçabilité complète de l'extraction par document et par version.
- Garantir le respect strict de l'**isolation multi-tenant** et des **permissions documentaires**.

---

## 2. Architecture Globale

```
Upload Document / Version (Web ou API)
          │
          ▼
   DocumentService (Enregistrement métadonnées & stockage physique privé)
          │
          ▼
   ProcessDocumentOcr::dispatch($document, $version)
          │
          ▼ (Queue asynchrone : database / redis / sqs)
   Worker Queue : ProcessDocumentOcr Job
          │
     ┌────┴─────────────────────────────────────────────┐
     │ 1. Vérification tenant & intégrité du document  │
     │ 2. Téléchargement temporaire depuis Storage (R2) │
     │ 3. OcrService -> OcrEngine (Tesseract / Native)  │
     │ 4. Nettoyage du fichier temporaire local        │
     │ 5. Sauvegarde dans la table document_ocrs       │
     │ 6. AuditLog + Notification au propriétaire      │
     └──────────────────────────────────────────────────┘
          │
          ▼
     PostgreSQL
     Table document_ocrs (GIN Index sur tsvector)
          │
          ▲
          │
     SearchService (to_tsvector @@ plainto_tsquery)
```

---

## 3. Moteur OCR Choisi & Justification Technique

### Moteur Principal : Tesseract OCR CLI
- **Open Source & Autonome** : Aucun coût récurrent, aucune API key externe, aucune transmission de données sensibles à un tiers.
- **Support Multilingue** : Modèles spécialisés en français (`fra`) et anglais (`eng`) disponibles nativement.
- **Intégration Laravel Sécurisée** : Exécuté via la façade `Illuminate\Support\Facades\Process` avec limitation stricte de timeout (120s), contrôle des arguments et isolation mémoire.
- **Pré-extraction native PDF** : Pour les fichiers PDF contenant déjà des flux de texte sélectionnable, le système extrait le texte natif directement sans solliciter l'OCR lourd.

### Abstraction Driver & Pilote de Test
- L'interface `App\Services\Ocr\Contracts\OcrEngineInterface` découple la logique métier du binaire système.
- En environnement de test automatisé, le `TestingEngine` garantit des tests unitaires et feature ultra-rapides sans dépendance matérielle.
- En cas d'absence du binaire Tesseract sur le serveur, le système enregistre proprement un statut `failed` avec message explicatif (`"Le moteur OCR Tesseract n'est pas installé ou n'est pas disponible sur le système"`), évitant tout crash applicatif ou blocage HTTP.

---

## 4. Formats Pris en Charge

| Extension | Type MIME | Comportement OCR |
|---|---|---|
| `pdf` | `application/pdf` | Extraction native prioritaire si texte vectoriel ; OCR sinon |
| `png` | `image/png` | Traitement OCR d'image |
| `jpg` / `jpeg` | `image/jpeg` | Traitement OCR d'image |
| `webp` | `image/webp` | Traitement OCR d'image |
| `docx`, `xlsx`, `pptx`, `zip`, etc. | Bureautique / Archives | Statut `skipped` avec motif "Format non pris en charge en V1" |

---

## 5. Schéma de Base de Données

Table dédiée : `document_ocrs`

| Colonne | Type | Description |
|---|---|---|
| `id` | `bigint PK` | Identifiant unique |
| `organization_id` | `foreignId` | Isolation multi-tenant (cascade on delete) |
| `document_id` | `foreignId` | Document lié (cascade on delete) |
| `document_version_id` | `foreignId (nullable)` | Version précise liée |
| `status` | `varchar(30)` | `pending`, `processing`, `completed`, `failed`, `skipped` |
| `extracted_text` | `text` (`longText`) | Texte brut extrait |
| `error_message` | `text (nullable)` | Message d'erreur si échec |
| `word_count` | `integer` | Nombre de mots indexés |
| `confidence` | `float (nullable)` | Indice de confiance estimé |
| `language` | `varchar(30)` | Langues utilisées (ex: `fra+eng`) |
| `execution_time_ms` | `integer (nullable)` | Temps d'exécution en millisecondes |
| `processed_at` | `timestamp (nullable)` | Date et heure de fin de traitement |
| `timestamps` | `timestamp` | `created_at`, `updated_at` |

### Indexation PostgreSQL
- Index composé `(document_id, document_version_id)`
- Index `organization_id`
- Index `status`
- **Index GIN Full-Text Search** :
  ```sql
  CREATE INDEX document_ocrs_fts_idx ON document_ocrs USING GIN (
      to_tsvector('simple', coalesce(extracted_text, ''))
  );
  ```

---

## 6. Intégration Stockage & Cloudflare R2

- Le fichier source peut être situé indifféremment sur le disque `private` local ou sur Cloudflare R2 (`r2`).
- Le worker OCR télécharge temporairement le fichier via l'abstraction `Storage::disk($disk)->get($storagePath)`.
- Aucun identifiant ou token Cloudflare R2 n'est jamais exposé.
- Le fichier temporaire local créé dans `storage_path('app/temp/ocr')` est **systématiquement supprimé** dans un bloc `finally`.

---

## 7. Recherche Documentaire & PostgreSQL FTS

Dans `App\Services\SearchService` :
Lorsqu'un terme `q` est recherché :
1. Les critères habituels (nom, nom de fichier, description) sont testés.
2. Une clause `orWhereHas('ocrs', ...)` interroge le texte extrait :
   ```sql
   to_tsvector('simple', coalesce(extracted_text, '')) @@ plainto_tsquery('simple', :term)
   ```
3. L'isolation multi-tenant (`organization_id`) et le périmètre d'accès ACL (`applyAccessScope`) s'appliquent strictement : **un utilisateur ne peut jamais trouver un document par son texte OCR s'il n'a pas accès au document**.

---

## 8. API Sanctum (V1)

### Consulter le statut et texte OCR
```http
GET /api/v1/documents/{document}/ocr
```
*Paramètre optionnel* : `?version_id={id}`  
*Permission requise* : `documents.view`

Exemple de réponse :
```json
{
  "data": {
    "id": 12,
    "document_id": 45,
    "document_version_id": 89,
    "organization_id": 1,
    "status": "completed",
    "status_label": "Texte indexé",
    "has_text": true,
    "word_count": 420,
    "confidence": 95.0,
    "language": "fra+eng",
    "execution_time_ms": 1150,
    "extracted_text": "RÉPUBLIQUE FRANÇAISE\nMINISTÈRE DU TRAVAIL...",
    "error_message": null,
    "processed_at": "2026-09-16T13:40:00.000000Z",
    "created_at": "2026-09-16T13:39:50.000000Z",
    "updated_at": "2026-09-16T13:40:00.000000Z"
  }
}
```

### Relancer le traitement OCR
```http
POST /api/v1/documents/{document}/ocr/retry
```
*Permission requise* : `documents.update`  
*Code HTTP retourné* : `202 Accepted`

---

## 9. Interface Utilisateur (Inertia + React)

Sur la page de détails du document (`/documents/{id}`) :
- **Badge d'état dans l'en-tête** : Affiche l'état de l'OCR (Indexé, En cours, En attente, Échec, Non applicable).
- **Bouton d'action "Relancer OCR"** : Permet de relancer l'extraction manuellement pour les utilisateurs autorisés (`documents.update`).
- **Onglet dédié "Texte OCR"** :
  - Statistiques d'indexation (statut, nombre de mots, langues, date de traitement).
  - Zone de visualisation complète du texte extrait avec bouton **Copier le texte**.
  - Affichage de l'erreur détaillée si le traitement a échoué.

---

## 10. Installation des Dépendances Système (Production)

### Debian / Ubuntu
```bash
sudo apt-get update
sudo apt-get install -y tesseract-ocr tesseract-ocr-fra tesseract-ocr-eng poppler-utils
```

### RHEL / CentOS / Rocky Linux
```bash
sudo dnf install -y epel-release
sudo dnf install -y tesseract tesseract-langpack-fra tesseract-langpack-eng poppler-utils
```

### Docker (Dockerfile exemple)
```dockerfile
RUN apt-get update && apt-get install -y \
    tesseract-ocr \
    tesseract-ocr-fra \
    tesseract-ocr-eng \
    poppler-utils \
    && rm -rf /var/lib/apt/lists/*
```

---

## 11. Variables d'Environnement (Optionnelles)

Toutes les options disposent de valeurs par défaut saines :

```env
# Activer / désactiver globalement l'OCR
OCR_ENABLED=true

# Pilote : tesseract ou testing
OCR_DRIVER=tesseract

# Chemin vers le binaire tesseract (si non présent dans le PATH standard)
OCR_BINARY_PATH=/usr/bin/tesseract

# Langues analysées (séparées par une virgule)
OCR_LANGUAGES=fra,eng

# Timeout d'extraction par document en secondes
OCR_TIMEOUT=120

# Taille maximale de fichier analysable en Ko (ex: 25600 = 25 Mo)
OCR_MAX_FILE_SIZE_KB=25600
```

---

## 12. Lancement des Workers Queue

Pour traiter les jobs OCR en arrière-plan en production :
```bash
php artisan queue:work --queue=default --tries=3 --timeout=180
```
