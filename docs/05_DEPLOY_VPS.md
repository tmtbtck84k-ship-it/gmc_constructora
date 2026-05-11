# Instructivo de Despliegue en VPS — ERP GMC

**Servidor objetivo:** Ubuntu 22.04 LTS o Debian 12.
**Stack:** Nginx + PHP 8.1 FPM + MySQL 8 + Composer + Certbot.
**Tiempo estimado:** 60–90 minutos (primer despliegue).

---

## 1. Provisionamiento

### 1.1 Crear el VPS
- Tamaño recomendado: 2 vCPU, 4 GB RAM, 40 GB SSD.
- Sistema: Ubuntu 22.04 LTS.
- Apunta el dominio `erp.gmc.cl` al IP del VPS (registro A).

### 1.2 Acceso inicial
```bash
ssh root@IP_DEL_VPS

# Crear usuario no-root
adduser deploy
usermod -aG sudo deploy
mkdir -p /home/deploy/.ssh
cp /root/.ssh/authorized_keys /home/deploy/.ssh/
chown -R deploy:deploy /home/deploy/.ssh
chmod 700 /home/deploy/.ssh
chmod 600 /home/deploy/.ssh/authorized_keys

# Endurecer SSH
nano /etc/ssh/sshd_config
# Cambiar:
#   PermitRootLogin no
#   PasswordAuthentication no
systemctl restart ssh

# Salir y entrar como deploy
exit
ssh deploy@IP_DEL_VPS
```

### 1.3 Firewall
```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

## 2. Instalar dependencias

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server certbot python3-certbot-nginx git unzip curl

# PHP 8.1
sudo apt install -y php8.1-fpm php8.1-mysql php8.1-mbstring php8.1-curl \
                    php8.1-gd php8.1-zip php8.1-intl php8.1-bcmath php8.1-xml

# Composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
composer --version
```

## 3. Configurar MySQL

```bash
sudo mysql_secure_installation
# Responde:
#  - Validar password: y, nivel medio
#  - Password root nuevo: <FUERTE>
#  - Remove anonymous: y
#  - Disallow root login remotely: y
#  - Remove test database: y
#  - Reload privilege tables: y
```

Crear BD y usuario:

```bash
sudo mysql -u root -p
```
```sql
CREATE DATABASE gmc_erp DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'gmc_app'@'localhost' IDENTIFIED BY 'PASSWORD_FUERTE_AQUI';
GRANT ALL PRIVILEGES ON gmc_erp.* TO 'gmc_app'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 4. Clonar el proyecto

```bash
sudo mkdir -p /var/www/gmc
sudo chown -R deploy:www-data /var/www/gmc
cd /var/www/gmc
git clone https://github.com/tmtbtck84k-ship-it/gmc_constructora.git .

# Bajar core de CodeIgniter (no versionado en git)
curl -L https://github.com/bcit-ci/CodeIgniter/archive/refs/tags/3.1.13.tar.gz | tar xz
mv CodeIgniter-3.1.13/system .
rm -rf CodeIgniter-3.1.13

# Bajar dependencias
composer install --no-dev --optimize-autoloader

# Bajar vendors frontend
bash bin/fetch-vendor.sh
```

## 5. Configurar variables de entorno

```bash
cp .env.example application/config/.env.php
nano application/config/.env.php
```

Valores de producción:

```php
'ENVIRONMENT' => 'production',
'BASE_URL'    => 'https://erp.gmc.cl',

'DB_HOSTNAME' => 'localhost',
'DB_PORT'     => 3306,
'DB_USERNAME' => 'gmc_app',
'DB_PASSWORD' => 'PASSWORD_FUERTE_AQUI',
'DB_DATABASE' => 'gmc_erp',

'ENCRYPTION_KEY' => '<generar con: php -r "echo bin2hex(random_bytes(32));">',

'SESSION_COOKIE_SECURE' => true,
'SESSION_SAMESITE'      => 'Lax',

'SMTP_HOST'       => 'smtp.tu-proveedor.cl',
'SMTP_PORT'       => 587,
'SMTP_USERNAME'   => 'no-reply@gmc.cl',
'SMTP_PASSWORD'   => 'PASSWORD_SMTP',
'SMTP_ENCRYPTION' => 'tls',
'SMTP_FROM_NAME'  => 'ERP GMC',
'SMTP_FROM_EMAIL' => 'no-reply@gmc.cl',

