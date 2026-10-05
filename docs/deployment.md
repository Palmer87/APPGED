# GEDAPP — Guide de Déploiement et Infrastructure de Production (V1)

Ce document détaille l'ensemble des procédures, configurations et bonnes pratiques pour déployer et exploiter **GEDAPP SaaS** en production de façon sécurisée, performante et sans interruption de service.

---

## 1. Architecture de Production

```
                                  [ Utilisateurs Web / API ]
                                              │ (HTTPS - Port 443)
                                              ▼
                              [ Cloudflare Proxy / CDN / WAF ]
                                              │ (SSL Full Strict)
                                              ▼
                                    [ Nginx 1.24+ ]
                                              │
                      ┌───────────────────────┴───────────────────────┐
                      │                                               │
             (Fichiers Statiques)                               (FastCGI PHP)
           /public/build/*, assets                                     │
                      │                                               ▼
                      ▼                                    [ PHP 8.5-FPM Pool ]
              Navigateur Client                                       │
                                                      ┌───────────────┴───────────────┐
                                                      │                               │
                                                      ▼                               ▼
                                            [ PostgreSQL 16+ ]              [ Cloudflare R2 ]
                                         Données, ACL, Sessions           Stockage Privé Documents
                                                      ▲
                                                      │
                                             [ Queue Workers ]
                                         Supervisor / systemd
                                        (OCR, Tesseract, Mail)
```

---

## 2. Prérequis Serveur (Linux Ubuntu 24.04 LTS / Debian 12)

### 2.1 Paquets Système
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y software-properties-common curl git unzip zip certbot python3-certbot-nginx

# Dépôt PHP Ondrej (pour PHP 8.5)
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Installation PHP 8.5 et extensions requises
sudo apt install -y php8.5-fpm php8.5-cli php8.5-pgsql php8.5-mbstring php8.5-xml \
    php8.5-curl php8.5-zip php8.5-intl php8.5-bcmath php8.5-soap php8.5-gd \
    php8.5-imagick php8.5-opcache php8.5-redis

# Nginx & Supervisor
sudo apt install -y nginx supervisor

# Tesseract OCR & Poppler
sudo apt install -y tesseract-ocr tesseract-ocr-fra tesseract-ocr-eng poppler-utils

# Node.js (LTS v22/v24 via NodeSource) & Composer
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

---

## 3. Configuration Nginx de Production

Le document root **DOIT IMPÉRATIVEMENT** pointer sur `/public` et **JAMAIS** sur la racine de l'application.

Fichier `/etc/nginx/sites-available/gedapp.conf` :

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name YOUR_DOMAIN.com www.YOUR_DOMAIN.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name YOUR_DOMAIN.com www.YOUR_DOMAIN.com;

    # Certificats SSL (Certbot Let's Encrypt ou Cloudflare Origin CA)
    ssl_certificate /etc/letsencrypt/live/YOUR_DOMAIN.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/YOUR_DOMAIN.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Racine publique de Laravel
    root /var/www/gedapp/public;
    index index.php;

    charset utf-8;

    # Taille maximale des téléversements (alignée sur GED & OCR)
    client_max_body_size 30M;

    # En-têtes de sécurité
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Journalisation
    access_log /var/log/nginx/gedapp_access.log;
    error_log /var/log/nginx/gedapp_error.log warn;

    # Routage principal Laravel
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Endpoint de santé
    location = /up {
        access_log off;
        try_files $uri /index.php?$query_string;
    }

    # Favicon et robots.txt
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    # Assets Vite avec cache navigateur long terme
    location ~* \.(css|js|woff|woff2|ttf|png|jpg|jpeg|gif|svg|ico)$ {
        expires 1y;
        add_header Cache-Control "public, no-transform, immutable";
        access_log off;
        try_files $uri =404;
    }

    # Exécution PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 180s;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    # Interdiction formelle d'accès aux fichiers sensibles et cachés
    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~ ^/(\.env|\.git|composer\.(json|lock)|package\.(json|lock)|phpunit\.xml) {
        deny all;
        return 404;
    }
}
```

Activation et vérification :
```bash
sudo ln -sf /etc/nginx/sites-available/gedapp.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## 4. Configuration PHP-FPM

Fichier `/etc/php/8.5/fpm/pool.d/gedapp.conf` (ou `www.conf`) :

```ini
[gedapp]
user = www-data
group = www-data
listen = /run/php/php8.5-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 1000

php_admin_value[memory_limit] = 512M
php_admin_value[upload_max_filesize] = 25M
php_admin_value[post_max_size] = 30M
php_admin_value[max_execution_time] = 180
php_admin_value[expose_php] = Off
```

