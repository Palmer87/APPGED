# Documentation Système d'Abonnement et Facturation (Billing V1) — GEDAPP

## 1. Vue d'ensemble

Le module Billing V1 de GEDAPP structure l'offre commerciale SaaS B2B de la plateforme. Il gère les abonnements par organisation (multi-tenant), les quotas de ressources, la période d'essai gratuite de 14 jours, le suivi d'utilisation en temps réel et l'extensibilité vers des passerelles de paiement électronique (CinetPay, Flutterwave, Stripe).

Aucune passerelle tierce réelle n'est connectée dans cette V1 : le système repose sur un fournisseur par défaut `ManualBillingProvider` implémentant le contrat `BillingProviderInterface`.

---

## 2. Plans d'Abonnement & Tarification

Les montants sont stockés en entiers stricts en FCFA (XOF).

| Propriété / Limite | Essentiel (`essential`) | Professionnel (`professional`) | Entreprise (`enterprise`) |
| :--- | :--- | :--- | :--- |
| **Prix Mensuel** | 19 000 FCFA | 39 000 FCFA | Sur devis (`null`) |
| **Prix Annuel** | 190 000 FCFA *(2 mois offerts)* | 390 000 FCFA *(2 mois offerts)* | Sur devis (`null`) |
| **Économie Annuelle** | 38 000 FCFA | 78 000 FCFA | N/A |
| **Utilisateurs Max** | 5 | 20 | Illimité (`null`) |
| **Stockage Max** | 20 Go (21 474 836 480 octets) | 100 Go (107 374 182 400 octets) | Sur mesure (`null`) |
| **Directions (Départements)** | 3 | 10 | Illimité (`null`) |
| **Types Documentaires** | 15 | 50 | Illimité (`null`) |
| **OCR (pages / mois)** | 100 pages | 1 000 pages | Sur mesure (`null`) |
| **Workflows Avancés** | Non | Oui | Oui |
| **Audit Avancé** | Non | Oui | Oui |
| **Accès API** | Non | Oui | Oui |
| **Support** | Email | Prioritaire | Dédié + SLA |
| **Personnalisation / Migration** | Non | Non | Oui |

> **Convention pour l'illimité** : Les quotas `NULL` en base de données représentent des capacités illimitées ou personnalisées (évite les valeurs magiques ambiguës comme `-1` ou `999999`).

---

## 3. Modèle de Données & Tables

### 3.1 Table `plans`
- `id` (bigint, pk)
- `name`, `slug` (unique)
- `description` (text, nullable)
- `monthly_price`, `annual_price` (integers, FCFA)
- `currency` (string, défaut `'XOF'`)
- `max_users`, `max_storage_bytes`, `max_directions`, `max_document_types`, `max_ocr_pages_month` (integers, nullables)
- Drapeaux de fonctionnalités : `has_api`, `has_workflows`, `has_advanced_audit`, `has_priority_support`, `has_dedicated_support`, `has_sla`, `has_custom_migration`, `has_custom_integrations`, `is_custom`, `is_active`, `sort_order`.

### 3.2 Table `subscriptions`
- `id` (bigint, pk)
- `organization_id` (foreign key -> `organizations.id`, cascade)
- `plan_id` (foreign key -> `plans.id`)
- `billing_cycle` (`monthly`, `annual`)
- `status` (`trialing`, `active`, `past_due`, `cancelled`, `expired`, `suspended`)
- `starts_at`, `trial_starts_at`, `trial_ends_at`, `current_period_starts_at`, `current_period_ends_at`
- `cancelled_at`, `ended_at`, `auto_renew`
- `provider` (`manual`, `stripe`, `cinetpay`, `flutterwave`, etc.)
- `provider_subscription_id`, `metadata` (json)

### 3.3 Table `invoices`
- `id` (bigint, pk)
- `organization_id`, `subscription_id`
- `invoice_number` (ex: `INV-202609-0001`, unique)
- `amount`, `subtotal`, `tax`, `total` (integers)
- `currency` (`XOF`)
- `status` (`draft`, `pending`, `paid`, `failed`, `cancelled`, `refunded`)
- `paid_at`, `due_at`, `billing_reason`
- `provider`, `provider_payment_id`, `pdf_path`, `metadata` (json)

---

## 4. Période d'Essai (14 Jours Gratuits)

