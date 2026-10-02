# Module 22 — Onboarding SaaS + Landing Page + Inscription V1

## 1. Vue d'ensemble

Le Module 22 transforme GEDAPP en un SaaS B2B complet avec :
- Un parcours commercial public fluide : **Landing Page (`/`)** → **Tarifs (`/tarifs`)** → **Inscription (`/inscription`)** → **Création Organisation (`/inscription/organisation`)** → **Choix du Plan (`/inscription/plan`)** → **Dashboard avec Onboarding (`/dashboard`)**.
- Un flux Enterprise dédié : **`/enterprise`** permettant aux grands comptes de soumettre une demande de démonstration et d'accompagnement sur-mesure (modèle `Lead`).
- Une isolation stricte entre l'espace public/tenant et l'espace Platform Admin (`/platform`).

---

## 2. Parcours d'Inscription SaaS

### Étape 1 : Création du compte administrateur (`GET|POST /inscription`)
- **Controller** : `RegistrationWebController@createAccount` & `storeAccount`
- **Champs** : Prénom, Nom, Email professionnel, Téléphone, Mot de passe sécurisé et confirmation.
- **Validation** :
  - `email` unique dans la table `users`.
  - Mot de passe conforme aux règles de sécurité.
- **Session** : Les informations sont stockées sous `registration.admin` (sans conserver le mot de passe en clair au-delà du cycle requis).

### Étape 2 : Création de l'organisation (`GET|POST /inscription/organisation`)
- **Controller** : `RegistrationWebController@createOrganization` & `storeOrganization`
- **Champs** : Nom de l'organisation, Secteur d'activité, Pays, Ville.
- **Sécurité** : Redirige vers `/inscription` si l'étape 1 n'est pas complétée.
- **Session** : Les données sont stockées sous `registration.organization`.

### Étape 3 : Choix du Plan & Finalisation Atomique (`GET|POST /inscription/plan`)
- **Controller** : `RegistrationWebController@createPlan` & `storePlan`
- **Choix disponibles** :
  - **Essentiel (19 000 FCFA/mois ou 190 000 FCFA/an)** : 5 utilisateurs, 20 Go, 3 directions, 15 types doc, 100 pages OCR/mois.
  - **Professionnel (39 000 FCFA/mois ou 390 000 FCFA/an)** : 20 utilisateurs, 100 Go, 10 directions, 50 types doc, 1 000 pages OCR/mois, Workflows, Audit avancé, API REST.
  - **Enterprise (Sur devis)** : Redirection immédiate vers `/enterprise` pour demande de démonstration. Ne crée aucun abonnement automatique sans accord commercial.

---

## 3. Transaction Atomique & Rollback

Le provisioning du tenant est orchestré par `RegistrationService::register()` au sein d'une transaction `DB::transaction()` :

1. **Validation du plan serveur** : Le slug (`essential` ou `professional`) est vérifié et le modèle `Plan` est récupéré directement en base de données. Aucun paramètre de prix, quotas ou limites envoyé par le navigateur n'est utilisé.
2. **Création de l'Organisation** : Insertion dans `organizations` avec génération de slug et statut actif.
3. **Création du Premier Utilisateur** : Insertion dans `users` avec hashage sécurisé du mot de passe.
4. **Attribution du rôle Administrateur** : Utilisation de Spatie Laravel Permission avec isolation d'équipe :
   ```php
   app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);
   $user->assignRole('admin');
   ```
5. **Création de la Souscription Trial 14 jours** :
   - Statut : `trialing`
   - Début : maintenant (`now()`)
   - Fin : `now()->addDays(14)`
   - Mécanisme : `ManualBillingProvider` (aucun paiement par carte requis pour démarrer le trial).
6. **Journalisation d'audit** :
   - Événements `organization.created`, `user.created`, `subscription.trial_started`.
   - Masquage automatique des secrets et mots de passe (`redacted`).

> **Garantie de rollback** : Si une étape échoue (contrainte SQL, rôle manquant, exception de souscription), la transaction entière est annulée. Aucune organisation orpheline ou utilisateur sans souscription n'est conservé.

---

## 4. Onboarding Dashboard

Après redirection vers `/dashboard` :
1. **Bannière d'accueil temporaire** : Récapitule le nom de l'organisation, le compte administrateur, le plan choisi et le décompte des 14 jours d'essai.
2. **Widget interactif OnboardingChecklist** :
   - Étape 1 : Organisation créée (validée automatiquement)
   - Étape 2 : Configurer les Directions (`/directions`)
   - Étape 3 : Ajouter les Services (`/services`)
   - Étape 4 : Créer les Types documentaires (`/document-types`)
   - Étape 5 : Importer un premier document (`/documents/create`)
