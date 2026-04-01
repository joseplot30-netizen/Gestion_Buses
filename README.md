# Documentación del proyecto AutoSoft Corp

## 1) Descripción general del proyecto

- Nombre: `Proyecto Node.js+PHP+MySql`
- Tipo: panel de control y monitoreo de flota de buses.
- Frontend: HTML/CSS (Tailwind), JavaScript Vanilla.
- Backend:
  - PHP: `frontend/public/admin.php`, `historial.php`, `logout.php`, `bus_status.php`, etc.
  - Python FastAPI: `backend-node/main.py` + SQLAlchemy.
- Base de datos: MySQL (`proyectobbdd`) y SQLAlchemy.

### Flujo principal
1. Usuario / operador se loguea en `index.html` -> `backend-node /login`.
2. Admin accede a `admin.php` (monitor en tiempo real).
3. Chofer accede a `bus_status.php`.
4. `admin.php` consume los endpoints: `/buses-status`, `/obtener-incidentes`, `/resolver-incidente`, `/asignar-ruta`, `/borrar-incidente`, `/mensaje-bus`.

---

## 2) Estructura de carpetas

- `backend-node/`
  - `main.py`: servidos FastAPI con endpoints CRUD.
  - `models.py`: SQLAlchemy ORM (Bus, User, Incidente, Destino, Admin).
  - `database.py`: configuración de BD, `engine`, `get_db`, `Base`.
- `frontend/`
  - `package.json`.
  - `tailwind.config.js`.
  - `public/`
    - `index.html`: login UI.
    - `admin.php`: panel admin de monitoreo y incidentes.
    - `historial.php`: historial de reportes y borrado.
    - Otros PHP de la app.
  - `src/` etc.

---

## 3) Tecnologías usadas

- HTML5 + CSS3 con Tailwind.
- JavaScript (DOM + Fetch API + async/await).
- PHP (MySQL + páginas dinámicas).
- Node.js (Express) + MySQL.
- Base de datos MySQL.

---

## 4) Requisitos del proyecto

- Node.js >= 18
- npm >= 9
- MySQL (MariaDB/XAMPP MySQL)
- `backend-node/requirements.txt` contiene dependencias del backend Express

---

## 5) Instrucciones de ejecución local

### Backend (Express)
1. Abrir terminal en `backend-node/`.
2. Instalar dependencias de Node (una sola vez):
   - `npm install`
3. Crear archivo `.env` si vas a personalizar la DB (opcional):
   - `DB_HOST=localhost`
   - `DB_PORT=3306`
   - `DB_USER=root`
   - `DB_PASS=`
   - `DB_NAME=proyectobbdd`
4. Ejecutar server:
   - `npm run start`
   - (modo desarrollo) `npm run dev` si tienes `nodemon` instalado global o como devDep.
5. Validar acceso a `http://127.0.0.1:8000/status`.

### Base de datos
1. Asegurar MySQL levantado.
2. Revisar credenciales en `frontend/php` y en `backend-node/.env`.
3. Crear la base de datos `proyectobbdd` si no existe, y usar esquema actual `buses/users/users_admin/reportes_incidentes/destinos`.

### Frontend
1. Colocar `frontend/public` en `htdocs` de XAMPP.
2. Acceder a `http://localhost/Proyecto Node.js+PHP+MySql/frontend/public/index.html` y/o `admin.php`.

---

## 6) Explicación de las piezas principales

### `backend-node/server.js`
- Express + cors + mysql2 + multer.
- Conexión con pool a MySQL (`proyectobbdd`).
- API pública:
  - `GET /status`
  - `GET /buses-status`
  - `POST /login`
  - `POST /register-universal`
  - `POST /crear-bus`
  - `GET /obtener-incidentes`
  - `POST /resolver-incidente/:incidente_id`
  - `POST /reportar-incidente`
  - `POST /asignar-ruta`
  - `POST /finalizar-ruta`
  - `POST /actualizar-bus`
  - `POST /mensaje-bus`
  - `POST /limpiar-mensaje-bus/:codigo_bus`
  - `GET /usuarios`
  - `PUT /usuario/:user_id`
  - `DELETE /usuario/:user_id`

### Estructura de BD usada
- `buses`: columnas `codigo_bus`, `estado`, `origen`, `destino`, `mensaje_central`, `en_ruta`, etc.
- `users`: choferes con `bus_asignado_id`.
- `users_admin`: administradores.
- `reportes_incidentes`: reportes con `estado_reporte` y `respuesta_admin`.
- `destinos`: rutas disponibles.

### `frontend/public/admin.php`
- monitoriza buses con `monitorearBuses()`.
- recibe incidentes con `actualizarIncidentes()`.
- notificaciones con `crearNotificacion()`.
- modales para asignar ruta y responder incidente.
- envío de mensajes con `enviarMensajeBus()`, filtro con `filtrarBuses()`.

### `frontend/public/index.html`
- login de unidad o admin con formulario.
- fetch a `/login`.
- guarda `localStorage` y redirige según rol.

---

## 6) Cambios recientes aplicados

- Tarjetas en `index.html`:
  - Estado de Conexión (99.7%)
  - Buses en Servicio (112)
  - Incidentes Pendientes (4)
- En `admin.php`:
  - endpoint `/mensaje-bus` y UI en el aside.
  - filtro por teclado para unidad en select.- En `backend-node/server.js`:
  - Validación mejorada para `/crear-bus` (trim, dependencias de campos, mensaje de error claro).
  - Endpoint `DELETE /borrar-incidente/:incidente_id` agregado.
  - Endpoints de administración agregados: `GET /admins`, `PUT /admin/:admin_id`, `DELETE /admin/:admin_id`.
- En `frontend/public/gestion_personal.php`:
  - Edición de admins habilitada (antes mostraba alert de no implementado).
  - Borrado de admin usa `DELETE /admin/:id` y usuarios usan `DELETE /usuario/:id`.
  - Modal de edición ahora puede modificar usuarios y admins según rol.
---

## 7) Recomendaciones de mejora

- Usar JWT o sesiones seguras para autenticación.
- No exponer contraseñas en texto.
- Validaciones de entrada en backend y frontend.
- Agregar tests automatizados (pytest + requests / PHPunit).
- Implementar logs y métricas.

---

## 8) Datos de carpetas y archivos

- `backend-node`:
  - `main.py`, `models.py`, `database.py`, `package.json`, `server.js`.
- `frontend`:
  - `tailwind.config.js`, `package.json`, `public/index.html`, `public/admin.php`, etc.
- `docs`:
  - `README.md` (esta documentación).

---

## 9) Cómo correr y depurar

1. Iniciar MySQL + XAMPP.
2. Ejecutar FastAPI con el entorno virtual.
3. Abrir `index.html` en navegador.
4. Ver consola de JavaScript para errores.
5. Revisar POST/GET en Network.
6. Cambios de código se reflejan al guardar en archivos (FastAPI con --reload).

---

## 10) FAQ rápido

- ¿Por qué 404 en `/borrar-incidente`? Se agrego endpoint en `backend-node/main.py`.
- ¿Cómo enviar instrucción a bus? Desde admin aside, selecciona bus, escribe mensaje y envía.
- ¿Si la lista es larga? Usar campo de búsqueda que filtra en `filtrarBuses()`.
