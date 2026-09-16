# Documentation Technique — Module 17 : Frontend Inertia + React V1

## 1. Vue d'ensemble et Architecture

Le **Module 17** dote la solution SaaS GED d'une interface utilisateur moderne, réactive, élégante et entièrement intégrée à l'écosystème Laravel via **Inertia.js v2** et **React 19**, stylisée avec **Tailwind CSS v4** et la librairie d'icônes **Lucide React**.

L'architecture Inertia élimine la complexité d'une SPA découplée (pas besoin d'état JWT côté client, d'API GraphQL superflue ou de duplication de routing) tout en conservant la fluidité d'une Single Page Application sans rechargement de page.

### Schéma d'Architecture Frontend / Backend

```text
                               Navigateur Client
                                       │
                     ┌─────────────────┴─────────────────┐
                     │                                   │
              Page Request (GET /documents)       Inertia Visit (JSON)
                     │                                   │
                     ▼                                   ▼
             Laravel Router                      Laravel Router
                     │                                   │
                     ▼                                   ▼
            Web Controller                      Web Controller
            (DocumentWebController)             (DocumentWebController)
                     │                                   │
                     ▼                                   ▼
            AccessControlService / Policies     AccessControlService / Policies
                     │                                   │
                     ▼                                   ▼
         Inertia::render('Documents/Index', $props)
                     │
                     ▼
          HandleInertiaRequests (Shared Props: auth, roles, permissions, flash)
                     │
                     ▼
             HTML Root (resources/views/app.blade.php)
                     │
                     ▼
        React Tree (resources/js/Pages/Documents/Index.jsx)
                     │
         ┌───────────┴───────────┐
         ▼                       ▼
  AuthenticatedLayout     Design System Components
  (Sidebar, Header,      (Button, Modal, Card, Table,
   Mobile Drawer, Toasts) Breadcrumb, Tabs, FileIcon)
```

---

## 2. Pile Technologique et Dépendances

| Composant | Technologie | Version / Rôle |
|---|---|---|
| **Backend Adapter** | `inertiajs/inertia-laravel` | ^3.3 |
| **Frontend Adapter** | `@inertiajs/react` | ^2.0 |
| **UI Framework** | `react` & `react-dom` | ^19.0 |
| **Styling** | `@tailwindcss/vite` | ^4.0 |
| **Icons** | `lucide-react` | Icônes SVG modernes et accessibles |
| **Bundler** | `vite` & `@vitejs/plugin-react` | Rechargement à chaud (HMR) & compilation optimisée |

---

## 3. Configuration & Middleware Inertia

### Vue Racine (`resources/views/app.blade.php`)
Définit la structure HTML5 minimale, inclut les polices Instrument Sans, et charge les bundles Vite avec les directives `@viteReactRefresh` et `@vite(['resources/css/app.css', 'resources/js/app.jsx'])`.

### Middleware (`app/Http/Middleware/HandleInertiaRequests.php`)
Partage systématiquement à tous les composants React les données globales :
- `auth.user` : Informations de l'utilisateur connecté (`id`, `name`, `email`, `avatar_url`, `organization_id`).
- `auth.organization` : Informations de l'organisation active (`id`, `name`, `slug`).
- `roles` & `permissions` : Rôles et permissions Spatie résolus avec l'organisation courante (`setPermissionsTeamId($user->organization_id)`).
- `unread_notifications_count` : Compteur de notifications non lues en temps réel.
- `flash` : Messages de succès, d'erreur ou d'information flashés en session (`flash.success`, `flash.error`, `flash.info`).

---

## 4. Design System & Composants Réutilisables (`resources/js/Components/`)

Le design system garantit la cohérence graphique, l'accessibilité et la réutilisabilité à travers toute l'application :

