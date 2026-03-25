<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Operativo - AutoSoft</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .mensaje-anim { animation: pulseBorder 2s infinite; }
        @keyframes pulseBorder {
            0% { border-color: rgba(56, 189, 248, 0.2); box-shadow: 0 0 0px rgba(56, 189, 248, 0); }
            50% { border-color: rgba(56, 189, 248, 0.8); box-shadow: 0 0 15px rgba(56, 189, 248, 0.3); }
            100% { border-color: rgba(56, 189, 248, 0.2); box-shadow: 0 0 0px rgba(56, 189, 248, 0); }
        }
        .status-dot { transition: all 0.3s ease; }
        .highlight-change { animation: flash 1.5s; }
        @keyframes flash {
            0% { background-color: rgba(56, 189, 248, 0.5); }
            100% { background-color: transparent; }
        }
        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
    </style>
</head>
<body class="bg-slate-950 text-slate-200 h-screen flex overflow-hidden">

    <main class="flex-1 p-8 flex flex-col relative overflow-y-auto custom-scroll">
        <header class="flex justify-between items-center mb-8 bg-slate-900/40 p-6 rounded-3xl border border-slate-800/50 relative">
            <div class="flex items-center gap-4">
                <div id="status_indicator" class="w-4 h-4 rounded-full bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.5)] status-dot"></div>
                <div>
                    <h1 class="text-2xl font-extrabold text-white tracking-tight">
                        Hola, <span id="nombre_usuario" class="text-sky-500">Juan Pérez</span> 
                    </h1>
                    <p class="text-xs text-slate-500 font-bold uppercase tracking-widest">Unidad: <span id="label_unidad" class="text-sky-400">BUS-101</span></p>
                </div>
            </div>

            <div class="text-right">
                <p id="reloj" class="text-3xl font-black text-white tabular-nums">00:00:00</p>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.3em]">Bogotá, Colombia</p>
            </div>
        </header>

        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="bg-slate-900/80 border border-slate-800 p-4 rounded-2xl flex items-center gap-4">
                <span id="clima_icon" class="text-2xl">☁️</span>
                <div>
                    <p class="text-[9px] font-black text-slate-500 uppercase">Clima Actual</p>
                    <p id="clima_texto" class="text-sm font-bold text-white">Cargando...</p>
                </div>
            </div>
            
            <div id="card_mensajes" class="bg-slate-900/80 border border-slate-800 p-4 rounded-2xl flex items-center gap-4 transition-all duration-500">
                <span id="mensaje_icon" class="text-2xl opacity-20">📩</span>
                <div class="flex-1">
                    <p class="text-[9px] font-black text-sky-500 uppercase">Respuesta de Central</p>
                    <p id="texto_central" class="text-sm font-medium text-slate-400 italic">Esperando instrucciones...</p>
                </div>
                <button onclick="limpiarMensaje()" class="text-[10px] bg-white/20 hover:bg-white/40 px-2 py-1 rounded-lg font-bold">OK</button>
            </div>
        </div>

        <section class="grid grid-cols-2 gap-4 flex-1">
            <div id="box_origen" class="bg-slate-900 border border-slate-800 p-6 rounded-3xl flex flex-col justify-center transition-colors">
                <p class="text-[10px] font-black text-sky-500 uppercase tracking-widest mb-1">Origen</p>
                <h3 id="info_origen" class="text-2xl font-bold text-white">Cargando...</h3>
            </div>

            <div id="box_destino" class="bg-sky-600 p-6 rounded-3xl flex flex-col justify-center shadow-lg shadow-sky-900/20 transition-all duration-500">
                <p class="text-[10px] font-black text-sky-100 uppercase tracking-widest mb-1">Destino</p>
                <h3 id="info_destino" class="text-2xl font-bold text-white">Disponible</h3>
            </div>

            <div class="bg-slate-900 border border-slate-800 p-6 rounded-3xl">
                <p class="text-[10px] font-black text-amber-500 uppercase tracking-widest mb-2">Próx. Mantenimiento</p>
                <div class="flex items-baseline gap-1">
                    <span class="text-4xl font-black text-white">1,240</span>
                    <span class="text-slate-600 text-sm">km</span>
                </div>
                <div class="w-full bg-slate-800 h-1.5 mt-4 rounded-full overflow-hidden">
                    <div class="bg-amber-500 h-full w-[75%]"></div>
                </div>
            </div>

            <div class="bg-slate-900 border border-slate-800 p-6 rounded-3xl">
                <p class="text-[10px] font-black text-emerald-500 uppercase tracking-widest mb-2">ETA (Llegada Est.)</p>
                <h3 id="info_tiempo" class="text-4xl font-black text-white">--:--</h3>
                <p id="eta_minutos" class="text-[10px] text-slate-500 mt-2 font-bold uppercase">Calculando ruta...</p>
            </div>
        </section>

        <button onclick="finalizarRuta()" class="mt-6 bg-slate-100 text-slate-950 font-black py-4 rounded-2xl hover:bg-sky-400 transition-all uppercase text-xs tracking-widest shadow-xl">
            Marcar llegada y liberar unidad
        </button>
    </main>

    <aside class="w-[22%] bg-slate-900 border-l border-slate-800 p-5 flex flex-col gap-5">
        <div>
            <h2 class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-3">Estado de la Unidad</h2>
            <div class="space-y-2">
                <button onclick="actualizarEstado('servicio')" class="w-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 py-2.5 rounded-xl text-[10px] font-bold hover:bg-emerald-500 hover:text-white transition uppercase">Servicio</button>
                <button onclick="actualizarEstado('espera')" class="w-full bg-amber-500/10 border border-amber-500/20 text-amber-500 py-2.5 rounded-xl text-[10px] font-bold hover:bg-amber-500 hover:text-white transition uppercase">Espera</button>
                <button onclick="actualizarEstado('inhabilitado')" class="w-full bg-red-500/10 border border-red-500/20 text-red-500 py-2.5 rounded-xl text-[10px] font-bold hover:bg-red-500 hover:text-white transition uppercase">Avería</button>
            </div>
        </div>

        <div>
            <h2 class="text-[9px] font-black text-rose-500 uppercase tracking-widest mb-3">Reportar Fallo</h2>
            <textarea id="desc_incidente" placeholder="¿Qué sucede con la unidad?" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-[11px] text-white outline-none focus:ring-1 focus:ring-rose-500 mb-2 h-20 resize-none"></textarea>
            <button onclick="enviarReporte()" class="w-full bg-rose-600 hover:bg-rose-500 text-white py-2.5 rounded-xl text-[10px] font-bold transition uppercase">Enviar Alerta</button>
        </div>

        <div class="flex flex-col min-h-0">
            <h2 class="text-[9px] font-black text-slate-600 uppercase tracking-widest mb-3">Logs del Sistema</h2>
            <div id="terminal_output" class="flex-1 bg-black/50 border border-slate-800 rounded-xl p-3 font-mono text-[9px] text-emerald-500/80 overflow-y-auto custom-scroll">
                <span class="opacity-50">[SYSTEM]: Link establecido...</span>
            </div>
        </div>

        <a href="./" class="bg-red-600/10 border border-red-600/20 text-red-500 px-4 py-2 rounded-xl text-[10px] font-black hover:bg-red-600 hover:text-white transition uppercase text-center">Cerrar Sesión</a>
    </aside>

