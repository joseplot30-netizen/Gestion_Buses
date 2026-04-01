<?php
$conn = new mysqli("localhost", "root", "", "proyectobbdd");
if ($conn->connect_error) { die("Error: " . $conn->connect_error); }

// Traer todos los destinos para el buscador del modal
$resDestinos = $conn->query("SELECT * FROM destinos ORDER BY nombre_ruta ASC");
$destinosArray = [];
while($row = $resDestinos->fetch_assoc()) {
    $destinosArray[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin - AutoSoft</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .card-bus { transition: all 0.3s ease; cursor: pointer; }
        .card-bus:hover { transform: translateY(-5px); border-color: #38bdf8; }
        .notif-anim { animation: slideIn 0.5s ease; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
    </style>
</head>
<body class="bg-slate-950 text-slate-200 h-screen flex overflow-hidden">

    <div id="modalAsignar" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-[150] flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 w-full max-w-md rounded-3xl p-6 shadow-2xl">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-black text-white">Asignar Ruta: <span id="modal_bus_id" class="text-sky-500">---</span></h3>
                <button onclick="cerrarModal()" class="text-slate-500 hover:text-white text-2xl">&times;</button>
            </div>
            <div class="relative mb-4">
                <input type="text" id="buscadorRuta" placeholder="Buscar destino o ruta..." 
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-sm outline-none focus:ring-2 focus:ring-sky-500 transition-all text-white">
                <span class="absolute right-4 top-3.5 opacity-30">🔍</span>
            </div>
            <div id="listaDestinos" class="max-h-60 overflow-y-auto custom-scroll space-y-1 pr-2"></div>
            <button onclick="cerrarModal()" class="w-full mt-6 py-3 text-[10px] font-black uppercase text-slate-400 hover:text-white transition">Cancelar</button>
        </div>
    </div>

    <div id="modalResponder" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-[200] flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 w-full max-w-sm rounded-3xl p-6 shadow-2xl">
            <h3 class="text-lg font-black text-white mb-2">Responder a <span id="resp_bus_id" class="text-rose-500">---</span></h3>
            <p class="text-[10px] text-slate-500 uppercase mb-4 tracking-widest">Instrucción para el chofer:</p>
            <input type="hidden" id="resp_incidente_id">
            <textarea id="texto_respuesta" placeholder="Ej: Entendido, diríjase al taller..." class="w-full bg-slate-950 border border-slate-800 rounded-2xl p-4 text-sm text-white outline-none focus:ring-2 focus:ring-sky-500 h-32 resize-none mb-4"></textarea>
            <div class="flex gap-2">
                <button onclick="cerrarModalResp()" class="flex-1 py-3 text-[10px] font-black uppercase text-slate-500 hover:text-white transition">Cancelar</button>
                <button onclick="enviarRespuestaFinal()" class="flex-1 py-3 bg-sky-600 hover:bg-sky-500 text-white text-[10px] font-black uppercase rounded-xl transition">Enviar Mensaje</button>
            </div>
        </div>
    </div>

    <div id="notif_container" class="fixed top-4 right-4 z-[250] flex flex-col gap-2 w-72"></div>

    <main class="w-[78%] p-6 overflow-y-auto border-r border-slate-800 custom-scroll">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-black text-white tracking-tighter">Panel de Control</h1>
                <p class="text-slate-500 text-xs uppercase tracking-widest">Monitoreo en tiempo real activo</p>
            </div>
            <div class="flex gap-2">
                <button onclick="window.location.href='gestion_personal.php'" class="bg-slate-800 hover:bg-slate-700 text-[10px] font-bold px-4 py-2 rounded-xl transition uppercase border border-slate-700">👥 Personal</button>
                <button onclick="window.location.href='historial.php'" class="bg-slate-800 hover:bg-slate-700 text-[10px] font-bold px-4 py-2 rounded-xl transition uppercase border border-slate-700">📜 Historial</button>
                <a href="logout.php" class="bg-red-600/10 border border-red-600/20 text-red-500 text-[10px] font-bold px-4 py-2 rounded-xl transition hover:bg-red-600 hover:text-white">Salir</a>
            </div>
        </div>

        <div id="contenedorBuses" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php
            $resBuses = $conn->query("SELECT b.*, u.nombre, u.apellido FROM buses b LEFT JOIN users u ON b.id = u.bus_asignado_id ORDER BY b.codigo_bus ASC");
            while($bus = $resBuses->fetch_assoc()):
                $nombreCompleto = !empty($bus['nombre']) ? $bus['nombre'] . " " . $bus['apellido'] : "⚠️ Sin asignar";
                $colorStatus = ($bus['estado'] == "servicio") ? "bg-emerald-500" : (($bus['estado'] == "espera") ? "bg-amber-500" : "bg-rose-500");
            ?>
            <div id="card-<?php echo $bus['codigo_bus']; ?>" class="card-bus bg-slate-900 border border-slate-800 p-5 rounded-2xl shadow-lg relative" onclick="abrirModalAsignar('<?php echo $bus['codigo_bus']; ?>')">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-xl font-black text-white"><?php echo $bus['codigo_bus']; ?></span>
                    <div id="status-dot-<?php echo $bus['codigo_bus']; ?>" class="w-3 h-3 rounded-full <?php echo $colorStatus; ?> shadow-lg"></div>
                </div>
                <div class="space-y-3">
                    <div>
                        <p class="text-[9px] font-black text-slate-500 uppercase">Chofer</p>
                        <p class="text-sm font-semibold truncate text-slate-200"><?php echo $nombreCompleto; ?></p>
                    </div>
                    <div>
                        <p class="text-[9px] font-black text-slate-500 uppercase">Destino Actual</p>
                        <p id="destino-text-<?php echo $bus['codigo_bus']; ?>" class="text-sm text-sky-400 font-bold"><?php echo $bus['destino'] ?: 'Disponible'; ?></p>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-800 flex justify-between items-center">
                    <span class="text-[9px] text-slate-500 italic">En Vivo</span>
                    <span id="status-text-<?php echo $bus['codigo_bus']; ?>" class="text-[10px] font-bold uppercase <?php echo str_replace('bg-', 'text-', $colorStatus); ?>">
                        <?php echo $bus['estado']; ?>
                    </span>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </main>

    <aside class="w-[22%] bg-slate-900/50 p-4 backdrop-blur-md flex flex-col h-full">
        <h2 class="text-[10px] font-black text-rose-500 uppercase tracking-[0.2em] mb-6 flex items-center gap-2">
            <span class="w-2 h-2 bg-rose-500 rounded-full animate-ping"></span>
            Averías y Reportes
        </h2>

        <div class="mb-4 p-3 bg-slate-800/30 border border-slate-700 rounded-2xl">
            <h3 class="text-xs font-black text-white uppercase tracking-widest mb-2">Enviar mensaje a unidad</h3>
            <input id="filterBusInput" type="text" placeholder="Buscar unidad por código..." class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-xs text-white mb-2 outline-none focus:ring-2 focus:ring-sky-500" oninput="filtrarBuses()" />
            <select id="selectBusMensaje" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-xs text-white mb-2 outline-none focus:ring-2 focus:ring-sky-500">
                <option value="">Selecciona una unidad</option>
            </select>
            <textarea id="texto_mensaje_bus" placeholder="Escribe la instrucción ..." class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-xs text-white mb-2 outline-none focus:ring-2 focus:ring-sky-500 resize-none h-18"></textarea>
            <button onclick="enviarMensajeBus()" class="w-full py-2 bg-sky-600 hover:bg-sky-500 text-[10px] font-black uppercase rounded-xl transition">Enviar mensaje</button>
        </div>

        <div id="lista_incidentes" class="flex-1 space-y-3 overflow-y-auto pr-1 custom-scroll">
            <p class="text-[10px] text-slate-600 text-center py-4 italic uppercase">Cargando...</p>
        </div>
    </aside>

<script>
    // 1. Declaramos variables globales al principio para evitar errores de inicialización
    let busSeleccionado = null;
    let estadosAnteriores = {};
    const destinos = <?php echo json_encode($destinosArray); ?>;

    const estilosMap = {
        'servicio':     { dot: 'bg-emerald-500', text: 'text-emerald-500', notif: 'bg-emerald-600' },
        'espera':       { dot: 'bg-amber-500',   text: 'text-amber-500',   notif: 'bg-amber-500'   },
        'inhabilitado': { dot: 'bg-rose-500',    text: 'text-rose-500',    notif: 'bg-rose-600'    }
    };

    // --- MONITOREO DE BUSES (TIEMPO REAL) ---
    async function monitorearBuses() {
        try {
            const res = await fetch('http://127.0.0.1:8000/buses-status');
            if (!res.ok) return;
            const buses = await res.json();
            
            buses.forEach(bus => {
                const id = bus.codigo_bus;
                const nuevoEstado = bus.estado;
                const nuevoDestino = bus.destino || 'Disponible';

                // Si el estado cambió, avisamos
                if (estadosAnteriores[id] !== undefined && estadosAnteriores[id] !== nuevoEstado) {
                    const config = estilosMap[nuevoEstado] || { notif: 'bg-slate-700' };
                    crearNotificacion(`UNIDAD ${id}: AHORA EN ${nuevoEstado.toUpperCase()}`, config.notif);
                }

                actualizarTarjetaBus(id, nuevoEstado, nuevoDestino);
                estadosAnteriores[id] = nuevoEstado;
            });
        } catch (e) { console.error("Error monitoreo:", e); }
    }

    function actualizarTarjetaBus(id, estado, destino) {
        const dot = document.getElementById(`status-dot-${id}`);
        const txtEstado = document.getElementById(`status-text-${id}`);
        const txtDestino = document.getElementById(`destino-text-${id}`);
        
        if (!dot || !txtEstado) return;

        const config = estilosMap[estado] || estilosMap['espera'];
        
        dot.className = `w-3 h-3 rounded-full ${config.dot} shadow-lg`;
        txtEstado.className = `text-[10px] font-bold uppercase ${config.text}`;
        txtEstado.innerText = estado;
        if(txtDestino) txtDestino.innerText = destino;
    }

    // --- MONITOREO DE INCIDENTES (REPORTES) ---
 // --- MONITOREO DE INCIDENTES (SIN ANIMACIÓN REPETITIVA) ---
async function actualizarIncidentes() {
    try {
        const res = await fetch('http://127.0.0.1:8000/obtener-incidentes');
        const data = await res.json();
        const contenedor = document.getElementById('lista_incidentes');

        if (data.length === 0) {
            contenedor.innerHTML = '<p class="text-[10px] text-slate-600 text-center py-4 italic uppercase">Sin reportes activos</p>';
            return;
        }

        // Eliminamos "notif-anim" para que no salte cada 4 segundos
        contenedor.innerHTML = data.map(inc => `
            <div class="bg-rose-500/10 border border-rose-500/20 p-4 rounded-2xl mb-3">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-[10px] font-black text-rose-500 uppercase">${inc.vehiculo_id}</span>
                    <span class="text-[9px] text-slate-500">Pendiente</span>
                </div>
                <p class="text-[11px] text-slate-200 leading-snug mb-3">${inc.descripcion}</p>
                <button onclick="abrirModalRespuesta(${inc.id}, '${inc.vehiculo_id}')" 
                        class="w-full py-2 bg-rose-600/20 hover:bg-rose-600 text-rose-500 hover:text-white text-[9px] font-black uppercase rounded-lg transition-all">
                    Responder al Chofer
                </button>
            </div>
        `).join('');
    } catch (e) { console.error("Error incidentes:", e); }
}

// --- ENVIAR RESPUESTA AL CHOFER (CORREGIDO) ---
async function enviarRespuestaFinal() {
    const idIncidente = document.getElementById('resp_incidente_id').value;
    const busId = document.getElementById('resp_bus_id').innerText;
    const mensaje = document.getElementById('texto_respuesta').value;
    
    if (!mensaje.trim()) {
        alert("Por favor, escribe una instrucción para el chofer.");
        return;
    }

    // Usamos FormData para que coincida con Form(...) de FastAPI
    const fd = new FormData();
    fd.append('respuesta_admin', mensaje);

    try {
        const res = await fetch(`http://127.0.0.1:8000/resolver-incidente/${idIncidente}`, { 
            method: 'POST', 
            body: fd 
        });
        
        const resultado = await res.json();

        if (res.ok) {
            crearNotificacion(`MENSAJE ENVIADO CON ÉXITO A LA UNIDAD ${busId}`, "bg-sky-600");
            cerrarModalResp();
            actualizarIncidentes(); // Refrescar lista de inmediato
        } else {
            alert("Error del servidor: " + (resultado.detail || "No se pudo enviar."));
        }
    } catch (e) { 
        console.error("Error de conexión:", e);
        alert("No se pudo conectar con el servidor para enviar la respuesta.");
    }
}
    // --- FUNCIONES DE MODALES ---
    function abrirModalAsignar(codigoBus) {
        busSeleccionado = codigoBus;
        document.getElementById('modal_bus_id').innerText = codigoBus;
        document.getElementById('modalAsignar').classList.remove('hidden');

        // Limpiar el buscador al abrir
        document.getElementById('buscadorRuta').value = '';
        filtrarDestinos(''); // Mostrar todas las rutas inicialmente
    }

    function abrirModalRespuesta(incidenteId, busId) {
        document.getElementById('resp_incidente_id').value = incidenteId;
        document.getElementById('resp_bus_id').innerText = busId;
        document.getElementById('modalResponder').classList.remove('hidden');
    }

    function cerrarModal() { document.getElementById('modalAsignar').classList.add('hidden'); }
    function cerrarModalResp() { document.getElementById('modalResponder').classList.add('hidden'); }

    // --- NOTIFICACIONES ---
    function crearNotificacion(msj, colorClase) {
        const container = document.getElementById('notif_container');
        const notif = document.createElement('div');
        notif.className = `${colorClase} text-white text-[10px] font-bold p-4 rounded-2xl shadow-2xl notif-anim uppercase tracking-widest border border-white/10`;
        notif.innerHTML = msj;
        container.appendChild(notif);
        setTimeout(() => {
            notif.style.opacity = '0';
            setTimeout(() => notif.remove(), 500);
        }, 4000);
    }

    // --- ENVIAR MENSAJE A BUS ---
    let busesDisponibles = [];

    async function cargarOpcionesBuses() {
        try {
            const res = await fetch('http://127.0.0.1:8000/buses-status');
            if (!res.ok) return;
            busesDisponibles = await res.json();
            filtrarBuses();
        } catch (e) {
            console.error('Error al cargar buses:', e);
        }
    }

    function filtrarBuses() {
        const select = document.getElementById('selectBusMensaje');
        const term = document.getElementById('filterBusInput').value.trim().toLowerCase();

        const filtrados = busesDisponibles.filter(bus =>
            bus.codigo_bus.toLowerCase().includes(term) ||
            (bus.destino && bus.destino.toLowerCase().includes(term))
        );

        select.innerHTML = '<option value="">Selecciona una unidad</option>';

        if (filtrados.length === 0) {
            const emptyOpt = document.createElement('option');
            emptyOpt.value = '';
            emptyOpt.disabled = true;
            emptyOpt.textContent = 'Sin resultados';
            select.appendChild(emptyOpt);
            return;
        }

        filtrados.forEach(bus => {
            const opt = document.createElement('option');
            opt.value = bus.codigo_bus;
            opt.textContent = bus.codigo_bus + (bus.destino ? ` - ${bus.destino}` : ' (Disponible)');
            select.appendChild(opt);
        });
    }

    async function enviarMensajeBus() {
        const select = document.getElementById('selectBusMensaje');
        const mensaje = document.getElementById('texto_mensaje_bus').value.trim();

        if (!select.value) {
            alert('Selecciona una unidad antes de enviar.');
            return;
        }
        if (!mensaje) {
            alert('Escribe un mensaje antes de enviar.');
            return;
        }

        try {
            const fd = new FormData();
            fd.append('codigo_bus', select.value);
            fd.append('mensaje', mensaje);

            const res = await fetch('http://127.0.0.1:8000/mensaje-bus', {
                method: 'POST',
                body: fd
            });

            if (res.ok) {
                crearNotificacion(`MENSAJE ENVIADO A ${select.value}`, 'bg-sky-600');
                document.getElementById('texto_mensaje_bus').value = '';
                cargarOpcionesBuses();
            } else {
                const data = await res.json();
                alert('No se pudo enviar el mensaje: ' + (data.detail || 'error del servidor')); 
            }
        } catch (e) {
            console.error('Error de red al enviar mensaje:', e);
            alert('No se pudo conectar con el servidor para enviar mensaje.');
        }
    }

    // --- LÓGICA DEL BUSCADOR DE RUTAS ---
    function filtrarDestinos(busqueda) {
        const contenedor = document.getElementById('listaDestinos');
        const term = busqueda.toLowerCase();

        const filtrados = destinos.filter(d => 
            d.nombre_ruta.toLowerCase().includes(term) ||
            d.origen.toLowerCase().includes(term) ||
            d.destino.toLowerCase().includes(term)
        );

        if (filtrados.length === 0) {
            contenedor.innerHTML = '<p class="text-[10px] text-slate-500 text-center py-4 italic uppercase">No se encontraron rutas</p>';
            return;
        }

        contenedor.innerHTML = filtrados.map(ruta => `
            <div onclick="confirmarAsignacion('${ruta.nombre_ruta}', '${ruta.origen}', '${ruta.destino}')" 
                 class="p-3 hover:bg-sky-600/20 border border-transparent hover:border-sky-500/30 rounded-xl cursor-pointer transition-all group">
                <p class="text-[11px] font-black text-white group-hover:text-sky-400 uppercase">${ruta.nombre_ruta}</p>
                <p class="text-[9px] text-slate-500 uppercase">${ruta.origen} → ${ruta.destino}</p>
            </div>
        `).join('');
    }

    // Escuchar lo que el usuario escribe en el buscador
    document.getElementById('buscadorRuta').addEventListener('input', (e) => {
        filtrarDestinos(e.target.value);
    });

    // --- ENVIAR LA ASIGNACIÓN AL SERVIDOR ---
    async function confirmarAsignacion(nombreRuta, origen, destino) {
        if(!busSeleccionado) return;

        const fd = new FormData();
        fd.append('codigo_bus', busSeleccionado);
        fd.append('destino', nombreRuta); // Enviamos el nombre de la ruta como destino principal
        fd.append('origen', origen);

        try {
            const res = await fetch('http://127.0.0.1:8000/asignar-ruta', {
                method: 'POST',
                body: fd
            });

            if (res.ok) {
                crearNotificacion(`RUTA ${nombreRuta} ASIGNADA AL ${busSeleccionado}`, "bg-emerald-600");
                cerrarModal();
                monitorearBuses(); // Actualizar la vista de inmediato
            } else {
                alert("Error al asignar ruta en el servidor");
            }
        } catch (e) {
            console.error("Error:", e);
            alert("Error de conexión con el servidor");
        }
    }

    // --- INICIALIZACIÓN ---
    // Quitamos los IDs que daban error y arrancamos los intervalos
    window.onload = () => {
        monitorearBuses();
        actualizarIncidentes();
        cargarOpcionesBuses();
        setInterval(monitorearBuses, 3000);
        setInterval(actualizarIncidentes, 4000);
    };
</script>
</body>
</html>