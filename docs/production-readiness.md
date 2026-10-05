# GEDAPP — Guide de Préparation et Durcissement pour la Production (V1)

Ce document formalise l'état d'audit, les durcissements appliqués, l'architecture de sécurité et la checklist de mise en production pour **GEDAPP SaaS**.

---

## 1. Vue d'Ensemble & Socle Architectural

GEDAPP est une plateforme multi-tenant de Gestion Électronique de Documents (GED) bâtie sur :
- **Framework** : Laravel 12 (PHP 8.4/8.5)
- **Base de Données** : PostgreSQL
- **Frontend** : Inertia.js + React 18 + Tailwind CSS + Vite
- **Stockage Privé** : Driver S3 / Cloudflare R2 avec fallback local sécurisé
- **Traitement Asynchrone** : Laravel Queues (Database / Redis) & Workers
- **Extraction Textuelle** : OCR asynchrone (Tesseract / CLI) avec isolation sandbox

---

## 2. Isolation Multi-Tenant & Cloisonnement des Guards

### 2.1 Périmètres de Tenants
- Chaque ressource métier (`Document`, `Folder`, `Direction`, `Service`, `MetadataDefinition`, `AuditLog`, etc.) est strictement rattachée à une `organization_id`.
- Aucun document ou métadonnée ne peut être partagé ou visualisé en dehors de l'organisation détentrice.

### 2.2 Séparation Stricte Platform Admin vs Tenant Web
- **Guard Web (`web`)** : Utilisateurs des organisations clientes. Contexte de permissions synchronisé via `EnsureApiTeamContext` (`setPermissionsTeamId($user->organization_id)`).
- **Guard Platform (`platform`)** : Propriétaires et opérateurs SaaS (`PlatformUser`). Modèle séparé (`platform_users`), guard dédié, sessions séparées, middleware `platform.auth` et `platform.role`.
- Un administrateur plateforme n'a pas accès direct aux sessions ou documents d'un tenant sans authentification sur le guard web dudit tenant.
- Un utilisateur d'organisation tentant d'accéder au domaine `/platform` est immédiatement redirigé vers `/platform/login`.

---

## 3. Sécurité des Authentifications & Rate Limiting

### 3.1 Protection Anti Brute-Force
- **Web Login (`/login`)** : Limité à 5 tentatives par minute par identifiant (`email` ou `phone`) combiné à l'adresse IP via `RateLimiter::tooManyAttempts()`. Verrouillage automatique avec message temporisé.
- **API Login (`/api/v1/auth/login`)** : Limité à 5 tentatives par minute avec réponse HTTP 422 standardisée et message de temporisation.
- **Platform Login (`/platform/login`)** : Limité à 5 tentatives par minute avec vidage de clé lors du succès.

### 3.2 Limitation Globale API
- Le groupe de routes `/api/v1` applique le middleware `throttle:api` configuré dans `AppServiceProvider` (60 requêtes par minute par utilisateur authentifié ou par IP).

---

## 4. Autorisation, Access Scopes & Contrôle d'Accès

### 4.1 Politiques Granulaires
- Les actions documentaires sont scellées par `DocumentPolicy` : `view`, `download`, `update`, `delete`, `share`, `archive`, `restore`, `forceDelete`.
- Distinction stricte entre la consultation (`view`) et le téléchargement (`download`) : les utilisateurs dotés uniquement du droit de lecture ne peuvent pas exporter ou télécharger les fichiers physiques.

### 4.2 Défense en Profondeur sur les FormRequests
- Validation anti-IDOR renforcée au niveau des FormRequests :
  - `StoreServiceRequest` & `UpdateServiceRequest` : validation `direction_id` limitée à `organization_id` du demandeur.
  - `StoreAccessScopeRequest` : validation `user_id`, `direction_id`, `service_id`, `folder_id`, `document_id` limitée à `organization_id`.
  - `DocumentTypeWebController` : validation de `direction_id`, `service_id`, `parent_id`, `metadata_definition_ids` limitée à l'organisation active.

---

## 5. Stockage Sécurisé & Cycle de Vie des Fichiers

### 5.1 Stockage Privé & Chiffrement en Transit
- Aucun fichier de document n'est public (`public/`).
- Disque configuré `private` (local ou Cloudflare R2 compatible S3).
- Téléchargements et prévisualisations servis au travers de streams contrôlés (`StreamedResponse`) avec en-têtes de sécurité :
  - `Content-Disposition: inline` (prévisualisation) ou `attachment` (téléchargement)
  - `X-Content-Type-Options: nosniff`
  - Validation préalable de la propriété tenant et des droits ACL.

### 5.2 Suppression Définitive & Nettoyage Disque
- Lors d'une suppression définitive (`forceDelete`), `DocumentLifecycleService` supprime le document en base et purge l'ensemble des fichiers physiques associés (toutes versions et répertoire parent).

---

## 6. Traitement Asynchrone, Queues & OCR

### 6.1 Résilience du Job OCR (`ProcessDocumentOcr`)
- Configuration de résilience : `tries = 3`, `timeout = 180s`.
- Vérification avant traitement : le document et la version doivent exister et ne pas être soft-deleted.
- En cas d'échec répété, la méthode `failed()` bascule le statut du document en `OcrStatus::Failed` et enregistre le diagnostic dans les logs système.

### 6.2 Nettoyage des Fichiers Temporaires
- `OcrService` télécharge temporairement le binaire distant dans le dossier scratch local pour exécuter l'extraction.
- La structure `finally` garantit la suppression systématique du fichier temporaire, même en cas d'exception non gérée.

---

## 7. Quotas, Abonnements & Modèle Économique SaaS

