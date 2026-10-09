# APPGED — ZAP Security Remediation

## 1. Contexte

- **Date du scan initial** : 08/10/2026
- **Outil d'audit** : OWASP ZAP 2.17.0
- **Cible analysée** : `https://ged.laravel.cloud`
- **Application** : APPGED — SaaS B2B multi-tenant de Gestion Électronique de Documents
- **Stack technique** : Laravel 13, PHP 8.5, Inertia.js, React 19, Vite, Tailwind CSS, PostgreSQL, Cloudflare R2, Laravel Sanctum, Spatie Permission.

---

## 2. État initial

Le rapport initial d'OWASP ZAP 2.17.0 répertoriait **14 alertes** réparties comme suit :

- **Critical** : 0
- **High** : 0
- **Medium** : 3
- **Low** : 5
- **Informational** : 6

### Tableau synthétique initial

| Finding | Risk | Count | Action requise | Statut |
| :--- | :--- | :---: | :--- | :--- |
| **Content Security Policy (CSP) Header Not Set** | Medium | 1 | Implémenter une CSP stricte compatible Inertia/React/Vite | **FIXED** |
| **Missing Anti-clickjacking Header** | Medium | 1 | Configurer `frame-ancestors 'self'` et `X-Frame-Options: SAMEORIGIN` | **FIXED** |
| **Sub Resource Integrity Attribute Missing** | Medium | 2 | Analyse polices CDN externes, local bundling Vite et `crossorigin="anonymous"` | **FIXED / ACCEPTED RISK** |
| **Cookie No HttpOnly Flag** | Low | 1 | Distinction stricte session cookie (`HttpOnly`) vs `XSRF-TOKEN` (CSRF SPA JS) | **FALSE POSITIVE / BY DESIGN** |
| **Cookie with SameSite Attribute None** | Low | 1 | Audit session APPGED (`SameSite=lax`) vs cookie edge Cloudflare (`__cf_bm`) | **FALSE POSITIVE / CLOUDFLARE** |
| **Strict-Transport-Security Header Not Set** | Low | 1 | Configurer HSTS `max-age=31536000` via middleware HTTP | **FIXED** |
| **Timestamp Disclosure - Unix** | Low | 1 | Audit des timestamps exposés (Cloudflare edge / HTTP Date headers) | **INFORMATIONAL / ACCEPTED RISK** |
| **X-Content-Type-Options Header Missing** | Low | 1 | Déployer `X-Content-Type-Options: nosniff` sur toutes les réponses | **FIXED** |
| **Weakly coupled cookie** | Info | 1 | Analyse scoping domaine et flags de sécurité | **INFORMATIONAL / ACCEPTED RISK** |
| **Information Disclosure - Suspicious Comments** | Info | 1 | Vérification des templates HTML et minification Vite en production | **FALSE POSITIVE / ACCEPTED RISK** |
| **Modern Web Application** | Info | 1 | Détection passive d'une architecture SPA (Inertia.js + React) | **INFORMATIONAL** |
| **Re-examine Cache-control Directives** | Info | 1 | Renforcement de `Cache-Control: no-store, private` sur les routes authentifiées | **FIXED** |
| **Retrieved from Cache** | Info | 1 | Détection du cache proxy edge Cloudflare | **INFORMATIONAL** |
| **Session Management Response Identified** | Info | 1 | Détection standard de la création de cookie de session sur login/auth | **INFORMATIONAL** |

---

## 3. Corrections détaillées

### 3.1 Content Security Policy (CSP) Header Not Set

