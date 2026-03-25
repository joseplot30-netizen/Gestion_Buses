<?php
$conn = new mysqli("localhost", "root", "", "proyectobbdd");

// 1. Obtener Buses disponibles (para el select de choferes)
$busesDisponibles = $conn->query("SELECT id, codigo_bus, placa FROM buses 
    WHERE id NOT IN (SELECT bus_asignado_id FROM users WHERE bus_asignado_id IS NOT NULL)");

// 2. Obtener Todos los Usuarios (Uniendo Admins y Choferes con UNION)
$queryUsuarios = "
    (SELECT id, nombre, apellido, username, email, 'ADMIN' as rol, '---' as unidad, 'admin' as tipo FROM users_admin)
    UNION
    (SELECT u.id, u.nombre, u.apellido, u.username, u.email, 'CHOFER' as rol, COALESCE(b.codigo_bus, 'Sin asignar') as unidad, 'user' as tipo 
     FROM users u LEFT JOIN buses b ON u.bus_asignado_id = b.id)
";
$usuarios = $conn->query($queryUsuarios);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Control de Activos - AutoSoft</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-200 p-6 font-sans">

    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-3xl font-bold text-sky-400">Panel de Personal y Flota</h1>
                <p class="text-slate-500">Gestión de identidades y unidades vehiculares.</p>
            </div>
            <a href="admin.php" class="bg-slate-900 border border-slate-800 px-6 py-2 rounded-xl text-xs hover:bg-slate-800 transition">VOLVER AL MANDO</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
            
            <div class="bg-slate-900 border border-slate-800 p-6 rounded-3xl shadow-2xl">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-sm font-black text-sky-500 uppercase tracking-widest">Registrar Usuario</h2>
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <span class="text-[10px] font-bold text-slate-500 group-hover:text-sky-400 transition">¿ES ADMIN?</span>
                        <input type="checkbox" id="chk_admin" onchange="toggleBusSelect(this.checked)" class="w-4 h-4 rounded border-slate-700 bg-slate-950 text-sky-600 focus:ring-sky-500">
                    </label>
                </div>
                
                <form id="formPersona" class="grid grid-cols-2 gap-4">
                    <input type="text" id="p_nombre" placeholder="Nombre" required class="bg-slate-950 border border-slate-800 p-3 rounded-xl text-sm outline-none focus:ring-1 focus:ring-sky-500">
                    <input type="text" id="p_apellido" placeholder="Apellido" required class="bg-slate-950 border border-slate-800 p-3 rounded-xl text-sm outline-none focus:ring-1 focus:ring-sky-500">
                    <input type="text" id="p_user" placeholder="Username" required class="bg-slate-950 border border-slate-800 p-3 rounded-xl text-sm outline-none focus:ring-1 focus:ring-sky-500">
                    <input type="email" id="p_email" placeholder="ejemplo@buses.co" required class="bg-slate-950 border border-slate-800 p-3 rounded-xl text-sm outline-none focus:ring-1 focus:ring-sky-500">
                    <input type="password" id="p_pass" placeholder="Contraseña" required class="col-span-1 bg-slate-950 border border-slate-800 p-3 rounded-xl text-sm outline-none focus:ring-1 focus:ring-sky-500">
                    
                    <select id="p_bus" class="bg-slate-950 border border-slate-800 p-3 rounded-xl text-sm text-slate-400 outline-none disabled:opacity-20 transition">
                        <option value="">Asignar Unidad...</option>
                        <?php while($b = $busesDisponibles->fetch_assoc()): ?>
                            <option value="<?= $b['id'] ?>"><?= $b['codigo_bus'] ?> (<?= $b['placa'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                    
                    <button type="submit" class="col-span-2 bg-sky-600 hover:bg-sky-500 text-white font-bold py-3 rounded-xl text-xs transition uppercase shadow-lg shadow-sky-900/20">Registrar Personal</button>
                </form>
            </div>

            <div class="bg-slate-900 border border-slate-800 p-6 rounded-3xl shadow-2xl">
                <h2 class="text-sm font-black text-emerald-500 uppercase tracking-widest mb-6">Añadir Nueva Unidad (Bus)</h2>
                <form id="formBus" class="grid grid-cols-2 gap-4">
                    <input type="text" id="b_codigo" placeholder="Código (Ej: BUS-101)" required class="bg-slate-950 border border-slate-800 p-3 rounded-xl text-sm outline-none focus:ring-1 focus:ring-emerald-500">
                    <input type="text" id="b_placa" placeholder="Placa (Ej: ABC-123)" required class="bg-slate-950 border border-slate-800 p-3 rounded-xl text-sm outline-none focus:ring-1 focus:ring-emerald-500">
                    <input type="number" id="b_capacidad" placeholder="Capacidad Pasajeros" value="40" class="col-span-2 bg-slate-950 border border-slate-800 p-3 rounded-xl text-sm outline-none focus:ring-1 focus:ring-emerald-500">
                    
                    <button type="submit" class="col-span-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 rounded-xl text-xs transition uppercase">Dar de alta Vehículo</button>
                </form>
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
            <div class="p-6 border-b border-slate-800 flex flex-wrap gap-4 items-center justify-between">
                <h3 class="font-bold">Base de Datos de Usuarios</h3>
                <div class="flex gap-2">
                    <input type="text" id="busqueda" onkeyup="filtrarTabla()" placeholder="Buscar por nombre o usuario..." class="bg-slate-950 border border-slate-800 px-4 py-2 rounded-lg text-xs outline-none focus:ring-1 focus:ring-sky-500 w-full md:w-64">
                    <select id="filtro_rol" onchange="filtrarTabla()" class="bg-slate-950 border border-slate-800 px-4 py-2 rounded-lg text-xs text-slate-400 outline-none">
                        <option value="">Todos los roles</option>
                        <option value="ADMIN">Administradores</option>
                        <option value="CHOFER">Choferes</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm min-w-[800px]" id="tablaUsuarios">
                <thead class="bg-slate-800/50 text-slate-500 text-[10px] font-black uppercase">
                    <tr>
                        <th class="p-4">Nombre Completo</th>
                        <th class="p-4">Usuario</th>
                        <th class="p-4">Email</th>
                        <th class="p-4">Rol</th>
                        <th class="p-4">Unidad</th>
                        <th class="p-4 text-center">Editar</th>
                        <th class="p-4 text-center">Borrar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    <?php while($u = $usuarios->fetch_assoc()): ?>
                    <tr class="hover:bg-slate-800/20 transition">
                        <td class="p-4 font-bold text-white"><?= $u['nombre'] . " " . $u['apellido'] ?></td>
                        <td class="p-4 text-sky-400 font-mono"><?= $u['username'] ?></td>
                        <td class="p-4 text-slate-400"><?= $u['email'] ?></td>
                        <td class="p-4">
                            <span class="rol-tag px-2 py-1 rounded text-[9px] font-black <?= $u['rol'] == 'ADMIN' ? 'bg-red-500/10 text-red-500' : 'bg-sky-500/10 text-sky-500' ?>">
                                <?= $u['rol'] ?>
                            </span>
                        </td>
                        <td class="p-4 text-slate-500"><?= $u['unidad'] ?></td>
                        <td class="p-4 text-center">
                            <button onclick="abrirEditarUsuario(<?= $u['id'] ?? 'null' ?>, '<?= $u['rol'] ?>')" class="text-slate-700 hover:text-sky-500 transition">✏️</button>
                        </td>
                        <td class="p-4 text-center">
                            <button onclick="borrarUsuario(<?= $u['id'] ?? 'null' ?>, '<?= $u['rol'] ?>')" class="text-slate-700 hover:text-red-500 transition">🗑️</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <!-- Modal Editar Usuario -->
    <div id="modalEditar" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 w-full max-w-lg rounded-3xl p-6 shadow-2xl">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-black text-white">Editar Usuario</h3>
                <button onclick="cerrarModalEditar()" class="text-slate-500 hover:text-white text-2xl">&times;</button>
            </div>
            <form id="formEditarUsuario" class="space-y-4">
                <input type="hidden" id="edit_user_id">
                <input type="hidden" id="edit_tipo">
                <div>
                    <label class="block text-sm font-medium mb-1 text-slate-300">Nombre</label>
                    <input type="text" id="edit_nombre" required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-sky-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-slate-300">Apellido</label>
                    <input type="text" id="edit_apellido" required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-sky-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-slate-300">Username</label>
                    <input type="text" id="edit_username" required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-sky-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-slate-300">Email</label>
                    <input type="email" id="edit_email" required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-sky-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-slate-300">Password (dejar vacío para no cambiar)</label>
                    <input type="password" id="edit_password" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-sky-500 outline-none">
                </div>
                <div id="bus_select_container">
                    <label class="block text-sm font-medium mb-1 text-slate-300">Bus Asignado</label>
                    <select id="edit_bus_id" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-sky-500 outline-none">
                        <option value="">Sin asignar</option>
                        <?php
                        $busesAll = $conn->query("SELECT id, codigo_bus FROM buses");
                        while($b = $busesAll->fetch_assoc()) {
                            echo "<option value='{$b['id']}'>{$b['codigo_bus']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <button type="submit" class="w-full bg-sky-600 hover:bg-sky-500 text-white font-bold py-3 rounded-lg transition">Guardar Cambios</button>
            </form>
        </div>
    </div>

    <script>
        // Lógica de UI
        function toggleBusSelect(isAdmin) {
            const select = document.getElementById('p_bus');
            select.disabled = isAdmin;
            if(isAdmin) select.value = "";
        }

        // Lógica de Filtrado
        function filtrarTabla() {
            const input = document.getElementById("busqueda").value.toUpperCase();
            const filterRol = document.getElementById("filtro_rol").value.toUpperCase();
            const rows = document.getElementById("tablaUsuarios").getElementsByTagName("tr");

            for (let i = 1; i < rows.length; i++) {
                const nombre = rows[i].cells[0].innerText.toUpperCase();
                const user = rows[i].cells[1].innerText.toUpperCase();
                const rol = rows[i].cells[3].innerText.toUpperCase();
                
                const coincideBusqueda = nombre.includes(input) || user.includes(input);
                const coincideRol = filterRol === "" || rol.includes(filterRol);

                rows[i].style.display = (coincideBusqueda && coincideRol) ? "" : "none";
            }
        }

        // Envío de Persona (Fetch a Python)
        document.getElementById('formPersona').onsubmit = async (e) => {
            e.preventDefault();
            const isAdmin = document.getElementById('chk_admin').checked;
            const fd = new FormData();
            fd.append('nombre', document.getElementById('p_nombre').value);
            fd.append('apellido', document.getElementById('p_apellido').value);
            fd.append('username', document.getElementById('p_user').value);
            fd.append('email', document.getElementById('p_email').value);
            fd.append('password', document.getElementById('p_pass').value);
            fd.append('es_admin', isAdmin);
            if(!isAdmin) fd.append('bus_id', document.getElementById('p_bus').value);

            const res = await fetch('http://127.0.0.1:8000/register-universal', { method: 'POST', body: fd });
            if(res.ok) location.reload(); else alert("Error al registrar persona.");
        };

        // Envío de Bus (Fetch a Python)
        document.getElementById('formBus').onsubmit = async (e) => {
            e.preventDefault();
            const fd = new FormData();
            fd.append('codigo', document.getElementById('b_codigo').value);
            fd.append('placa', document.getElementById('b_placa').value);
            fd.append('capacidad', document.getElementById('b_capacidad').value);

            const res = await fetch('http://127.0.0.1:8000/crear-bus', { method: 'POST', body: fd });
            if(res.ok) location.reload(); else alert("Error al registrar unidad.");
        };

        // Funciones para editar usuario
        function abrirEditarUsuario(id, rol) {
            if (!id) return; // Para admins, id podría ser null si no se incluye
            // Para simplificar, asumir que editamos solo users, no admins, ya que el endpoint es para users.
            if (rol === 'ADMIN') {
                alert('Edición de admins no implementada aún.');
                return;
            }
            // Cargar datos del usuario desde el backend
            fetch(`http://127.0.0.1:8000/usuarios`)
                .then(res => res.json())
                .then(usuarios => {
                    const user = usuarios.find(u => u.id == id);
                    if (user) {
                        document.getElementById('edit_user_id').value = user.id;
                        document.getElementById('edit_tipo').value = 'user';
                        document.getElementById('edit_nombre').value = user.nombre;
                        document.getElementById('edit_apellido').value = user.apellido;
                        document.getElementById('edit_username').value = user.username;
                        document.getElementById('edit_email').value = user.email;
                        document.getElementById('edit_password').value = '';
                        document.getElementById('edit_bus_id').value = user.bus_asignado_id || '';
                        document.getElementById('bus_select_container').style.display = 'block';
                        document.getElementById('modalEditar').classList.remove('hidden');
                    }
                })
                .catch(err => console.error('Error cargando usuario:', err));
        }

        function cerrarModalEditar() {
            document.getElementById('modalEditar').classList.add('hidden');
        }

        function borrarUsuario(id, rol) {
            if (!id) return;
            if (rol === 'ADMIN') {
                alert('No se puede borrar administradores.');
                return;
            }
            if (confirm('¿Estás seguro de que quieres borrar este usuario?')) {
                fetch(`http://127.0.0.1:8000/usuario/${id}`, { method: 'DELETE' })
                    .then(res => {
                        if (res.ok) {
                            alert('Usuario borrado');
                            location.reload();
                        } else {
                            alert('Error borrando usuario');
                        }
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        alert('Error de conexión');
                    });
            }
        }

        document.getElementById('formEditarUsuario').onsubmit = async (e) => {
            e.preventDefault();
            const userId = document.getElementById('edit_user_id').value;
            const fd = new FormData();
            fd.append('nombre', document.getElementById('edit_nombre').value);
            fd.append('apellido', document.getElementById('edit_apellido').value);
            fd.append('username', document.getElementById('edit_username').value);
            fd.append('email', document.getElementById('edit_email').value);
            const pass = document.getElementById('edit_password').value;
            if (pass) fd.append('password', pass);
            fd.append('bus_asignado_id', document.getElementById('edit_bus_id').value || '');

            try {
                const res = await fetch(`http://127.0.0.1:8000/usuario/${userId}`, { method: 'PUT', body: fd });
                if (res.ok) {
                    alert('Usuario actualizado');
                    location.reload();
                } else {
                    alert('Error actualizando usuario');
                }
            } catch (e) {
                console.error('Error:', e);
                alert('Error de conexión');
            }
        };
    </script>
</body>
</html>