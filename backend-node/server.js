const express = require('express');
const cors = require('cors');
const mysql = require('mysql2/promise');
const multer = require('multer');
require('dotenv').config();

const upload = multer();
const app = express();
app.use(cors({ origin: '*' }));
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

const dbConfig = {
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASS || '',
  database: process.env.DB_NAME || 'proyectobbdd',
  port: Number(process.env.DB_PORT || 3306),
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
};

const pool = mysql.createPool(dbConfig);

async function runQuery(sql, params = []) {
  const [rows] = await pool.execute(sql, params);
  return rows;
}

function responderError(res, err) {
  console.error('ERROR:', err);
  return res.status(500).json({ status: 'error', detail: err.message || 'Error de servidor' });
}

app.get('/status', (req, res) => res.json({ status: 'En línea' }));

app.get('/buses-status', async (req, res) => {
  try {
    const buses = await runQuery('SELECT * FROM buses');
    res.json(buses);
  } catch (e) { responderError(res, e); }
});

app.post('/login', upload.none(), async (req, res) => {
  try {
    const { username, codigo_unidad, codigo_bus, password } = req.body;
    const key = username || codigo_unidad || codigo_bus;

    if (!key || !password) return res.status(400).json({ detail: 'Faltan credenciales' });

    const adminRows = await runQuery('SELECT * FROM users_admin WHERE username = ? LIMIT 1', [key]);
    const admin = adminRows[0];
    if (admin && admin.password === password) {
      return res.json({ status: 'success', is_admin: true, redirect_to: 'admin.php', nombre: admin.nombre });
    }

    const userRows = await runQuery('SELECT * FROM users WHERE username = ? LIMIT 1', [key]);
    let user = userRows[0];

    if (!user) {
      const busRows = await runQuery('SELECT * FROM buses WHERE codigo_bus = ? LIMIT 1', [key]);
      const bus = busRows[0];
      if (bus) {
        const uRows = await runQuery('SELECT * FROM users WHERE bus_asignado_id = ? LIMIT 1', [bus.id]);
        user = uRows[0];
      }
    }

    if (user && user.password === password) {
      const bus = user.bus_asignado_id ? (await runQuery('SELECT * FROM buses WHERE id = ? LIMIT 1', [user.bus_asignado_id]))[0] : null;
      return res.json({
        status: 'success', is_admin: false,
        codigo_unidad: bus ? bus.codigo_bus : null,
        nombre: user.nombre,
        redirect_to: 'bus_status.php'
      });
    }

    return res.status(401).json({ detail: 'Credenciales incorrectas' });
  } catch (e) { responderError(res, e); }
});

app.post('/register-universal', upload.none(), async (req, res) => {
  try {
    const { nombre, apellido, username, email, password, es_admin, bus_id } = req.body;
    if (!nombre || !apellido || !username || !email || !password) {
      return res.status(400).json({ detail: 'Faltan campos requeridos' });
    }

    const exists = await runQuery('SELECT 1 FROM users WHERE username = ? UNION SELECT 1 FROM users_admin WHERE username = ? LIMIT 1', [username, username]);
    if (exists.length > 0) return res.status(400).json({ detail: 'El nombre de usuario ya existe' });

    if (String(es_admin) === 'true' || String(es_admin) === '1') {
      await runQuery('INSERT INTO users_admin (nombre, apellido, username, email, password, nivel_acceso) VALUES (?, ?, ?, ?, ?, ?)', [nombre, apellido, username, email, password, 'SuperAdmin']);
      return res.json({ status: 'success' });
    }

    if (!bus_id) return res.status(400).json({ detail: 'Debe asignar un ID de bus' });

    await runQuery('INSERT INTO users (nombre, apellido, username, email, password, bus_asignado_id) VALUES (?, ?, ?, ?, ?, ?)', [nombre, apellido, username, email, password, bus_id]);
    res.json({ status: 'success' });
  } catch (e) { responderError(res, e); }
});

app.post('/crear-bus', upload.none(), async (req, res) => {
  try {
    const codigo = (req.body.codigo || '').trim();
    const placa = (req.body.placa || '').trim();
    const capacidad = (req.body.capacidad || '').trim();

    if (!codigo || !placa) return res.status(400).json({ detail: 'Faltan datos de bus' });

    const existing = await runQuery('SELECT * FROM buses WHERE codigo_bus = ? OR placa = ? LIMIT 1', [codigo, placa]);
    if (existing.length > 0) return res.status(400).json({ detail: 'Bus ya existe' });

    await runQuery('INSERT INTO buses (codigo_bus, placa, capacidad) VALUES (?, ?, ?)', [codigo, placa, capacidad || 40]);
    res.json({ status: 'success' });
  } catch (e) { responderError(res, e); }
});

app.get('/obtener-incidentes', async (req, res) => {
  try {
    const incidents = await runQuery('SELECT * FROM reportes_incidentes WHERE estado_reporte = ? ORDER BY fecha_reporte DESC', ['pendiente']);
    res.json(incidents);
  } catch (e) { responderError(res, e); }
});

