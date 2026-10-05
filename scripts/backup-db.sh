#!/usr/bin/env bash
# ==============================================================================
# GEDAPP SaaS — Script de Sauvegarde Quotidienne PostgreSQL
# Usage: ./scripts/backup-db.sh
# ==============================================================================

set -euo pipefail

BACKUP_DIR="${BACKUP_DIR:-/var/backups/gedapp/postgres}"
RETENTION_DAYS="${RETENTION_DAYS:-30}"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/gedapp_db_${DATE}.dump"

mkdir -p "${BACKUP_DIR}"

echo "[$(date)] Démarrage de la sauvegarde PostgreSQL de GEDAPP..."

# Utilise les variables d'environnement standard PostgreSQL (PGHOST, PGUSER, PGDATABASE, etc.)
# ou les paramètres par défaut
DB_NAME="${DB_DATABASE:-gedapp}"
DB_USER="${DB_USERNAME:-postgres}"

sudo -u "${DB_USER}" pg_dump -d "${DB_NAME}" -F c -b -v -f "${BACKUP_FILE}"

chmod 600 "${BACKUP_FILE}"
echo "[$(date)] Sauvegarde terminée avec succès: ${BACKUP_FILE}"

# Rétention
echo "[$(date)] Nettoyage des sauvegardes plus anciennes que ${RETENTION_DAYS} jours..."
find "${BACKUP_DIR}" -type f -name "gedapp_db_*.dump" -mtime "+${RETENTION_DAYS}" -delete

echo "[$(date)] Opération de sauvegarde achevée."
