# Roadmap Fase 2 y Fase 3 — ERP GMC

**Documento elaborado por:** Tech Lead del rediseño.
**Fecha:** 2026-05-04.
**Base:** Propuesta firmada 22-01-2026 + decisiones de negocio del Sprint 0–6.

Este documento contiene el backlog detallado, el modelo de datos sugerido y las estimaciones para completar el Modelo de Gestión Integral de GMC al 100%.

Las estimaciones están en **UF** según la propuesta original, y en **puntos de historia** para planning interno (1 punto ≈ 0,3 UF aproximadamente).

---

## Fase 2 — Presupuestos de venta + Subcontratistas + Gantt

**Estimación total:** 160–260 UF (según nivel de detalle y aprobaciones).
**Plazo sugerido:** 14 semanas.
**Composición:** 3 sub-módulos.

### F2-A. Presupuestos de venta end-to-end

**Objetivo:** soportar el flujo completo de generación, costeo y emisión de presupuestos para licitaciones públicas y privadas.

**Tablas nuevas:**
- `gmc_licitaciones` — origen del presupuesto (pública/privada/directa).
- `gmc_presupuestos_venta` — encabezado, versión, estado, vigente.
- `gmc_presupuestos_venta_partidas` — APUs (análisis de precios unitarios).
- `gmc_presupuestos_venta_recursos` — material, mano de obra, equipo, GG, utilidad.
- `gmc_aprobaciones_multinivel` — flujo configurable (ej: JP → Gerencia → Comercial → emisión).

**Historias clave (~22):**
- Crear presupuesto desde cero o a partir de una licitación cargada (bases + planos como adjuntos).
- Versionado con comparativo entre versiones.
- APU: cubicación con precios unitarios desglosados.
- Cálculo automático de totales con margen, GG, utilidad, IVA.
- Aprobación multinivel configurable.
- Emisión formal en PDF profesional con timbrado.
- Conversión presupuesto aprobado → presupuesto inicial de obra (vincula a Fase 1).

**Estimación:** 60–90 UF (200–300 puntos).

### F2-B. Motor de asignación de subcontratistas + OT

**Objetivo:** evaluación objetiva de subcontratistas para cada partida y emisión formal de Ordenes de Trabajo (OT).

**Tablas nuevas:**
- `gmc_subcontratistas_evaluaciones` — matriz de puntajes (calidad, cumplimiento, precio, seguridad, antigüedad).
- `gmc_subcontratistas_calificaciones_partida` — qué partida puede ejecutar cada uno.
- `gmc_ordenes_trabajo` — OT con encabezado (proyecto, partida, subcontratista, montos, plazos).
- `gmc_ot_estados_log` — máquina de estados de la OT.
- `gmc_ot_pagos` — vínculo OT ↔ SDP (estado de pago de cada OT).

**Historias clave (~18):**
- Cargar matriz de evaluación con pesos configurables.
- Calificar subcontratistas por partida (pintura, instalaciones, terminaciones, etc).
- Recomendación automática del top 3 subcontratistas para una partida nueva.
- Crear OT a partir del presupuesto + selección de subcontratista.
- Flujo OT: Borrador → Emitida → En ejecución → Recibida → Cerrada.
- Vinculación OT ↔ SDP: el sistema sugiere SDPs según OT.
- Reporte de cumplimiento por subcontratista (entregas a tiempo, calidad, etc).

**Estimación:** 50–80 UF (170–270 puntos).

### F2-C. Gantt jerárquico de obras

**Objetivo:** planificación temporal de proyectos con hitos, actividades, dependencias y visualización Gantt.

**Tablas nuevas:**
- `gmc_hitos` — hitos del proyecto (id, proyecto_id, codigo, nombre, fecha_objetivo, fecha_real, completado, orden).
- `gmc_actividades` — tareas (id, proyecto_id, hito_id, padre_id, codigo, nombre, fecha_inicio_planificada, fecha_termino_planificada, fecha_inicio_real, fecha_termino_real, duracion_dias, porcentaje_avance, responsable_id, orden).
- `gmc_actividad_dependencias` — predecesores (actividad_id, predecesor_id, tipo FS/SS/FF/SF, lag_dias).
- `gmc_actividad_recursos` — asignación de recursos a actividad (opcional).

