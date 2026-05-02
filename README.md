# GMC ERP — Constructora GMC

Sistema ERP web liviano para el Modelo de Gestión Integral de Constructora GMC. Soporta control financiero (Solicitud de Pago), compras y costos, ejecución de obra (bitácora + cierre) y reportes de gestión.

**Repositorio:** https://github.com/tmtbtck84k-ship-it/gmc_constructora.git
**Stack:** CodeIgniter 3.1.13 · PHP 8.1 · MySQL 8.0 · Bootstrap 5.3
**Estado:** Sprint 1 — Base ERP en construcción.

## Requisitos

- PHP 8.0+ (recomendado 8.1) con extensiones: `mysqli`, `mbstring`, `curl`, `gd`, `zip`, `intl`, `fileinfo`.
- MySQL 8.0 (mínimo 5.7 con `utf8mb4`).
- Composer 2.x.
- Apache 2.4 con `mod_rewrite` o Nginx con bloque equivalente.

## Instalación local

```bash
git clone https://github.com/tmtbtck84k-ship-it/gmc_constructora.git
cd gmc_constructora

# 1. Dependencias PHP (PHPMailer, mPDF, PhpSpreadsheet)
composer install

# 2. Variables de entorno
cp .env.example application/config/.env.php
# Editar application/config/.env.php con credenciales reales

# 3. Crear BD
mysql -u root -p -e "CREATE DATABASE gmc_erp DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 4. Migrar y poblar datos iniciales
php index.php cli/migrate latest

# 5. Permisos en carpetas escribibles
chmod -R 750 application/logs application/cache storage/uploads storage/tmp
chown -R www-data:www-data application/logs application/cache storage/

# 6. Servir
php -S localhost:8000   # local
# o configurar Apache/Nginx con DocumentRoot apuntando a public/
```

## Credenciales iniciales

Después del seed, el usuario administrador es:

- **Email:** `admin@gmc.cl`
- **RUT:** `11111111-1`
- **Password temporal:** `GMC.2026!` (debes cambiarla en el primer login)

## Estructura

```
gmc_constructora/
├── application/
│   ├── config/         Configuración (db, routes, autoload, hooks, migration, .env)
│   ├── controllers/    Controllers delgados (auth, admin, maestros, finanzas, compras, obras, reportes, cli)
│   ├── core/           MY_Controller, MY_Model
│   ├── helpers/        format, rut, menu
│   ├── hooks/          Pre-system / pre-controller
│   ├── language/       i18n (español)
│   ├── libraries/      Auth, Acl, Audit, Uploader, Notifier, RateLimiter, Pdf, Excel
│   ├── migrations/     Migraciones por timestamp + sql/ (DDL canónico y seed)
│   ├── models/         Repositorios (uno por entidad)
│   ├── seeders/        Seeders PHP idiomáticos
│   ├── services/       Lógica de negocio por dominio
│   └── views/          Vistas Bootstrap 5 (layout, auth, dashboard, maestros, ...)
├── public/
│   ├── assets/         CSS/JS/img/vendor (sin CDN, todo local)
│   ├── index.php       Front controller
│   └── .htaccess
├── storage/            Uploads y tmp (FUERA de DocumentRoot)
├── system/             Core de CodeIgniter 3.1.13
├── tests/              Pruebas
├── composer.json
├── .env.example
├── .gitignore
└── README.md
```

## Comandos CLI

```bash
php index.php cli/migrate latest     # Aplica migraciones pendientes
php index.php cli/migrate rollback   # Rollback a versión anterior
php index.php cli/migrate status     # Estado de migraciones
php index.php cli/seed AdminUser     # Re-seedea admin con clave fresca
php index.php cli/backup             # Backup de BD (requiere mysqldump)
php index.php cli/cleanup            # Limpieza de huérfanos / sesiones
php index.php cli/mailer             # Procesa cola de notificaciones
```

## Roles y permisos

| Rol | Descripción |
|---|---|
| `admin` | Gestión de usuarios, roles y configuración. |
| `gerencia` | Acceso transversal con foco en reportes y dashboard. |
| `finanzas` | Validación / programación / pago de SDP, exportes contables. |
| `jefe_proyecto` | Lidera obra: SDP, bitácora, cierre, aprueba rindes de su proyecto. |
| `administrador_obra` | Apoyo administrativo en una o varias obras: rindes, compras, bitácora. |
| `bodega_obra` | Recepciones, compras y rinde de gastos en terreno. |
| `contabilidad` | Sólo lectura transversal + exportes. |

Catálogo completo de permisos en formato `<modulo>.<recurso>.<accion>` se carga vía seeder. Verificación con `Acl::can($permiso)` o `MY_Controller::require_permission($permiso)`.

## Licencia

Propietario: Constructora GMC. Uso interno. No redistribuir.
