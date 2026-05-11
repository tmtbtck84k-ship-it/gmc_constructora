# Guía de Roles — ERP GMC

## 1. Los 7 roles del sistema

| Rol | Descripción | Permisos clave |
|---|---|---|
| **admin** | Administrador del sistema | TODO (gestiona usuarios, roles, configuración) |
| **gerencia** | Gerencia | Acceso transversal de lectura, dashboards, reportes, edición de maestros |
| **finanzas** | Finanzas | Validar/programar/pagar/rechazar SDP, exportes contables, ver auditoría |
| **jefe_proyecto** | Jefe de Proyecto | Liderar técnicamente la obra: SDP, bitácora, cierre, aprobar rindes de su proyecto |
| **administrador_obra** | Administrador de Obra | Apoyo administrativo en una o varias obras: rindes, compras, bitácora |
| **bodega_obra** | Bodega / Obra | Recepciones de compras y rindes de gastos en terreno |
| **contabilidad** | Contabilidad (lectura) | Acceso transversal de sólo lectura + exportes |

## 2. Matriz: qué puede hacer cada rol

✓ = puede hacerlo · — = no tiene permiso · 🔒 = sólo de su proyecto/responsabilidad

| Acción | admin | gerencia | finanzas | JP | adm_obra | bodega | contab |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| Ver clientes | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ |
| Crear/editar clientes | ✓ | ✓ | — | — | — | — | — |
| Ver proveedores | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Crear/editar proveedores | ✓ | ✓ | — | — | — | — | — |
| Ver proyectos | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Crear/editar proyectos | ✓ | ✓ | — | 🔒 | — | — | — |
| Ver tipos de cambio | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ |
| Cargar tipos de cambio | ✓ | — | ✓ | — | — | — | — |
| Ver SDP | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ |
| Crear SDP | ✓ | — | ✓ | ✓ | ✓ | — | — |
| Editar SDP (Pendiente) | ✓ | — | ✓ | ✓ | ✓ | — | — |
| Validar SDP | ✓ | — | ✓ | — | — | — | — |
| Programar SDP | ✓ | — | ✓ | — | — | — | — |
| Pagar SDP | ✓ | — | ✓ | — | — | — | — |
| Rechazar SDP | ✓ | — | ✓ | — | — | — | — |
| Exportar SDP | ✓ | ✓ | ✓ | — | — | — | ✓ |
| Ver compras | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Crear/editar compras | ✓ | — | — | — | ✓ | ✓ | — |
| Anular compra | ✓ | — | — | — | ✓ | ✓ | — |
| Ver rindes | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Crear rinde | ✓ | — | — | ✓ | ✓ | ✓ | — |
| Enviar rinde | ✓ | — | — | ✓ | ✓ | ✓ | — |
| Aprobar rinde | ✓ | ✓ | ✓¹ | 🔒 | — | — | — |
| Rechazar rinde | ✓ | ✓ | ✓¹ | 🔒 | — | — | — |
| Ver bitácora | ✓ | ✓ | — | ✓ | ✓ | ✓ | ✓ |
| Crear bitácora | ✓ | — | — | ✓ | ✓ | ✓ | — |
| Editar bitácora (24h) | ✓ | ✓ | — | 🔒² | 🔒² | 🔒² | — |
| Ver cierre | ✓ | ✓ | — | ✓ | — | — | ✓ |
| Crear/cerrar obra | ✓ | ✓ | — | ✓ | — | — | — |
| Ver presupuesto | ✓ | ✓ | — | ✓ | ✓ | — | ✓ |
| Editar presupuesto | ✓ | ✓ | — | ✓ | — | — | — |
| Ver reportes | ✓ | ✓ | ✓ | ✓ | parcial | — | ✓ |
| Exportar reportes | ✓ | ✓ | ✓ | — | — | — | ✓ |
| Ver auditoría | ✓ | ✓ | — | — | — | — | — |
| Gestionar usuarios | ✓ | — | — | — | — | — | — |
| Editar roles/permisos | ✓ | — | — | — | — | — | — |

