# MODULE 14 — Notifications V1

## 1. Objectif & Vue d'ensemble

Le module de notifications permet d'informer en temps réel ou de manière asynchrone les utilisateurs des événements majeurs survenus dans la GED SaaS (partage de document, nouvelle version, archivage, restauration, commentaires, validation de workflow).

Il repose sur le système natif de notifications de Laravel (`Illuminate\Notifications\Notifiable`, `Illuminate\Notifications\Notification`, `Illuminate\Notifications\DatabaseNotification`) tout en garantissant :
* Une étanchéité multi-tenant absolue (aucun risque de fuite cross-tenant).
* Une séparation stricte entre notification et permission (une notification n'accorde jamais de droit d'accès au document).
* L'absence totale de données sensibles dans les payloads stockés.
* La gestion fine des préférences utilisateurs (activation/désactivation base de données et email).

---

## 2. Schéma de la Base de Données

### A. Table `notifications` (Standard Laravel)

```sql
CREATE TABLE notifications (
    id UUID PRIMARY KEY,
    type VARCHAR(255) NOT NULL,
    notifiable_type VARCHAR(255) NOT NULL,
    notifiable_id BIGINT NOT NULL,
    data JSONB NOT NULL,
    read_at TIMESTAMP WITHOUT TIME ZONE NULL,
    created_at TIMESTAMP WITHOUT TIME ZONE NULL,
    updated_at TIMESTAMP WITHOUT TIME ZONE NULL
);

CREATE INDEX notifications_notifiable_type_notifiable_id_index ON notifications(notifiable_type, notifiable_id);
CREATE INDEX notifications_read_at_index ON notifications(read_at);
CREATE INDEX notifications_created_at_index ON notifications(created_at);
```

### B. Table `notification_preferences`

```sql
CREATE TABLE notification_preferences (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    organization_id BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    notification_type VARCHAR(100) NOT NULL,
    database_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    email_enabled BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP WITHOUT TIME ZONE NULL,
    updated_at TIMESTAMP WITHOUT TIME ZONE NULL,
    CONSTRAINT notification_preferences_user_type_unique UNIQUE (user_id, notification_type)
);

CREATE INDEX notification_preferences_org_type_index ON notification_preferences(organization_id, notification_type);
```

---

## 3. Types de Notifications & Données Structurées

### A. Dictionnaire des types normalisés

| Type | Classe | Événement déclencheur |
|---|---|---|
| `document.shared` | `DocumentSharedNotification` | Partage direct avec un utilisateur ou un groupe. |
| `document.version_created` | `DocumentVersionCreatedNotification` | Dépôt d'une nouvelle version incrémentale. |
| `document.share_revoked` | `DocumentShareRevokedNotification` | Révocation d'un partage existant. |
| `document.commented` | `DocumentCommentedNotification` | Ajout d'un commentaire sur un document. |
| `document.workflow` | `DocumentWorkflowNotification` | Évolution du statut de validation d'un document. |
| `document.archived` | `DocumentArchivedNotification` | Archivage d'un document actif. |
| `document.restored` | `DocumentRestoredNotification` | Restauration d'un document depuis la corbeille. |

### B. Format des données (`data`)

Le contenu JSON de la colonne `data` est strictement standardisé et exempt de secrets :

```json
{
    "type": "document.shared",
    "title": "Document partagé",
    "message": "Le document 'Contrat.pdf' a été partagé avec vous par Jean Kouassi.",
    "document_id": 42,
    "document_name": "Contrat.pdf",
    "actor_id": 7,
    "actor_name": "Jean Kouassi",
    "permission": "download",
    "url": "/documents/42"
}
```

> [!IMPORTANT]
> Ne sont jamais stockés dans `data` : mots de passe, jetons, secrets, chemins physiques de stockage sur disque (`storage_path`), ni le contenu brut du document.

---

## 4. Architecture des Services

### A. `NotificationPreferenceService`
Gère les préférences de canal par utilisateur et par type de notification :
* `getPreferences(User $user)`
* `updatePreference(User $user, string $type, bool $database, bool $email)`
* `isDatabaseEnabled(User $user, string $type): bool` (défaut : `true`)
* `isEmailEnabled(User $user, string $type): bool` (défaut : `false`)

### B. `NotificationService`
Point d'entrée pour la diffusion sécurisée des notifications :
* **Déduplication** : si un utilisateur est ciblé directement et fait également partie d'un groupe destinataire, il ne reçoit qu'une seule notification.
* **Exclusion de l'acteur** : l'utilisateur à l'origine de l'action ne s'auto-notifie jamais.
* **Barrière Multi-Tenant** : chaque destinataire doit impérativement appartenir à l'organisation du document. Les membres extérieurs sont ignorés.
* **Méthodes spécialisées** :
  * `notifyDocumentShared(Document $document, User $actor, User|Group $target, string $permission)`
  * `notifyDocumentShareRevoked(Document $document, User $actor, User|Group $target)`
  * `notifyDocumentVersionCreated(Document $document, User $actor, DocumentVersion $version)`
  * `notifyDocumentArchived(Document $document, User $actor)`
  * `notifyDocumentRestored(Document $document, User $actor)`

---

## 5. Notification vs ACL & Sécurité Multi-Tenant

1. **La notification n'est pas un droit d'accès** :
   Une notification n'est qu'un message informatif pointant vers une URL (`/documents/{id}`). L'accès réel au document reste toujours conditionné à l'évaluation en direct de `DocumentPolicy` et `AccessControlService`.
2. **Partage révoqué ou expiré** :
   Si un partage est révoqué ou expire, la notification historique reste présente dans le centre de notifications, mais cliquer sur le lien déclenche immédiatement un `403 Forbidden` ou `404 Not Found`.
3. **Indépendance vis-à-vis des Audit Logs** :
   La suppression d'une notification par l'utilisateur n'affecte en rien la table `audit_logs`, qui demeure immuable.

---

## 6. Endpoints API / Web

| Méthode | URI | Nom de Route | Description |
|---|---|---|---|
| `GET` | `/notifications` | `notifications.index` | Liste paginée (1 à 100, défaut 25, tri décroissant) avec filtre optionnel `?unread=1` et compteur `unread_count`. |
| `GET` | `/notifications/unread` | `notifications.unread` | Retour rapide du compteur et des notifications non lues. |
| `POST` | `/notifications/{id}/read` | `notifications.read` | Marque la notification comme lue (idempotent). Contrôle d'appartenance strict (`403` si tiers). |
| `POST` | `/notifications/read-all` | `notifications.read_all` | Marque toutes les notifications non lues de l'utilisateur comme lues. |
| `DELETE` | `/notifications/{id}` | `notifications.destroy` | Supprime la notification de l'utilisateur connecté (`403` si tiers). |
| `GET` | `/notifications/preferences` | `notifications.preferences.index` | Consulte les préférences de l'utilisateur. |
| `PUT` | `/notifications/preferences` | `notifications.preferences.update` | Met à jour une préférence utilisateur. |

---

## 7. Préparation Mobile & Queues Asynchrones

* **Compatibilité API Sanctum / React Native** : les contrôleurs retournent des réponses JSON structurées directement réutilisables par une application cliente mobile ou SPA.
* **Queues (ShouldQueue)** : les classes de notification utilisent le trait `Queueable`. L'envoi des notifications par email peut être différé via la file d'attente Laravel sans bloquer la requête HTTP.
