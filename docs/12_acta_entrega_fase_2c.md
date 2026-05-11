# Acta de Entrega — Fase 2-C: Gantt Jerárquico

**Cliente:** Constructora GMC
**Proveedor:** Tech Lead asignado
**Sprint cerrado:** Fase 2-C (3 sprints A, B, C)
**Fecha de entrega:** ____________________
**Versión:** 2.2.0

---

## 1. Alcance entregado

### Sprint A — Modelo + CRUD jerárquico (3 niveles)

- [x] Migración aditiva `20260615000001_crear_gantt_y_calendario.php`:
  - 3 columnas nuevas en `gmc_proyectos` (calendario laboral por proyecto)
  - Tabla `gmc_feriados` con seed 15 feriados oficiales Chile 2026
  - Tabla `gmc_hitos`, `gmc_actividades`, `gmc_actividad_dependencias`
  - 5 permisos nuevos asignados a roles
  - Correlativos `hito` y `actividad` registrados
- [x] Servicios: `CalendarioService`, `HitoService`, `ActividadService`, `FeriadoService`
- [x] Repos: `HitoRepo`, `ActividadRepo`, `DependenciaRepo`, `FeriadoRepo`, `UsuarioRepo->activos()`
- [x] Controllers: `obras/Hitos`, `obras/Actividades`, `obras/Feriados`, `obras/Gantt`
- [x] Vistas: index + form de cada uno + importador CSV de feriados
- [x] Form de Proyectos extendido con bloque "Calendario laboral"
- [x] Sidebar y routes actualizados

### Sprint B — Gantt visual + Dependencias

- [x] Visualizador Frappe Gantt v0.6.1 integrado
- [x] Drag & drop de barras con AJAX (mover y redimensionar)
- [x] Recálculo automático en cascada respetando calendario (`PlanificadorService`)
- [x] Modal "Nueva dependencia" con FS / SS / FF / SF + lag
- [x] Validación de dependencias circulares (DFS antes de guardar)
- [x] Eliminar dependencia desde panel de detalle
- [x] Slider de % avance desde el panel de detalle
- [x] Zoom Día / Semana / Mes con persistencia en localStorage
- [x] Colores por hito + ruta crítica en rojo
- [x] CSRF rotativo propagado automáticamente en cada respuesta JSON

### Sprint C — CPM + Alertas + Exports + Reportes + Docs

- [x] `CpmService`: forward + backward pass, holguras, marca de actividades críticas
- [x] Toggle "Ruta crítica" en el Gantt
- [x] Botón "Recalcular CPM" + recálculo automático en cada movimiento o cambio de dependencia
- [x] CLI `cli/gantt_alertas`: detecta actividades retrasadas, envía correo consolidado al responsable y JP, encola en `gmc_notificaciones`
- [x] Export PDF del Gantt (mPDF, A3 apaisado, leyenda + tabla)
- [x] Export PNG del Gantt (cliente, html2canvas)
- [x] Reporte Avance vs Planificado con semáforo verde/amarillo/rojo + desviación % y días
- [x] Manual de capacitación (60 min) — `docs/11_capacitacion_gantt.md`
- [x] Acta de entrega — este documento

## 2. Criterios de aceptación verificados