app.post('/resolver-incidente/:incidente_id', upload.none(), async (req, res) => {
  try {
    const { incidente_id } = req.params;
    const { respuesta_admin } = req.body;
    const incident = await runQuery('SELECT * FROM reportes_incidentes WHERE id = ? LIMIT 1', [incidente_id]);
    if (!incident.length) return res.status(404).json({ detail: 'No existe el reporte' });

    await runQuery('UPDATE reportes_incidentes SET estado_reporte = ?, respuesta_admin = ? WHERE id = ?', ['resuelto', respuesta_admin, incidente_id]);

    const vehiculo_id = incident[0].vehiculo_id;
    const bus = await runQuery('SELECT * FROM buses WHERE codigo_bus = ? LIMIT 1', [vehiculo_id]);
    if (bus.length) {
      await runQuery('UPDATE buses SET mensaje_central = ? WHERE id = ?', [respuesta_admin, bus[0].id]);
    }

    res.json({ status: 'success', message: 'Respuesta enviada al bus' });
  } catch (e) { responderError(res, e); }
});

app.post('/reportar-incidente', upload.none(), async (req, res) => {
  try {
    const { vehiculo_id, descripcion, prioridad } = req.body;
    if (!vehiculo_id || !descripcion) return res.status(400).json({ detail: 'Faltan datos' });

    await runQuery('INSERT INTO reportes_incidentes (vehiculo_id, descripcion, prioridad, fecha_reporte, estado_reporte) VALUES (?, ?, ?, NOW(), ?)', [vehiculo_id, descripcion, prioridad || 'Alta', 'pendiente']);
    res.json({ status: 'success' });
  } catch (e) { responderError(res, e); }
});

app.delete('/borrar-incidente/:incidente_id', async (req, res) => {
  try {
    const { incidente_id } = req.params;
    const incident = await runQuery('SELECT * FROM reportes_incidentes WHERE id = ? LIMIT 1', [incidente_id]);
    if (!incident.length) return res.status(404).json({ detail: 'No existe el reporte' });

    await runQuery('DELETE FROM reportes_incidentes WHERE id = ?', [incidente_id]);
    res.json({ status: 'success', message: 'Reporte eliminado' });
  } catch (e) { responderError(res, e); }
});

app.post('/asignar-ruta', upload.none(), async (req, res) => {
  try {
    const { codigo_bus, origen, destino } = req.body;
    if (!codigo_bus || !origen || !destino) return res.status(400).json({ detail: 'Faltan datos de ruta' });

    const bus = await runQuery('SELECT * FROM buses WHERE codigo_bus = ? LIMIT 1', [codigo_bus]);
    if (!bus.length) return res.status(404).json({ detail: 'Bus no encontrado' });

    await runQuery('UPDATE buses SET origen = ?, destino = ?, estado = ?, en_ruta = ?, ultima_actualizacion = NOW() WHERE codigo_bus = ?', [origen, destino, 'servicio', 1, codigo_bus]);
    res.json({ status: 'success', message: `Unidad ${codigo_bus} en ruta` });
  } catch (e) { responderError(res, e); }
});

app.post('/finalizar-ruta', upload.none(), async (req, res) => {
  try {
    const { codigo_bus } = req.body;
    if (!codigo_bus) return res.status(400).json({ detail: 'Falta codigo_bus' });

    const bus = await runQuery('SELECT * FROM buses WHERE codigo_bus = ? LIMIT 1', [codigo_bus]);
    if (!bus.length) return res.status(404).json({ detail: 'Bus no encontrado' });

    await runQuery('UPDATE buses SET en_ruta = 0, origen = ?, destino = ?, pasajeros = 0, estado = ?, ultima_actualizacion = NOW() WHERE codigo_bus = ?', ['Parqueadero', 'Disponible', 'espera', codigo_bus]);
    res.json({ status: 'success' });
  } catch (e) { responderError(res, e); }
});

app.post('/actualizar-bus', upload.none(), async (req, res) => {
  try {
    const { codigo_bus, estado } = req.body;
    if (!codigo_bus || !estado) return res.status(400).json({ detail: 'Faltan datos' });

    const bus = await runQuery('SELECT * FROM buses WHERE codigo_bus = ? LIMIT 1', [codigo_bus]);
    if (!bus.length) return res.status(404).json({ detail: 'Bus no encontrado' });

    await runQuery('UPDATE buses SET estado = ?, ultima_actualizacion = NOW() WHERE codigo_bus = ?', [estado, codigo_bus]);
    const updated = await runQuery('SELECT * FROM buses WHERE codigo_bus = ? LIMIT 1', [codigo_bus]);
    res.json({ status: 'success', mensaje: `Unidad ${codigo_bus} actualizada a ${estado}`, data: updated[0] });
  } catch (e) { responderError(res, e); }
});

