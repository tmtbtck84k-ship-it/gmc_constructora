# Plan de Capacitación — ERP GMC

**Audiencia:** equipo de Constructora GMC que usará el sistema.
**Duración:** 1 sesión de 90 minutos + Q&A.
**Modalidad:** presencial o videollamada con compartir pantalla y grabación.

## 1. Objetivos

Al finalizar la sesión, los asistentes deben poder:
1. Iniciar sesión y cambiar su clave.
2. Ejecutar los flujos correspondientes a su rol (crear SDP, validar, pagar; rendir gastos; cargar bitácora; etc).
3. Entender el modelo de aprobaciones y notificaciones.
4. Saber qué hacer si olvidan su clave o si algo no funciona.

## 2. Asistentes recomendados

| Rol | Personas | Importancia |
|---|---|---|
| Gerencia | 1–2 | Alta — visión ejecutiva |
| Finanzas | 1–2 | Crítica — flujo SDP completo |
| Jefes de Proyecto | 1 por cada uno | Crítica — bitácora, cierre, aprobación rindes |
| Administradores de Obra | 1 por cada uno | Alta — uso diario operativo |
| Bodega | 1 representante | Media — recepción de compras |
| Contabilidad | 1 representante | Media — exportes |
| TI / Soporte interno | 1 | Crítica — administración del sistema |

## 3. Estructura de la sesión

### 3.1 Bloque 1 — Presentación general (10 min)
- Qué resuelve el ERP GMC.
- Tour rápido por las secciones principales.
- Modelo de roles y permisos.
- Demostración del dashboard ejecutivo.

### 3.2 Bloque 2 — Login, perfil y seguridad (10 min)
- Cómo ingresar al sistema.
- Cambio de clave forzado en el primer login.
- Recuperación de clave si la olvidan.
- Cierre de sesión y políticas de inactividad.

### 3.3 Bloque 3 — Flujo de Solicitud de Pago (20 min)
- Crear una SDP en CLP.
- Crear una SDP en USD/UF mostrando el snapshot del TC.
- Adjuntar respaldo (factura).
- Recorrer estados: Pendiente → Validada → Programada → Pagada.
- Mostrar caso de Rechazo con motivo.
- Bandeja Finanzas: filtros, exportes CSV/Excel.
- Auditoría: quién hizo qué.

### 3.4 Bloque 4 — Compras y Rindes (15 min)
- Registrar una compra con items.
- Vincular factura a SDP existente.
- Crear un rinde de gastos personal.
- Enviar a aprobación.
- Aprobador (JP) revisa, aprueba o rechaza.
- Generar SDP de pago al rinde aprobado.

### 3.5 Bloque 5 — Obras (15 min)
- Bitácora: registrar avance, observación, incidencia.
- Edición acotada (24 hrs) y rol de la auditoría.
- Presupuesto inicial: cargar líneas, versionar.
- Cierre de obra: borrador → cerrar → PDF.

### 3.6 Bloque 6 — Reportes y Dashboard (10 min)
- Estado de Pagos.
- Gastos por obra y CC.
- Desviación con semáforo.
- Exportes CSV/Excel.
- Dashboard ejecutivo en tiempo real.

### 3.7 Bloque 7 — Administración (sólo para admin/TI, 10 min)
- Crear usuarios y asignar roles.
- Editar matriz roles × permisos.
- Auditoría: filtros y export.
- Tipos de cambio: carga manual + sync automático con Banco Central.

### 3.8 Q&A (10 min)

## 4. Material de apoyo

| Documento | Para |
|---|---|
| Manual de uso (PDF) | Todos los asistentes |
| Guía de roles (PDF) | Todos los asistentes |
| Cheat sheet de atajos | Power users |
| Grabación de la sesión | Onboarding posterior |
| URL de soporte / contacto | Resolución de dudas posteriores |

## 5. Datos de prueba para la demo

Antes de la capacitación, cargar:
- 3 clientes ficticios con nombres reconocibles.
- 5 proveedores (uno subcontratista).
- 2 proyectos en estado "ejecución".
- 4 SDPs en estados distintos (Pendiente, Validada, Programada, Pagada).
- 1 rinde de gastos en estado "Aprobado".
- 5 entradas de bitácora (mezclar avance, observación, incidencia).
- 1 presupuesto inicial cargado.

## 6. Acta de capacitación

Al final, todos los asistentes firman un acta que confirma:
- Asistencia
- Comprensión del flujo correspondiente a su rol
- Recepción del manual de uso

(Plantilla del acta en `07_ACTA_ENTREGA.md`)

## 7. Soporte post-capacitación

- Canal de comunicación definido (email, Slack, WhatsApp).
- Tiempo de respuesta SLA acordado.
- Escalamiento de incidencias críticas vs consultas de uso.
