# Manual de Capacitación — Gantt Jerárquico (Fase 2-C)

**Duración estimada:** 60 minutos
**Audiencia:** Jefes de Proyecto, Gerencia, Administradores de Obra
**Pre-requisito:** sesión iniciada con permisos de `obras.gantt.ver` (todos los roles operativos lo tienen)

---

## 1. Conceptos previos (5 min)

| Concepto | Definición práctica |
|---|---|
| **Proyecto** | La obra completa (ej: "Edificio Las Condes"). Tiene un código `OBR-2026-NNN`. |
| **Hito** | Un grupo de actividades con un objetivo común y fecha de cumplimiento (ej: "Movimiento de Tierra"). Código `HT-NN/OBR-...`. |
| **Actividad** | Tarea concreta con fecha de inicio, duración y responsable (ej: "Excavación Lote A"). Código `ACT-NNN/OBR-...`. |
| **Dependencia** | Relación entre dos actividades. Tipo más común: FS (la sucesora arranca cuando termina la predecesora). |
| **Calendario laboral** | Por defecto L–V; configurable por proyecto a L–S, L–D o personalizado. Permite o no trabajar feriados. |
| **Ruta crítica** | Cadena de actividades sin holgura: si una se atrasa, todo el proyecto se atrasa. |

## 2. Flujo recomendado (10 min)

1. **Configurar calendario del proyecto.** Maestros → Proyectos → Editar → bloque "Calendario laboral".
2. **Crear los hitos** del proyecto (en orden lógico).
3. **Bajo cada hito, crear las actividades** con su duración estimada.
4. **Conectar dependencias** entre actividades.
5. **Recalcular CPM** para identificar la ruta crítica.
6. **Día a día:** ir registrando avances (%), las fechas reales de inicio/término.
7. **Semanal:** revisar el reporte "Avance vs Planificado" y exportar PDF para la reunión.

## 3. Crear un hito (5 min)

1. Sidebar → **Hitos** → seleccionar proyecto → **Nuevo hito**.
2. Llenar:
   - **Nombre** (obligatorio): "Movimiento de Tierra"
   - **Descripción** (opcional)
   - **Fecha objetivo**: cuando se debe cumplir (referencial, no bloquea las actividades)
   - **Orden**: dejar en blanco para auto (10, 20, 30...)
3. Guardar. Se genera código `HT-01/OBR-2026-001`.

## 4. Crear una actividad (10 min)

1. Sidebar → **Actividades** → seleccionar proyecto → **Nueva actividad**.
2. Campos:
   - **Nombre**: "Excavación Lote A"
   - **Hito**: seleccionar el hito al que pertenece (puede quedar suelta)
   - **Fecha inicio planificada**: si cae en día no laboral, el sistema la mueve al siguiente laboral.
   - **Duración (días)**: en **días laborales del proyecto**. Si pone 5 días en un calendario L–V partiendo el viernes, el término será el jueves siguiente (saltando sábado y domingo).
   - **Responsable**: usuario asignado.
   - **Colaboradores**: texto libre (no FK, ej: "Cuadrilla 3, Subcontrato Estuco SpA").
3. Guardar. El sistema calcula automáticamente la **fecha de término planificada**.

> **Importante:** la duración considera el calendario del proyecto y los feriados configurados. No es necesario sumar manualmente.

## 5. Visualizar el Gantt (10 min)

Sidebar → **Gantt** → seleccionar proyecto.

- **Barras** representan actividades. Color por hito o rojo si están en ruta crítica.
- **Zoom**: botones Día / Semana / Mes (recordable por usuario en localStorage).
- **Click en una barra**: panel derecho muestra detalle, predecesoras, sucesoras y slider de avance.
- **Arrastrar barra**: mueve la actividad. Si tiene sucesoras dependientes, **se mueven en cascada** automáticamente.
- **Botón "Ruta crítica"**: resalta solo las actividades sin holgura.
- **Botón "Recalcular CPM"** (icono ↻): fuerza el recálculo de ruta crítica y holguras.

## 6. Crear dependencias (10 min)

1. En el Gantt → botón "Nueva dependencia".
2. Modal:
   - **Sucesora**: la actividad que depende.
   - **Predecesora**: la actividad de la que depende.
   - **Tipo**:
     - **FS** (default): "Pintura empieza cuando termine Estuco" → 90% de los casos
     - **SS**: ambas arrancan en paralelo (ej: Excavación + Compactación)
     - **FF**: ambas terminan al mismo tiempo (ej: Inspección de calidad termina cuando termina la actividad inspeccionada)
     - **SF**: raro; auditoría de turno saliente
   - **Lag (días)**: tiempo de espera entre las dos. Ej: FS con lag 3 = "Pintura empieza 3 días después que termine Estuco" (tiempo de fragüe).