| ID Backlog | Criterio | Estado |
|---|---|:---:|
| F2C-A-01 | Migración ejecuta sin error, agrega columnas, crea tablas, registra correlativos y permisos | ✓ |
| F2C-A-02 | Repos heredan `MY_Model`, soft delete donde aplique, métodos específicos | ✓ |
| F2C-A-03 | `CalendarioService::esLaboral`, `sumarDiasLaborales`, `diasEntre` respetan días + trabajo en feriados | ✓ |
| F2C-A-04 | Crear hito con form simple, código `HT-NN/OBR-...`, auditoría | ✓ |
| F2C-A-05 | Crear actividad con form, calcula término por duración + calendario, código `ACT-NNN/OBR-...` | ✓ |
| F2C-A-06 | Editar hito/actividad, bloqueado si proyecto cerrado, auditoría con delta, recalcula avance del hito | ✓ |
| F2C-A-07 | Eliminar (soft delete), borra dependencias asociadas, auditoría | ✓ |
| F2C-A-08 | Configurar calendario laboral del proyecto desde su ficha | ✓ |
| F2C-A-09 | Admin de feriados (CRUD + import CSV), solo admin | ✓ |
| F2C-A-10 | Lista plana de actividades con filtros (hito, responsable, fechas, estado) | ✓ |
| F2C-B-01 | Frappe Gantt en `/obras/gantt` con todos los hitos+actividades, días no laborales y críticas en color | ✓ |
| F2C-B-02 | Drag & drop actualiza fechas vía AJAX, recálculo automático de dependientes | ✓ |
| F2C-B-03 | Zoom diario/semanal/mensual persistente | ✓ |
| F2C-B-04 | Crear dependencia con tipo + lag, visual con flecha entre barras | ✓ |
| F2C-B-05 | Eliminar dependencia desde panel de detalle | ✓ |
| F2C-B-06 | Validación de ciclos (DFS) con mensaje claro | ✓ |
| F2C-B-07 | Recálculo automático en cascada controlada (sin loops infinitos) | ✓ |
| F2C-B-08 | Slider 0-100, recalcula `porcentaje_avance` del hito | ✓ |
| F2C-C-01 | CPM: `es_critica` y `holgura_dias` calculados con forward + backward pass | ✓ |
| F2C-C-02 | Toggle "Ruta crítica" resalta actividades críticas en rojo | ✓ |
| F2C-C-03 | Job `cli/gantt_alertas` detecta atrasadas y envía correo al responsable + JP | ✓ |
| F2C-C-04 | Exportar Gantt a PDF (A3 apaisado con leyenda) | ✓ |
| F2C-C-05 | Exportar Gantt a PNG (cliente html2canvas) | ✓ |
| F2C-C-06 | Reporte avance vs planificado con semáforo + desviación días | ✓ |
| F2C-C-07 | Capacitación 60 min documentada con casos guiados | ✓ |
| F2C-C-08 | Acta de entrega firmada | ⏳ Pendiente firma |

## 3. Métricas técnicas

- **PHP files agregados/modificados:** 28
- **Líneas de código nuevas (sin tests):** ~3.500
- **Migraciones nuevas:** 1 (aditiva, no destructiva)
- **Tablas nuevas:** 4
- **Permisos nuevos:** 5
- **Correlativos nuevos:** 2 (hito, actividad)
- **Endpoints AJAX nuevos:** 7
- **Vistas nuevas:** 11 (incluye PDF y reporte)
- **Documentos:** 12 markdown + 1 mockup HTML

## 4. Configuración de cron en producción

Agregar al crontab del usuario web:

```
# Alertas diarias de actividades retrasadas (8:00 AM)
0 8 * * *  /usr/bin/php /var/www/gmc/public/index.php cli/gantt_alertas >> /var/log/gmc-gantt-alertas.log 2>&1
```

## 5. Soporte post-entrega

**Período de soporte:** 30 días desde la firma de esta acta.
**Cubre:**
- Bugfixes en lo entregado en Fase 2-C.
- Aclaraciones de uso vía email/WhatsApp.
- 1 sesión adicional remota de hasta 60 min para refuerzo si fuese necesario.

**No cubre:**
- Nuevas funcionalidades.
- Cambios de modelo de datos.
- Importación masiva de proyectos históricos.

## 6. Aceptación de prueba en ambiente de cliente

| Ítem | Cliente probó | OK | Comentarios |
|---|:---:|:---:|---|
| Migración aplicada en producción | ☐ | ☐ | |
| Crear hito + actividad | ☐ | ☐ | |
| Drag & drop en Gantt | ☐ | ☐ | |
| Crear dependencia FS | ☐ | ☐ | |
| Validación de ciclo | ☐ | ☐ | |
| Recálculo en cascada | ☐ | ☐ | |
| Toggle ruta crítica | ☐ | ☐ | |
| Export PDF | ☐ | ☐ | |
| Export PNG | ☐ | ☐ | |
| Reporte avance vs planificado | ☐ | ☐ | |
| Cron de alertas configurado | ☐ | ☐ | |
| Capacitación realizada con equipo | ☐ | ☐ | |

## 7. Firmas

**Por GMC (Cliente):**
Nombre: ____________________________
Cargo:  ____________________________
Firma:  ____________________________
Fecha:  ____________________________

**Por Tech Lead (Proveedor):**
Nombre: ____________________________
Firma:  ____________________________
Fecha:  ____________________________

---

> **Próximo paso sugerido:** Fase 3 — Reportería avanzada / BI dashboards, integración con SII, y mobile companion. Ver `docs/08_ROADMAP_FASE_2_3.md`.