1. **Création Automatique** :
   À la création d'une nouvelle `Organization` (via le hook Eloquent `booted()` de [Organization.php](file:///c:/Users/palme/Desktop/GEDAPP/app/Models/Organization.php)), un abonnement d'essai de 14 jours est automatiquement initialisé :
   - Plan par défaut : **Essentiel**
   - Statut : `trialing`
   - `trial_starts_at` : Date du jour
   - `trial_ends_at` : Date du jour + 14 jours
   - `auto_renew` : `false`

2. **Affichage & Avertissements** :
   - Bandeau d'alerte en haut du tableau de bord (`Dashboard/Index.jsx`).
   - À 3 jours ou moins de l'expiration : bandeau ambré/rouge invitant à activer l'abonnement.
   - Calcul des jours restants via `$subscription->trialDaysRemaining()`.

3. **Expiration** :
   - En cas de dépassement de la date d'essai sans souscription, le statut passe en `expired`.
   - **Règle absolue : Aucune donnée n'est supprimée**. Les accès en écriture bloqués demandent une régularisation, mais les documents, utilisateurs et historiques restent intacts.

---

## 5. Contrôle des Limites & Règle Anti-Destruction

### 5.1 Quotas surveillés
1. **Utilisateurs** : Nombre de membres dans l'organisation (`users()`).
2. **Stockage** : Volume cumulé en octets des fichiers de documents (`documents()->sum('file_size')`).
3. **Directions** : Nombre de dossiers de type `FolderType::Department`.
4. **Types documentaires** : Nombre de dossiers de type `FolderType::DocumentType`.
5. **OCR** : Nombre de pages analysées au cours du mois calendaire courant (`documents()->whereMonth('ocr_processed_at')->sum('page_count')`).

