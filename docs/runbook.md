# GEDAPP — Runbook Opérationnel & Procédures d'Urgence (V1)

Ce runbook fournit aux équipes d'exploitation les procédures diagnostiques et correctives pour répondre aux incidents de production sur **GEDAPP SaaS**.

---

## 1. Application Indisponible (Erreur 502 / 503 / 500)

### Symptômes
- Erreur `502 Bad Gateway` Nginx
- Erreur `503 Service Unavailable`
- Échec du Health Check `/up`

### Procédure Diagnostique
```bash
# 1. Vérifier le statut des services Nginx et PHP-FPM
sudo systemctl status nginx
sudo systemctl status php8.5-fpm

# 2. Inspecter les derniers logs Nginx
sudo tail -n 50 /var/log/nginx/gedapp_error.log

# 3. Inspecter les derniers logs Laravel
tail -n 100 /var/www/gedapp/storage/logs/laravel.log
```

### Résolution
- **Si PHP-FPM est arrêté ou en crash** :
  ```bash
  sudo systemctl restart php8.5-fpm
  ```
- **Si l'application est bloquée en mode maintenance** :
  ```bash
  cd /var/www/gedapp && php artisan up
  ```
- **Si le cache de configuration est corrompu** :
  ```bash
  cd /var/www/gedapp && php artisan optimize:clear && php artisan optimize
  ```

---

## 2. Queue Bloquée / En File d'Attente Excessive

### Symptômes
- Traitements de documents en attente infinie
- Notifications non envoyées
- Accumulation de lignes dans la table `jobs`

### Procédure Diagnostique
```bash
# 1. Vérifier l'état des workers Supervisor
sudo supervisorctl status

# 2. Compter les jobs en attente dans la base
php artisan tinker --execute "echo DB::table('jobs')->count();"

# 3. Consulter les logs des workers
sudo tail -n 100 /var/log/supervisor/gedapp-worker.log
```

### Résolution
- **Relancer les workers** :
  ```bash
  cd /var/www/gedapp && php artisan queue:restart
  sudo supervisorctl restart all
  ```
- **Augmenter temporairement le nombre de processus workers** dans `/etc/supervisor/conf.d/gedapp-worker.conf` (`numprocs=8`), puis :
  ```bash
  sudo supervisorctl reread && sudo supervisorctl update
  ```

---

## 3. OCR Bloqué ou Échecs Répétés

### Symptômes
- Documents bloqués au statut `OcrStatus::Processing`
- Échecs systématiques avec statut `OcrStatus::Failed`

### Procédure Diagnostique
```bash
# 1. Vérifier la disponibilité binaire de Tesseract et Poppler
which tesseract && tesseract --version
which pdftoppm && pdftoppm -v

# 2. Vérifier les dictionnaires de langues installés
tesseract --list-langs

# 3. Rechercher les erreurs OCR spécifiques dans les logs
grep -i "ocr" /var/www/gedapp/storage/logs/laravel.log | tail -n 50
```

### Résolution
- **Si les langues manquent (fra / eng)** :
  ```bash
  sudo apt install -y tesseract-ocr-fra tesseract-ocr-eng
  ```
- **Si `pdftoppm` est manquant pour les PDF rasterisés** :
  ```bash
  sudo apt install -y poppler-utils
  ```
- **Reprogrammer les OCR en échec** :
  ```bash
  php artisan queue:retry all
  ```

---

## 4. Disque Plein (No Space Left on Device)

### Symptômes
- Erreur PHP `file_put_contents(): Write failed`
- Arrêt des transactions PostgreSQL ou des sessions Laravel

### Procédure Diagnostique
```bash
# 1. Analyser l'espace disque global et les inodes
df -h
df -i

# 2. Identifier les répertoires volumineux
sudo du -sh /var/log/* /var/www/gedapp/storage/* | sort -h
```

### Résolution
- **Purger les fichiers temporaires et les caches anciens** :
  ```bash
  cd /var/www/gedapp
  rm -rf storage/app/scratch/*
  find storage/logs -name "*.log" -mtime +14 -delete
  ```
- **Nettoyer les journaux système Linux** :
  ```bash
  sudo journalctl --vacuum-time=3d
  sudo apt clean
  ```

---

## 5. Erreurs de Stockage Cloudflare R2

### Symptômes
- Exceptions `Aws\S3\Exception\S3Exception` lors de l'upload ou du téléchargement
- Erreurs `403 Forbidden` ou `SignatureDoesNotMatch`

### Procédure Diagnostique
```bash
# 1. Vérifier la résolution réseau et l'accès HTTPS à l'endpoint R2
curl -I https://<account_id>.r2.cloudflarestorage.com

# 2. Vérifier les variables d'environnement actives (sans révéler les clés)
php artisan tinker --execute "echo config('filesystems.disks.r2.endpoint') . ' | bucket: ' . config('filesystems.disks.r2.bucket');"
```

### Résolution
- Vérifier que l'horloge système du serveur est synchronisée (drift NTP bloque les requêtes AWS Signature V4) :
  ```bash
  sudo timedatectl status
  sudo systemctl restart systemd-timesyncd
  ```
- Si l'accès R2 est perturbé temporairement, basculer les nouveaux uploads sur le stockage local privé le temps du rétablissement :
  `DOCUMENTS_DISK=private` dans `.env` puis `php artisan config:cache`.

---

## 6. Erreurs de Base de Données PostgreSQL

### Symptômes
- Erreurs `SQLSTATE[08006]` (Connexion impossible)
- `Too many connections` ou verrous bloquants

### Procédure Diagnostique
```bash
# 1. Vérifier le service PostgreSQL
sudo systemctl status postgresql

# 2. Analyser les connexions actives
sudo -u postgres psql -c "SELECT count(*), state FROM pg_stat_activity GROUP BY state;"
```

### Résolution
- Si saturation des connexions : redémarrer les pools PHP-FPM pour libérer les connexions inactives :
  ```bash
  sudo systemctl restart php8.5-fpm
  ```
- Optimiser `max_connections` dans `/etc/postgresql/16/main/postgresql.conf` si nécessaire.

---

## 7. Gestion des Failed Jobs

### Procédure
```bash
# Lister tous les jobs échoués
php artisan queue:failed

# Inspecter le détail d'un échec
php artisan queue:failed --id=ID_DU_JOB

# Réessayer un job précis
php artisan queue:retry ID_DU_JOB

# Réessayer tous les jobs échoués après résolution de l'incident
php artisan queue:retry all

# Purger les jobs échoués irrécupérables (ATTENTION : action irréversible)
php artisan queue:flush
```

---

## 8. Procédure de Rollback d'Urgence

Si un déploiement introduit une anomalie bloquante en production :

```bash
cd /var/www/gedapp

# 1. Activer le mode maintenance
php artisan down

# 2. Revenir au commit ou tag stable précédent
git reset --hard HASH_PRECEDENT

# 3. Réinstaller les dépendances stables
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 4. Reconstruire les caches
php artisan optimize

# 5. Redémarrer les workers et PHP-FPM
php artisan queue:restart
sudo supervisorctl restart all
sudo systemctl reload php8.5-fpm

# 6. Rouvrir l'application
php artisan up
```
