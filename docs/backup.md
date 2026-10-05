# GEDAPP — Politique et Procédures de Sauvegarde & Reprise d'Activité (V1)

Ce document établit la stratégie de sauvegarde, de rétention et de Plan de Reprise d'Activité (PRA / DRP) pour **GEDAPP SaaS**.

---

## 1. Objectifs de Continuité d'Activité

- **RPO (Recovery Point Objective)** : Maximum 1 heure (grâce aux sauvegardes PostgreSQL quotidiennes et à l'archivage WAL).
- **RTO (Recovery Time Objective)** : Moins de 30 minutes pour restaurer l'application et la base de données sur une infrastructure de secours.

---

## 2. Sauvegarde de la Base de Données PostgreSQL

### 2.1 Script de Sauvegarde Quotidienne Chiffrée
Fichier `/usr/local/bin/backup-gedapp-db.sh` :

```bash
#!/bin/bash
set -e

BACKUP_DIR="/var/backups/gedapp/postgres"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/gedapp_db_${DATE}.sql.gz"
RETENTION_DAYS=30

mkdir -p $BACKUP_DIR

echo "[$(date)] Démarrage du dump PostgreSQL..."

# Dump compressé de la base de données
sudo -u postgres pg_dump -d gedapp -F c -b -v -f "${BACKUP_FILE}"

# Ajustement des permissions (lecture restreinte à root / backup)
chmod 600 "${BACKUP_FILE}"

echo "[$(date)] Sauvegarde réussie : ${BACKUP_FILE}"

# Purge automatique des sauvegardes locales de plus de 30 jours
find "${BACKUP_DIR}" -type f -name "gedapp_db_*.sql.gz" -mtime +${RETENTION_DAYS} -delete
echo "[$(date)] Purge des sauvegardes antérieures à ${RETENTION_DAYS} jours terminée."
```

Rendre le script exécutable et planifier via Cron :
```bash
sudo chmod +x /usr/local/bin/backup-gedapp-db.sh

# Planification quotidienne à 02:00 du matin
sudo crontab -e
# Ajouter :
0 2 * * * /usr/local/bin/backup-gedapp-db.sh >> /var/log/gedapp_backup.log 2>&1
```

### 2.2 Procédure de Restauration PostgreSQL
```bash
# 1. Mettre l'application en maintenance
cd /var/www/gedapp && php artisan down

# 2. Terminer les connexions actives sur la base cible
sudo -u postgres psql -c "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = 'gedapp' AND pid <> pg_backend_pid();"

# 3. Restaurer le dump
sudo -u postgres pg_restore -d gedapp -c -v /var/backups/gedapp/postgres/gedapp_db_YYYYMMDD_HHMMSS.sql.gz

# 4. Exécuter les éventuelles migrations de schéma
php artisan migrate --force

# 5. Réinitialiser les caches et rouvrir l'application
php artisan optimize
php artisan up
```

---

## 3. Stratégie de Sauvegarde des Fichiers & Documents (Cloudflare R2)

GEDAPP sépare les métadonnées relationnelles (PostgreSQL) et les binaires de documents (Cloudflare R2).

### 3.1 Protection & Versioning R2
- **Versioning d'objets R2** : Activé sur le bucket de production pour garantir l'immutabilité et empêcher toute suppression accidentelle ou malveillante.
- **Règles de cycle de vie (Lifecycle Rules)** : Conservation des versions supprimées pendant 90 jours dans un état soft-deleted récupérable via l'API Cloudflare.
- **Replication Inter-Régionale** : Activation optionnelle de la réplication de bucket pour se prémunir d'une indisponibilité régionale majeure.

---

## 4. Politique de Rétention & Règle 3-2-1

| Données | Fréquence | Emplacement Principal | Emplacement Secondaire (Hors site) | Rétention |
|---|---|---|---|:---:|
| **PostgreSQL** | Quotidien (02h00) + WAL | `/var/backups/gedapp/` | Bucket Chiffré Sécurisé S3/R2 | 30 jours |
| **Documents R2** | Temps réel | Bucket Cloudflare R2 | Versioning Cloudflare natif | Illimitée / 90j versions |
| **Logs Système & Audit**| Quotidien | `/var/log/` | Stockage froid | 1 an (Audit compliance) |

---

## 5. Procédure de Test de Restauration Régulière

Une sauvegarde n'est valide que si sa restauration a été testée avec succès.
Tous les trimestres :
1. Restaurer une sauvegarde de production sur un environnement de staging isolé.
2. Exécuter la suite complète de smoke tests et de tests de non-régression (`php artisan test`).
3. Vérifier l'intégrité des relations entre métadonnées PostgreSQL et objets stockés sur R2.
