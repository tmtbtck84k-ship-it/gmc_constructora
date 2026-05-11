# Acta de Entrega — ERP GMC, Fase 1

**Proyecto:** ERP GMC — Modelo de Gestión Integral (Fase 1: MVP Operativo y Trazabilidad)
**Cliente:** Constructora GMC
**Proveedor:** _______________________________
**Fecha de entrega:** _______ / _______ / _______
**Versión entregada:** 1.0.0

---

## 1. Antecedentes

Conforme a la propuesta de desarrollo "ERP GMC — Modelo de Gestión Integral (versión faseada)" con fecha 22-01-2026, presupuesto fijo de CLP$ 1.852.600 (≈ 46,63 UF) y plazo de 12 semanas, se entrega la Fase 1 con los componentes acordados.

## 2. Alcance entregado

### 2.1 Base del ERP (transversal)
- [x] Gestión de usuarios y roles (admin, gerencia, finanzas, jefe_proyecto, administrador_obra, bodega_obra, contabilidad).
- [x] Maestros: Clientes, Proveedores/Subcontratistas, Proyectos/Obras, Centros de Costo (por obra + Administración), Tipos de Gasto.
- [x] Gestión documental: carga, descarga y soft delete de adjuntos polimórficos.
- [x] Auditoría: registro de acciones (quién/cuándo/qué) en `gmc_auditoria_logs`.
- [x] Notificaciones por correo encoladas y procesadas vía cron.

### 2.2 Control financiero (Solicitud de Pago)
- [x] Formulario de SDP con centro de costo, tipo de gasto, multimoneda (CLP, USD, UF, EUR).
- [x] Flujo: Pendiente → Validada → Programada → Pagada / Rechazada (con motivo).
- [x] Adjunto de respaldo (factura/boleta/comprobante) y comentarios.
- [x] Bandeja para Finanzas con filtros (obra, proveedor, estado, fecha).
- [x] Exportación CSV y XLSX.
- [x] Snapshot de tipo de cambio al crear (multimoneda nativa con conversión a CLP).

### 2.3 Compras y costos
- [x] Registro de compra/recepción con items, centro de costo, multimoneda.
- [x] Vinculación de factura a Solicitud de Pago o a Rinde de Gastos.
- [x] Rinde de Gastos con items, aprobación según regla (JP del proyecto o Finanzas), generación de SDP de pago al rinde aprobado.
- [x] Consulta consolidada de gastos por obra y centro de costo.

### 2.4 Ejecución de obra
- [x] Bitácora de obra (avance, observación, incidencia, otro) con adjuntos.
- [x] Edición acotada a 24 hrs para autor; admin/gerencia siempre.
- [x] Presupuesto inicial por obra con versionado y única vigente.
- [x] Cierre de obra: borrador, validación de SDPs en estado final antes de cerrar, generación de PDF.

### 2.5 Reportes mínimos (gestión)
- [x] Estado de pagos (por obra, proveedor, estado, fechas) con totales por estado en CLP.
- [x] Gastos por obra y centro de costo (consolidado SDPs + Compras + Rindes).
- [x] Desviación gasto real vs presupuesto inicial con semáforo verde/ámbar/rojo.
- [x] Dashboard ejecutivo con 6 KPIs reales y gráfico de gasto mensual.

### 2.6 Automatizaciones y operación
- [x] Sincronización diaria de tipos de cambio desde mindicador.cl (Banco Central de Chile).
- [x] Backup MySQL diario con compresión gzip y rotación de N días.
- [x] Limpieza diaria de datos transitorios (login_attempts, notificaciones, adjuntos huérfanos).
- [x] Cola de notificaciones procesada cada 5 minutos.
- [x] Generador de correlativos thread-safe (SDP, OCI, RDG, BIT, OBR).

### 2.7 Seguridad
- [x] Bcrypt cost 12 + CSRF + sesión en BD + headers de seguridad + rate limiting.
- [x] Validación de RUT chileno con módulo 11.
- [x] Subida de adjuntos con validación MIME real, whitelist y storage fuera de DocumentRoot.
- [x] ACL granular con 57 permisos asignados por rol.
- [x] HTTPS forzado en producción + Let's Encrypt.

## 3. Entregables documentales

| # | Documento | Ubicación |
|---|---|---|
| 1 | Diagnóstico del proyecto actual | `_rediseno/docs/01_diagnostico_codigo.md` |
| 2 | Diagnóstico del archivo SQL | `_rediseno/docs/02_diagnostico_sql.md` |
| 3 | Decisión técnica | `_rediseno/docs/03_decision_tecnica.md` |
| 4 | Documento de arquitectura | `_rediseno/docs/04_arquitectura.md` |
| 5 | Modelo de base de datos | `_rediseno/docs/05_modelo_datos.md` |
| 6 | SQL final ejecutable | `_rediseno/sql/erp_gmc.sql` |
| 7 | Migraciones y seeders | `_rediseno/migrations/` y `_rediseno/seeders/` |
| 8 | Backlog MVP | `_rediseno/docs/06_backlog_mvp.csv` |
| 9 | Plan de pruebas | `docs/01_PLAN_PRUEBAS.md` |
| 10 | Hardening y seguridad | `docs/02_HARDENING.md` |
| 11 | Manual de uso | `docs/03_MANUAL_USO.md` |
| 12 | Guía de roles | `docs/04_GUIA_ROLES.md` |
| 13 | Instructivo de despliegue VPS | `docs/05_DEPLOY_VPS.md` |
| 14 | Plan de capacitación | `docs/06_CAPACITACION.md` |
| 15 | Acta de entrega (este documento) | `docs/07_ACTA_ENTREGA.md` |
| 16 | Roadmap Fase 2 y 3 | `docs/08_ROADMAP_FASE_2_3.md` |
| 17 | Smoke test E2E | `tests/SmokeTest.php` |

