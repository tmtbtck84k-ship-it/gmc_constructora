# Guía rápida de instalación local

## 1. Clonar y preparar

```bash
git clone https://github.com/tmtbtck84k-ship-it/gmc_constructora.git
cd gmc_constructora
composer install
cp .env.example application/config/.env.php
```

Editar `application/config/.env.php` con:
- Credenciales de BD locales (`DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE`).
- `ENCRYPTION_KEY` única (generar con `php -r 'echo bin2hex(random_bytes(32));'`).
- `BASE_URL` (`http://localhost:8000` para servidor embebido PHP).
- SMTP (puede quedar dummy si no se necesitan correos por ahora).

## 2. Descargar `system/` de CodeIgniter 3.1.13

```bash
curl -L https://github.com/bcit-ci/CodeIgniter/archive/refs/tags/3.1.13.tar.gz | tar xz
mv CodeIgniter-3.1.13/system .
rm -rf CodeIgniter-3.1.13
```

> El folder `system/` no se versiona; cada despliegue lo descarga.

## 3. Descargar assets vendor

Necesarios en `public/assets/vendor/`:
- bootstrap (5.3.x) → `bootstrap/css/bootstrap.min.css`, `bootstrap/js/bootstrap.bundle.min.js`
- bootstrap-icons (1.11.x) → `bootstrap-icons/bootstrap-icons.css` + fuentes
- jquery (3.7.1) → `jquery/jquery-3.7.1.min.js`
- datatables (1.13.x) → `datatables/jquery.dataTables.min.js`, `dataTables.bootstrap5.min.js`, `dataTables.bootstrap5.min.css`, `es-CL.json`
- select2 (4.1.x) → `select2/select2.min.{js,css}`, `select2-bootstrap-5.min.css`
- sweetalert2 (11.x) → `sweetalert2/sweetalert2.all.min.js`, `sweetalert2.min.css`

Script automático `bin/fetch-vendor.sh` (a generar en Sprint 1 día 14).

## 4. Crear BD y ejecutar migraciones

```bash
mysql -u root -p -e "CREATE DATABASE gmc_erp DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE USER 'gmc_user'@'localhost' IDENTIFIED BY 'TU_PASSWORD'; GRANT ALL ON gmc_erp.* TO 'gmc_user'@'localhost'; FLUSH PRIVILEGES;"

# Migrar
php index.php cli/migrate latest
```

## 5. Permisos

```bash
chmod -R 750 application/logs application/cache storage/uploads storage/tmp
# En Linux con Apache: chown -R www-data:www-data esos folders
```

## 6. Servir

```bash
# Con servidor embebido PHP (desde public/)
php -S localhost:8000 -t public/

# O configurar Apache/Nginx con DocumentRoot apuntando a public/
```

## 7. Login

- URL: http://localhost:8000/login
- RUT: `11111111-1`
- Password: `GMC.2026!`

El sistema te obligará a cambiar la clave en el primer ingreso.

## Smoke test mínimo

| Paso | Esperado |
|---|---|
| Navegar a `/` | Redirige a `/login` |
| Login con credenciales correctas | Redirige a `/password/change` |
| Cambiar clave (cumple reglas) | Mensaje de éxito; redirige a `/dashboard` |
| Click en "Cerrar sesión" | Redirige a `/login` |
| 5 intentos fallidos | Bloqueo 15 min |
| Solicitud `/password/forgot` con email del admin | Mensaje genérico de éxito; correo encolado |
| `php index.php cli/migrate status` | Muestra dos migraciones aplicadas |
