# Manual Técnico - AutoSoft Corp

## Objetivo
Este manual describe la arquitectura, endpoints, objetos y flujo interno del sistema para desarrolladores y equipos de mantenimiento.

---

## 1) Arquitectura general

- Frontend web con TailwindCSS y JavaScript.
- Backend:
  - PHP (páginas de administración y paneles; integración con MySQL vía `mysqli`).
  - Express.js en Node (API REST para buses/incidentes/usuarios y operaciones CRUD).
- Base de datos: MySQL (`proyectobbdd`, `buses`, `users`, `users_admin`, `reportes_incidentes`, `destinos`).

---

## 2) Endpoints de la API (Express.js)

### GET `/buses-status`
- Devuelve todos los registros de la tabla `buses`.
- Se usa en `admin.php` para estado en tiempo real.

### GET `/obtener-incidentes`
- Devuelve incidentes en `estado_reporte == "pendiente"` ordenados por `fecha_reporte` descendente.
- Se usa en `admin.php` para lista lateral.

### POST `/resolver-incidente/{incidente_id}`
- Cambia `estado_reporte` a `resuelto` y guarda `respuesta_admin`.
- Si existe el bus coincidente, actualiza `bus.mensaje_central`.

### DELETE `/borrar-incidente/{incidente_id}`
- Elimina incidentes de la tabla.
- Se usa en `historial.php`.

### POST `/asignar-ruta`
- Recibe `codigo_bus`, `origen`, `destino`.
- Actualiza el bus: estado `servicio`, `en_ruta = 1`, y tiempo.

### POST `/finalizar-ruta`
- Termina ruta y normaliza detalles.

### POST `/actualizar-bus`
- Actualiza estado de una unidad.

### POST `/mensaje-bus`
- Guardar instrucción en `bus.mensaje_central`.

### POST `/login`
- Autentica admin y chofer:
  - Busca en `users_admin` para admin.
  - Busca en `users` (y en `buses` relacionando `bus_asignado_id`).

---

## 3) Modelos de datos (SQLAlchemy)

- `class Bus`: campos clave `id`, `codigo_bus`, `estado`, `origen`, `destino`, `mensaje_central`, etc.
- `class User`: campos `id`, `nombre`, `apellido`, `username`, `email`, `password`, `bus_asignado_id`.
- `class Incidente`: campos `id`, `vehiculo_id`, `descripcion`, `prioridad`, `estado_reporte`, `respuesta_admin`.
- `class Destino`: rutas para asignar.
- `class Admin`: credenciales de administradores.

---

## 4) Flujo de datos en `admin.php`

1. Carga inicial (window.onload):
   - `monitorearBuses()` cada 3 s
   - `actualizarIncidentes()` cada 4 s
   - `cargarOpcionesBuses()` (busqueda apoyada por select)

2. Actualización de estado de buses:
   - Fetch a `/buses-status`
   - Detecta cambio de estado -> notificación
   - Cambia el color del dot y texto.

3. Incidentes:
   - Fetch a `/obtener-incidentes`.
   - Lista desde estado pendiente.

4. Funciones de UI:
   - Modal asignar ruta + confirmar
   - Modal responder incidente + enviar respuesta
   - Notificaciones emergentes
   - Buscador de destinos de rutas

---

## 5) Variables principales (backend)

- `db`: instancia de sesión SQLAlchemy.
- `incidente`: instancia `models.Incidente` cargada por ID.
- `bus`: instancia `models.Bus` cargada por `codigo_bus`.

---

## 6) Variables principales (frontend)

- `busSeleccionado`: código de bus para asignaciones.
- `estadosAnteriores`: objeto mapa por `codigo_bus` para detectar transición de estado.
- `destinos`: listado cargado en PHP desde tabla `destinos`.
- `busesDisponibles`: lista local desde `/buses-status` para filtro en el select.

---

## 7) Recomendaciones para desarrollo

- Usa debugging de `console.log()` y verifica `Network` en DevTools.
- Agrega Pydantic schemas y respuesta con validación.
- Configura CORS de forma más segura.
- Añade control de sesiones y hashing de passwords.

---

## 8) Comandos útiles

- `uvicorn main:app --reload --host 127.0.0.1 --port 8000`
- `npm install` / `npm run ...` según `package.json` (si aplica)
- `mysql -u root -p` para DB manual.

---

## 9) Diagrama rápido

1. `index.html` -> `/login` -> admin o bus.
2. `admin.php` -> `/buses-status`, `/obtener-incidentes`, `/resolver-incidente`, `/asignar-ruta`, `/mensaje-bus`.
3. `historial.php` -> `/borrar-incidente`.
4. `bus_status.php` -> consulta estado `bus` (no cubierto en este manual, revisar código propio).