| Composant | Fichier | Description & Fonctionnalités |
|---|---|---|
| **Button** | `Button.jsx` | Variantes (`primary`, `secondary`, `danger`, `outline`, `ghost`), tailles (`sm`, `md`, `lg`), états de chargement (`loading`), icônes avant/après. |
| **Input** | `Input.jsx` | Champ texte avec icône d'entrée, gestion d'état d'erreur, labels accessibles, focus ring subtil. |
| **Select** | `Select.jsx` | Menu déroulant natif stylisé avec options, icône chevron, support d'erreurs de validation. |
| **Textarea** | `Textarea.jsx` | Zone de texte redimensionnable avec support d'erreurs et compteurs. |
| **Modal** | `Modal.jsx` | Dialogue accessible avec backdrop flouté (`backdrop-blur-sm`), fermeture touche Échap, animations d'entrée/sortie, tailles configurables (`sm`, `md`, `lg`, `xl`, `2xl`). |
| **Card** | `Card.jsx` | Conteneur avec en-tête, corps et pied de page, bordures douces et ombres portées élégantes. |
| **Badge** | `Badge.jsx` | Puces d'état colorées (`gray`, `blue`, `green`, `amber`, `red`, `purple`, `indigo`) avec dot indicateur. |
| **Table** | `Table.jsx` | Tableaux de données responsives avec en-têtes triables et lignes interactives. |
| **Pagination** | `Pagination.jsx` | Navigation paginée utilisant les liens générés par Laravel Paginator via Inertia `<Link>`. |
| **Breadcrumb** | `Breadcrumb.jsx` | Fil d'Ariane dynamique facilitant la navigation hiérarchique au sein des dossiers. |
| **Toast** | `Toast.jsx` | Système de notification flottant affichant automatiquement les messages `flash.success` et `flash.error`. |
| **EmptyState** | `EmptyState.jsx` | État vide illustré avec icône, titre explicatif, description et bouton d'action contextuel. |
| **ConfirmDialog** | `ConfirmDialog.jsx` | Boîte de dialogue de confirmation pour les actions destructives (suppression, archivage, révocation). |
| **FileIcon** | `FileIcon.jsx` | Icône contextuelle selon le type MIME et l'extension (`pdf`, `image`, `word`, `excel`, `code`, `archive`, `text`). |
| **Tabs** | `Tabs.jsx` | Système d'onglets pour switcher les vues sans recharger la page. |

---

## 5. Layout et Navigation (`resources/js/Layouts/AuthenticatedLayout.jsx`)

Le layout principal encapsule l'application dans une interface professionnelle :
- **Sidebar collapsible** :
  - Identité de l'organisation courante.
  - Liens principaux : Tableau de bord, Documents, Dossiers, Recherche, Favoris, Récents, Partages, Workflows.
  - Section Administration : Catégories, Tags, Métadonnées.
  - Section Gestion documentaire : Corbeille, Archives.
  - Indicateurs de badges dynamiques (workflows en attente, corbeille).
- **Header supérieur** :
  - Bascule de la barre latérale pour mobile (drawer responsive).
  - Barre de recherche globale rapide.
  - Cloche de notifications avec badge de compteur non lu en temps réel.
  - Menu utilisateur avec accès au profil et bouton de déconnexion.
- **Zone de contenu principale** :
  - En-tête de page avec titre, sous-titre et actions principales.
  - Conteneur responsive avec padding harmonieux.
  - Affichage automatique des Toasts flash.

---

## 6. Pages et Fonctionnalités Couvertes (`resources/js/Pages/`)

### 1. Tableau de bord (`Dashboard/Index.jsx`)
- Statistiques clés : Documents actifs, dossiers, stockage consommé, favoris, workflows en attente.
- Filtre temporel interactif (7 jours, 30 jours, 90 jours, 12 mois).
- Graphique d'activité et distribution documentaire par type de fichier.
- Accès direct aux documents récents, favoris et tâches de validation urgentes.

### 2. Explorateur de documents (`Documents/Index.jsx`)
- Affichage en grille ou en liste avec mémorisation de préférence.
- Upload de fichiers par glisser-déposer (Drag & Drop) ou via dialogue de sélection.
- Filtres avancés par dossier, catégorie, date et recherche textuelle.
- Menu d'actions par document : Aperçu, Téléchargement, Partage, Verrouillage (Check-out/Check-in), Favori, Archivage, Corbeille.