<script>
    const params = new URLSearchParams(window.location.search);
    const unidadParam = params.get('unidad');
    const unidadLocal = localStorage.getItem('unidad_bus');
    const nombreLocal = localStorage.getItem('nombre_usuario');

    const unidadID = unidadParam || unidadLocal || "no hay datos";
    const nombreUsuario = nombreLocal || "no hay datos";

    document.getElementById('label_unidad').innerText = unidadID;
    document.getElementById('nombre_usuario').innerText = nombreUsuario;

    let rutaActual = "";
    let ultimoMsgContenido = "";

    function actualizarReloj() {
        const opciones = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false, timeZone: 'America/Bogota' };
        document.getElementById('reloj').innerText = new Intl.DateTimeFormat('es-CO', opciones).format(new Date());
    }

    async function obtenerClima() {
        try {
            const res = await fetch('https://api.open-meteo.com/v1/forecast?latitude=4.6097&longitude=-74.0817&current_weather=true');
            const data = await res.json();
            document.getElementById('clima_texto').innerText = `${Math.round(data.current_weather.temperature)}°C - Bogotá`;
        } catch (e) { document.getElementById('clima_texto').innerText = "18°C - Bogotá"; }
    }

    // --- CÁLCULO DE ETA REAL ---
    function calcularETA(estaEnServicio) {
        if (!estaEnServicio) {
            document.getElementById('info_tiempo').innerText = "--:--";
            document.getElementById('eta_minutos').innerText = "Unidad en espera";
            return;
        }
        // Simulamos un tiempo de viaje de 45 minutos desde ahora
        const ahora = new Date();
        const llegada = new Date(ahora.getTime() + 45 * 60000); 
        const horaLlegada = llegada.getHours().toString().padStart(2, '0');
        const minLlegada = llegada.getMinutes().toString().padStart(2, '0');
        
        document.getElementById('info_tiempo').innerText = `${horaLlegada}:${minLlegada}`;
        document.getElementById('eta_minutos').innerText = "Aprox. 45 min de trayecto";
    }

    // --- MENSAJES DE CENTRAL (MEJORADO) ---
    function gestionarMensajeCentral(nuevoMensaje) {
    const txtCentral = document.getElementById('texto_central');
    const card = document.getElementById('card_mensajes');
    const icon = document.getElementById('mensaje_icon');

    // Si no hay mensaje o es el mismo que ya tenemos, no hacemos nada
    if (!nuevoMensaje || nuevoMensaje === ultimoMsgContenido) return;

    // Si llegamos aquí, es un mensaje NUEVO
    ultimoMsgContenido = nuevoMensaje;
    txtCentral.innerText = nuevoMensaje;
    
    // Efecto visual de alerta (Azul brillante y animación)
    card.classList.remove('bg-slate-900/80', 'border-slate-800');
    card.classList.add('bg-sky-600', 'mensaje-anim', 'border-sky-400');
    icon.classList.remove('opacity-20');
    icon.classList.add('animate-bounce');

    imprimirTerminal(`CENTRAL: ${nuevoMensaje}`, 'info');
}