### 7.1 Intégrité des Données Commerciales
- Résolution 100% côté serveur des prix, quotas et caractéristiques des plans (`PlanService`, `BillingService`).
- Aucune manipulation de montant, de nombre de documents ou d'utilisateurs possible côté client.
- Application stricte des verrous de quotas avant création d'utilisateurs, de documents ou de types documentaires.

---

## 8. En-têtes de Sécurité & Sessions

- Protection CSRF active sur toutes les requêtes mutantes Web (`VerifyCsrfToken`).
- Sessions sécurisées avec régénération lors du login (`$request->session()->regenerate()`) et invalidation complète lors de la déconnexion.
- Cookies de session configurés avec flags `HttpOnly`, `SameSite=Lax` et `Secure` en environnement de production HTTPS.

---

## 9. Base de Données & Intégrité Transactionnelle

- Opérations critiques encapsulées dans des transactions `DB::transaction()` :
  - Inscription SaaS (création Organisation, Admin, Rôles Spatie, Souscription Trial, Folders racines).
  - Versioning documentaire et archivage.
  - Exécution des étapes de workflow documentaire.
- Clés étrangères protégées avec contraintes d'intégrité référentielle.

---

## 10. Audit Logging & Traçabilité

- `AuditService` et `PlatformAuditService` tracent de manière immuable :
  - Authentifications (succès, échecs, déconnexions).
  - Consultations, téléchargements, partages et modifications documentaires.
  - Événements de transition de workflow et changements de statut.
  - Actions opérateurs sur la plateforme SaaS (suspension, facturation, prolongation d'essai).

---

## 11. Gestion des Erreurs & Confidentialité

- En environnement `APP_DEBUG=false`, aucun détail technique ou stack trace PHP n'est exposé à l'utilisateur final.
- `bootstrap/app.php` force le rendu JSON en cas de requête API ou attendue en JSON.
- Les exceptions métiers renvoient des codes HTTP appropriés (401, 403, 404, 422, 429).

---

## 12. Performance Frontend & Compilation Vite

- Assets compilés via `npm run build` : bundling React minifié, code splitting dynamique par route Inertia, extraction CSS optimisée sans avertissement ni chunk orphelin.

---

## 13. Health Check & Surveillance

- Endpoint de santé système natif exposé à `/up` (HTTP 200 OK si le runtime et le storage répondent).
- Surveillance des workers queues et de la disponibilité de la base de données.

---

## 14. Stratégie de Sauvegardes & Reprise d'Activité

- **Base de données PostgreSQL** : Sauvegarde quotidienne `pg_dump` chiffrée avec rétention 30 jours + WAL archiving pour Point-In-Time-Recovery (PITR).
- **Stockage Cloudflare R2** : Versioning d'objets activé sur le bucket de production pour prévenir toute suppression accidentelle.

---

## 15. Gestion des Variables d'Environnement (`.env`)

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY` générée et sécurisée
- `SESSION_SECURE_COOKIE=true`
- Clés d'API et identifiants R2 injectés via variables d'environnement sécurisées (secrets manager).

---

## 16. Procédures de Déploiement

1. Mise en mode maintenance : `php artisan down --retry=60`
2. Déploiement du code source
3. Installation des dépendances : `composer install --no-dev --optimize-autoloader`
4. Migration de schéma : `php artisan migrate --force`
5. Optimisation des caches :
   - `php artisan config:cache`
   - `php artisan route:cache`
   - `php artisan view:cache`
   - `php artisan event:cache`
6. Redémarrage des workers queues : `php artisan queue:restart`
7. Sortie du mode maintenance : `php artisan up`

---

## 17. Suite de Tests de Durcissement

- Suite dédiée : `tests/Feature/ProductionHardeningTest.php`
- Couverture :
  - Blocage anti brute-force Web (5 tentatives).
  - Blocage anti brute-force API (5 tentatives).
  - Cloisonnement inter-organisations sur la création de services (IDOR rejeté 422).
  - Cloisonnement inter-organisations sur les scopes d'accès (IDOR rejeté 422).
  - Cloisonnement inter-organisations sur les types documentaires (IDOR rejeté 422).
  - Différenciation stricte de l'autorisation vue vs téléchargement documentaire.
  - Interdiction de téléchargement / prévisualisation inter-tenants (403/404).
  - Isolation étanche du guard Platform vis-à-vis du guard Tenant.

---

## 18. PRE-PRODUCTION CHECKLIST (GO / NO-GO)

| Domaine | Contrôle | Statut |
|---|---|:---:|
| **Sécurité** | `APP_DEBUG=false` en production | [X] Prêt |
| **Sécurité** | Rate limiter actif sur login Web et API (5 essais / min) | [X] Prêt |
| **Sécurité** | Rate limiter global actif sur l'API (60 req / min) | [X] Prêt |
| **Sécurité** | FormRequests protégés contre l'injection d'identifiants cross-tenants | [X] Prêt |
| **Sécurité** | Téléchargement privé restreint aux détenteurs de la permission | [X] Prêt |
| **Sécurité** | Cloisonnement strict guard Platform vs guard Web Tenant | [X] Prêt |
| **Stockage** | Bucket R2 / S3 configuré en privé sans accès anonyme | [X] Prêt |
| **Stockage** | Purge des fichiers physiques lors du force-delete | [X] Prêt |
| **Queues** | Queue worker configuré avec supervision, timeout (180s) et retries (3) | [X] Prêt |
| **OCR** | Gestion des échecs avec statut failed et nettoyage des fichiers temporaires | [X] Prêt |
| **Qualité** | 864 tests automatisés validés à 100% sans aucun échec | [X] Prêt |
| **Code Style** | Formatage conforme Pint (`vendor/bin/pint --dirty --format agent`) | [X] Prêt |
| **Frontend** | Build de production Vite propre (`npm run build`) | [X] Prêt |