'BACKUP_PATH' => '/var/backups/gmc',
'BACKUP_RETENTION_DAYS' => 14,
```

## 6. Permisos

```bash
sudo chown -R deploy:www-data /var/www/gmc
sudo chmod -R 750 /var/www/gmc
sudo chmod -R 770 /var/www/gmc/application/logs /var/www/gmc/application/cache
sudo chmod -R 770 /var/www/gmc/storage/uploads /var/www/gmc/storage/tmp
sudo chmod 600 /var/www/gmc/application/config/.env.php
sudo mkdir -p /var/backups/gmc /var/log/gmc
sudo chown -R deploy:www-data /var/backups/gmc /var/log/gmc
sudo chmod 770 /var/backups/gmc /var/log/gmc
```

## 7. Migrar datos

```bash
cd /var/www/gmc
php public/index.php cli/migrate latest
```

Esperado: "Migraciones aplicadas correctamente."

Verifica:
```bash
mysql -u gmc_app -p gmc_erp -e "SHOW TABLES; SELECT email FROM gmc_usuarios;"
```

## 8. Configurar Nginx

```bash
sudo nano /etc/nginx/sites-available/gmc
```

```nginx
server {
    listen 80;
    server_name erp.gmc.cl;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name erp.gmc.cl;

    root /var/www/gmc/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/erp.gmc.cl/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/erp.gmc.cl/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    client_max_body_size 12M;

    # Bloquear acceso a carpetas sensibles
    location ~ ^/(application|system|storage|vendor|tests)/ {
        deny all;
        return 403;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Cache de assets estáticos
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|woff|woff2)$ {
        expires 7d;
        add_header Cache-Control "public, immutable";
    }

    error_log /var/log/nginx/gmc-error.log;
    access_log /var/log/nginx/gmc-access.log;
}
```

```bash
sudo ln -s /etc/nginx/sites-available/gmc /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## 9. SSL con Let's Encrypt

```bash
sudo certbot --nginx -d erp.gmc.cl
# Sigue las instrucciones; aceptar términos, suministrar email.
# Certbot configura auto-renovación.
```

Verifica:
```bash
curl -I https://erp.gmc.cl/login
```

## 10. PHP en producción

```bash
sudo nano /etc/php/8.1/fpm/php.ini
```

Cambia:
```ini
display_errors = Off
log_errors = On
error_log = /var/log/php8.1-fpm.log
expose_php = Off
upload_max_filesize = 12M
post_max_size = 14M
max_execution_time = 60
opcache.enable = 1
opcache.revalidate_freq = 60
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
```

```bash
sudo systemctl restart php8.1-fpm
```

## 11. Cron jobs

```bash
sudo crontab -e -u deploy
```

```cron
30 9  * * *  cd /var/www/gmc && /usr/bin/php public/index.php cli/sync_tc  >> /var/log/gmc/sync_tc.log 2>&1
0  2  * * *  cd /var/www/gmc && /usr/bin/php public/index.php cli/backup   >> /var/log/gmc/backup.log 2>&1
0  3  * * *  cd /var/www/gmc && /usr/bin/php public/index.php cli/cleanup  >> /var/log/gmc/cleanup.log 2>&1
*/5 * * * *  cd /var/www/gmc && /usr/bin/php public/index.php cli/mailer   >> /var/log/gmc/mailer.log 2>&1
```

## 12. Smoke test post-deploy

```bash
cd /var/www/gmc
GMC_BASE_URL="https://erp.gmc.cl" \
GMC_RUT="11111111-1" \
GMC_PASSWORD="GMC.2026!" \
php tests/SmokeTest.php
```

Esperado: todas las pruebas pasadas.

## 13. Cambiar la clave del admin

Login en https://erp.gmc.cl con `11111111-1 / GMC.2026!` → te obliga a cambiarla.

## 14. Crear usuarios reales del equipo

Como admin, **Administración → Usuarios → Nuevo**. A cada usuario llega correo con su clave temporal.

## 15. Backup remoto (opcional pero recomendado)

Configura sincronización del directorio `/var/backups/gmc` a un bucket S3, Backblaze B2 o equivalente:

```bash
# Ejemplo con rclone
sudo apt install -y rclone
rclone config  # configurar remote 'backup'

# Cron adicional (sube backups del día a la nube)
30 4 * * * rclone copy /var/backups/gmc backup:gmc-erp/ >> /var/log/gmc/rclone.log 2>&1
```

## 16. Monitorización (opcional)

- Uptime: UptimeRobot o BetterStack apuntando a `https://erp.gmc.cl/login`.
- Logs centralizados: Logtail, Papertrail o ELK.
- Alertas: cron que revise tamaño de backup diario y avise si <100KB.

## 17. Actualizaciones futuras

```bash
ssh deploy@IP_DEL_VPS
cd /var/www/gmc
git pull origin main
composer install --no-dev --optimize-autoloader
php public/index.php cli/migrate latest
sudo systemctl reload php8.1-fpm
```

## 18. Rollback

Si una actualización rompe algo:
```bash
cd /var/www/gmc
git log --oneline -5     # ver últimos commits
git checkout <commit-anterior>
php public/index.php cli/migrate version <timestamp-anterior>
sudo systemctl reload php8.1-fpm
```

## 19. Checklist final pre-go-live

- [ ] HTTPS funciona y redirige desde HTTP
- [ ] Smoke test pasa
- [ ] Login con admin funciona y obliga cambio de clave
- [ ] SMTP envía correos correctamente (probar reset password)
- [ ] Cron jobs corriendo (verificar logs en `/var/log/gmc/`)
- [ ] Backup ejecuta y genera archivo
- [ ] Restore probado en otro ambiente
- [ ] Permisos de carpetas correctos (no 777 en ningún lado)
- [ ] `.env.php` con permisos 600 y dueño correcto
- [ ] Headers de seguridad presentes (`curl -I`)
- [ ] DNS apunta correctamente
- [ ] Capacitación realizada con usuarios clave
- [ ] Acta de entrega firmada
