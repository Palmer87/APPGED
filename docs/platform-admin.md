# GEDAPP — Documentation Platform Admin & Propriétaire SaaS (Module 21)

## 1. Architecture Globale

Le module 21 introduit une séparation stricte entre le domaine SaaS global (**Platform Domain**) et les espaces clients multi-tenants (**Tenant Domain**) :

```
GEDAPP SaaS
├── PLATFORM DOMAIN (/platform)
│   ├── Guard: 'platform' (Provider: PlatformUser)
│   ├── Utilisateurs Internes SaaS (Owner, Admin, Support, Billing)
│   ├── Gestion globale des Organisations (Active, Suspended)
│   ├── Plans tarifaires & Grilles de quotas (Essential, Professional, Enterprise)
│   ├── Abonnements & Historique des cycles
│   ├── Gestion des Périodes d'Essai (14 jours, extension, résiliation, conversion)
│   ├── Quotas & Métriques d'utilisation globale (Users, Storage, OCR, Directions)
│   ├── Facturation manuelle & Traçabilité des paiements (XOF)
│   ├── Support technique & Billetterie client
│   ├── Piste d'audit SaaS inviolable et assainie
│   └── Paramètres système & Politiques d'inscription
│
└── TENANT DOMAIN (/dashboard, /documents, /admin, etc.)
    └── Guard: 'web' (Provider: User lié à organization_id)
        ├── Spatie Permission avec Teams (organization_id)
        ├── Direction → Service → Périmètres d'accès
        └── Dossiers techniques, Documents, Versions, OCR, Workflows
```

---

## 2. Authentification & Sécurité

### Isolation Totale des Guards
- **Guard `platform`** :
  - Modèle : `App\Models\PlatformUser` (table `platform_users`).
  - Session séparée du guard client `web`.
  - Mot de passe haché (Argon2id/Bcrypt), protection contre le brute-force.
- **Blocage des Utilisateurs Clients** :
  - Aucun utilisateur `User` d'une organisation cliente ne peut s'authentifier sur `/platform/login`.
  - Toute tentative d'accès non autorisé à `/platform/*` redirige vers `/platform/login` ou renvoie une erreur HTTP 403 Forbidden.

### Rôles Platform et Matrice des Permissions
Quatre rôles internes sont définis sur `PlatformUser` :

| Rôle | Périmètre d'accès | Droits accordés |
| :--- | :--- | :--- |
| `platform_owner` | Accès absolu | Tous les modules, paramètres globaux, gestion d'équipe plateforme, suspension d'organisation, audit complet. |
| `platform_admin` | Administration opérationnelle | Organisations, plans, abonnements, trials, quotas/usage, annuaire clients, audit. Pas d'accès aux configurations système sensibles. |
| `platform_support` | Assistance & Diagnostic | Annuaire clients, vue des organisations, billetterie support technique, assistance utilisateur contrôlée. Pas d'accès aux flux financiers. |
| `platform_billing` | Finance & Facturation | Plans tarifaires, abonnements, factures, encaissements manuels, métriques de revenus (MRR/ARR). |

---

## 3. Gestion des Organisations & Suspension Sécurisée

### Statut d'Organisation vs Statut d'Abonnement
- **Organization** :
  - `active` : L'organisation et tous ses collaborateurs accèdent normalement à GEDAPP.
  - `suspended` : L'organisation est bloquée au niveau de la passerelle middleware `EnsureOrganizationActive`.
- **Règle absolue de non-destruction lors de la suspension** :
  - Les utilisateurs reçoivent un code HTTP 403 explicitant la suspension.
  - Les documents, métadonnées, historiques et fichiers Cloudflare R2 / S3 restent **100 % intacts**.
  - L'action de suspension enregistre un motif obligatoire dans la piste d'audit.
  - La réactivation restaure instantanément l'accès sans altération des droits Spatie.

---

## 4. Plans Tarifaires, Quotas & Consommation

### Grille des Plans V1 (Stockée en Base de Données)
1. **Essential** :
   - 19 000 FCFA / mois (190 000 FCFA / an)
   - 5 utilisateurs max, 20 Go de stockage, 3 directions, 15 types documentaires, 100 pages OCR / mois.
2. **Professional** :
   - 39 000 FCFA / mois (390 000 FCFA / an)
   - 20 utilisateurs max, 100 Go de stockage, 10 directions, 50 types documentaires, 1 000 pages OCR / mois.