> ¹ Finanzas aprueba rindes que NO tienen proyecto (administración general).
> ² Sólo el autor puede editar dentro de 24 hrs.

## 3. Casos de uso típicos

### Como Jefe de Proyecto (JP)
1. Lunes 9:00 → entras al sistema, ves dashboard con KPIs.
2. **Bitácora**: registras avances de la semana en tu proyecto.
3. **Solicitudes de Pago**: creas las SDPs por servicios/honorarios pendientes de pago.
4. **Rindes**: te llega correo con un rinde de un colaborador. Lo revisas y apruebas o rechazas con motivo.
5. **Cierre**: cuando termina la obra, completas el resumen, conformidades, fecha real, y "Cerrar obra".

### Como Finanzas
1. Llega correo: "Nueva SDP creada" → entras al detalle.
2. **Validar**: si el documento está OK, click "Validar".
3. Más tarde, **Programar**: defines fecha y forma de pago.
4. Día del pago: **Marcar pagada** con la fecha real.
5. Fin de mes: **Reporte Estado de Pagos** → exportas a CSV para contabilidad.
6. Gestionar **Tipos de Cambio**: corregir manualmente si el sync automático trae un valor incorrecto.

### Como Bodega / Obra
1. Llega un proveedor con materiales → entras y creas la **Compra**.
2. Agregas los ítems del documento que trae el proveedor.
3. "Confirmar recepción" → la compra queda registrada.
4. Si vino con factura, vinculas la SDP correspondiente.
5. Si compraste algo de tu bolsillo, creas un **Rinde de Gastos** y lo envías a aprobación.

### Como Administrador de Obra
1. Llevas la administración de varias obras al mismo tiempo (puedes estar asignado a cada una).
2. Creas **SDPs** y **Rindes** del lado administrativo (gastos administrativos, viáticos, materiales).
3. Llevas **bitácora administrativa** de tus obras.
4. Apoyas al JP con la documentación.

### Como Gerencia
1. Acceso de lectura a todo + exportes.
2. **Dashboard** con KPIs en tiempo real.
3. **Reportes de Desviación** para ver qué obras se están saliendo del presupuesto.
4. Si necesitas, editar maestros (clientes, proveedores, proyectos).

### Como Contabilidad
1. **Reporte Estado de Pagos** → exportas para tu sistema contable.
2. **Reporte Gastos por obra** → conciliación contable.
3. Sólo lectura, nunca modificas datos operativos.

### Como Admin
1. Creas usuarios para el equipo: les llega clave temporal por correo.
2. Asignas rol según función.
3. Si alguien tiene problema, "Restablecer contraseña".
4. Revisas **Auditoría** ante cualquier sospecha o consulta.
5. Mantienes la **matriz de roles y permisos** actualizada según necesidades del negocio.

## 4. Quién aprueba qué

| Documento | Aprobador |
|---|---|
| SDP (Validar/Programar/Pagar) | Finanzas |
| SDP (Rechazo) | Finanzas |
| Rinde de Gastos (con proyecto) | Jefe de Proyecto del proyecto |
| Rinde de Gastos (sin proyecto) | Finanzas |
| Compras / Recepciones | Bodega/Admin Obra (autoaprobado al confirmar recepción) |
| Cierre de Obra | Jefe de Proyecto o Gerencia |

## 5. Notificaciones automáticas por correo

| Evento | Quién recibe |
|---|---|
| SDP creada | Finanzas |
| SDP validada / programada / pagada | Solicitante (creador) |
| SDP rechazada | Solicitante (con motivo) |
| Rinde enviado para aprobación | Aprobador (JP del proyecto o Finanzas) |
| Rinde aprobado / rechazado | Solicitante |
| Recuperación de clave | Usuario que la solicitó |
| Reset por admin | Usuario afectado |
| Creación de usuario | Usuario nuevo (con clave temporal) |