app.post('/mensaje-bus', upload.none(), async (req, res) => {
  try {
    const { codigo_bus, mensaje } = req.body;
    if (!codigo_bus || !mensaje) return res.status(400).json({ detail: 'Faltan datos' });

    const bus = await runQuery('SELECT * FROM buses WHERE codigo_bus = ? LIMIT 1', [codigo_bus]);
    if (!bus.length) return res.status(404).json({ detail: 'Bus no encontrado' });

    await runQuery('UPDATE buses SET mensaje_central = ? WHERE codigo_bus = ?', [mensaje, codigo_bus]);
    res.json({ status: 'success', message: 'Mensaje enviado al bus' });
  } catch (e) { responderError(res, e); }
});

app.post('/limpiar-mensaje-bus/:codigo_bus', async (req, res) => {
  try {
    const { codigo_bus } = req.params;
    const bus = await runQuery('SELECT * FROM buses WHERE codigo_bus = ? LIMIT 1', [codigo_bus]);

    if (!bus.length) return res.status(404).json({ detail: 'Bus no encontrado' });

    await runQuery('UPDATE buses SET mensaje_central = NULL WHERE codigo_bus = ?', [codigo_bus]);
    res.json({ status: 'ok' });
  } catch (e) { responderError(res, e); }
});

app.get('/usuarios', async (req, res) => {
  try {
    const users = await runQuery('SELECT * FROM users');
    res.json(users);
  } catch (e) { responderError(res, e); }
});

app.put('/usuario/:user_id', async (req, res) => {
  try {
    const { user_id } = req.params;
    const { nombre, apellido, username, email, password, bus_asignado_id } = req.body;
    const user = await runQuery('SELECT * FROM users WHERE id = ? LIMIT 1', [user_id]);
    if (!user.length) return res.status(404).json({ detail: 'Usuario no encontrado' });

    const updates = [];
    const values = [];
    if (nombre !== undefined) { updates.push('nombre = ?'); values.push(nombre); }
    if (apellido !== undefined) { updates.push('apellido = ?'); values.push(apellido); }
    if (username !== undefined) { updates.push('username = ?'); values.push(username); }
    if (email !== undefined) { updates.push('email = ?'); values.push(email); }
    if (password !== undefined) { updates.push('password = ?'); values.push(password); }
    if (bus_asignado_id !== undefined) { updates.push('bus_asignado_id = ?'); values.push(bus_asignado_id); }

    if (updates.length > 0) {
      values.push(user_id);
      await runQuery(`UPDATE users SET ${updates.join(', ')} WHERE id = ?`, values);
    }

    const updated = await runQuery('SELECT * FROM users WHERE id = ? LIMIT 1', [user_id]);
    res.json({ status: 'success', user: updated[0] });
  } catch (e) { responderError(res, e); }
});

app.delete('/usuario/:user_id', async (req, res) => {
  try {
    const { user_id } = req.params;
    const user = await runQuery('SELECT * FROM users WHERE id = ? LIMIT 1', [user_id]);
    if (!user.length) return res.status(404).json({ detail: 'Usuario no encontrado' });

    await runQuery('DELETE FROM users WHERE id = ?', [user_id]);
    res.json({ status: 'success', message: 'Usuario eliminado' });
  } catch (e) { responderError(res, e); }
});

// Endpoints para administradores
app.get('/admins', async (req, res) => {
  try {
    const admins = await runQuery('SELECT id, nombre, apellido, username, email, nivel_acceso FROM users_admin ORDER BY nombre');
    res.json(admins);
  } catch (e) { responderError(res, e); }
});

app.put('/admin/:admin_id', upload.none(), async (req, res) => {
  try {
    const { admin_id } = req.params;
    const { nombre, apellido, username, email, password } = req.body;

    const admin = await runQuery('SELECT * FROM users_admin WHERE id = ? LIMIT 1', [admin_id]);
    if (!admin.length) return res.status(404).json({ detail: 'Administrador no encontrado' });

    // Verificar si el username ya existe en otro admin
    const existing = await runQuery('SELECT id FROM users_admin WHERE username = ? AND id != ? LIMIT 1', [username, admin_id]);
    if (existing.length > 0) return res.status(400).json({ detail: 'El nombre de usuario ya existe' });

    let query = 'UPDATE users_admin SET nombre = ?, apellido = ?, username = ?, email = ? WHERE id = ?';
    let params = [nombre, apellido, username, email, admin_id];

    if (password) {
      query = 'UPDATE users_admin SET nombre = ?, apellido = ?, username = ?, email = ?, password = ? WHERE id = ?';
      params = [nombre, apellido, username, email, password, admin_id];
    }

    await runQuery(query, params);
    res.json({ status: 'success', message: 'Administrador actualizado' });
  } catch (e) { responderError(res, e); }
});

app.delete('/admin/:admin_id', async (req, res) => {
  try {
    const { admin_id } = req.params;
    const admin = await runQuery('SELECT * FROM users_admin WHERE id = ? LIMIT 1', [admin_id]);
    if (!admin.length) return res.status(404).json({ detail: 'Administrador no encontrado' });

    await runQuery('DELETE FROM users_admin WHERE id = ?', [admin_id]);
    res.json({ status: 'success', message: 'Administrador eliminado' });
  } catch (e) { responderError(res, e); }
});

app.listen(process.env.PORT || 8000, () => {
  console.log(`API Express escuchando en puerto ${process.env.PORT || 8000}`);
});