- **Problème** : Absence d'en-tête `Content-Security-Policy`, exposant l'application aux attaques par injection de contenu et XSS si une vulnérabilité applicative venait à exister.
- **Cause** : Aucun middleware global ou de groupe web ne définissait de politique de sécurité des contenus.
- **Correction** :
  - Création du middleware `App\Http\Middleware\SecurityHeaders` enregistré dans les groupes `web` et `api` au sein de `bootstrap/app.php`.
  - Construction d'une politique CSP stricte, alignée sur l'architecture APPGED :
    - `default-src 'self'` : interdiction des chargements arbitraires.
    - `script-src 'self'` en production (sans `unsafe-eval`, sans `unsafe-inline` arbitraire ; assets Vite compilés servis depuis le même domaine). En environnement local, tolérance adaptée pour le serveur HMR Vite (`http://localhost:5173`, `ws://localhost:5173`).
    - `style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://fonts.googleapis.com` : styles locaux, polices autorisées, et `unsafe-inline` strictement réservé aux propriétés dynamiques de style React JSX (`style={{ ... }}`).
    - `font-src 'self' https://fonts.bunny.net https://fonts.gstatic.com data:` : polices locales bundlées par Vite et CDN de polices autorisés.
    - `img-src 'self' data: blob: https:` : support des aperçus d'images, des blobs d'upload et du stockage Cloudflare R2 / S3.
    - `media-src 'self' data: blob:` : flux multimédia locaux et blobs.
    - `connect-src 'self' https://fonts.bunny.net https://fonts.googleapis.com` : appels API Sanctum, polices et WebSockets en local.
    - `frame-src 'self' blob:` : indispensable pour autoriser la prévisualisation in-app des documents PDF dans les éléments `<iframe>`.
    - `frame-ancestors 'self'` : empêche tout site tiers d'encapsuler APPGED (protection anti-clickjacking).
    - `object-src 'none'` : neutralise les greffons obsolètes (Flash, Java).
    - `base-uri 'self'` : bloque le détournement de l'élément `<base>`.
    - `form-action 'self'` : interdit l'exfiltration de formulaires vers des domaines tiers.
    - `upgrade-insecure-requests` : forçage du transit HTTPS en production.
- **Fichiers modifiés** :
  - `app/Http/Middleware/SecurityHeaders.php`
  - `bootstrap/app.php`
- **Tests associés** : `SecurityHardeningZapTest::test_security_headers_are_present_on_web_routes`, `SecurityHardeningZapTest::test_security_headers_are_present_on_api_routes`.
- **Résultat** : En-tête CSP injecté sur 100% des requêtes web et API sans perturbation du runtime Inertia/React.

---

### 3.2 Missing Anti-clickjacking Header

- **Problème** : Absence d'en-tête de protection contre l'encapsulation en iframe (`X-Frame-Options` ou `frame-ancestors`), exposant potentiellement les utilisateurs à des attaques par détournement de clic (UI redressing / clickjacking).
- **Cause** : L'en-tête n'était pas configuré au niveau de la réponse HTTP globale.
- **Analyse fonctionnelle** :
  - Une politique `DENY` stricte aurait **cassé** la prévisualisation des documents PDF dans l'interface APPGED, car `resources/js/Pages/Documents/Show.jsx` affiche les fichiers PDF dans un `<iframe>` pointant sur la route interne `/documents/{document}/preview`.
- **Correction** :
  - Mise en place conjointe de la protection moderne CSP `frame-ancestors 'self'` et de la compatibilité ascendante `X-Frame-Options: SAMEORIGIN`.
  - Cela autorise l'application à encapsuler ses propres prévisualisations tout en interdisant formellement à tout domaine tiers d'intégrer APPGED dans une frame.
- **Fichiers modifiés** :
  - `app/Http/Middleware/SecurityHeaders.php`
- **Tests associés** : `SecurityHardeningZapTest::test_security_headers_are_present_on_web_routes`.
- **Résultat** : Conformité anti-clickjacking totale sans régression sur l'aperçu PDF.

---

### 3.3 Sub Resource Integrity Attribute Missing

- **Problème** : ZAP a détecté deux balises `<link rel="stylesheet">` externes sans attribut `integrity` ni `crossorigin`.
- **Cause** : `resources/views/app.blade.php` référençait les feuilles de styles de polices :
  1. `https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700`
  2. `https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&display=swap`
- **Analyse technique de faisabilité SRI** :
  - Les fournisseurs de polices dynamiques (`fonts.googleapis.com` et `fonts.bunny.net`) négocient dynamiquement le CSS servi en fonction du `User-Agent` du client (formats woff2, unicode-range, compatibilité navigateur).
  - L'application d'un hash cryptographique statique (`integrity="sha384-..."`) sur ces endpoints dynamiques provoque une rupture immédiate de rendu pour tous les navigateurs recevant une variante optimisée dont le hash diffère.
