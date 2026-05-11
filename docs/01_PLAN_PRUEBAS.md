# Plan de Pruebas — ERP GMC Fase 1

**Versión:** 1.0
**Fecha:** 2026-05-04
**Alcance:** Validación funcional de los módulos entregados en Fase 1.

---

## 1. Estrategia de pruebas

| Nivel | Tipo | Responsable | Cuándo |
|---|---|---|---|
| Unitarias | PHPUnit (servicios y validadores) | Desarrollo | Cada commit |
| Integración | E2E smoke test (login + flujo completo) | Desarrollo | Pre-release |
| Aceptación | Manual con checklist por módulo | Cliente / QA GMC | Pre-deploy a producción |
| Regresión | Re-ejecución del checklist completo | Cliente | Cada release menor |

## 2. Ambientes

- **Development:** `http://gmc.local` (XAMPP local).
- **Testing:** `https://qa.gmc-erp.cl` (VPS, datos sintéticos).
- **Producción:** `https://erp.gmc.cl` (VPS, datos reales).

## 3. Datos de prueba

| Tipo | Cantidad | Origen |
|---|---|---|
| Usuarios por rol | 1 cada uno (7 roles) | Seeder + creación manual |
| Clientes | 5 reales | CSV cliente |
| Proveedores | 10 reales (2 subcontratistas) | CSV cliente |
| Proyectos | 3 (1 planificación, 1 ejecución, 1 pausado) | Manual con datos reales |
| SDPs | 20 (varias monedas y estados) | Manual durante pruebas |

## 4. Casos de prueba por módulo

### 4.1 Autenticación

| ID | Caso | Pasos | Resultado esperado |
|---|---|---|---|
| AU-01 | Login válido | RUT correcto + clave correcta | Redirige a `/dashboard` o `/password/change` si force=1 |
| AU-02 | Login inválido | Clave incorrecta | Mensaje genérico "Credenciales inválidas". NO indica si el RUT existe. |
| AU-03 | Bloqueo por intentos | 5 fallos seguidos en <15 min | Mensaje "Demasiados intentos. Intenta nuevamente en unos minutos" |
| AU-04 | RUT inválido | RUT con DV erróneo | Validación frontal lo rechaza; backend confirma rechazo |
| AU-05 | Recuperación clave | `/password/forgot` con email válido | Mensaje genérico de éxito; correo encolado en `gmc_notificaciones` |
| AU-06 | Reset clave con token | Click link del correo | Permite definir nueva clave |
| AU-07 | Cambio forzado | Usuario con `force_password_change=1` | Cualquier ruta redirige a `/password/change` |
| AU-08 | Validador fortaleza | Clave nueva sin mayúscula | Rechaza con mensaje específico |
| AU-09 | Logout | Click "Cerrar sesión" | Sesión destruida, redirige a `/login` |
| AU-10 | Sesión expirada | Esperar 2hr o cookie inválida | Próxima petición redirige a `/login` |

### 4.2 Maestros

| ID | Caso | Resultado esperado |
|---|---|---|
| MA-01 | Crear cliente con RUT válido | Cliente creado; visible en lista |
| MA-02 | Crear cliente con RUT duplicado | Rechaza con "El RUT ya está registrado" |
| MA-03 | Crear cliente con email duplicado | Rechaza con "El email ya está registrado" |
| MA-04 | Editar cliente | Cambios persisten; auditoría registra `cliente.editar` |
| MA-05 | Eliminar cliente (soft) | Desaparece de lista; queda en BD con `deleted_at` |
| MA-06 | Crear proveedor subcontratista | Flag `es_subcontratista=1`; visible en filtro |
| MA-07 | Crear proyecto | Genera correlativo `OBR-AAAA-NNN`; crea CC `ADM-OBR` automático |
| MA-08 | Asignar JP y administrador de obra | Selectores muestran usuarios activos; persisten |
| MA-09 | CC del proyecto | Nuevo CC respeta UNIQUE (proyecto_id+codigo) |
| MA-10 | CC ADM general no editable | Botón "Editar" no aparece; URL directa muestra error |
| MA-11 | Tipo de gasto duplicado | Rechaza por código UNIQUE |
| MA-12 | Tipos de cambio manual | UPSERT correcto; valor actualizado en card |
| MA-13 | Tipo de cambio sync | `cli/sync_tc` actualiza solo `auto`, respeta `manual` |

### 4.3 Solicitud de Pago (SDP)

| ID | Caso | Resultado esperado |
|---|---|---|
| SDP-01 | Crear SDP en CLP | Estado=Pendiente; correlativo `SDP-AAAA-NNNN`; `monto_total_clp = monto_total` |
| SDP-02 | Crear SDP en USD | Snapshot de TC; `monto_total_clp = monto * TC` |
| SDP-03 | Crear SDP en moneda sin TC | Bloquea con mensaje claro pidiendo cargar TC |
| SDP-04 | Adjuntar factura PDF al crear | Archivo subido; lista en ficha; descarga OK |
| SDP-05 | Adjuntar archivo no permitido (.exe) | Rechaza con mensaje de extensión |
| SDP-06 | Adjuntar > 10MB | Rechaza por tamaño |
| SDP-07 | Editar SDP en Pendiente | Permite; auditoría registra delta |
| SDP-08 | Editar SDP en Validada | Bloquea con "Sólo en estado Pendiente" |
| SDP-09 | Validar SDP | Pendiente → Validada; correo a Finanzas; log en `gmc_sdp_estados_log` |
| SDP-10 | Programar SDP | Validada → Programada; fecha y forma de pago obligatorias |
| SDP-11 | Pagar SDP | Programada → Pagada (final); SDP queda inmutable |
| SDP-12 | Rechazar SDP | Cualquier estado no-final → Rechazada con motivo (mín 5 chars) |
| SDP-13 | Línea de tiempo en ficha | Muestra todas las transiciones con quién y cuándo |
| SDP-14 | Eliminar Pendiente | Soft delete; ya no aparece en bandeja |
| SDP-15 | Eliminar Pagada | Bloquea con "Sólo en Pendiente o Rechazada" |
| SDP-16 | Bandeja con filtros | Filtro por proyecto, proveedor, estado, fechas funciona |
| SDP-17 | Exportar CSV | Archivo CSV con BOM, todas las columnas, respeta filtros |
| SDP-18 | Exportar XLSX | Archivo Excel con encabezados negrita, freeze en fila 1 |
| SDP-19 | RBAC: bodega ve SDP? | Sólo si tiene `finanzas.sdp.ver`; según seed, no |
| SDP-20 | Notificación al solicitante | Al validar/rechazar/pagar llega correo con motivo |

