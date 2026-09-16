# Documentation Technique — Module 15 : Dashboard V1

## 1. Vue d'ensemble et Architecture

Le **Dashboard V1** offre un point d'entrée centralisé, ergonomique et hautement sécurisé pour les utilisateurs du SaaS GED. Il agrège en temps réel les indicateurs documentaires, organisationnels et collaboratifs sans jamais compromettre l'isolation multi-tenant ni les contrôles d'accès ACL.

### Schéma d'Architecture

```text
HTTP Request: GET /dashboard [?period=7d|30d|90d|12m]
                      │
                      ▼
             DashboardController
                      │
                      ▼
               DashboardService
   ┌──────────────────┼─────────────────────────┐
   │                  │                         │
   ▼                  ▼                         ▼
AccessControlService  WorkflowService           AuditLogService
(Documents, Dossiers) (Workflows assignés)      (Activité récente)
   │                  │                         │
   ▼                  ▼                         ▼
FavoriteService       NotificationService       RecentDocumentService
(Favoris de l'user)   (Notifications non lues) (Consultations récentes)
                      │
                      ▼
               Payload Structuré
                      │
         ┌────────────┴────────────┐
         ▼                         ▼
  Blade UI View              Inertia React Page
(dashboard.blade.php)       (Dashboard/Index.jsx)
```

---

## 2. Statistiques & Indicateurs Clés

Le service `DashboardService` calcule de manière consolidée :
1. **Documents actifs** : nombre total de documents non archivés et non supprimés auxquels l'utilisateur a effectivement accès (via ACL directe, groupe, dossier parent ou partage actif).
2. **Documents archivés** : nombre de documents de l'organisation placés en archive (`archived_at is not null`) accessibles en consultation.
3. **Documents en corbeille** : documents de l'organisation placés en corbeille (`deleted_at is not null`).
4. **Dossiers accessibles** : dossiers au sein de l'organisation de l'utilisateur visibles selon les permissions `folders.view` et les règles d'ACL spécifiques.
5. **Favoris** : nombre de documents marqués en favoris par l'utilisateur connecté et toujours accessibles.
6. **Workflows en attente** : workflows en cours pour lesquels l'étape courante requiert expressément l'approbation de l'utilisateur ou de l'un de ses groupes.
7. **Notifications non lues** : compte en direct des notifications non acquittées de l'utilisateur.
8. **Stockage consommé** : total cumulé en octets des fichiers des documents actifs, converti dynamiquement en unité lisible (Ko, Mo, Go).

---

## 3. Sécurité & Règles d'Isolation Multi-Tenant

- **Contexte serveur strict** : Le contrôleur n'accepte aucun paramètre `user_id` ou `organization_id` issu du client. L'utilisateur authentifié est systématiquement résolu via `Auth::user()`.
- **Tenant Isolation** :
  - Tout document, dossier, workflow, journal d'audit ou notification renvoyé est rigoureusement restreint à `organization_id === user->organization_id`.
  - Aucune information d'une autre organisation n'est accessible ni comptabilisée.
- **AccessControlService & ACL** :
  - L'affichage des documents applique le scope `AccessControlService::applyAccessScope`. Si un utilisateur ne possède pas les droits de lecture sur un document, ce dernier n'apparaît ni dans les compteurs, ni dans les documents récents, ni dans les favoris, ni dans les workflows.
  - La révocation des droits d'un document supprime instantanément ce document de la vue de l'utilisateur, même s'il avait été consulté ou favorisé précédemment.
- **Rôle Super-Admin** :
  - Le `super-admin` bénéficie d'une visibilité transversale via `Gate::before`, mais ses compteurs restent cohérents avec la structure globale du système sans mélanger les organisations de façon incohérente.
- **Anti-IDOR** :
  - Les identifiants passés en route (`/documents/{document}/favorite`) sont validés pour s'assurer de l'appartenance à la même organisation et des droits de consultation effectifs.

---

## 4. Modules Collaboratifs & Intégrations

### Favoris (`FavoriteService`)
- Permet à l'utilisateur de marquer des documents prioritaires via `toggleFavorite()`.
- Persistance dans la table `document_favorites` avec unicité `(user_id, document_id)`.
- Traçabilité complète des ajouts et retraits dans `AuditService`.

### Documents Récents (`RecentDocumentService`)
- Analyse chronologique des événements `document.previewed`, `document.viewed`, `document.created`, `document.updated` dans `AuditLog` pour l'utilisateur.
- Complétion dynamique avec les derniers documents modifiés accessibles dans l'organisation.
- Application stricte de la portée ACL pour masquer tout document devenu inaccessible.

### Workflows & Approbations
- Détection des instances de workflow en statut `InProgress` dont l'étape active (`currentStep`) a pour approbateur :
  - Soit l'utilisateur directement (`approver_type = 'user'` et `approver_user_id = $user->id`).
  - Soit un des groupes de l'utilisateur (`approver_type = 'group'` et `approver_group_id IN ($userGroupIds)`).
- Indicateur visuel d'alerte (`Action requise`) dès qu'une tâche est en souffrance.

### Activité Récente & Traçabilité
- Extraction des 8 derniers événements d'audit de l'organisation via `AuditLog`.
- Masquage des détails sensibles (`password`, tokens, etc.) et formulation de libellés conviviaux en français.

---

## 5. Visualisations & Graphiques Analytiques

Le Dashboard intègre trois axes d'analyse visuelle :
1. **Répartition par type** : Barres de distribution pour les extensions clés (`PDF`, `Images`, `Word/DOCX`, `Excel/XLSX`, `Autres`).
2. **Répartition par statut** : Proportions relatives entre documents actifs, archivés et en corbeille.
3. **Chronologie d'activité (Timeline)** :
   - Graphique en barres/histogramme SVG dynamique réactif.
   - Périodes supportées : `7d` (7 jours), `30d` (30 jours), `90d` (90 jours, avec sous-échantillonnage pour lisibilité), `12m` (12 mois glissants).
   - Validation stricte de la période côté serveur contre une liste blanche.

---

## 6. Performance & Optimisations

- **Prévention du N+1** : Utilisation systématique de `with(['folder', 'categories', 'tags'])`, `whereExists`, `whereIn` et d'agrégats SQL directs (`count()`, `sum()`).
- **Pas de requêtes superflues** : Regroupement des statistiques principales sur une base de requête réutilisée par clonage.
- **Cache organisationnel (prêt pour la production)** :
  - Si activé ultérieurement, la clé de cache doit impérativement être segmentée par tenant et utilisateur : `dashboard:{organization_id}:{user_id}:{period}` avec une expiration courte (ex: 5 minutes) et invalidation lors des mutations documentaires.