- **Correction & Mitigations appliquées** :
  - Ajout de `crossorigin="anonymous"` sur les balises de préconnexion et de feuilles de styles externes afin d'éviter l'envoi de cookies d'authentification vers ces domaines tiers.
  - Utilisation du bundling local Vite pour les polices principales via `laravel-vite-plugin/fonts` (Instrument Sans généré dans `public/build/assets/`).
  - Encadrement strict par la politique CSP (`style-src` et `font-src`) limitant le chargement uniquement à ces origines reconnues et vérifiées.
- **Fichiers modifiés** :
  - `resources/views/app.blade.php`
- **Tests associés** : `npm run build`, validation visuelle du layout.
- **Résultat** : Risque maîtrisé, isolation CORS appliquée, politique CSP limitant strictement les origines autorisées.

---

### 3.4 Cookie No HttpOnly Flag & Cookie with SameSite Attribute None

- **Problème** : ZAP a remonté un cookie sans drapeau `HttpOnly` et un cookie avec `SameSite=None`.
- **Analyse détaillée des cookies** :
  1. **Cookie de session APPGED** :
     - Configuré dans `config/session.php` : `'http_only' => env('SESSION_HTTP_ONLY', true)`, `'same_site' => env('SESSION_SAME_SITE', 'lax')`.
     - Durcissement de la valeur par défaut pour `'secure'` : `env('SESSION_SECURE_COOKIE', env('APP_ENV') === 'production' ? true : null)`.
     - Le cookie de session est donc **toujours** `HttpOnly=true`, `SameSite=lax`, et `Secure=true` en production.
  2. **Cookie `XSRF-TOKEN`** :
     - Conçu par Laravel (`Illuminate\Foundation\Http\Middleware\ValidateCsrfToken`) pour être accessible en JavaScript par les frameworks SPA (Inertia, Axios, React) afin de lire le token CSRF et le transmettre dans l'en-tête `X-XSRF-TOKEN`.
     - Le rendre `HttpOnly` casserait la protection CSRF de l'ensemble de l'interface SPA.
     - Ce constat constitue un **FALSE POSITIVE / BEHAVIOR BY DESIGN**.
  3. **Cookie avec `SameSite=None`** :
     - Aucun cookie généré par le code PHP d'APPGED n'utilise `SameSite=None`.
     - L'alerte ZAP provient du cookie edge Cloudflare (`__cf_bm` / Bot Management) injecté par le proxy Cloudflare sur `https://ged.laravel.cloud` pour la détection automatisée des bots.
     - Ce constat constitue un **FALSE POSITIVE / EXTERNAL CLOUDFLARE EDGE BEHAVIOR**.
- **Fichiers modifiés** :
  - `config/session.php`
- **Tests associés** : `SecurityHardeningZapTest::test_session_cookie_attributes_are_securely_configured`.
- **Résultat** : Cookies applicatifs pleinement sécurisés, conformité CSRF SPA préservée.

---

### 3.5 Strict-Transport-Security Header Not Set

- **Problème** : Absence de l'en-tête HSTS informant le navigateur de ne communiquer qu'en HTTPS.
- **Cause** : Non configuré au niveau applicatif Laravel.
- **Correction** :
  - Ajout dans `SecurityHeaders` de l'en-tête :
    `Strict-Transport-Security: max-age=31536000` (durée de validité : 1 an).
  - L'en-tête est conditionné aux requêtes sécurisées (`$request->isSecure()`, proxy HTTPS, ou environnement `production`).
  - Les directives `includeSubDomains` et `preload` n'ont pas été ajoutées arbitrairement pour éviter tout impact collatéral sur l'ensemble du domaine d'hébergement partagé `laravel.cloud`.
- **Fichiers modifiés** :
  - `app/Http/Middleware/SecurityHeaders.php`
- **Tests associés** : `SecurityHardeningZapTest::test_hsts_header_is_enforced_over_https`.
- **Résultat** : HSTS actif et validé sur toutes les connexions HTTPS.

---

### 3.6 X-Content-Type-Options Header Missing

