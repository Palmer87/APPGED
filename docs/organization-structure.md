# Architecture Organisationnelle et Modèle d'Autorisation V2

## 1. Vue d'Ensemble

GEDAPP V2 introduit une séparation claire et modulaire entre :
- **La structure organisationnelle** (où travaille l'utilisateur),
- **Les rôles applicatifs** (ce que l'utilisateur est habilité à faire),
- **Les périmètres d'accès** (sur quelles zones organisationnelles ses habilitations s'appliquent),
- **Les listes de contrôle d'accès (ACL) et partages** (les dérogations et exceptions granulaires).

```
                 ORGANISATION
                       │
          ┌────────────┴────────────┐
          │                         │
      STRUCTURE                 UTILISATEURS
          │                         │
      Direction              Service principal
          │                         │
       Service                Services associés
          │                         │
   Type documentaire               Rôles
          │                         │
       Document              Périmètres d'accès
          │                         │
     Métadonnées                    │
                                    │
                                    ▼
                              AUTORISATIONS
                                    │
                  ┌─────────────────┼────────────────┐
                  ▼                 ▼                ▼
               Service           Dossier          Document
                                    │
                                    ▼
                                  ACL
```

---

## 2. Distinction Fondamentale

| Concept | Définition | Question Répondue | Système Technique |
| :--- | :--- | :--- | :--- |
| **SERVICE** | Unité organisationnelle de rattachement métier (ex. Comptabilité, Trésorerie). | *Où travaille l'utilisateur au quotidien ?* | Modèle `Service`, table `services`, pivot `service_user` |
| **RÔLE** | Ensemble de capacités et permissions applicatives (ex. Super-Admin, Admin, Collaborateur, Validateur). | *Quelles actions l'utilisateur peut-il exécuter ?* | Spatie Laravel Permission (`roles`, `permissions`) avec Teams |
| **GROUPE** | Regroupement transversal d'utilisateurs pour les workflows ou droits partagés (ex. Commission des Achats). | *Avec qui l'utilisateur collabore-t-il fonctionnellement ?* | Modèle `Group`, table `groups`, pivot `group_user` |
| **PÉRIMÈTRE (Access Scope)** | Cible d'application globale ou départementale des rôles d'un utilisateur. | *Sur quel pan de l'organisation ses droits s'étendent-ils ?* | Modèle `AccessScope`, table `access_scopes` |
| **ACL (Exceptions)** | Droits précis accordés ou révoqués sur un dossier ou un document individuel. | *Quelles exceptions ciblées priment sur la règle générale ?* | Modèles `FolderPermission`, `DocumentPermission`, `DocumentShare` |

> [!IMPORTANT]
> **Règle d'or :** `SERVICE ≠ RÔLE ≠ GROUPE ≠ PÉRIMÈTRE ≠ ACL`.
> Un service n'accorde jamais de droit par lui-même. C'est le rôle de l'utilisateur qui définit ses actions possibles, et son périmètre d'accès qui définit les documents/services sur lesquels ces actions sont permises.

---

## 3. Composants de l'Architecture

### 3.1. Organisation (Tenant)
- Racine stricte du multi-tenant.
- Chaque direction, service, dossier, document, rôle et utilisateur est strictement partitionné par `organization_id`.
- Aucune fuite d'information transversale (protection stricte contre l'IDOR).

### 3.2. Direction
- Direction générale ou direction métier rattachée à une organisation (ex: *Direction Administrative & Financière (DAF)*, *Direction des Ressources Humaines (DRH)*).
- Synchronisée avec un dossier racine de type `department` pour assurer la continuité d'arborescence physique et logique.
- Comporte un code (ex: `DAF`, `DRH`), un nom, une description et un indicateur d'activation.

### 3.3. Service
- Division opérationnelle appartenant à une Direction (ex: *Comptabilité*, *Finance*, *Paie*).
- Synchronisé avec un sous-dossier de type `service` sous le dossier de la Direction parente.
- Sert d'ancrage documentaire : les documents et types documentaires y sont directement associés.

### 3.4. Utilisateur
- Collaborateur de l'organisation.
- Dispose d'un **Service Principal** (`primary_service_id`) déterminant :
  - Le contexte par défaut de son tableau de bord,
  - Le pré-remplissage des formulaires d'importation et de recherche,
  - La navigation métier contextualisée.
- Peut disposer de zéro ou plusieurs **Services Associés** (`service_user`), permettant l'appartenance transverse (ex: un comptable qui supervise également la trésorerie).

