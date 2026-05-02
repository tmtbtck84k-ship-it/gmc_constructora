#!/usr/bin/env bash
# ----------------------------------------------------------------------
# fetch-vendor.sh — Descarga assets frontend a public/assets/vendor/
# Sin CDN, todo se sirve local.
#
# Uso: bash bin/fetch-vendor.sh
# ----------------------------------------------------------------------
set -euo pipefail

cd "$(dirname "$0")/.."
VENDOR="public/assets/vendor"
mkdir -p "$VENDOR"/{bootstrap/css,bootstrap/js,bootstrap-icons/fonts,jquery,datatables,select2,sweetalert2,chartjs}

echo "==> Bootstrap 5.3.3"
curl -sSL -o "$VENDOR/bootstrap/css/bootstrap.min.css"     https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css
curl -sSL -o "$VENDOR/bootstrap/js/bootstrap.bundle.min.js" https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js

echo "==> Bootstrap Icons 1.11.3"
curl -sSL -o "$VENDOR/bootstrap-icons/bootstrap-icons.css"      https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css
curl -sSL -o "$VENDOR/bootstrap-icons/fonts/bootstrap-icons.woff2" https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/fonts/bootstrap-icons.woff2
curl -sSL -o "$VENDOR/bootstrap-icons/fonts/bootstrap-icons.woff"  https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/fonts/bootstrap-icons.woff
# Reescribir paths relativos en CSS (la copia ya apunta a fonts/<x>)

echo "==> jQuery 3.7.1"
curl -sSL -o "$VENDOR/jquery/jquery-3.7.1.min.js" https://code.jquery.com/jquery-3.7.1.min.js

echo "==> DataTables 1.13.10 + integración Bootstrap 5"
curl -sSL -o "$VENDOR/datatables/jquery.dataTables.min.js"     https://cdn.datatables.net/1.13.10/js/jquery.dataTables.min.js
curl -sSL -o "$VENDOR/datatables/dataTables.bootstrap5.min.js" https://cdn.datatables.net/1.13.10/js/dataTables.bootstrap5.min.js
curl -sSL -o "$VENDOR/datatables/dataTables.bootstrap5.min.css" https://cdn.datatables.net/1.13.10/css/dataTables.bootstrap5.min.css
curl -sSL -o "$VENDOR/datatables/es-CL.json"                   https://cdn.datatables.net/plug-ins/1.13.10/i18n/es-CL.json

echo "==> Select2 4.1.0 + tema Bootstrap 5"
curl -sSL -o "$VENDOR/select2/select2.min.css" https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css
curl -sSL -o "$VENDOR/select2/select2.min.js"  https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js
curl -sSL -o "$VENDOR/select2/select2-bootstrap-5.min.css" https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css

echo "==> SweetAlert2 11.10.x"
curl -sSL -o "$VENDOR/sweetalert2/sweetalert2.min.css"      https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css
curl -sSL -o "$VENDOR/sweetalert2/sweetalert2.all.min.js"   https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js

echo "==> Chart.js 4.4.x"
curl -sSL -o "$VENDOR/chartjs/chart.umd.min.js" https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js

echo ""
echo "  Vendors descargados en $VENDOR"
echo ""
du -sh "$VENDOR"