- **Problème** : Absence de l'en-tête `X-Content-Type-Options`, permettant théoriquement au navigateur d'ignorer le `Content-Type` déclaré et de deviner le type MIME par analyse du contenu (MIME sniffing).
- **Cause** : L'en-tête était présent sur les réponses de prévisualisation dans `PreviewService`, mais absent des réponses globales (pages Inertia, JSON d'API, redirections).
- **Correction** :
  - Ajout systématique de `X-Content-Type-Options: nosniff` dans `SecurityHeaders` sur l'ensemble des réponses HTTP (web et api).
  - Vérification qu'aucune altération ne touche :
    - Les prévisualisations PDF (`Content-Type: application/pdf`).
    - Les téléchargements de fichiers (`Content-Disposition: attachment`).
    - Les fichiers d'images et données OCR.
    - Les réponses JSON d'API (`Content-Type: application/json`).
- **Fichiers modifiés** :
  - `app/Http/Middleware/SecurityHeaders.php`
- **Tests associés** : `SecurityHardeningZapTest::test_security_headers_are_present_on_web_routes`, `SecurityHardeningZapTest::test_security_headers_are_present_on_api_routes`.
- **Résultat** : Protection MIME-sniffing déployée universellement.

---

### 3.7 Timestamp Disclosure - Unix

- **Problème** : ZAP a détecté un nombre à 10 chiffres interprétable comme un horodatage Unix dans la réponse HTTP.
- **Cause** : Ce finding correspond à la détection passive d'un horodatage dans les métadonnées HTTP externes de Cloudflare (cookie de score bot `__cf_bm`, en-tête `Date` / `Age`) ou dans les horodatages standards de gestion de session et de tokens.
- **Analyse d'exploitabilité** :
  - Aucun timestamp ne divulgue de secret d'infrastructure, de clé cryptographique ou de structure de base de données interne.
  - La présence d'horodatages temporels dans les métadonnées de requête et de token est standard dans les architectures web modernes (RFC 7231, JWT / Sanctum timestamps).
- **Statut** : **INFORMATIONAL / ACCEPTED RISK**.

---

### 3.8 Cache-Control Directives & Autres Alertes Informationnelles

- **Re-examine Cache-control Directives** :
  - **Correction** : Le middleware `SecurityHeaders` force `Cache-Control: no-store, private, max-age=0, must-revalidate` et `Pragma: no-cache` sur toutes les sessions authentifiées (dashboard, documents, platform admin) pour empêcher tout stockage intermédiaire dans les proxys partagés.
- **Information Disclosure - Suspicious Comments** :
  - **Statut** : **FALSE POSITIVE / ACCEPTED RISK**. Les commentaires signalés par l'analyse statique ZAP sont éliminés lors de la minification Vite en production (`npm run build`).
- **Weakly coupled cookie** :
  - **Statut** : **INFORMATIONAL**. Les cookies de session et CSRF sont isolés au sous-domaine avec chemin `/`.
- **Modern Web Application & Session Management** :
  - **Statut** : **INFORMATIONAL**. Confirmations informatives de l'architecture SPA et du mécanisme standard de session Laravel.

---

## 4. Multi-tenant Security

L'architecture multi-tenant d'APPGED repose sur le cloisonnement par `organization_id` validé systématiquement aux frontières de routage, de policies et d'ACL.

### Scénarios de test automatisés (Organisation A vs Organisation B)

| Scénario testé | Action | Résultat attendu | Statut |
| :--- | :--- | :--- | :--- |
| **Utilisateur A → Document B (Web)** | `GET /documents/{docB->id}` | `403 Forbidden` ou `404 Not Found` | **PASSED** |
| **Utilisateur A → Download Document B** | `GET /documents/{docB->id}/download` | `403 Forbidden` | **PASSED** |
| **Utilisateur A → Preview Document B** | `GET /documents/{docB->id}/preview` | `403 Forbidden` | **PASSED** |
| **Utilisateur A → Version Document B (Download)** | `GET /documents/{docB->id}/versions/{versionB->id}/download` | `403 Forbidden` | **PASSED** |
| **Utilisateur A → Version Document B (Preview)** | `GET /documents/{docB->id}/versions/{versionB->id}/preview` | `403 Forbidden` | **PASSED** |
| **Utilisateur B → Document A (Download)** | `GET /documents/{docA->id}/download` | `403 Forbidden` | **PASSED** |
| **Utilisateur B → Document A (Preview)** | `GET /documents/{docA->id}/preview` | `403 Forbidden` | **PASSED** |
| **Utilisateur A → Document B (API JSON)** | `GET /api/v1/documents/{docB->id}` | `403 Forbidden` ou `404 Not Found` | **PASSED** |
| **Utilisateur A → Download Document B (API)** | `GET /api/v1/documents/{docB->id}/download` | `403 Forbidden` | **PASSED** |
| **Utilisateur A → Preview Document B (API)** | `GET /api/v1/documents/{docB->id}/preview` | `403 Forbidden` | **PASSED** |

