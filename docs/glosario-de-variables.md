# Glosario de Variables - AutoSoft Corp

Este documento describe variables clave que aparecen en el código y su significado funcional.

## Backend (FastAPI/Python)
- `db`: sesión de base de datos SQLAlchemy (con `Depends(get_db)`).
- `incidente`: objeto `models.Incidente` (registro único de reporte de avería).
- `bus`: objeto `models.Bus` (registro de unidad de transporte).
- `codigo_bus`: identificador único de unidad (ej. `BUS-001`).
- `estado_reporte`: `pendiente` o `resuelto` en `Incidente`.
- `mensaje_central`: mensaje que el admin envía al bus.
- `Form(...)`: parámetro obligatorio de FastAPI para formularios.

## Frontend (admin.php)

- `busSeleccionado`: código BUS elegido en modal de asignación.
- `estadosAnteriores`: mapa `{codigo_bus: estado}` para detectar cambios.
- `destinos`: arreglo de rutas cargado desde base de datos (`destinos` table).
- `estilosMap`: mapeo de colores para estados `servicio`, `espera`, `inhabilitado`.
- `modalAsignar`, `modalResponder`: modales para interacción del admin.
- `lista_destinos`: contenedor de tarjetas de rutas en modal.
- `lista_incidentes`: contenedor con incidentes pendientes.
- `notif_container`: contenedor de notificaciones UI.

## Frontend (index.html)

- `form`: elemento de formulario para login.
- `mensajeDiv`: contenedor de mensaje de status.
- `formData`: datos enviados al endpoint `/login`.
- `unidad`, `nombre`: datos guardados en `localStorage` para uso posterior.

##Variables globales y temporales

- `busesDisponibles`: listado de objetos `Bus` usado para filtrar select.
- `Term`: texto de búsqueda en `filterBusInput`.
- `filtrados`: buses que cumplen criterio de búsqueda.

## Frontend (admin.php) - detalle extendido

- `window.onload`: inicializador de temporizadores para cargar datos.
- `monitorearBuses()`: solicitud al endpoint `/buses-status`.
  - `res`: respuesta HTTP.
  - `buses`: array de objetos bus.
  - `id`: `bus.codigo_bus`.
  - `nuevoEstado` / `nuevoDestino`: valores actualizados de la unidad.
- `actualizarTarjetaBus(id, estado, destino)`: actualiza DOM para un bus.
  - Elementos: `status-dot-{id}`, `status-text-{id}`, `destino-text-{id}`.
- `actualizarIncidentes()`: solicitud a `/obtener-incidentes`.
  - `data`: array de incidentes.
  - `inc`: objeto incidente con `vehiculo_id`, `descripcion`, `id`.
- modales:
  - `abrirModalAsignar`, `cerrarModal`, `confirmarAsignacion()` -> POST `/asignar-ruta`.
  - `abrirModalRespuesta`, `cerrarModalResp`, `enviarRespuestaFinal()` -> POST `/resolver-incidente/{id}`.
- `crearNotificacion(msj, colorClase)`: genera y elimina elementos de notificación auto.
- `filtrarDestinos(busqueda)`: filtra el array `destinos` con texto ingresado.
- `selectBusMensaje`, `filterBusInput`, `texto_mensaje_bus`: campos de la lógica de mensajes directos.
- **nuevos métodos**:
  - `cargarOpcionesBuses()`: llena `busesDisponibles` desde `/buses-status`.
  - `filtrarBuses()`: filtra por `codigo_bus` y `destino` y reconstruye `<select>`.

## Frontend (index.html) - detalle extendido

- `form`: formulario de login.
- `usuario`, `password`: inputs con credenciales.
- `formData`: envía `username`, `codigo_unidad`, `codigo_bus`, `password`.
- `fetch('http://127.0.0.1:8000/login', { method:'POST', body: formData })`.
- `data`: respuesta de login.
- `response.ok` determina estado de login.
- `mensajeDiv`: sección donde se muestra `❌` ó `✅`.
- `unidad`, `nombre`: guardados en `localStorage` para sesión.
- `destino`: `admin.php` si `is_admin`; `bus_status.php?unidad=...` si no.

## Backend (server.js) - detalle extendido

- `GET /buses-status`: obtiene todos los buses.
- `POST /login`: login dual con `username`, `codigo_unidad`, `codigo_bus`, `password`.
  - `login_key`: valor de login prioritario.
  - `users_admin`: tabla de admins.
  - `users`: tabla de choferes.
  - `buses`: tabla de buses para búsqueda cruzada.
- `POST /register-universal`: crea `users_admin` o `users`.
- `POST /crear-bus`: inserta bus en tabla `buses`.
- `GET /obtener-incidentes`: trae incidentes pendientes.
- `POST /resolver-incidente/:incidente_id`: marca resuelto y guarda `respuesta_admin`; setea `bus.mensaje_central`.
- `POST /reportar-incidente`: inserta nuevo reporte en `reportes_incidentes`.
- `POST /asignar-ruta`: actualiza `buses` con `origen`, `destino`, `estado`, `en_ruta`, `ultima_actualizacion`.
- `POST /finalizar-ruta`: reinicia estado del bus a predeterminado (espera, parqueadero, disponible).
- `POST /actualizar-bus`: actualiza `estado` y `ultima_actualizacion`.
- `POST /mensaje-bus`: actualiza `mensaje_central`.
- `POST /limpiar-mensaje-bus/:codigo_bus`: establece `mensaje_central` NULL.
- `GET /usuarios`: devuelve users.
- `PUT /usuario/:user_id`: actualiza user.
- `DELETE /usuario/:user_id`: borra user.

## Backend (SQL / tablas) - modelo

- Clases SQLAlchemy:
  - `Bus`: `id`, `codigo_bus`, `placa`, `capacidad`, `estado`, `pasajeros`, `origen`, `destino`, `tiempo_llegada_estimado`, `en_ruta`, `ultima_actualizacion`, `mensaje_central`.
  - `User`: `id`, `nombre`, `apellido`, `username`, `email`, `password`, `bus_asignado_id`, `fecha_registro`.
  - `Incidente`: `id`, `vehiculo_id`, `descripcion`, `prioridad`, `fecha_reporte`, `estado_reporte`, `respuesta_admin`.
  - `Destino`: `id`, `nombre_ruta`, `origen`, `destino`, `distancia_km`.
  - `Admin`: `id`, `nombre`, `apellido`, `username`, `email`, `password`, `nivel_acceso`, `fecha_registro`.

## PHP (historial.php)

- `borrarRegistro(id)`: función JS que manda `DELETE` a `/borrar-incidente/{id}`.
- `fila`: elemento `tr` con `id="fila-${id}"`.
- El manejo de confirmación y animaciones visuales se hace con classes CSS.

---

### Nota
Este glosario ahora detalla gran parte de las variables y conceptos por archivo principal. Para una trazabilidad completa, se recomienda seguir con búsqueda por función (Ctrl+F) en cada archivo y anotar contextos específicos de uso.