3. Crear → la sucesora se mueve automáticamente al término del predecesor + lag.

> El sistema **valida ciclos**: si intentás A→B→C→A, lo rechaza con mensaje claro.

## 7. Eliminar dependencias

1. Click en la actividad sucesora → panel derecho.
2. En "Predecesoras", click en la `×` roja al lado de la dependencia.
3. Confirmar.

## 8. Registrar avance (5 min)

**Tres formas:**

1. **Desde el Gantt** (más rápido): click en barra → slider en panel derecho → mover → enter.
2. **Desde la lista**: Sidebar → Actividades → editar → cambiar `Avance %`.
3. **Drag & drop del progreso en la barra del Gantt** (el rectángulo interior verde).

Cuando llega a 100%, el sistema marca automáticamente la fecha real de término. Si todas las actividades de un hito llegan a 100%, el hito se marca completado.

## 9. Configurar calendario laboral del proyecto (5 min)

1. Maestros → Proyectos → Editar.
2. Bloque "Calendario laboral":
   - **Días laborales**: L–V / L–S / L–D / Personalizado
   - Si Personalizado: tildar los días específicos.
   - **Trabaja feriados**: si la obra opera incluso los feriados oficiales.
3. Guardar.

> Cambiar el calendario después de crear actividades **no recalcula fechas viejas**. Si cambia, conviene revisar el Gantt.

## 10. Administrar feriados (3 min)

Solo admin (`obras.feriado.editar`). Sidebar → Feriados.

- Ver todos los feriados de un año.
- Agregar / editar / eliminar feriados puntuales.
- **Importar CSV** masivo: encabezado `fecha,nombre,irrenunciable,tipo`. Si la fecha existe, actualiza; si no, crea.

## 11. Exportar y reportar (5 min)

- **PDF** (servidor, mPDF): botón "PDF" → descarga A3 apaisado con barra horizontal y leyenda de hitos.
- **PNG** (cliente, html2canvas): botón "PNG" → captura visual del Gantt.
- **Reporte Avance vs Planificado**: botón "Reporte avance" → tabla con semáforo verde/amarillo/rojo y desviación (% y días). Imprimible.

## 12. Alertas automáticas

El sistema corre `cli/gantt_alertas` cada mañana (cron 8:00). Detecta actividades con:
- Fecha de término planificada vencida + avance < 100%
- Envía un correo al **responsable** y al **jefe de proyecto** del proyecto, con la lista consolidada.
- Encola una notificación interna en `gmc_notificaciones`.

Para forzarlo manualmente:
```bash
/Applications/XAMPP/xamppfiles/bin/php public/index.php cli/gantt_alertas
```

## 13. Permisos

| Permiso | Quién |
|---|:---:|
| `obras.gantt.ver` | Todos los roles operativos |
| `obras.gantt.editar` | Admin, Gerencia, JP (solo su proyecto) |
| `obras.gantt.dependencia` | Admin, Gerencia, JP |
| `obras.gantt.exportar` | Admin, Gerencia, JP, Contabilidad |
| `obras.feriado.editar` | Solo Admin |

## 14. Casos prácticos guiados

### Caso A: Replanteo + Cimentación + Estructura

1. Crear hito "Obra Gruesa".
2. Crear actividades: Replanteo (3 días), Cimentación (10 días), Estructura (20 días).
3. Crear dependencias FS:
   - Cimentación depende de Replanteo
   - Estructura depende de Cimentación
4. Recalcular CPM → las 3 quedan críticas (no hay paralelas).
5. Mover el inicio de Replanteo a una semana después → todas se desplazan en cascada.

### Caso B: Pintura espera fragüe del estuco

1. Crear actividades Estuco y Pintura.
2. Crear dependencia: Pintura FS Estuco con **lag = 3** (3 días de fragüe).
3. Si Estuco termina el 15-mar, Pintura arranca el 18-mar (saltando feriados/fines de semana del calendario).

## 15. Buenas prácticas

- **Mantener la duración pequeña**: actividades de 1-15 días. Si una actividad dura más de 4 semanas, partila.
- **Nombrá con verbo + sustantivo**: "Excavar lote A", no solo "Excavación".
- **Asigná responsable siempre**: las alertas dependen de eso.
- **Recalculá CPM al menos una vez por semana** o después de mover varias barras.
- **Revisá el reporte avance vs planificado en cada reunión semanal**.

---

**Acta de capacitación:** firmar al final con asistencia de cada participante.