Les tentatives de falsification d'identifiants (IDOR) dans les URLs, paramètres de route ou payloads d'API sont formellement interceptées par `DocumentPolicy` et `DocumentService`.

---

## 5. API Security

Les API Sanctum (V1) sont soumises à un ensemble de contrôles stricts :

1. **Authentification & Invalidité des tokens** :
   - Requête non authentifiée (`GET /api/v1/auth/me`) retourne `401 Unauthorized` au format JSON sans fuite de stack trace (`test_unauthenticated_api_request_returns_401_json`).
2. **Rate Limiting** :
   - Throttling configuré via `RateLimiter::for('api')` (60 req/min par utilisateur ou IP).
   - Throttling strict de protection brute-force sur `/login` et `/api/v1/auth/login` (blocage au bout de 5 tentatives infructueuses vérifié dans `ProductionHardeningTest`).
3. **Codes HTTP conformes** :
   - `401` : Non authentifié.
   - `403` : Action interdite par la politique de sécurité ou frontière multi-tenant.
   - `404` : Ressource introuvable ou masquée pour des raisons de cloisonnement.
   - `422` : Échec de validation de formulaire.
   - `429` : Dépassement de quota de débit (Rate Limit).

---

## 6. Document Security & Upload Hardening

### 6.1 Stockage privé et absence d'exposition publique
- Tous les documents sont stockés sur le disque `private` (ou Cloudflare R2 avec accès privé).
- Le chemin physique de stockage est anonymisé sous forme de UUID :
  `organizations/{orgId}/documents/{docId}/versions/{versionNumber}/{uuid}.{extension}`
- Aucun lien symbolique public (`storage/`) n'expose les documents des locataires. L'accès physique direct est rendu impossible.

### 6.2 Contrôles stricts à l'upload (`DocumentService::validateFile`)
1. **Contrôle d'extension et de double extension** :
   - Rejet de toute extension suspecte ou dangereuse dans n'importe quelle section du nom de fichier original : `php`, `phtml`, `phar`, `sh`, `exe`, `bat`, `cmd`, `cgi`, `py`, `js`, `jsp`, `asp`, `html`, etc.
   - Validation via `SecurityHardeningZapTest::test_upload_blocks_dangerous_extensions_and_path_traversal` (rejet de `malicious.php.pdf`).
2. **Protection Path Traversal & Null Byte** :
   - Rejet automatique des séquences `..` et du caractère nul `\0` dans le nom de fichier original.
3. **Validation MIME type stricte** :
   - Interdiction des types MIME exécutables et scripts (`application/x-php`, `application/x-executable`, `application/x-sh`, `text/html`, etc.).
4. **Contrôle de taille** :
   - Plafond de taille de fichier enforced par `config('documents.max_file_size_kb')` et vérification de quota par `BillingService::assertCanAddStorage`.

---

## 7. Platform Security (Séparation Platform / Tenant)

APPGED sépare strictement le domaine d'administration plateforme (`PlatformUser`) de l'espace des locataires (`User`) :

1. **Guards d'authentification séparés** :
   - Espace locataire : Guard `web` (modèle `App\Models\User`).
   - Espace plateforme : Guard `platform` (modèle `App\Models\PlatformUser`).
2. **Cloisonnement des accès vérifié par les tests** :
   - Un utilisateur locataire tentant d'accéder à `/platform` ou `/platform/organizations` est systématiquement redirigé vers la mire de connexion plateforme `platform.login` (`test_tenant_user_cannot_access_platform_admin`).
   - Un opérateur plateforme connecté sur le guard `platform` ne peut pas accéder au tableau de bord locataire `/dashboard` sans disposer d'une authentification valide sur le guard `web` (`test_platform_user_cannot_access_tenant_dashboard`).
