# Module 10 — Prévisualisation des Documents V1

Ce document décrit le fonctionnement technique, l'architecture, la sécurité et les règles de gestion de la prévisualisation documentaire dans la GED SaaS.

---

## 1. Objectif

Permettre la prévisualisation directe et sécurisée des documents stockés sur le disque privé au sein du navigateur, sans téléchargement forcé et sans exposition d'URL publiques de stockage ou de chemins physiques.

La prévisualisation est **totalement indépendante du téléchargement** :
- Un utilisateur disposant de la permission `documents.view` peut prévisualiser le document.
- La permission `documents.download` n'est **PAS** requise pour prévisualiser.

---

## 2. Formats Supportés en V1

La V1 prend en charge nativement les formats lisibles directement par les navigateurs web :

| Extension | Type MIME | Comportement |
|---|---|---|
| `pdf` | `application/pdf` | Streamé inline pour affichage dans le visualiseur PDF du navigateur |
| `jpg` / `jpeg` | `image/jpeg` | Streamé inline pour affichage de l'image |
| `png` | `image/png` | Streamé inline pour affichage de l'image |
| `webp` | `image/webp` | Streamé inline pour affichage de l'image |

### Formats Non Supportés en V1
Les formats bureautiques et d'archives (`DOC`, `DOCX`, `XLS`, `XLSX`, `PPT`, `PPTX`, `TXT`, `ZIP`, etc.) ne sont pas prévisualisables en V1.
Toute tentative de prévisualisation de ces formats renvoie un code HTTP **415 Unsupported Media Type**.

> [!NOTE]
> La conversion automatique des documents Office vers PDF (via LibreOffice ou moteur dédié) sera implémentée dans un module ultérieur.

---

## 3. Routes & Contrôleur

Les routes sont définies dans `routes/web.php` et protégées par le middleware d'authentification `auth` :

```php
GET /documents/{document}/preview
Name: documents.preview

GET /documents/{document}/versions/{version}/preview
Name: documents.versions.preview
```

Le contrôleur `App\Http\Controllers\DocumentPreviewController` reste ultra-léger et délègue l'intégralité du traitement et des contrôles à `App\Services\PreviewService`.

---

## 4. Contrôle d'Accès & Sécurité Multi-Tenant

Le service applique rigoureusement les contrôles de sécurité :

1. **Isolation Multi-Tenant** :
   - `user.organization_id === document.organization_id` est obligatoire (rejet HTTP 403 immédiat).
   - Les utilisateurs d'une organisation ne peuvent pas prévisualiser les documents d'une autre organisation en altérant l'ID dans l'URL.
   - Le rôle `super-admin` dispose d'un accès global conformément à `Gate::before`.
2. **Autorisations & ACLs** :
   - La prévisualisation passe par `Gate::authorize('view', $document)`.
   - `DocumentPolicy::view` vérifie l'attribution du rôle Spatie `documents.view` et, le cas échéant, les ACLs granulaires de document ou héritées du dossier parent (`AccessControlService::canAccessDocument`).
   - Un utilisateur n'ayant que `documents.download` sans `documents.view` se voit refuser la prévisualisation (HTTP 403).
3. **Documents Supprimés (Soft Delete)** :
   - Les documents soft-deleted ne peuvent pas être prévisualisés (rejet HTTP 404).

---

## 5. Gestion des Versions

Le service permet de prévisualiser aussi bien la version courante que n'importe quelle version archivée d'un document :
- La version demandée doit **obligatoirement** appartenir au document cible (`version.document_id === document.id`).
- Une tentative de forcer un ID de version appartenant à un autre document ou à une autre organisation est immédiatement rejetée avec une erreur HTTP 403.
- Si la version est valide, le flux du fichier correspondant à cette version spécifique est streamé.

---

## 6. Stockage Privé & Streaming Sécurisé

- **Disque Privé** : Les fichiers résident sur le disque configuré (`private`), inaccessible publiquement.
- **Aucune Exposition d'URL** : Ni `Storage::url()`, ni URL signée S3/R2 publique n'est exposée au client.
- **Streaming HTTP** : Le backend Laravel stream le flux binaire via `response()->stream()` et `fpassthru()`, garantissant une empreinte mémoire constante quel que soit le volume du fichier.
- **Fichier Absent du Stockage** : Si la métadonnée existe en base mais que le fichier physique a disparu du stockage, le service intercepte l'erreur et retourne un code HTTP **404 Not Found** propre, sans exposer les chemins internes du serveur.

### En-têtes HTTP de Sécurité
Chaque réponse de prévisualisation inclut :
- `Content-Type: <mime_type_du_fichier>`
- `Content-Disposition: inline; filename="nom_du_fichier"` (interdiction de l'en-tête `attachment` pour permettre la lecture dans le navigateur).
- `X-Content-Type-Options: nosniff` (empêche le navigateur d'exécuter du contenu en ignorant le type MIME déclaré).
