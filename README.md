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
  - `package.json`.
  - `server.js` (posible servidor adicional).
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
- Python (FastAPI) + SQLAlchemy.
- Base de datos MySQL.

---

## 4) Instrucciones de ejecución local

### Backend (FastAPI)
1. Abrir terminal en `backend-node/`.
2. Crear entorno virtual:
   - `python -m venv venv`
   - `venv\Scripts\activate` (Windows)
3. Instalar dependencias:
   - `pip install fastapi uvicorn sqlalchemy pydantic pymysql`
4. Ejecutar:
   - `uvicorn main:app --reload --host 127.0.0.1 --port 8000`
5. Validar acceso a `http://127.0.0.1:8000/docs`.

### Base de datos
1. Asegurar MySQL levantado.
2. Revisar credenciales en `frontend/php` y en `backend-node/database.py`.
3. El app crea tablas automáticamente con `models.Base.metadata.create_all(bind=engine)`.

### Frontend
1. Colocar `frontend/public` en `htdocs` de XAMPP.
2. Acceder a `http://localhost/Proyecto Node.js+PHP+MySql/frontend/public/index.html` y/o `admin.php`.

---

## 5) Explicación de las piezas principales

### `backend-node/main.py`
- `app = FastAPI(...)`.
- `get_db` obtiene sesión DB.
- `/buses-status`: devuelve todos los buses.
- `/resolver-incidente/{id}`: marca como resuelto y añade `mensaje_central`.
- `/asignar-ruta`: asigna origen/destino/estado.
- `/borrar-incidente/{id}`: borra reporte (agregado).
- `/mensaje-bus`: actualiza `mensaje_central`.

### `backend-node/models.py`
- `Bus`: `codigo_bus`, `estado`, `origen`, `destino`, `mensaje_central`.
- `Incidente`: `estado_reporte`, `respuesta_admin`.
- `User`, `Admin`, `Destino`.

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
  - filtro por teclado para unidad en select.

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
