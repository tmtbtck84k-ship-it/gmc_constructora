# Guía de Hardening — ERP GMC

## 1. Checklist pre-producción

### 1.1 Variables de entorno
- [ ] `ENVIRONMENT='production'` en `application/config/.env.php`
- [ ] `ENCRYPTION_KEY` con 64 caracteres hex únicos (no la del ejemplo)
- [ ] `BASE_URL` con HTTPS (`https://erp.gmc.cl`)
- [ ] `SESSION_COOKIE_SECURE=true`
- [ ] Credenciales BD con usuario propio (no root) y password fuerte
- [ ] Credenciales SMTP reales y password protegida

### 1.2 Permisos de archivos
```bash
# El usuario web (www-data) sólo debe poder escribir donde estrictamente necesita
chown -R www-data:www-data /var/www/gmc
chmod -R 750 /var/www/gmc
chmod -R 770 /var/www/gmc/application/logs /var/www/gmc/application/cache
chmod -R 770 /var/www/gmc/storage/uploads /var/www/gmc/storage/tmp
chmod 600 /var/www/gmc/application/config/.env.php
```

### 1.3 Apache/Nginx
- [ ] DocumentRoot apunta a `/var/www/gmc/public/`
- [ ] `application/`, `system/`, `storage/`, `vendor/` fuera del DocumentRoot
- [ ] HTTPS forzado (Let's Encrypt + auto-renew)
- [ ] HTTP/2 habilitado
- [ ] Listing de directorios deshabilitado
- [ ] Versión de servidor oculta (`ServerTokens Prod` en Apache)

### 1.4 PHP
- [ ] PHP 8.1 con `display_errors=Off`, `expose_php=Off`
- [ ] `session.cookie_httponly=1`, `session.cookie_secure=1`, `session.use_strict_mode=1`
- [ ] OPcache habilitado para producción
- [ ] `upload_max_filesize` y `post_max_size` ≥ 12 MB

### 1.5 MySQL
- [ ] Usuario `gmc_app` con privilegios sólo en `gmc_erp` (no GRANT ALL global)
- [ ] root con password fuerte y sin acceso remoto
- [ ] Backup diario corriendo + prueba de restore mensual
- [ ] `bind-address=127.0.0.1` (no expuesto a internet)

## 2. Headers de seguridad (ya implementados)

`application/hooks/SecurityHeaders.php` envía en producción:
- `Strict-Transport-Security: max-age=31536000; includeSubDomains`
- `X-Frame-Options: DENY`
- `X-Content-Type-Options: nosniff`
- `X-XSS-Protection: 1; mode=block`
- `Referrer-Policy: same-origin`
- `Content-Security-Policy: default-src 'self'; ...`

Verificar con:
```bash
curl -I https://erp.gmc.cl/login
```

## 3. Revisión de seguridad — Checklist final

### Autenticación
- [x] Bcrypt cost 12 (no SHA-1, no MD5)
- [x] CSRF activo en todos los forms
- [x] Sesión regenerada al login
- [x] Cookies HttpOnly + Secure + SameSite=Lax
- [x] Rate limit por IP+RUT (5 intentos / 15 min)
- [x] Mensaje genérico tras login fallido
- [x] Reset de password con token de un solo uso (TTL 60 min, SHA-256)
- [x] Validador de fortaleza de password

### Autorización
- [x] ACL granular `<modulo>.<recurso>.<accion>` (57 permisos)
- [x] `MY_AuthController::require_login()`
- [x] `MY_Controller::require_permission()` por método
- [x] Cache de permisos en sesión, invalidación al cambiar rol
- [x] Sidebar filtrado por permisos
- [x] Botones de acción condicionados con `can($perm)`

### Inputs
- [x] Validación tipada (`form_validation`) en todos los forms
- [x] RUT chileno con validación de DV
- [x] Email con `filter_var(FILTER_VALIDATE_EMAIL)`
- [x] Subida de archivos: MIME real con `finfo`, whitelist extensiones, máx 10 MB
- [x] Nombres de archivo regenerados con UUID (no se preserva el del usuario)
- [x] Storage fuera del DocumentRoot
- [x] Descarga vía controller con verificación de permiso

### SQL
- [x] 100% parametrizado (Query Builder de CI3 + binds)
- [x] No hay concatenación de strings de input en queries
- [x] FK con políticas explícitas
- [x] Soft delete en lugar de DELETE físico

### Auditoría
- [x] Log de todas las acciones críticas en `gmc_auditoria_logs`
- [x] Estado anterior + nuevo en JSON
- [x] IP y user-agent registrados
- [x] Login exitoso y fallido logueados

### Operación
- [x] Backups automáticos diarios + retención 14 días
- [x] Cleanup de datos transitorios (login_attempts, notificaciones, huérfanos)
- [x] Notificaciones encoladas (no bloquean request)
- [x] Logs aplicativos en `application/logs/`

## 4. Plan de respuesta a incidentes

### Caso: usuario sospecha que su cuenta fue comprometida
1. Admin entra a **Administración → Usuarios** y desactiva la cuenta.
2. Click "Restablecer contraseña" → genera clave temporal nueva.
3. Revisa `Administración → Auditoría` filtrando por usuario para detectar acciones sospechosas.
4. Si hubo cambios en SDPs/Compras/Rindes, evaluar reversión manual.

### Caso: pérdida de datos
1. Identificar el momento del incidente.
2. `cd /var/www/gmc && ls -la storage/backups/` (o ruta del cron)
3. Restaurar el último backup previo:
   ```bash
   gunzip < storage/backups/gmc-AAAAMMDD-HHMMSS.sql.gz | mysql -u root -p gmc_erp
   ```
4. Aplicar migraciones si hubo cambios de esquema desde el backup.

### Caso: deface o intrusión
1. Tomar snapshot del estado actual (`mysqldump` + tar de storage/).
2. Cerrar el VPS al exterior (firewall).
3. Revisar logs (`application/logs/`, `/var/log/nginx/`, `gmc_auditoria_logs`).
4. Restaurar último backup limpio + último estado de archivos del git.
5. Forzar reset de claves de todos los usuarios (`UPDATE gmc_usuarios SET force_password_change=1`).
6. Revocar y regenerar `ENCRYPTION_KEY`.
7. Notificar a usuarios.

## 5. Comandos rápidos de diagnóstico

```bash
# Logs de hoy
tail -100 /var/www/gmc/application/logs/log-$(date +%Y-%m-%d).php

# Últimos errores PHP
tail -50 /var/log/php8.1-fpm.log

# Logins fallidos recientes
mysql -u root -p gmc_erp -e "SELECT ip, rut, created_at FROM gmc_login_attempts WHERE exitoso=0 ORDER BY id DESC LIMIT 20;"

# Auditoría última hora
mysql -u root -p gmc_erp -e "SELECT created_at, accion, entidad, ip FROM gmc_auditoria_logs WHERE created_at > NOW() - INTERVAL 1 HOUR ORDER BY id DESC;"

# Espacio en disco
df -h /var/www /var/log /var/backups

# Procesos cron pendientes
crontab -l
ls -la /var/spool/cron/
```