3. **Contrôle des rôles plateforme** :
   - Le middleware `EnsurePlatformRole` restreint les actions critiques (facturation, suspension d'organisation, gestion des plans) aux seuls rôles habilités (`platform_owner`, `platform_admin`, `platform_billing`, `platform_support`).

---

## 8. Résultat ZAP après correction

| Catégorie | État initial ZAP (08/10/2026) | État après remédiation (Local / Suite de tests) | Statut cible déployée (`https://ged.laravel.cloud`) |
| :--- | :---: | :---: | :---: |
| **Critical** | 0 | 0 | **NON VÉRIFIÉ** (en attente de redéploiement) |
| **High** | 0 | 0 | **NON VÉRIFIÉ** (en attente de redéploiement) |
| **Medium** | 3 | 0 | **NON VÉRIFIÉ** (en attente de redéploiement) |
| **Low** | 5 | 0 *(hors findings acceptés)* | **NON VÉRIFIÉ** (en attente de redéploiement) |
| **Informational** | 6 | Documenté & justifié | **NON VÉRIFIÉ** (en attente de redéploiement) |

### Comparatif direct par finding précédent

| Finding initial | Statut post-remédiation | Justification |
| :--- | :--- | :--- |
| **Content Security Policy (CSP)** | **FIXED** | En-tête CSP complet implémenté et testé sur web & api |
| **Missing Anti-clickjacking Header** | **FIXED** | `frame-ancestors 'self'` et `X-Frame-Options: SAMEORIGIN` actifs |
| **Sub Resource Integrity Attribute Missing** | **FIXED / ACCEPTED RISK** | Isolation CORS `crossorigin="anonymous"`, polices locales Vite, restriction CSP stricte sur les CDN autorisés |
| **Cookie No HttpOnly Flag** | **FALSE POSITIVE / BY DESIGN** | `XSRF-TOKEN` lisible côté JS pour le jeton CSRF SPA ; cookies de session strictement `HttpOnly` |
| **Cookie with SameSite Attribute None** | **FALSE POSITIVE / ACCEPTED RISK** | Cookie edge Cloudflare Bot Management (`__cf_bm`) ; cookies applicatifs en `SameSite=lax` |
| **Strict-Transport-Security Header** | **FIXED** | HSTS `max-age=31536000` actif sur les flux sécurisés |
| **Timestamp Disclosure - Unix** | **ACCEPTED RISK** | Métadonnées temporelles HTTP standards et cookies de bordure Cloudflare sans impact sur la sécurité |
| **X-Content-Type-Options Header** | **FIXED** | `nosniff` injecté sur 100% des réponses |
| **Re-examine Cache-control Directives** | **FIXED** | `Cache-Control: no-store, private` forcé sur les routes authentifiées |
| **Suspicious Comments / Modern App** | **INFORMATIONAL / FALSE POSITIVE** | Minification Vite appliquée en production ; détection normale du framework SPA |

---

## 9. Risques résiduels

Les seuls risques résiduels identifiés à ce stade sont :

1. **Dépendance aux polices distantes (Bunny Fonts / Google Fonts)** :
   - Bien que protégées par `crossorigin="anonymous"` et encadrées par la CSP, une indisponibilité ou un incident de sécurité majeur chez ces fournisseurs de polices pourrait impacter le rendu typographique non mis en cache.
   - *Recommandation* : Intégrer la totalité des polices (notamment Caveat) dans les assets statiques locaux compilés au build lorsque la connectivité de build le permet.
2. **Reverse Proxy Edge Cloudflare** :
   - Les cookies injectés à la bordure par Cloudflare (`__cf_bm`) continueront d'être observés par les scanners passifs automatisés. Ce comportement est inhérent à la protection CDN / Anti-DDoS.

---

## 10. Limites

> **Avertissement important** :
> Ce scan OWASP ZAP automatisé (DAST) et les corrections associées apportent un durcissement défensif mesurable, mais **ne constituent pas à eux seuls un test d'intrusion (pentest) complet**.
> Un audit de sécurité complet requiert des tests manuels approfondis, des revues de code complètes des flux métier complexes, et une validation en conditions réelles de production.