Fichier `/etc/php/8.5/fpm/conf.d/10-opcache.ini` :
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.save_comments=1
```

Redémarrage :
```bash
sudo systemctl restart php8.5-fpm
```

---

## 5. Configuration PostgreSQL

GEDAPP utilise PostgreSQL avec transactions ACID et contraintes d'intégrité.

```sql
-- Création de la base et de l'utilisateur dédié
CREATE USER gedapp_user WITH PASSWORD 'DEFINIR_MOT_DE_PASSE_FORT';
CREATE DATABASE gedapp OWNER gedapp_user ENCODING 'UTF8';
GRANT ALL PRIVILEGES ON DATABASE gedapp TO gedapp_user;
```

---

## 6. Configuration Supervisor (Queue Workers)

GEDAPP traite l'OCR et les notifications de manière asynchrone via les queues Laravel.

Fichier `/etc/supervisor/conf.d/gedapp-worker.conf` :

```ini
[program:gedapp-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/gedapp/artisan queue:work database --sleep=3 --tries=3 --timeout=180 --max-time=3600
directory=/var/www/gedapp
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/supervisor/gedapp-worker.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=5
```

Rechargement de Supervisor :
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

---

## 7. Configuration du Scheduler (Cron)

Le scheduler Laravel exécute les tâches planifiées de nettoyage, d'expiration de partages et de surveillance de quota.

Édition du crontab de l'utilisateur web :
```bash
sudo crontab -u www-data -e
```

Ajouter la ligne unique suivante :
```cron
* * * * * cd /var/www/gedapp && php artisan schedule:run >> /dev/null 2>&1
```

---

## 8. Environnement OCR (Tesseract & Poppler)

GEDAPP utilise Tesseract pour l'OCR et Poppler (`pdftoppm`) pour la rasterisation des PDF multipages scannés.

Vérification de l'environnement :
```bash
tesseract --version
tesseract --list-langs
# Sortie attendue : eng, fra, osd

pdftoppm -v
# Sortie attendue : pdftoppm version 24.xx (poppler)
```

---

## 9. Stockage Cloudflare R2 (Compatible S3)

Les documents privés de GEDAPP sont stockés sur Cloudflare R2 avec exclusion stricte de tout accès public.

Configuration dans `.env` de production (sans commiter) :
```dotenv
FILESYSTEM_DISK=local
DOCUMENTS_DISK=r2

# Identifiants Cloudflare R2
R2_ACCESS_KEY_ID=VOTRE_R2_ACCESS_KEY
R2_SECRET_ACCESS_KEY=VOTRE_R2_SECRET_KEY
R2_DEFAULT_REGION=auto
R2_BUCKET=gedapp-production
R2_ENDPOINT=https://VOTRE_ACCOUNT_ID.r2.cloudflarestorage.com
R2_USE_PATH_STYLE_ENDPOINT=false
```

---

## 10. Permissions Linux & Propriété des Fichiers

Les répertoires de cache et de logs doivent être inscriptibles par l'utilisateur web `www-data` sans jamais recourir à `chmod 777`.

```bash
cd /var/www/gedapp

# Propriété récursive
sudo chown -R www-data:www-data /var/www/gedapp

# Permissions standards (755 répertoires, 644 fichiers)
sudo find /var/www/gedapp -type d -exec chmod 755 {} \;
sudo find /var/www/gedapp -type f -exec chmod 644 {} \;

# Droits d'écriture stricts sur storage et bootstrap/cache
sudo chmod -R 775 /var/www/gedapp/storage
sudo chmod -R 775 /var/www/gedapp/bootstrap/cache
```

---

## 11. Procédure de Déploiement Standard (Sans Downtime)

Script de déploiement type `/var/www/deploy.sh` :

```bash
#!/bin/bash
set -e

APP_DIR="/var/www/gedapp"
cd $APP_DIR

echo "==> 1. Activation du mode maintenance"
php artisan down --retry=60 || true

echo "==> 2. Récupération des dernières sources"
git fetch origin main
git reset --hard origin/main

echo "==> 3. Installation des dépendances PHP"
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

echo "==> 4. Compilation des assets frontend"
npm ci
npm run build

echo "==> 5. Exécution des migrations de schéma"
php artisan migrate --force

echo "==> 6. Mise en cache des configurations et routes"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> 7. Redémarrage des queue workers"
php artisan queue:restart
sudo supervisorctl restart all

echo "==> 8. Rechargement de PHP-FPM"
sudo systemctl reload php8.5-fpm

echo "==> 9. Fin du mode maintenance"
php artisan up

echo "==> 10. Vérification du Health Check"
curl -f -s -o /dev/null http://localhost/up && echo "Health Check: OK" || echo "Health Check: ECHEC"

echo "==> Déploiement terminé avec succès !"
```

Rendre le script exécutable :
```bash
chmod +x /var/www/deploy.sh
```

---

## 12. Checklist Smoke Tests de Production

Après chaque mise en production, valider les parcours suivants :

1. **Authentification** : Connexion espace organisation (`/login`) et espace plateforme (`/platform/login`).
2. **Dashboard** : Chargement des métriques et compteurs sans erreur 500.
3. **Upload Document** : Téléversement d'un PDF et d'une image PNG.
4. **Queue Worker & OCR** : Dépouillement asynchrone du job `ProcessDocumentOcr`, extraction du texte visible dans l'onglet OCR.
5. **Recherche** : Recherche full-text par mot-clé extrait de l'OCR.
6. **Prévisualisation & Téléchargement** : Visualisation inline et téléchargement sécurisé.
7. **Isolation Multi-Tenant** : Tentative d'accès à un document d'un autre tenant rejetée (403/404).
8. **Health Check** : `curl -I https://YOUR_DOMAIN.com/up` retourne HTTP 200 OK.