### 5.2 Exception `SubscriptionLimitExceededException` (HTTP 403)
Levée automatiquement par [BillingService](file:///c:/Users/palme/Desktop/GEDAPP/app/Services/BillingService.php) avant la création de ressources :
- `assertCanAddUser($organization)`
- `assertCanAddStorage($organization, $fileSizeBytes)`
- `assertCanAddDirection($organization)`
- `assertCanAddDocumentType($organization)`
- `assertCanProcessOcr($organization, $pageCount)`

### 5.3 Politique de Downgrade Non-Destructif
Lorsqu'une organisation passe d'un plan supérieur (ex: Professionnel avec 18 utilisateurs) à un plan inférieur (Essentiel plafonné à 5) :
- Les 18 utilisateurs **sont intégralement conservés**.
- Aucune donnée n'est purgée ni archivée de force.
- Le système passe l'indicateur `is_exceeded: true` pour la ressource concernée.
- Seule l'ajout de nouveaux utilisateurs (le 19ème) est interdit jusqu'au retour sous la limite ou la mise à niveau.

---

## 6. Services & Architecture Métier

### 6.1 `SubscriptionUsageService`
Calcule les métriques d'usage en temps réel sans duplication de colonnes en base :
```php
$usage = app(SubscriptionUsageService::class)->getUsageSummary($organization);
```
Retourne pour chaque ressource :
- `used`
- `limit` (ou `'unlimited'`)
- `percentage`
- `is_approaching` (`true` dès 80 % d'utilisation)
- `is_exceeded`

### 6.2 `BillingService`
Point d'entrée unique de la logique d'abonnement :
- `getCurrentSubscription(Organization $organization): ?Subscription`
- `getCurrentPlan(Organization $organization): ?Plan`
- `changePlan(Organization $organization, Plan $plan, string $cycle): Subscription`
- `cancelSubscription(Organization $organization, bool $immediately): Subscription`
- `resumeSubscription(Organization $organization): Subscription`
- `extendTrial(Subscription $subscription, int $days): Subscription`
- Enregistrement des pistes d'audit : `subscription.trial_started`, `subscription.plan_changed`, `subscription.cancelled`, `subscription.resumed`.
- Déclenchement des notifications administrateurs.

### 6.3 Abstraction Fournisseur (`BillingProviderInterface`)
Contrat permettant de brancher n'importe quel PSP sans altérer la logique applicative :
```php
interface BillingProviderInterface
{
    public function getIdentifier(): string;
    public function createCheckoutSession(Organization $organization, Plan $plan, string $cycle, array $options = []): array;
    public function changeSubscription(Subscription $subscription, Plan $newPlan, string $cycle): Subscription;
    public function cancelSubscription(Subscription $subscription, bool $immediately = false): Subscription;
    public function resumeSubscription(Subscription $subscription): Subscription;
}
```
Implémentation actuelle : [ManualBillingProvider](file:///c:/Users/palme/Desktop/GEDAPP/app/Services/Billing/Providers/ManualBillingProvider.php).

---

## 7. Permissions & Isolation Multi-Tenant

- **Permissions Spatie** créées et associées aux rôles `super-admin`, `admin`, et `manager` :
  - `billing.view` : Consultation de la page d'abonnement et des factures.
  - `billing.manage` : Changement de plan, annulation et reprise.
- **Politique d'autorisation [SubscriptionPolicy](file:///c:/Users/palme/Desktop/GEDAPP/app/Policies/SubscriptionPolicy.php)** :
  - Vérification systématique de l'IDOR : `$user->organization_id === $subscription->organization_id`.
  - Contrôle des droits `billing.view` et `billing.manage`.
- **Isolation des requêtes** :
  Toutes les requêtes de facturation sont scopées par `organization_id`.

---

## 8. Endpoints API REST (v1)

Tous les endpoints API requièrent une authentification Sanctum (`auth:sanctum`) et l'adhésion à une organisation.

| Méthode | URI | Description | Rôles / Droits |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/plans` | Liste des plans publics actifs avec tarifs et limites | Public / Authentifié |
| `GET` | `/api/v1/subscription` | Détails de l'abonnement actif de l'organisation | `billing.view` |
| `GET` | `/api/v1/subscription/usage` | Rapport d'utilisation et alertes de seuils (80%) | `billing.view` |
| `POST` | `/api/v1/subscription/change-plan` | Changement de plan ou de cycle de facturation | `billing.manage` |
| `POST` | `/api/v1/subscription/cancel` | Demande de résiliation de l'abonnement | `billing.manage` |
| `POST` | `/api/v1/subscription/resume` | Reprise d'un abonnement résilié | `billing.manage` |

---

## 9. Interfaces Frontend (Inertia.js + React + Tailwind)

1. **Page Publique Pricing** (`/pricing`) :
   - Switch Mensuel / Annuel avec calcul dynamique de l'économie (2 mois offerts).
   - Cartes comparatives mettant en avant le plan Professionnel (« Le plus choisi »).
   - Matrice détaillée de comparaison des fonctionnalités.
   - Responsive, dark-mode compatible et accessible.

2. **Page Abonnement Organisation** (`/settings/subscription`) :
   - Statut du contrat (`trialing`, `active`, `cancelled`, etc.).
   - Jauges de progression colorées (vert <80%, ambre 80-99%, rouge >=100%).
   - Historique des factures avec montants en FCFA.
   - Boutons de mise à niveau / résiliation avec confirmation modale.

3. **Page Choix de Plan** (`/subscription/choose`) :
   - Grille de sélection pour souscrire ou passer à un plan supérieur.
   - Bouton de devis sur mesure pour l'offre Entreprise.

4. **Widget Dashboard** :
   - Encart discret dans la colonne latérale droite du tableau de bord affichant le plan actuel et l'utilisation.
   - Bannière d'alerte en cas d'approche des 80% ou de fin de période d'essai.

---

## 10. Audit & Notifications

- **Audit Logs** :
  Enregistrés via `AuditService` avec typologie d'événements :
  `subscription.created`, `subscription.trial_started`, `subscription.plan_changed`, `subscription.cancelled`, `subscription.resumed`, `subscription.expired`.
- **Notifications Laravel** :
  - `TrialEndingSoonNotification` (à 3 jours de l'échéance d'essai).
  - `SubscriptionActivatedNotification` (passage à un plan payant).
  - `SubscriptionPlanChangedNotification` (changement de formule).
  - `SubscriptionCancelledNotification` (résiliation demandée).
  - `SubscriptionExpiredNotification` (expiration de période d'essai ou d'abonnement).

---

## 11. Commandes et Maintenance

```bash
# Exécution du seeder des plans et permissions (idempotent)
php artisan db:seed --class=BillingSeeder

# Exécution de la suite complète de tests de facturation
php artisan test --filter=Billing
php artisan test tests/Feature/PlanTest.php tests/Feature/SubscriptionTest.php tests/Feature/BillingServiceTest.php tests/Feature/SubscriptionUsageTest.php tests/Feature/SubscriptionLimitTest.php tests/Feature/TrialTest.php tests/Feature/PricingTest.php tests/Feature/BillingPermissionTest.php tests/Feature/BillingTenantIsolationTest.php tests/Feature/SubscriptionApiTest.php

# Validation du formattage de code Pint
vendor/bin/pint --dirty --format agent

# Compilation des assets frontend Vite
npm run build
```