### 3.5. Rôles et Permissions (Spatie)
- Gérés via Spatie Laravel Permission en mode Multi-Team (`team_foreign_key = organization_id`).
- Rôles standards :
  - `super-admin` : Accès plateforme global,
  - `admin` : Administrateur de l'organisation, gestion complète de la structure et des utilisateurs,
  - `collaborator` : Utilisateur standard restreint à ses périmètres et ACL.

### 3.6. Périmètres d'Accès (`AccessScope`)
- Définissent le rayon d'action de l'utilisateur :
  - `organization` : Accès à l'ensemble des documents de l'organisation,
  - `direction` : Accès à tous les services et documents de la direction ciblée,
  - `service` : Accès uniquement aux documents du service ciblé,
  - `document_type` : Accès aux documents d'un type documentaire particulier,
  - `folder` : Accès restreint à un dossier spécifique et ses sous-dossiers,
  - `document` : Accès ciblé à un document spécifique.

### 3.7. Types Documentaires et Dossiers
- Un type documentaire est représenté par un dossier spécialisé (`folder_type = FolderType::DocumentType`).
- Il peut définir des schémas de métadonnées spécifiques (ex: N° de facture, Fournisseur, Montant HT, TVA, Date échéance).
- Lors de l'import d'un document, l'utilisateur choisit simplement son type documentaire dans son contexte de service sans avoir à naviguer dans l'arborescence technique.

### 3.8. Documents
- Contenu fichier hébergé sur Cloudflare R2 ou disque privé sécurisé.
- Rattaché directement à `organization_id`, `direction_id`, `service_id`, et `folder_id`.
- Supporte le versioning, l'OCR Tesseract, les workflows d'approbation, les partages temporaires et les audits complets.

---

## 4. Priorité d'Évaluation des Droits (AccessControlService)

Lorsqu'un utilisateur tente d'accéder à un document ou un dossier (lecture, téléchargement, modification) :

1. **Super Admin** : Si l'utilisateur possède le rôle `super-admin`, accès accordé sans restriction.
2. **Isolation Tenant** : Si `user.organization_id !== resource.organization_id`, rejet immédiat (`403 / 404`).
3. **État de la ressource** : Si la ressource est supprimée (soft-deleted) et non sollicitée en mode corbeille, accès refusé.
4. **Admin d'Organisation** : Un administrateur de l'organisation dispose de la vue et gestion sur toutes les ressources de son organisation.
5. **ACL directe Document / Dossier** : Si une entrée `DocumentPermission` ou `FolderPermission` cible explicitement l'utilisateur avec la permission demandée, accès accordé.
6. **ACL de Groupe** : Si un des groupes de l'utilisateur possède l'ACL requise, accès accordé.
7. **Héritage Dossier** : Si le dossier parent accorde l'accès, celui-ci descend aux documents enfants.
8. **Partage Explicite (DocumentShare)** : Si un lien de partage actif et valide (non expiré) est consenti à l'utilisateur ou à son groupe pour cette action, accès accordé.
9. **Périmètre d'Accès (AccessScope)** : Si la ressource se situe dans l'un des périmètres actifs de l'utilisateur (`organization`, `direction`, `service`, `document_type`), accès accordé.
10. **Refus par défaut** : Si aucune des conditions ci-dessus n'est remplie, l'accès est strictement refusé.

---

## 5. Endpoints API et Routes Web

### API REST (`/api/v1`)
- `GET /api/v1/directions` : Liste des directions de l'organisation
- `POST /api/v1/directions` : Création d'une direction
- `GET /api/v1/directions/{direction}` : Détails d'une direction
- `PUT /api/v1/directions/{direction}` : Mise à jour d'une direction
- `DELETE /api/v1/directions/{direction}` : Suppression d'une direction
- `GET /api/v1/directions/{direction}/services` : Services d'une direction
- `GET /api/v1/services` : Liste de tous les services accessibles
- `POST /api/v1/services` : Création d'un service sous une direction
- `GET /api/v1/services/{service}` : Détails d'un service
- `PUT /api/v1/services/{service}` : Mise à jour d'un service
- `DELETE /api/v1/services/{service}` : Suppression d'un service
- `GET /api/v1/services/{service}/users` : Utilisateurs rattachés au service

### Routes Web Inertia
- `/directions` : Interface de gestion des directions
- `/services` : Interface de gestion des services et assignation des utilisateurs
- `/access-scopes` : Gestion des périmètres d'accès
- `/users` : Création et modification enrichies avec direction, service principal et services associés
- `/documents/create` : Importation documentaire contextualisée
- `/search` : Recherche documentaire filtrable par direction et service
- `/dashboard` : Tableau de bord contextualisé selon l'espace de travail
