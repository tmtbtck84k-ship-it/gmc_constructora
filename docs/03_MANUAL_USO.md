# Manual de Uso — ERP GMC

**Versión:** 1.0 (Fase 1)
**Audiencia:** usuarios finales del ERP en Constructora GMC.

## 1. Primer ingreso

1. Abre el navegador en la URL del sistema: `https://erp.gmc.cl` (o `http://gmc.local` en local).
2. Ingresa tu **RUT** (con formato `12.345.678-9` o `12345678-9`) y la **clave temporal** que recibiste por correo.
3. El sistema te pedirá cambiar tu clave en el primer ingreso. Define una clave que cumpla:
   - Al menos 8 caracteres.
   - Una mayúscula.
   - Una minúscula.
   - Un dígito.
   - Un símbolo (`!`, `@`, `#`, etc).

## 2. Olvidé mi clave

1. En la pantalla de login, click en **"¿Olvidaste tu contraseña?"**.
2. Ingresa tu email y haz clic en **Enviar instrucciones**.
3. Revisa tu correo. Recibirás un enlace válido por **60 minutos**.
4. Click en el enlace, define la nueva clave.
5. Vuelve a `/login` y entra con la nueva clave.

## 3. Pantalla principal (Dashboard)

Al ingresar verás:
- **6 cards con KPIs**: SDP por estado, proyectos activos, gasto del mes.
- **Gráfico de gasto mensual** de los últimos 12 meses.
- **Últimas bitácoras** con link al detalle.

El menú lateral izquierdo te muestra **sólo las secciones que tu rol permite ver**.

## 4. Maestros

### 4.1 Clientes
**Maestros → Clientes** → "Nuevo cliente".
- RUT obligatorio (se valida con módulo 11).
- Razón social obligatoria.
- Email único en el sistema.
- Comuna seleccionable de un dropdown agrupado por región.

### 4.2 Proveedores
**Maestros → Proveedores** → "Nuevo proveedor".
Igual que clientes + flag **"Es subcontratista"** + categoría libre.

### 4.3 Proyectos
**Maestros → Proyectos** → "Nuevo proyecto".
- El sistema asigna automáticamente un código `OBR-AAAA-NNN`.
- Selecciona Cliente, Jefe de Proyecto y Administrador de Obra (este último puede estar en varias obras simultáneamente).
- Al crearse, el sistema **crea automáticamente** un Centro de Costo "ADM-OBR" para la administración interna del proyecto.

### 4.4 Centros de Costo
**Maestros → Centros de Costo** → filtrar por proyecto y "Nuevo CC".
- Código único dentro del proyecto.
- El CC raíz "Administración" general no es editable.

### 4.5 Tipos de Gasto
**Maestros → Tipos de Gasto**: catálogo (MAT, COMB, ARR, etc).

### 4.6 Tipos de Cambio
**Maestros → Tipos de Cambio** → ves UF, USD, EUR vigentes en cards.
- "Cargar TC del día" para ingresar manualmente.
- El sistema sincroniza automáticamente desde **mindicador.cl** todos los días (Banco Central de Chile). Tus cargas manuales no se sobreescriben.

## 5. Solicitudes de Pago (SDP)

### 5.1 Crear una SDP
**Finanzas → Solicitudes de Pago** → "Nueva SDP".
- Selecciona Proyecto (opcional, si es un gasto de Administración general puede ir vacío).
- Centro de Costo (filtrado al proyecto seleccionado).
- Proveedor.
- Tipo de Gasto.
- Moneda (CLP, USD, UF, EUR).
- Monto Neto + IVA → el total se calcula automáticamente.
- Fecha de emisión, tipo y número de documento.
- Adjunta la factura/boleta como respaldo.
- Al guardar queda en estado **Pendiente** con código `SDP-AAAA-NNNN`.

> **Si la moneda no es CLP**: el sistema toma el TC vigente del día y guarda un *snapshot*. Si no hay TC cargado, te pide cargarlo antes.

### 5.2 Flujo de aprobación
| Estado | Quién actúa | Acción |
|---|---|---|
| Pendiente | Finanzas | Validar (verificar documento) |
| Validada | Finanzas | Programar (fija fecha y forma de pago) |
| Programada | Finanzas | Marcar pagada |
| Cualquier no-final | Finanzas | Rechazar (con motivo) |

Cada cambio queda en la **línea de tiempo** de la ficha y notifica al solicitante por correo.

## 6. Compras y Rindes

