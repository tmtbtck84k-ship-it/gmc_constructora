# Setup local con XAMPP en macOS

Este documento es la **ruta corta** para correr el proyecto en XAMPP. Para despliegue en VPS ver `INSTALL.md`.

## 1. Ubicación

El proyecto debe quedar en:
```
/Applications/XAMPP/xamppfiles/htdocs/gmc_constructora
```

URL de acceso: **http://localhost/gmc_constructora/**

## 2. PATH a binarios de XAMPP

Para no tener que escribir el path completo cada vez, agrega los binarios de XAMPP a tu PATH (sólo una vez):

```bash
echo 'export PATH="/Applications/XAMPP/xamppfiles/bin:$PATH"' >> ~/.zshrc
source ~/.zshrc

# Verificar
which php      # debería decir /Applications/XAMPP/xamppfiles/bin/php
which mysql    # idem
php --version  # debería ser 8.x (XAMPP 8.x)
```

Si tu XAMPP tiene PHP < 8.0, **no funcionará**. Actualiza a XAMPP 8.x desde https://www.apachefriends.org/download.html.

## 3. Iniciar Apache + MySQL desde el panel de XAMPP

Abrir **manager-osx** desde Aplicaciones/XAMPP, ir a la pestaña **Manage Servers** y arrancar:
- Apache Web Server
- MySQL Database

## 4. Composer

XAMPP no incluye Composer, hay que instalarlo aparte:

```bash
# Instalar Composer global
brew install composer
# o desde https://getcomposer.org/download/
```

Después, en la carpeta del proyecto:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/gmc_constructora
composer install
```

> **No usar `sudo`** con composer.

## 5. Bajar vendors frontend y core de CodeIgniter

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/gmc_constructora
bash bin/fetch-vendor.sh

curl -L https://github.com/bcit-ci/CodeIgniter/archive/refs/tags/3.1.13.tar.gz | tar xz
mv CodeIgniter-3.1.13/system .
rm -rf CodeIgniter-3.1.13
```

## 6. Configurar `.env.php` para XAMPP

```bash
cp .env.example application/config/.env.php
nano application/config/.env.php
```

Valores específicos para XAMPP:

```php
'ENVIRONMENT' => 'development',
'BASE_URL'    => 'http://localhost/gmc_constructora',

// MySQL de XAMPP: usuario root, password vacío por defecto
'DB_HOSTNAME' => 'localhost',
'DB_PORT'     => 3306,
'DB_USERNAME' => 'root',
'DB_PASSWORD' => '',
'DB_DATABASE' => 'gmc_erp',
'DB_CHARSET'  => 'utf8mb4',
'DB_COLLATE'  => 'utf8mb4_unicode_ci',

// Genera la llave: php -r 'echo bin2hex(random_bytes(32));'
'ENCRYPTION_KEY' => 'PEGAR_HEX_AQUI',

// Sesión
'SESSION_COOKIE_SECURE' => false,    // false en local (no hay HTTPS)
```

## 7. Crear la base de datos

**Opción A — phpMyAdmin (gráfico):**
- Abrir http://localhost/phpmyadmin
- Click en "Nueva" → nombre `gmc_erp` → cotejamiento `utf8mb4_unicode_ci` → Crear.

**Opción B — terminal:**
```bash
mysql -u root -e "CREATE DATABASE gmc_erp DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

## 8. Migrar y poblar datos

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/gmc_constructora
php index.php cli/migrate latest
```

Salida esperada:
```
Ejecutando migraciones a la última versión...
Migraciones aplicadas correctamente.
```

Si te pide path al index, recuerda que está en la raíz; o usa `php public/index.php cli/migrate latest`.

## 9. Permisos en carpetas escribibles

```bash
chmod -R 755 application/logs application/cache storage/uploads storage/tmp
```

## 10. Acceder al sistema

Abrir en navegador: **http://localhost/gmc_constructora/**

- Te redirige a `/login`.
- Ingresar:
  - **RUT:** `11111111-1`
  - **Password:** `GMC.2026!`
- Te obliga a cambiar la clave en el primer ingreso.
- Entras al dashboard.

## Problemas comunes en XAMPP

| Problema | Solución |
|---|---|
| `404 Not Found` en cualquier ruta menos `/` | Apache no tiene `mod_rewrite` activo o `AllowOverride` en `httpd.conf`. Editar `/Applications/XAMPP/xamppfiles/etc/httpd.conf`, buscar `<Directory "/Applications/XAMPP/xamppfiles/htdocs">` y poner `AllowOverride All`. Reiniciar Apache. |
| `mod_rewrite` deshabilitado | Mismo `httpd.conf`: descomentar `LoadModule rewrite_module modules/mod_rewrite.so`. Reiniciar. |
| `SQLSTATE[HY000] [2002]` al migrar | Confirma que MySQL de XAMPP esté corriendo desde el manager. |
| `Class 'CI_Controller' not found` | Falta el core de CI. Volver al paso 5 y bajar `system/`. |
| Error de permisos al escribir logs | `chmod -R 755 application/logs application/cache storage/uploads storage/tmp` |
| `Access denied for user 'root'@'localhost'` | XAMPP por default tiene root sin password; si lo cambiaste, ajústalo en `.env.php`. |
| `vendor/autoload.php not found` | Faltó `composer install` |

## Verificación rápida

Después del setup:

| Acción | Resultado esperado |
|---|---|
| `http://localhost/gmc_constructora/` | Redirige a `/login` |
| Login con `admin@gmc.cl` / `GMC.2026!` | Redirige a `/password/change` |
| Cambiar clave (cumple reglas) | Redirige a `/dashboard` con cards vacías |
| Click en "Administración → Usuarios" | Lista con el admin |
| Click en "Administración → Auditoría" | Ver eventos: login, password.changed |
