<?php
// Configuración de conexión (Asegúrate de que los datos coincidan con tu XAMPP)
$conn = new mysqli("localhost", "root", "", "proyectobbdd");

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Incidentes - AutoSoft</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-950 text-slate-200 p-8">

    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-10 bg-slate-900/50 p-6 rounded-3xl border border-slate-800">
            <div>
                <h1 class="text-3xl font-extrabold text-white tracking-tight">Historial de Reportes</h1>
                <p class="text-slate-500 text-sm">Gestión de incidentes resueltos y limpieza de base de datos</p>
            </div>
            <a href="admin.php" class="bg-white text-slate-950 px-6 py-2 rounded-xl text-xs font-black uppercase hover:bg-sky-400 transition-all shadow-lg">
                Volver al Panel
            </a>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-800/50 text-[10px] uppercase tracking-widest text-slate-400">
                        <th class="p-5 font-black">Unidad</th>
                        <th class="p-5 font-black">Fecha</th>
                        <th class="p-5 font-black">Reporte del Chofer</th>
                        <th class="p-5 font-black">Respuesta Central</th>
                        <th class="p-5 font-black text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    <?php
                    // Solo mostramos los que ya fueron marcados como 'resuelto'
                    $sql = "SELECT * FROM reportes_incidentes WHERE estado_reporte = 'resuelto' ORDER BY fecha_reporte DESC";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0):
                        while($row = $result->fetch_assoc()):
                    ?>
                        <tr id="fila-<?php echo $row['id']; ?>" class="hover:bg-slate-800/30 transition-colors group">
                            <td class="p-5">
                                <span class="whitespace-nowrap inline-block bg-sky-500/10 text-sky-500 text-xs font-bold px-3 py-1 rounded-full border border-sky-500/20">
                                    <?php echo $row['vehiculo_id']; ?>
                                </span>
                            </td>
                            <td class="p-5 text-xs text-slate-500 font-medium">
                                <?php echo date("d/m/Y H:i", strtotime($row['fecha_reporte'])); ?>
                            </td>
                            <td class="p-5">
                                <p class="text-xs text-slate-300 leading-relaxed italic">"<?php echo $row['descripcion']; ?>"</p>
                            </td>
                            <td class="p-5">
                                <div class="bg-emerald-500/5 border-l-2 border-emerald-500 p-2">
                                    <p class="text-xs text-emerald-400 font-bold uppercase text-[9px] mb-1">Finalizado:</p>
                                    <p class="text-xs text-slate-200 font-medium"><?php echo $row['respuesta_admin']; ?></p>
                                </div>
                            </td>
                            <td class="p-5 text-center">
                                <button onclick="borrarRegistro(<?php echo $row['id']; ?>)" 
                                        class="bg-rose-600/10 text-rose-500 p-2 rounded-lg hover:bg-rose-600 hover:text-white transition-all group-hover:scale-110 shadow-sm"
                                        title="Eliminar permanentemente">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    <?php 
                        endwhile; 
                    else: 
                    ?>
                        <tr>
                            <td colspan="5" class="p-20 text-center text-slate-600 italic">
                                <div class="flex flex-col items-center gap-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                    </svg>
                                    No hay reportes en el historial.
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        async function borrarRegistro(id) {
            if(!confirm("¿Deseas eliminar este reporte del historial permanentemente? Esta acción no se puede deshacer.")) return;

            try {
                // Llamada al endpoint DELETE de FastAPI que configuramos antes
                const response = await fetch(`http://127.0.0.1:8000/borrar-incidente/${id}`, {
                    method: 'DELETE'
                });

                if(response.ok) {
                    const fila = document.getElementById(`fila-${id}`);
                    fila.classList.add('opacity-0', 'scale-95', 'translate-x-4');
                    fila.style.transition = "all 0.5s ease";
                    
                    setTimeout(() => {
                        fila.remove();
                        // Si la tabla queda vacía, refrescar para mostrar el mensaje de "No hay reportes"
                        if(document.querySelectorAll('tbody tr').length === 0) location.reload();
                    }, 500);
                } else {
                    alert("Error: El servidor no permitió el borrado.");
                }
            } catch (error) {
                console.error("Error de red:", error);
                alert("No se pudo conectar con el servidor de Python.");
            }
        }
    </script>

</body>
</html>