3. **Enterprise** :
   - Sur mesure (utilisateurs, stockage et OCR extensibles).

### Service Centralisé des Quotas (`QuotaService`)
Le service `App\Services\QuotaService` calcule dynamiquement :
- L'utilisation réelle : utilisateurs actifs, espace disque consommé par les fichiers actifs (hors corbeille), nombre de directions créées, types documentaires, pages OCR traitées durant le mois calendaire.
- Les seuils d'alerte :
  - **80 %** : Vigilance (`info`).
  - **90 %** : Seuil critique (`warning`).
  - **100 %** : Quota atteint ou dépassé (`danger`).

---

## 5. Périodes d'Essai (Trials de 14 Jours)

- Tout nouvel enregistrement bénéficie d'une période d'essai de 14 jours (`trialing`).
- L'interface affiche le décompte exact en jours ouvrés restants ou la mention "Expiré".
- Le Platform Owner/Admin peut :
  - **Prolonger** l'essai d'un nombre déterminé de jours (avec enregistrement d'audit).
  - **Terminer** l'essai immédiatement.
  - **Convertir** l'essai en abonnement payant actif (mensuel ou annuel).

---

## 6. Facturation & Architecture des Paiements

### Abstraction `BillingProviderInterface`
```php
interface BillingProviderInterface
{
    public function getName(): string;
    public function createCheckout(Subscription $subscription, Plan $plan, string $cycle, array $options = []): array;
    public function changeSubscription(Subscription $subscription, Plan $newPlan, string $cycle): Subscription;
    public function cancelSubscription(Subscription $subscription, bool $immediately = false): Subscription;
    public function resumeSubscription(Subscription $subscription): Subscription;
}
```

### Fournisseur V1 : `ManualBillingProvider`
Permet la gestion administrative des factures et l'enregistrement des règlements hors-ligne (virement bancaire, chèque d'entreprise, espèces, validation manuelle d'un transfert mobile).

### Extension Future (V2)
Pour intégrer de nouveaux prestataires (Stripe, Wave, Orange Money, MTN MoMo) :
1. Créer une classe implémentant `BillingProviderInterface` (ex: `WaveBillingProvider`).
2. Configurer le webhook entrant pour marquer automatiquement les factures comme `paid` et injecter l'enregistrement dans la table `payments`.

---

## 7. Assistance & Support Client

- Module de tickets rattaché à l'organisation et au collaborateur demandeur.
- Priorités : `low`, `normal`, `high`, `urgent`.
- Statuts : `open`, `in_progress`, `resolved`, `closed`.
- Attribution des tickets aux agents `PlatformUser` disposant du rôle `platform_support` ou `platform_admin`.

---

## 8. Piste d'Audit Plateforme (`PlatformAuditLog`)

Toutes les opérations d'administration SaaS sont tracées dans `platform_audit_logs` :
- `platform.organization.suspended` / `platform.organization.reactivated`
- `platform.plan.created` / `platform.plan.updated` / `platform.plan.disabled`
- `platform.subscription.changed` / `platform.subscription.cancelled`
- `platform.trial.extended` / `platform.trial.ended`
- `platform.invoice.paid` / `platform.payment.recorded`
- `platform.support.ticket_created` / `platform.support.ticket_updated`
- `platform.settings.updated`

### Règle de Sécurité et Assainissement
La méthode `PlatformAuditService::sanitize()` masque systématiquement :
- `password`, `password_confirmation`
- `token`, `access_token`, `refresh_token`
- `secret`, `r2_secret`, `s3_secret`
- Remplacés par `***REDACTED***` avant toute persistance en base de données.

---

## 9. Comptes SaaS Pré-configurés (Seeder)

Lors du déploiement initial (`PlatformUserSeeder`) :
- **Propriétaire (Owner)** : `owner@gedapp.com` (Rôle : `platform_owner`)
- **Administrateur** : `admin@gedapp.com` (Rôle : `platform_admin`)
- **Support Client** : `support@gedapp.com` (Rôle : `platform_support`)
- **Facturation** : `billing@gedapp.com` (Rôle : `platform_billing`)
- Mot de passe par défaut : `Password123!` (À modifier impérativement lors du premier démarrage en production).
