#!/usr/bin/env bash
# ==============================================================================
# GEDAPP SaaS — Script de Déploiement Automatisé Ubuntu 24.04 / Debian 12
# Usage: ./scripts/deploy.sh
# ==============================================================================

set -euo pipefail

APP_DIR="/var/www/gedapp"
BRANCH="main"

echo "=========================================================="
echo " [GEDAPP] Démarrage du déploiement en production"
echo "=========================================================="

cd "${APP_DIR}"

# 1. Vérification de l'utilisateur
if [ "$EUID" -eq 0 ]; then
    echo "(!) Attention: exécution en tant que root. Les permissions seront réajustées pour www-data."
fi

# 2. Mode maintenance
echo "==> 1. Activation du mode maintenance..."
php artisan down --retry=60 || true

# 3. Synchronisation Git (si dépôt git présent)
if [ -d ".git" ]; then
    echo "==> 2. Synchronisation Git (${BRANCH})..."
    git fetch origin "${BRANCH}"
    git reset --hard "origin/${BRANCH}"
fi

# 4. Installation dépendances Composer
echo "==> 3. Installation des dépendances Composer..."
composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# 5. Compilation des assets frontend
echo "==> 4. Compilation des assets Vite..."
npm ci --prefer-offline --no-audit
npm run build

# 6. Migrations de schéma sécurisées
echo "==> 5. Application des migrations de schéma..."
php artisan migrate --force

# 7. Optimisation des caches Laravel
echo "==> 6. Mise en cache des configurations, routes, vues et événements..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 8. Permissions des répertoires
echo "==> 7. Ajustement des permissions storage et cache..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 9. Redémarrage des Queue Workers et rechargement PHP-FPM
echo "==> 8. Redémarrage des workers et de PHP-FPM..."
php artisan queue:restart
if command -v supervisorctl &> /dev/null; then
    supervisorctl restart all || true
fi

if systemctl is-active --quiet php8.5-fpm; then
    systemctl reload php8.5-fpm
elif systemctl is-active --quiet php8.4-fpm; then
    systemctl reload php8.4-fpm
fi

# 10. Sortie du mode maintenance
echo "==> 9. Fin du mode maintenance..."
php artisan up

# 11. Health Check
echo "==> 10. Vérification du Health Check..."
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/up || echo "000")
if [ "${HTTP_STATUS}" -eq 200 ]; then
    echo " [OK] Health Check validé (HTTP 200)"
else
    echo " [WARN] Health Check retourné: ${HTTP_STATUS}"
fi

echo "=========================================================="
echo " [GEDAPP] Déploiement terminé avec succès !"
echo "=========================================================="