3. **Dismissible** : L'utilisateur peut fermer le guide à tout moment via `POST /dashboard/onboarding/dismiss`, conservé en session (`onboarding_dismissed`).

---

## 5. Flux Enterprise & Modèle Lead

Pour les grandes organisations nécessitant des quotas personnalisés ou un hébergement souverain dédié :
- **Page dédiée** : `GET /enterprise`
- **Formulaire de lead** : `POST /enterprise` et `POST /enterprise/contact` (protégés par rate limiting `throttle:10,1`).
- **Stockage isolé** : Table `leads` (champs : `name`, `company`, `email`, `phone`, `estimated_users`, `needs`, `message`, `status`, `ip_address`, `metadata`).
- **Garantie** : Un lead commercial ne crée **jamais** d'organisation, de compte utilisateur ou d'abonnement sans validation préalable de l'équipe commerciale.

---

## 6. Sécurité & Anti-Abus

- **Rate Limiting** : `throttle:10,1` appliqué sur tous les endpoints POST publics (`/inscription`, `/inscription/organisation`, `/inscription/plan`, `/enterprise`, `/contact`).
- **Séparation Tenant / Platform** :
  - Les utilisateurs des organisations clientes sont authentifiés sur le guard `web` (`auth:web`).
  - L'espace Platform Admin (`/platform`) requiert le guard `platform` (`platform.auth`) et des rôles dédiés (`platform.role`).
  - Aucun utilisateur inscrit via le portail public ne peut accéder à `/platform`.
  - Un compte `PlatformUser` ne peut pas accéder au dashboard d'un tenant.
- **Résolution contrôlée des prix** : L'ID ou slug du Plan est contrôlé côté serveur. Les tentatives d'injection de prix (`amount: 0`, `price: 0`, `max_users: 9999`) sont strictement ignorées.
- **Isolation d'équipe (Teams Spatie)** : Chaque tenant dispose de son propre périmètre de permissions via `setPermissionsTeamId($org->id)`.

---

## 7. Routes Publiques & Privées

| Méthode | Route | Action / Contrôleur | Description |
|---|---|---|---|
| `GET` | `/` | `PublicLandingController@index` | Landing page SaaS avec aperçu tarifs, hero, solutions |
| `GET` | `/tarifs`, `/pricing` | `PricingWebController@index` | Grille tarifaire dynamique (Essentiel, Pro, Enterprise) |
| `GET` | `/fonctionnalites` | `PublicLandingController@features` | Présentation détaillée de la GED, OCR, Workflows |
| `GET` | `/enterprise` | `EnterpriseController@index` | Page Enterprise grands comptes |
| `POST` | `/enterprise` | `EnterpriseController@store` | Envoi d'une demande de démo (`throttle:10,1`) |
| `GET` | `/contact` | `PublicLandingController@contact` | Page de contact commercial et support |
| `POST` | `/contact` | `PublicLandingController@storeContact` | Soumission formulaire contact (`throttle:10,1`) |
| `GET` | `/inscription` | `RegistrationWebController@createAccount` | Étape 1 : Inscription administrateur |
| `POST` | `/inscription` | `RegistrationWebController@storeAccount` | Validation compte & session (`throttle:10,1`) |
| `GET` | `/inscription/organisation` | `RegistrationWebController@createOrganization` | Étape 2 : Informations organisation |
| `POST` | `/inscription/organisation` | `RegistrationWebController@storeOrganization` | Validation organisation & session (`throttle:10,1`) |
| `GET` | `/inscription/plan` | `RegistrationWebController@createPlan` | Étape 3 : Choix de l'offre |
| `POST` | `/inscription/plan` | `RegistrationWebController@storePlan` | Finalisation atomique & Trial 14j (`throttle:10,1`) |
| `POST` | `/dashboard/onboarding/dismiss` | `OnboardingController@dismiss` | Masquage du guide de démarrage |

---

## 8. Tests Automatisés

Le module est validé par une suite complète de tests Feature :
1. `tests/Feature/PublicLandingTest.php` : Landing page, page tarifs, fonctionnalités, page contact et envoi de message.
2. `tests/Feature/RegistrationTest.php` : Parcours complet étape par étape, validation, sessions, création atomique et redirection dashboard.
3. `tests/Feature/TrialSignupTest.php` : Calcul exact du trial 14 jours, statuts `trialing`, dates d'échéance et quotas associés.
4. `tests/Feature/EnterpriseLeadTest.php` : Création de lead commercial, isolation totale (aucune création d'org/sub).
5. `tests/Feature/OnboardingTest.php` : Affichage de la checklist sur le dashboard, masquage synchrone et asynchrone JSON.
6. `tests/Feature/SecurityTest.php` : Tentatives de falsification de prix, unicité email, isolation tenant/platform, isolation inter-organisations.