### 6.1 Compras / Recepciones
**Compras → Compras** → "Nueva compra".
- Registra la recepción de mercadería.
- Agrega ítems con cantidad, unidad, precio unitario.
- Total se calcula al vuelo.
- Al guardar queda en **Borrador**. Click "Confirmar recepción" para marcar como **Recibida**.
- Puedes vincular esta compra a una SDP existente (para que la factura quede asociada al pago).

### 6.2 Rindes de Gastos
**Compras → Rindes de Gastos** → "Nuevo rinde".
- Cualquier usuario puede rendir gastos en su nombre.
- Agrega ítems (fecha, tipo de gasto, descripción, número de boleta, monto).
- Estado **Borrador** → "Enviar" → estado **Enviada**.
- El **Jefe del Proyecto** del rinde lo aprueba o rechaza (si el rinde no tiene proyecto, lo aprueba **Finanzas**).
- Cuando está **Aprobado**, click "Generar SDP de pago" → se crea automáticamente una SDP enlazada para reembolsarlo.

## 7. Obras

### 7.1 Bitácora
**Obras → Bitácora** → seleccionar proyecto → "Nueva entrada".
- Tipo: avance / observación / incidencia / otro.
- Título, detalle, fecha del evento.
- Adjuntos (fotos, planos, documentos).
- **Edición**: el autor puede editar dentro de las primeras 24 hrs. Luego queda inmutable.

### 7.2 Presupuesto inicial
**Obras → Presupuesto inicial** → seleccionar proyecto → "Crear presupuesto".
- Agrega líneas con CC + tipo de gasto + descripción + monto.
- Sólo una versión vigente por proyecto.
- "Nueva versión" copia las líneas de la vigente y la marca como anterior.

### 7.3 Cierre de obra
**Obras → Cierre de Obra** → click el proyecto.
- "Crear borrador" → completa fecha de término real, resumen, conformidades, observaciones, adjuntos.
- "Cerrar obra" → marca el proyecto como cerrado y queda inmutable.
- **Atención**: el sistema bloquea el cierre si hay SDPs en estado distinto de Pagada o Rechazada.
- "Descargar PDF" → genera un informe PDF con todos los datos del cierre.

## 8. Reportes

### 8.1 Estado de Pagos
**Reportes → Estado de Pagos**: bandeja consolidada de SDPs con totales por estado en CLP. Filtros por obra, proveedor, estado y fechas. Botón "Exportar CSV".

### 8.2 Gastos por obra y CC
**Reportes → Gastos por Obra**: pivot consolidado de SDPs pagadas + Compras recibidas + Rindes aprobados/pagados. Todo en CLP.

### 8.3 Desviación
**Reportes → Desviación** → seleccionar proyecto.
- Compara el presupuesto inicial vigente vs el gasto real consolidado.
- Semáforo por línea:
  - 🟢 Verde: dentro del presupuesto o por debajo.
  - 🟡 Amarillo: 0 a 5% de sobrepaso.
  - 🔴 Rojo: más de 5% de sobrepaso.

## 9. Administración (sólo rol Admin)

### 9.1 Usuarios
**Administración → Usuarios** → "Nuevo usuario".
- Crea cuentas para tu equipo.
- Asigna uno o más roles.
- El sistema genera una clave temporal y la envía por correo.
- "Restablecer contraseña" → genera clave nueva si el usuario la olvida.

### 9.2 Roles y Permisos
**Administración → Roles y Permisos**: ves la matriz de 7 roles × 57 permisos.
- Click "Editar" en un rol → ajustar checkboxes por módulo.
- El rol `admin` siempre tiene todos los permisos (no editable).

### 9.3 Auditoría
**Administración → Auditoría**: log de todas las acciones del sistema.
- Filtra por usuario, acción, entidad, fechas.
- Exporta a CSV.

## 10. Atajos útiles

| Atajo | Para qué |
|---|---|
| Click en el RUT/email del top | Abrir menú de usuario (cambiar clave / cerrar sesión) |
| Buscadores DataTables | Buscar dentro de cualquier listado |
| Selectores con búsqueda (Select2) | Escribir para filtrar opciones |
| Botón "Exportar" | Disponible en la mayoría de listados |
| Confirmaciones (SweetAlert) | Acciones destructivas piden confirmación |

## 11. Soporte

Si tienes un problema:
1. Revisa que tu rol tenga el permiso necesario.
2. Toma una captura de pantalla del error.
3. Consulta con el administrador del sistema o el equipo TI.
4. Reporta a Soporte adjuntando: URL, captura, hora del incidente, qué intentabas hacer.