**Historias clave (~16):**
- CRUD jerárquico hito → actividad → subtarea (3 niveles soportados).
- Dependencias entre actividades con tipo (FS/SS/FF/SF) y lag.
- Cálculo automático de % avance del hito desde subtareas.
- Visualizador Gantt con [Frappe Gantt](https://frappe.io/gantt) (MIT, gratis).
- Drag & drop de fechas en el Gantt.
- Zoom diario/semanal/mensual.
- Cálculo de ruta crítica (CPM) con resaltado en rojo.
- Exporte Gantt a PDF / imagen.
- Reporte de avance vs planificado.
- Notificaciones de actividades retrasadas.

**Estimación:** 50–90 UF (170–300 puntos).

---

## Fase 3 — Cierre completo + Lecciones aprendidas + Dashboards

**Estimación total:** 140–260 UF (según KPI, prorrateos y analítica).
**Plazo sugerido:** 12 semanas.

### F3-A. Cierre completo de obra

**Tablas nuevas:**
- `gmc_evaluaciones_subcontratistas` — calificación post-obra por subcontratista que participó.
- `gmc_evaluaciones_internas` — autoevaluación del equipo GMC en la obra.
- `gmc_lecciones_aprendidas` — registro estructurado: categoría, problema, solución, recomendación.

**Historias clave (~12):**
- Wizard de cierre: presupuesto vs real → selección de subcontratistas → calificación de cada uno → autoevaluación interna → lecciones aprendidas → conformidades del cliente.
- PDF de cierre profesional con todos los componentes.
- Repositorio de lecciones aprendidas buscable y filtrado por categoría.
- Sugerencia automática de lecciones aplicables al iniciar nueva obra.
- Exporte de evaluaciones de subcontratistas a CSV.

**Estimación:** 40–70 UF (130–230 puntos).

### F3-B. Dashboards ejecutivos avanzados

**Tablas nuevas:**
- `gmc_kpi_snapshots` — guardado periódico de KPIs históricos (para tendencias).
- `gmc_prorrateos` — reglas de prorrateo de gastos comunes (admin general → obras según %).

**Historias clave (~14):**
- Dashboard ejecutivo con drill-down (de KPI agregado a detalle).
- KPIs: margen bruto por obra, EBITDA con prorrateos, días promedio de pago, % avance vs presupuesto, subcontratistas top.
- Gráficos: barras, líneas, dona, mapa de calor por CC.
- Tendencia mensual/trimestral/anual.
- Comparativo entre obras del mismo tipo.
- Alertas configurables (ej: "Avísame si una obra supera 10% de desviación").
- Reporte ejecutivo PDF mensual auto-generado.

**Estimación:** 50–90 UF (170–300 puntos).

### F3-C. Métricas y analítica histórica

**Historias clave (~10):**
- Tablero de tendencias trimestrales.
- Comparativo año vs año (YoY).
- Análisis de ciclos (tiempo promedio de SDP, tiempo promedio de aprobación, tiempo promedio de pago).
- Top proveedores por monto, por confiabilidad.
- Top subcontratistas por evaluación.
- Reporte de salud financiera con semáforo.

**Estimación:** 30–60 UF (100–200 puntos).

### F3-D. Reportes y exportes avanzados

**Historias clave (~6):**
- Constructor visual de reportes (drag & drop columnas y filtros).
- Programación de reportes automáticos (envío diario/semanal por email).
- Plantillas de reportes guardadas y compartibles.

**Estimación:** 20–40 UF (70–130 puntos).

---

## Fase 4 (opcional) — Integraciones

**Estimación:** **a evaluar caso a caso**, varía mucho según APIs disponibles.

### F4-A. SII (Servicio de Impuestos Internos)
- Emisión de DTE (factura electrónica) directo desde SDP/OT.
- Recepción de DTE de proveedores.
- Libro de compras y ventas automático.
- **Estimación:** 80–150 UF.

### F4-B. Bancos
- Conexión con APIs de Banco de Chile / BCI / Santander para conciliación bancaria automática.
- Transferencias masivas (carga TBK).
- **Estimación:** 60–120 UF.

### F4-C. Contabilidad externa
- Exporte automático a softwares contables (Defontana, Softland, Manager).
- Mapeo configurable de cuentas contables.
- **Estimación:** 40–80 UF.

### F4-D. Firma electrónica
- Integración con FirmaVirtual o similar para firma de actas, contratos, OTs.
- **Estimación:** 30–60 UF.

### F4-E. App móvil offline para terreno
- App iOS/Android para bitácora, fotos, evaluaciones en obra sin conectividad.
- Sincronización al retornar.
- **Estimación:** 100–200 UF.

---

## Resumen económico

| Fase | Componentes | Rango (UF) | Plazo |
|---|---|---|---|
| **Fase 1** (entregada) | MVP Operativo | 46,63 (cerrado) | 12 sem |
| **Fase 2** | Presupuestos venta + Subcontratistas + Gantt | 160–260 | 14 sem |
| **Fase 3** | Cierre completo + Dashboards + Analítica | 140–260 | 12 sem |
| **Fase 4** | Integraciones (modular) | 80–600 según módulos | variable |
| **Total Modelo Gestión Integral** | F1 + F2 + F3 | **350–570 UF** | **~38 sem** |
| Total con integraciones | F1 + F2 + F3 + F4 completo | 700–1.200 UF | ~52 sem |

## Recomendación de priorización

Si el cliente debe priorizar por impacto/costo:

1. **Fase 2-C (Gantt)** primero. Mayor visibilidad operativa, alta percepción de valor.
2. **Fase 3-B (Dashboards)** segundo. Cuando se tenga data acumulada de F1, esto la pone en valor.
3. **Fase 2-A (Presupuestos)** tercero. Si la operación de licitaciones es activa.
4. **Fase 2-B (Subcontratistas)** cuarto. Si se trabaja con muchos subcontratistas.
5. **Fase 3-A (Cierre completo)** quinto. Mejora la captura de aprendizajes.
6. **Fase 4** según necesidad puntual (SII suele ser el más urgente por compliance).

## Riesgos y consideraciones

- **Mantenibilidad:** las fases nuevas siguen la misma arquitectura (services + repos + controllers delgados + ACL granular) → curva baja.
- **Migraciones:** todas las tablas nuevas serán aditivas, sin romper Fase 1.
- **Performance:** F3 (analítica) puede requerir vistas materializadas o ETL nocturno si la BD crece a > 1M filas en transaccionales.
- **Dependencias externas:** sólo en F4 (SII, bancos). Mientras GMC opere sin integraciones, F1+F2+F3 son autosuficientes.

## Próximos pasos sugeridos

1. **Operar Fase 1 durante 30–60 días** y recolectar feedback real de usuarios.
2. Reunión de planificación de Fase 2 con el cliente: priorizar componentes según necesidades observadas.
3. Cotización formal de Fase 2 con alcance ajustado.
4. Definir si la Fase 2 se hace completa o por incrementos (MVP de cada sub-módulo).