### 4.4 Compras y Rindes

| ID | Caso | Resultado esperado |
|---|---|---|
| CO-01 | Crear compra con 3 items | Total se calcula; estado Borrador |
| CO-02 | Confirmar recepción | Borrador → Recibida; queda inmutable salvo anular |
| CO-03 | Anular compra | Pide motivo; estado Anulada (final) |
| CO-04 | Vincular compra a SDP | Selector muestra SDPs activas; vínculo persiste |
| CO-05 | Vincular compra a Rinde | Idem con rindes |
| CO-06 | Crear rinde con items | Estado Borrador; total = suma |
| CO-07 | Enviar rinde sin items | Bloquea con "Agrega al menos un ítem" |
| CO-08 | Aprobar rinde con proyecto | Solo el JP del proyecto puede; gerencia/admin también |
| CO-09 | Aprobar rinde sin proyecto | Solo Finanzas/Gerencia/Admin |
| CO-10 | Rechazar rinde | Pide motivo (mín 5 chars); notifica solicitante |
| CO-11 | Generar SDP del rinde aprobado | Crea SDP enlazada con monto del rinde |

### 4.5 Obras

| ID | Caso | Resultado esperado |
|---|---|---|
| OB-01 | Crear bitácora | Correlativo `BIT-AAAA-NNNN/OBR-2026-001`; aparece en lista |
| OB-02 | Editar bitácora < 24h | Permite; admin/gerencia siempre |
| OB-03 | Editar bitácora > 24h | Bloquea para autor común |
| OB-04 | Crear presupuesto inicial | Marca v1 vigente; total = suma |
| OB-05 | Nueva versión | Copia ítems de v1; v2 vigente, v1 anterior |
| OB-06 | Crear borrador cierre | Permite; estado Borrador |
| OB-07 | Cerrar con SDPs activas | Bloquea con "N SDP(s) en estado distinto de Pagada/Rechazada" |
| OB-08 | Cerrar con todas finales | Cierre exitoso; proyecto pasa a `cerrado` |
| OB-09 | Generar PDF cierre | Descarga PDF con identificación, resumen, presupuesto, bitácoras |

### 4.6 Reportes y Dashboard

| ID | Caso | Resultado esperado |
|---|---|---|
| RE-01 | Dashboard KPIs | 6 cards con números reales (no `—`) |
| RE-02 | Gráfico mensual | Barras de últimos 12 meses; suma SDP+Compras |
| RE-03 | Estado de Pagos filtros | Cards de resumen por estado totalizan en CLP |
| RE-04 | Gastos por obra | Pivot consolidado de SDP+Compras+Rindes |
| RE-05 | Desviación con presupuesto | Semáforo verde ≤0%, ámbar 0-5%, rojo >5% |
| RE-06 | Desviación sin presupuesto | Mensaje "carga el presupuesto inicial" |
| RE-07 | Exportes CSV | Todos respetan filtros; BOM UTF-8 |

### 4.7 Auditoría

| ID | Caso | Resultado esperado |
|---|---|---|
| AD-01 | Login registra `auth.login.ok` | Aparece en log con IP y user-agent |
| AD-02 | Login fallido registra `auth.login.failed` | Idem con RUT intentado |
| AD-03 | SDP transiciones | Cada cambio queda con estado_anterior y estado_nuevo |
| AD-04 | Edición de cliente | `logChanges` graba sólo el delta |
| AD-05 | Filtros y export CSV | Funcionan con BOM y respetan filtros |

### 4.8 Operación (jobs cron)

| ID | Caso | Resultado esperado |
|---|---|---|
| OP-01 | `cli/sync_tc` con TC manual existente | Respeta manual; actualiza sólo `auto` |
| OP-02 | `cli/backup` | Genera `gmc-AAAAMMDD-HHMMSS.sql.gz`; rota > 14 días |
| OP-03 | `cli/cleanup` | Borra login_attempts > 30d, notif > 90d, adjuntos huérfanos |
| OP-04 | `cli/mailer` | Procesa cola; reintenta hasta 3 veces; marca fallida |

## 5. Criterios de aceptación

Para considerar Fase 1 entregada:

- [ ] 100% de los casos AU, MA, SDP, CO, OB pasan en producción.
- [ ] 100% de los casos OP funcionan en cron real.
- [ ] Backup generado y restaurable: prueba de restore en otro ambiente.
- [ ] Smoke test E2E corre en menos de 60 segundos sin errores.
- [ ] Capacitación realizada con asistencia firmada.
- [ ] Acta de entrega firmada por responsable de negocio.

## 6. Plantilla de reporte de bug

```
Título:
Severidad: (Crítica / Alta / Media / Baja)
Módulo afectado:
Ambiente:
Pasos para reproducir:
1. ...
2. ...
Resultado obtenido:
Resultado esperado:
Adjunto: (screenshot o log)
Detectado por:
Fecha:
```
