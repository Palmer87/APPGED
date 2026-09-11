# Architecture fonctionnelle — GED SaaS

La GED sera construite comme un produit SaaS professionnel, avec une architecture évolutive. Le modèle de données complet (MCD) précède le MLD PostgreSQL puis les migrations Laravel.

## Structure principale

```text
                         ┌──────────────────────┐
                         │    ORGANISATION       │
                         └──────────┬───────────┘
                                    │
              ┌─────────────────────┼─────────────────────┐
              │                     │                     │
              ▼                     ▼                     ▼
        UTILISATEURS             GROUPES              RÔLES
              │                     │                     │
              └──────────────┬──────┴─────────────────────┘
                             ▼
                       PERMISSIONS
                             │
                             ▼
                    ┌─────────────────┐
                    │    DOSSIERS     │
                    │                 │
                    │  ├─ Sous-dossier│
                    │  ├─ Sous-dossier│
                    │  └─ Documents   │
                    └────────┬────────┘
                             │
                             ▼
                       DOCUMENTS
                             │
              ┌──────────────┼──────────────┐
              ▼              ▼              ▼
          VERSIONS         TAGS        MÉTADONNÉES
              │
              ▼
         HISTORIQUE
```

## Modules associés aux documents

```text
Documents
   │
   ├── 🔗 Partages
   ├── ⭐ Favoris
   ├── 💬 Commentaires
   ├── 🔔 Notifications
   ├── ✅ Workflow
   ├── 🗄️ Archivage
   └── 🗑️ Corbeille
```

## Principes structurants

- Les permissions peuvent être accordées à un utilisateur, un groupe, un dossier ou directement à un document.
- Les permissions de dossier peuvent être héritées par les sous-dossiers et les documents.
- L’isolation des données est garantie par l’organisation, afin de permettre une exploitation SaaS multi-tenant.
- Le MCD sera décliné en MLD PostgreSQL, puis en migrations Laravel.

## Socle technique cible

```text
Laravel 12
PHP 8.4
PostgreSQL
Inertia React
Tailwind CSS
Spatie Permission
S3 / Cloudflare R2
```