### 3. Fiche détaillée Document (`Documents/Show.jsx`)
- En-tête complet avec statut, version actuelle, propriétaire, date de création et actions directes.
- Zone de prévisualisation intégrée (PDF, images, texte).
- Navigation par onglets riches :
  - **Versions** : Historique complet des versions, taille, auteur, bouton d'upload d'une nouvelle version et restauration.
  - **Métadonnées** : Champs personnalisés typés avec édition en ligne pour les utilisateurs autorisés.
  - **Commentaires & Réponses** : Fil de discussion collaboratif avec mentions et résolutions.
  - **Partages** : Liste des partages internes actifs, niveau de permission et révocation.
  - **Workflow** : État de circulation du document, approbateurs, historique d'approbation et soumission de nouvelle instance.
  - **Historique & Audit** : Traces d'audit complètes de toutes les actions survenues sur le document.

### 4. Gestion des Dossiers (`Folders/Index.jsx`)
- Arborescence visuelle des dossiers avec navigation hiérarchique (dossier parent, sous-dossiers).
- Création, modification et suppression de dossiers.
- Compteur dynamique des documents et sous-dossiers contenus.

### 5. Recherche Globale & Facettée (`Search/Index.jsx`)
- Champ de saisie avec recherche plein texte sur le titre et le contenu.
- Facettes dynamiques : dossier, catégorie, type MIME, plage de dates.
- Mise en évidence des correspondances et navigation directe vers les résultats.

### 6. Favoris & Récents (`Favorites/Index.jsx`, `Recent/Index.jsx`)
- Gestion personnalisée des favoris par utilisateur avec bascule immédiate en un clic.
- Historique chronologique des documents récemment ouverts ou modifiés.

### 7. Partages Internes (`Shares/Index.jsx`)
- Onglet "Partagés avec moi" et onglet "Partagés par moi".
- Affichage des permissions accordées (`read`, `write`, `admin`) et statut d'expiration.
- Contrôle de révocation immédiate pour les émetteurs.

### 8. Workflows de validation (`Workflows/Index.jsx`)
- Tableau des instances de workflow en cours nécessitant une décision.
- Dialogue d'action immédiate : Approuver, Rejeter, Demander correction avec saisie de commentaire.
- Gestion des modèles de workflows disponibles dans l'organisation.

### 9. Administration & Configuration (`Categories/`, `Tags/`, `Metadata/`)
- CRUD des catégories avec sélecteur de couleurs et icônes.
- CRUD des étiquettes (tags) documentaires.
- Définition des champs de métadonnées personnalisées (types : texte, nombre, date, booléen, liste déroulante) avec obligation et validation.

### 10. Cycle de Vie Documentaire (`Trash/Index.jsx`, `Archive/Index.jsx`)
- **Corbeille** : Consultation des documents supprimés, restauration en un clic, suppression définitive unitaire ou vidage complet de la corbeille.
- **Archives** : Consultation des documents archivés à valeur probante avec possibilité de désarchivage.

### 11. Centre de Notifications (`Notifications/Index.jsx`)
- Liste paginée des notifications in-app (partages, validations, commentaires).
- Actions individuelles et globales : Marquer comme lu, Tout marquer comme lu, Supprimer.

### 12. Profil Utilisateur & Préférences (`Profile/Index.jsx`)
- Consultation et mise à jour des informations personnelles.
- Configuration granulaire des canaux de notification (in-app, e-mail).

---

## 7. Sécurité & Contrôle d'Accès

- **Policies & Permissions Spatie** : Tous les contrôleurs web (`DocumentWebController`, `FolderWebController`, etc.) appliquent rigoureusement les Policies Laravel existantes (`authorize('view', $document)`, `authorize('create', Folder::class)`).
- **Filtrage ACL strict** : Toutes les listes et compteurs passent par `AccessControlService::applyAccessScope` pour empêcher toute fuite inter-utilisateurs ou inter-rôles.
- **Protection Multi-Tenant** : Le middleware `TenantMiddleware` et les scopes Eloquent garantissent une étanchéité absolue entre les organisations.
- **Tokens CSRF** : Pris en charge automatiquement par les requêtes HTTP d'Inertia via les en-têtes Axios configurés nativement.

---

## 8. Commandes de Compilation et de Test

```bash
# Compilation des assets frontend pour la production
npm run build

# Mode développement avec Hot Module Replacement (HMR)
npm run dev

# Exécution des tests du frontend Inertia
php artisan test --filter=FrontendInertiaTest

# Exécution de l'intégralité de la suite de tests
php artisan test
```