## 4. Inventario de código

| Métrica | Valor |
|---|---|
| Archivos PHP de aplicación | ~155 |
| Tablas en base de datos | 32 (con prefijo `gmc_`) |
| Permisos en catálogo ACL | 57 |
| Migraciones versionadas | 4 |
| Seeders ejecutables | 1 (geografía Chile + roles + permisos + admin + tipos de gasto + monedas + estados + correlativos + TC iniciales) |
| Comandos CLI | 5 (`migrate`, `seed`, `mailer`, `backup`, `cleanup`, `sync_tc`) |
| Cobertura de la propuesta funcional | 100% del alcance de Fase 1 |

## 5. Criterios de aceptación

| # | Criterio | Cumplido |
|---|---|---|
| 1 | Flujos de Solicitud de Pago operativos con estados y auditoría | [ ] |
| 2 | Compras/recepción con centro de costo y adjuntos funcionando | [ ] |
| 3 | Bitácora y cierre básico disponibles por obra | [ ] |
| 4 | Reportes mínimos accesibles por rol correspondiente | [ ] |
| 5 | Capacitación realizada | [ ] |
| 6 | Acta de entrega firmada | [ ] |

## 6. Fuera de alcance (registrado para Fase 2/3)

Conforme a la propuesta original, **no se incluyen** en Fase 1:
- Presupuestos de venta end-to-end completo (licitaciones, versiones, costeo avanzado).
- Motor de asignación objetiva de subcontratistas y Orden de Trabajo (OT).
- Gantt jerárquico de obras (hitos + actividades + dependencias).
- Informe de cierre completo con evaluación de subcontratista y lecciones aprendidas estructuradas.
- Dashboards ejecutivos avanzados (margen, EBITDA con prorrateos).
- Integraciones (bancos, contabilidad externa, ERP externo, SII, firma electrónica).
- App móvil/offline.

Estos componentes están detallados con estimaciones en `08_ROADMAP_FASE_2_3.md`.

## 7. Soporte y mantenimiento

Durante los **30 días** posteriores a esta acta, el proveedor garantiza la corrección sin costo de:
- Defectos funcionales sobre el alcance entregado.
- Errores derivados del despliegue en el ambiente acordado.

**No incluye** en la garantía:
- Cambios de alcance.
- Errores derivados de modificaciones hechas por el cliente.
- Configuraciones de infraestructura externas al proyecto (DNS, SMTP de terceros, etc).
- Bugs de software de terceros (mPDF, PHPMailer, mindicador.cl).

Pasados los 30 días, soporte y mantenimiento se cotizan aparte (ver propuesta original sección 8).

## 8. Activos entregados

- [ ] Repositorio Git con código fuente completo.
- [ ] Acceso al ambiente de producción (VPS).
- [ ] Credenciales del usuario administrador inicial.
- [ ] Credenciales SMTP/BD documentadas en `.env.example`.
- [ ] Backups iniciales de BD.
- [ ] Manual de uso en PDF.
- [ ] Grabación de la sesión de capacitación.

## 9. Pagos

Forma de pago acordada (referencia):
- 30% al inicio (levantamiento + diseño + setup) — Sprint 0.
- 40% al finalizar Semana 6 (Base ERP + Finanzas operativo) — Sprints 1 y 2.
- 20% al finalizar Semana 10 (Compras/Costos + Obras operativo) — Sprints 3 y 4.
- 10% al finalizar Semana 12 (puesta en marcha y acta de entrega) — Sprints 5 y 6.

Estado al momento de la firma:
- [ ] Hito 1 pagado
- [ ] Hito 2 pagado
- [ ] Hito 3 pagado
- [ ] Hito 4 pendiente / pagado

## 10. Firmas

Las partes declaran que el alcance entregado cumple con la propuesta firmada y aceptan la entrega formal de la Fase 1.

|  Por el Cliente  |  Por el Proveedor  |
|:---:|:---:|
|  |  |
|  |  |
|  |  |
| _________________________ | _________________________ |
| Nombre: | Nombre: |
| Cargo: | Cargo: |
| RUT: | RUT: |
| Fecha: | Fecha: |

---

**Anexos:**
- Anexo A: Inventario detallado de funcionalidades (este documento).
- Anexo B: Roadmap Fase 2 y Fase 3.
- Anexo C: Lista de usuarios creados con sus roles.
- Anexo D: Acta de capacitación firmada por los asistentes.
