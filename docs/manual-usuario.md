# Manual de Usuario - AutoSoft Corp

## Objetivo
Guía práctica para operadores y administradores que usan el sistema día a día.

---

## 1) Acceso
1. Asegurarse de que el servidor esté corriendo en `backend-node`:
   - `npm install`
   - `npm run start` (o `npm run dev` si usas nodemon)
2. Abrir navegador e ir a `http://localhost/Proyecto Node.js+PHP+MySql/frontend/public/index.html`.
3. Ingresar Usuario/Código de unidad y contraseña.
4. Si es administrador, será redirigido a `admin.php`.
5. Si es chofer, será redirigido a `bus_status.php`.

---

## 2) Panel de administrador (`admin.php`)

### Sección principal
- Muestra tarjetas con % de conexión, unidades activas e incidentes.
- Panel central con tarjetas por bus: color según estado (`servicio`, `espera`, `inhabilitado`).

### Modal "Asignar ruta"
- Click sobre un bus para abrir modal de asignación.
- Buscar ruta en el input (filtro instantáneo).
- Seleccionar destino y confirmar.

### Lado derecho (Averías y reportes)
- Lista de incidentes activos.
- Botón "Responder al Chofer" abre modal de respuesta.
- Al enviar, se actualiza en backend y el reporte desaparece.

### Mensajes directos a bus
- En el aside: seleccionar unidad y escribir instrucción.
- Botón "Enviar mensaje" postea mensaje directo al bus.
- El bus recibe la instrucción en su campo `mensaje_central`.

### Historial de reportes
- Accede con botón `Historial`.
- Elimina incidentes con el botón de papelera.
- Confirmación de borrado antes de ejecutar.

---

## 3) Panel de chofer (`bus_status.php`)
(Asumido que tu proyecto contiene esta página; gestiona el estado actual del bus, pero no se describe en detalle aquí.)

---

## 4) Notificaciones y errores

- Cambios de estado generan notificaciones emergentes.
- Errores de servidor muestran alertas con detalle (insuficiente conexion, endpoint caída).

---

## 5) Buenas prácticas de uso

- Actualizar la página solo si es estrictamente necesario (red sincronizada automática).
- Asegurarse de que el backend esté corriendo (`uvicorn` + MySQL).
- Usar contraseñas seguras y cerrar sesión con botón `Salir`.

---

## 6) FAQ

- ¿No ves reportes en el sidebar? Espera unos segundos, se actualiza cada 4 s.
- ¿La tabla de buses no cambia? Verifica que el API de `buses-status` responde OK.
- ¿No encuentras un bus en el selector? Usa campo "Buscar unidad por código".
