# LMS Backend — Production Deployment Guide

This guide outlines the steps to deploy the Laravel backend application to a production Linux VPS (e.g., Ubuntu 22.04 LTS).

---

## 1. System Requirements & Stack
* PHP 8.1+ (with extensions: `openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `curl`, `zip`)
* Nginx Web Server
* MySQL 8.0+
* Composer
* Git

---

## 2. Server Setup (Ubuntu)

### Install PHP, Nginx, and MySQL
```bash
sudo apt update
sudo apt install -y nginx mysql-server composer git
sudo apt install -y php-fpm php-mysql php-mbstring php-xml php-bcmath php-curl php-zip
```

### Create MySQL Database
```sql
CREATE DATABASE lms_db;
CREATE USER 'lms_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON lms_db.* TO 'lms_user'@'localhost';
FLUSH PRIVILEGES;
```

---

## 3. Clone and Initialize Project

1. Clone your project repository to `/var/www/lms`:
   ```bash
   sudo git clone <your-repo-url> /var/www/lms
   cd /var/www/lms/backend
   ```
2. Install dependencies:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
3. Copy environment file and configure it:
   ```bash
   cp .env.example .env
   nano .env
   ```
   *Update the following credentials in `.env`:*
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://api.yourdomain.com

   DB_DATABASE=lms_db
   DB_USERNAME=lms_user
   DB_PASSWORD=your_secure_password

   # Place your firebase-credentials.json in backend/ storage path
   FIREBASE_CREDENTIALS=storage/app/firebase-credentials.json
   ```
4. Generate App Key:
   ```bash
   php artisan key:generate --force
   ```
5. Run migrations & seeds:
   ```bash
   php artisan migrate --force
   ```
6. Set correct folder permissions:
   ```bash
   sudo chown -R www-data:www-data /var/www/lms/backend/storage
   sudo chown -R www-data:www-data /var/www/lms/backend/bootstrap/cache
   ```

---

## 4. Nginx Server Block Configuration

Create a file `/etc/nginx/sites-available/lms` and paste the following:

```nginx
server {
    listen 80;
    server_name api.yourdomain.com;
    root /var/www/lms/backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock; # Adjust PHP version socket
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site and reload Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/lms /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## 5. SSL Certificate (Let's Encrypt)
Run Certbot to secure your domain with HTTPS:
```bash
sudo apt install snapd
sudo snap install core; sudo snap refresh core
sudo snap install --classic certbot
sudo ln -s /snap/bin/certbot /usr/bin/certbot
sudo certbot --nginx -d api.yourdomain.com
```

---

## 6. Background Queue Workers & Task Scheduling

### System Cron (for Task Scheduling)
Add the scheduler to your server cron tab:
```bash
* * * * * cd /var/www/lms/backend && php artisan schedule:run >> /dev/null 2>&1
```

### Supervisor (for Queue Workers)
To keep the PhonePe payment verification and enrollment sync processes running in the background, install and configure Supervisor:
```bash
sudo apt install supervisor
```
Create a configuration file at `/etc/supervisor/conf.d/lms-worker.conf`:
```ini
[program:lms-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/lms/backend/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/lms/backend/storage/logs/worker.log
stopwaitsecs=3600
```
Start the worker:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start lms-worker:*
```