// Esta función ahora solo sirve para limpiar el mensaje cuando el chofer lo lea
async function limpiarMensaje() {
    try {
        const res = await fetch(`http://127.0.0.1:8000/limpiar-mensaje-bus/${unidadID}`, {
            method: 'POST'
        });

        if (res.ok) {
            ultimoMsgContenido = "";
            const card = document.getElementById('card_mensajes');
            const txtCentral = document.getElementById('texto_central');
            const icon = document.getElementById('mensaje_icon');

            card.classList.add('bg-slate-900/80', 'border-slate-800');
            card.classList.remove('bg-sky-600', 'mensaje-anim', 'border-sky-400');
            txtCentral.innerText = "Esperando instrucciones...";
            icon.classList.add('opacity-20');
            icon.classList.remove('animate-bounce');

            imprimirTerminal("MENSAJE MARCADO COMO LEÍDO");
        } else {
            imprimirTerminal("ERROR AL MARCAR COMO LEÍDO", 'error');
        }
    } catch (e) {
        imprimirTerminal("ERROR AL MARCAR COMO LEÍDO", 'error');
    }
}

async function monitorearCambiosRuta() {
    try {
        const res = await fetch('http://127.0.0.1:8000/buses-status');
        const buses = await res.json();
        
        // Buscamos nuestra unidad (usando toUpperCase para evitar errores de escritura)
        const miBus = buses.find(b => b.codigo_bus.toUpperCase() === unidadID.toUpperCase());

        if (miBus) {
            // 1. Gestionar Cambios de Ruta
            if (rutaActual !== miBus.destino && miBus.destino) {
                imprimirTerminal(`NUEVA RUTA ASIGNADA: ${miBus.destino}`);
                document.getElementById('box_destino').classList.add('highlight-change');
                setTimeout(() => document.getElementById('box_destino').classList.remove('highlight-change'), 2000);
            }
            
            document.getElementById('info_origen').innerText = miBus.origen || "Parqueadero";
            document.getElementById('info_destino').innerText = miBus.destino || "Disponible";
            
            actualizarPuntoEstado(miBus.estado);
            calcularETA(miBus.estado === 'servicio');
            rutaActual = miBus.destino;

            // 2. Gestionar Mensajes de Central (Columna mensaje_central)
            gestionarMensajeCentral(miBus.mensaje_central);
        }
    } catch (e) { console.error("Error en monitoreo de bus"); }
}

    function imprimirTerminal(msg, tipo = 'info') {
        const term = document.getElementById('terminal_output');
        const color = tipo === 'error' ? 'text-red-400' : 'text-emerald-500';
        term.innerHTML += `<br><span class="text-white opacity-30">></span> <span class="${color}">${msg}</span>`;
        term.scrollTop = term.scrollHeight;
    }

    function actualizarPuntoEstado(estado) {
        const dot = document.getElementById('status_indicator');
        dot.className = "w-4 h-4 rounded-full status-dot";
        if(estado === 'servicio') dot.classList.add('bg-emerald-500', 'shadow-[0_0_10px_rgba(16,185,129,0.5)]');
        else if(estado === 'espera') dot.classList.add('bg-amber-500', 'shadow-[0_0_10px_rgba(245,158,11,0.5)]');
        else dot.classList.add('bg-red-500', 'shadow-[0_0_10px_rgba(239,68,68,0.5)]');
    }

    async function actualizarEstado(nuevoEstado) {
        const fd = new FormData();
        fd.append('codigo_bus', unidadID);
        fd.append('estado', nuevoEstado);
        try {
            const res = await fetch('http://127.0.0.1:8000/actualizar-bus', { method: 'POST', body: fd });
            if (res.ok) {
                imprimirTerminal(`ESTADO ACTUALIZADO A ${nuevoEstado.toUpperCase()}`);
                monitorearCambiosRuta();
            }
        } catch (e) { imprimirTerminal(`ERROR DE CONEXIÓN`, 'error'); }
    }

    async function enviarReporte() {
        const desc = document.getElementById('desc_incidente').value;
        if(!desc) return;
        const fd = new FormData();
        fd.append('vehiculo_id', unidadID);
        fd.append('descripcion', desc);
        try {
            const res = await fetch('http://127.0.0.1:8000/reportar-incidente', { method: 'POST', body: fd });
            if (res.ok) {
                imprimirTerminal(`REPORTE ENVIADO A CENTRAL`);
                document.getElementById('desc_incidente').value = "";
            }
        } catch (e) { }
    }

    async function finalizarRuta() {
        if(!confirm("¿Confirmar llegada y liberar unidad?")) return;
        const fd = new FormData();
        fd.append('codigo_bus', unidadID);
        try {
            await fetch('http://127.0.0.1:8000/finalizar-ruta', { method: 'POST', body: fd });
            imprimirTerminal("RUTA FINALIZADA - UNIDAD LIBRE");
            monitorearCambiosRuta();
        } catch (e) { }
    }

    window.onload = () => {
        actualizarReloj();
        obtenerClima();
        monitorearCambiosRuta();
        setInterval(actualizarReloj, 1000);
        setInterval(monitorearCambiosRuta, 3000);
        setInterval(monitorearMensajes, 3000);
        setInterval(obtenerClima, 300000);
    };
</script>
</body>
</html>