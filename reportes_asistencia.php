<?php
session_start();
require_once 'config.php';
include 'mascota.php';
requireSession();
$usuario    = $_SESSION['usuario'];
$rol        = $_SESSION['rol'];
$usuario_id = $_SESSION['usuario_id'];
$menu_url = ($rol == 1)
    ? 'menu_admin.php'
    : 'menu_catedratico.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de Asistencia - UMG</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body { background:#f0f4f8; font-family:'Segoe UI',sans-serif; }
        nav { background:#1a2e4a; padding:0 1.5rem; display:flex; justify-content:space-between; align-items:center; height:60px; box-shadow:0 2px 10px rgba(0,0,0,.3); position:sticky; top:0; z-index:50; }
        .nav-title { color:white; font-weight:700; font-size:.95rem; }
        .btn-back { background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.2); color:white; padding:.35rem .85rem; border-radius:8px; text-decoration:none; font-size:.8rem; }
        .nav-user { color:rgba(255,255,255,.7); font-size:.8rem; }
        .presente { background:#f0fdf4; border-left:4px solid #16a34a; }
        .ausente  { background:#fef2f2; border-left:4px solid #dc2626; }
        .curso-card { cursor:pointer; transition:.2s; }
        .curso-card:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,.1); }
        .curso-card.selected { border:2px solid #2563eb; }
    </style>
</head>
<body>
<nav>
    <div style="display:flex;align-items:center;gap:1rem">
        <a class="btn-back" href="<?= $menu_url ?>"><i class="fas fa-arrow-left"></i> Menú</a>
        <span class="nav-title">Reportes de Asistencia · UMG</span>
    </div>
    <span class="nav-user"><?= htmlspecialchars($usuario) ?> · <?= htmlspecialchars($rol) ?></span>
</nav>

<div class="container mx-auto px-4 py-6 max-w-7xl">

    <!-- Selector fecha -->
    <div class="flex justify-between items-center mb-5">
        <h2 class="text-xl font-bold text-gray-800">Control de Asistencia</h2>
        <input type="date" id="fechaActual" class="border rounded-lg px-3 py-2 text-sm">
    </div>

    <!-- Cursos -->
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-3 mb-6" id="listaCursos">
        <div class="bg-white rounded-lg p-4 text-center text-gray-400 text-sm">Cargando cursos...</div>
    </div>

    <!-- Estadísticas -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <div class="bg-white rounded-lg p-3 shadow-sm">
            <div class="text-xs text-gray-500 mb-1">Total estudiantes</div>
            <div id="totalEst" class="text-2xl font-bold">0</div>
        </div>
        <div class="bg-white rounded-lg p-3 shadow-sm">
            <div class="text-xs text-gray-500 mb-1">Presentes</div>
            <div id="presentes" class="text-2xl font-bold text-green-600">0</div>
            <div class="w-full bg-gray-100 rounded-full h-1.5 mt-1"><div id="barPresentes" class="bg-green-500 h-1.5 rounded-full" style="width:0%"></div></div>
        </div>
        <div class="bg-white rounded-lg p-3 shadow-sm">
            <div class="text-xs text-gray-500 mb-1">Ausentes</div>
            <div id="ausentes" class="text-2xl font-bold text-red-600">0</div>
            <div class="w-full bg-gray-100 rounded-full h-1.5 mt-1"><div id="barAusentes" class="bg-red-500 h-1.5 rounded-full" style="width:0%"></div></div>
        </div>
        <div class="bg-white rounded-lg p-3 shadow-sm">
            <div class="text-xs text-gray-500 mb-1">Porcentaje asistencia</div>
            <div id="porcentaje" class="text-2xl font-bold">0%</div>
        </div>
    </div>

    <!-- Tabla estudiantes -->
    <div class="bg-white rounded-xl shadow overflow-hidden mb-4">
        <div class="px-5 py-3 border-b bg-gray-50 flex justify-between items-center">
            <h3 class="font-semibold text-sm text-gray-700" id="tituloCurso">Selecciona un curso para ver la lista</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Estudiante</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Estado</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Hora</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Acción</th>
                    </tr>
                </thead>
                <tbody id="cuerpoTabla" class="divide-y divide-gray-100">
                    <tr><td colspan="4" class="px-5 py-8 text-center text-gray-400 text-sm">Selecciona un curso</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Botones acción -->
    <div class="flex gap-3 flex-wrap">
        <button id="btnConfirmar" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition">
            <i class="fas fa-check-circle mr-1"></i> Confirmar Asistencia
        </button>
        <button id="btnPDF" class="bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition">
            <i class="fas fa-file-pdf mr-1"></i> Generar PDF
        </button>
    </div>
</div>

<script>
const CATEDRATICO_ID = <?= (int)$usuario_id ?>;
let cursoSeleccionado = null;
let estudiantes = [];

// Fecha de hoy
const hoy = new Date().toISOString().split('T')[0];
document.getElementById('fechaActual').value = hoy;

// ---- Cargar cursos ----
async function cargarCursos() {
    try {
        const action = '<?= $rol === "Administrador" ? "get_todos_cursos" : "get_cursos" ?>';
        const url = `asistencia_api.php?action=${action}&catedratico_id=${CATEDRATICO_ID}`;
        const res = await fetch(url);
        const cursos = await res.json();
        const cont = document.getElementById('listaCursos');
        cont.innerHTML = '';

        if (!cursos.length) {
            cont.innerHTML = '<div class="col-span-4 text-center text-gray-400 text-sm bg-white rounded-lg p-4">No hay cursos asignados</div>';
            return;
        }

        cursos.forEach(curso => {
            const div = document.createElement('div');
            div.className = 'curso-card bg-white rounded-lg p-4 shadow-sm border-2 border-transparent';
            div.innerHTML = `
                <h3 class="font-bold text-sm text-blue-800">${curso.nombre}</h3>
                <p class="text-xs text-gray-500 mt-1">Salón ${curso.salon}</p>
                <p class="text-xs text-gray-400 mt-0.5">${curso.total_estudiantes} estudiantes</p>
            `;
            div.addEventListener('click', () => seleccionarCurso(curso, div));
            cont.appendChild(div);
        });
    } catch(e) { console.error(e); }
}

function seleccionarCurso(curso, el) {
    document.querySelectorAll('.curso-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    cursoSeleccionado = curso;
    document.getElementById('tituloCurso').textContent = `Listado – ${curso.nombre} · Salón ${curso.salon}`;
    cargarEstudiantes(curso.id);
}

// ---- Cargar estudiantes ----
async function cargarEstudiantes(cursoId) {
    const fecha = document.getElementById('fechaActual').value;
    try {
        const res = await fetch(`asistencia_api.php?action=get_estudiantes&curso_id=${cursoId}&fecha=${fecha}&_=${Date.now()}`);
        estudiantes = await res.json();
        renderTabla();
        actualizarStats();
    } catch(e) { console.error(e); }
}

function renderTabla() {
    const tbody = document.getElementById('cuerpoTabla');
    tbody.innerHTML = '';
    if (!estudiantes.length) {
        tbody.innerHTML = '<tr><td colspan="4" class="px-5 py-8 text-center text-gray-400 text-sm">Sin estudiantes en este curso</td></tr>';
        return;
    }
    estudiantes.forEach(est => {
        const presente = est.presente == 1;
        const tr = document.createElement('tr');
        tr.className = presente ? 'presente' : 'ausente';
        const foto = est.foto_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(est.nombre+'+'+est.apellidos)}&background=dbeafe&color=1e40af`;
        tr.innerHTML = `
            <td class="px-5 py-3">
                <div class="flex items-center gap-3">
                    <img src="${foto}" class="w-9 h-9 rounded-full object-cover border" onerror="this.src='https://ui-avatars.com/api/?name=UMG&background=dbeafe&color=1e40af'">
                    <div>
                        <div class="text-sm font-semibold">${est.nombre} ${est.apellidos}</div>
                        <div class="text-xs text-gray-500">${est.correo}</div>
                    </div>
                </div>
            </td>
            <td class="px-5 py-3">
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold ${presente ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                    ${presente ? 'Presente' : 'Ausente'}
                </span>
            </td>
            <td class="px-5 py-3 text-sm text-gray-500">${presente && est.hora_registro ? est.hora_registro : '--:--:--'}</td>
            <td class="px-5 py-3">
                <button class="toggle-btn text-xs font-semibold px-3 py-1 rounded-lg ${presente ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200'}"
                        data-id="${est.id}">
                    <i class="fas ${presente ? 'fa-user-times' : 'fa-user-check'} mr-1"></i>${presente ? 'Marcar Ausente' : 'Marcar Presente'}
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function actualizarStats() {
    const total     = estudiantes.length;
    const pres      = estudiantes.filter(e => e.presente == 1).length;
    const aus       = total - pres;
    const pct       = total > 0 ? Math.round((pres/total)*100) : 0;
    document.getElementById('totalEst').textContent   = total;
    document.getElementById('presentes').textContent  = pres;
    document.getElementById('ausentes').textContent   = aus;
    document.getElementById('porcentaje').textContent = pct + '%';
    document.getElementById('barPresentes').style.width = pct + '%';
    document.getElementById('barAusentes').style.width  = (100-pct) + '%';
}

// Cambiar estado en la tabla (sin guardar aún)
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.toggle-btn');
    if (!btn) return;
    const id  = parseInt(btn.dataset.id);
    const est = estudiantes.find(e => e.id === id);
    if (est) {
        est.presente = est.presente == 1 ? 0 : 1;
        if (est.presente == 1) est.hora_registro = new Date().toTimeString().slice(0,8);
        else est.hora_registro = null;
        renderTabla();
        actualizarStats();
    }
});

// Recargar al cambiar fecha
document.getElementById('fechaActual').addEventListener('change', () => {
    if (cursoSeleccionado) cargarEstudiantes(cursoSeleccionado.id);
});

// Escuchar actualizaciones del lector QR
window.addEventListener('storage', e => {
    if (e.key === 'qr_update' && cursoSeleccionado) cargarEstudiantes(cursoSeleccionado.id);
});

// Confirmar asistencia
document.getElementById('btnConfirmar').addEventListener('click', async () => {
    if (!cursoSeleccionado) { alert('Selecciona un curso primero.'); return; }
    const datos = {
        curso_id: cursoSeleccionado.id,
        fecha: document.getElementById('fechaActual').value,
        catedratico_id: CATEDRATICO_ID,
        asistencias: estudiantes.map(e => ({ estudiante_id: e.id, presente: e.presente == 1 ? 1 : 0 }))
    };
    try {
        const res = await fetch('asistencia_api.php?action=guardar_asistencia', {
            method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(datos)
        });
        const data = await res.json();
        if (data.success) alert('✅ Asistencia guardada correctamente.');
        else alert('❌ Error: ' + (data.error || 'desconocido'));
    } catch(e) { alert('Error de red.'); }
});

// Generar PDF
document.getElementById('btnPDF').addEventListener('click', () => {
    if (!cursoSeleccionado) { alert('Selecciona un curso primero.'); return; }
    const fecha = document.getElementById('fechaActual').value;
    window.open(`generar_pdf.php?curso_id=${cursoSeleccionado.id}&fecha=${encodeURIComponent(fecha)}`, '_blank');
});

cargarCursos();
</script>
</body>
</html>
