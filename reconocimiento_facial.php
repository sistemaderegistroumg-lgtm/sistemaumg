<?php
session_start();
include 'mascota.php';
require_once __DIR__ . '/config.php';

requireSession();

$rol        = (int)$_SESSION['rol'];
$usuario_id = (int)$_SESSION['usuario_id'];

// Menú según rol
switch ($rol) {
    case 1:
        $menu_url = 'menu_admin.php';
        break;

    case 3:
        $menu_url = 'menu_catedratico.php';
        break;

    case 2:
        $menu_url = 'menu_estudiante.php';
        break;

    default:
        session_destroy();
        header("Location: login.php");
        exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asistencia Facial - UMG</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Montserrat',sans-serif;background:linear-gradient(135deg,#0f2027,#203a43,#2c5364);min-height:100vh;color:#f1f1f1;}
        nav{background:rgba(0,0,0,.35);backdrop-filter:blur(10px);padding:0 2rem;display:flex;justify-content:space-between;align-items:center;height:60px;border-bottom:1px solid rgba(255,255,255,.1);}
        .nav-title{color:#00d4a8;font-weight:700;font-size:1rem;display:flex;align-items:center;gap:.5rem;}
        .btn-back{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);color:white;padding:.4rem .9rem;border-radius:8px;text-decoration:none;font-size:.8rem;}
        .logo-umg{
    width:42px;
    height:42px;
    object-fit:contain;

    background:white;
    border-radius:50%;

    padding:4px;

    box-shadow:0 2px 8px rgba(0,0,0,.25);
}

.nav-left{
    display:flex;
    align-items:center;
    gap:1rem;
}

.btn-back{
    display:inline-flex;
    align-items:center;
    gap:.45rem;

    background:rgba(255,255,255,.1);
    border:1px solid rgba(255,255,255,.2);

    color:white;
    padding:.45rem 1rem;

    border-radius:8px;
    text-decoration:none;

    font-size:.82rem;
    font-weight:600;

    transition:.2s;
}

.btn-back:hover{
    background:#00d4a8;
    color:#111;
}
        .main{display:grid;grid-template-columns:1fr 340px;gap:1.5rem;padding:1.5rem;max-width:1200px;margin:0 auto;}
        .cam-panel{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.15);backdrop-filter:blur(14px);border-radius:20px;padding:1.5rem;}
        .panel-title{font-size:1rem;font-weight:700;color:#00d4a8;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem;}
        .selector-row{display:flex;gap:.75rem;margin-bottom:1rem;}
        select{flex:1;padding:.55rem 1rem;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);color:white;border-radius:10px;font-size:.85rem;}
        select option{background:#1a2e4a;}
        .video-wrap{position:relative;border-radius:14px;overflow:hidden;background:#000;box-shadow:0 0 25px rgba(0,212,168,.25);margin-bottom:1rem;}
        #video{width:100%;display:block;max-height:420px;object-fit:cover;}
        #canvasOverlay{position:absolute;top:0;left:0;width:100%;height:100%;}
        .btn{flex:1;padding:.65rem 1rem;border:none;border-radius:10px;cursor:pointer;font-weight:700;font-size:.85rem;display:flex;align-items:center;justify-content:center;gap:.4rem;transition:.2s;}
        .btns-row{display:flex;gap:.75rem;}
        .btn-menu{background:rgba(255,255,255,.08);color:white;border:1px solid rgba(255,255,255,.15);}
        .btn-menu:hover{background:rgba(255,255,255,.18);}
        .status-bar{display:flex;align-items:center;gap:.75rem;background:rgba(0,0,0,.3);border-radius:10px;padding:.65rem 1rem;margin-top:1rem;font-size:.82rem;min-height:48px;}
        .dot{width:10px;height:10px;border-radius:50%;background:#6b7280;flex-shrink:0;}
        .dot.verde{background:#10b981;box-shadow:0 0 8px #10b981;}
        .dot.amarillo{background:#f59e0b;box-shadow:0 0 8px #f59e0b;}
        .dot.rojo{background:#ef4444;box-shadow:0 0 8px #ef4444;}
        @keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
        .dot.pulsando{animation:pulse 1s infinite;}
        .right-panel{display:flex;flex-direction:column;gap:1rem;}
        .info-card{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:1.25rem;}
        .info-title{font-size:.8rem;color:#aaa;font-weight:600;text-transform:uppercase;margin-bottom:.75rem;}
        .resultado-box{background:rgba(0,0,0,.3);border-radius:12px;padding:1rem;text-align:center;min-height:130px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.5rem;}
        .stats-grid{display:grid;grid-template-columns:1fr 1fr;gap:.6rem;}
        .stat-box{background:rgba(0,0,0,.2);border-radius:10px;padding:.6rem .75rem;text-align:center;}
        .stat-num{font-size:1.4rem;font-weight:700;}
        .stat-lbl{font-size:.7rem;color:#aaa;margin-top:.1rem;}
        .historial{max-height:280px;overflow-y:auto;}
        .historial-item{display:flex;align-items:center;gap:.75rem;padding:.6rem .75rem;border-radius:10px;background:rgba(0,0,0,.2);margin-bottom:.4rem;font-size:.82rem;}
        .historial-avatar{width:36px;height:36px;border-radius:50%;background:#1a2e4a;color:#00d4a8;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem;flex-shrink:0;}
        .historial-nombre{font-weight:600;line-height:1.3;}
        .historial-hora{font-size:.72rem;color:#6b7280;}
        .historial-badge{margin-left:auto;font-size:.7rem;padding:.2rem .5rem;border-radius:20px;font-weight:700;white-space:nowrap;}
        .badge-ok{background:#f0fdf4;color:#166534;}
        .badge-dup{background:#fef3c7;color:#92400e;}
        @media(max-width:768px){.main{grid-template-columns:1fr;}}
    </style>
</head>
<body>
<nav>

    <div class="nav-left">

        <!-- BOTÓN MENÚ -->
        <a class="btn-back" href="<?= $menu_url ?>">
            <i class="fas fa-arrow-left"></i>
            Menú
        </a>

        <!-- LOGO -->
        <img
            src="logo_umg.png"
            alt="UMG"
            class="logo-umg"
        >

        <!-- TÍTULO -->
        <div class="nav-title">
            <i class="fas fa-camera"></i>
            Asistencia Facial · UMG
        </div>

    </div>

    <div style="display:flex;gap:.75rem;align-items:center">
        <span style="font-size:.75rem;color:rgba(255,255,255,.5)">
            <?= htmlspecialchars($_SESSION['usuario']) ?>
        </span>
    </div>

</nav>

<div class="main">
    <!-- Cámara -->
    <div class="cam-panel">
        <div class="panel-title"><i class="fas fa-video"></i> Cámara — Detección automática en vivo</div>

        <div class="selector-row">
            <select id="selectCurso">
                <option value="">— Cargando cursos... —</option>
            </select>
            <button class="btn btn-menu" style="flex:0;padding:.55rem .9rem" onclick="cargarEstudiantes()" title="Recargar">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>

        <div class="video-wrap">
            <video id="video" autoplay playsinline muted></video>
            <canvas id="canvasOverlay"></canvas>
        </div>

        <div class="btns-row">
            <a class="btn btn-menu" href="<?= $menu_url ?>"><i class="fas fa-home"></i> Volver al Menú</a>
        </div>

        <div class="status-bar">
            <div class="dot amarillo pulsando" id="statusDot"></div>
            <span id="statusMsg">Iniciando modelos de reconocimiento facial...</span>
        </div>
    </div>

    <!-- Panel derecho -->
    <div class="right-panel">
        <div class="info-card">
            <div class="info-title"><i class="fas fa-user-check"></i> Último reconocimiento</div>
            <div class="resultado-box" id="resultadoBox">
                <i class="fas fa-face-smile" style="font-size:2.5rem;color:#374151"></i>
                <div style="font-size:.85rem;color:#6b7280">Esperando rostro en cámara...</div>
            </div>
        </div>

        <div class="info-card">
            <div class="info-title"><i class="fas fa-chart-bar"></i> Sesión actual</div>
            <div class="stats-grid">
                <div class="stat-box"><div class="stat-num" id="statTotal">0</div><div class="stat-lbl">Registradas</div></div>
                <div class="stat-box"><div class="stat-num" id="statDuplicados" style="color:#f59e0b">0</div><div class="stat-lbl">Ya registrados</div></div>
                <div class="stat-box"><div class="stat-num" id="statEstudiantes" style="color:#00d4a8">0</div><div class="stat-lbl">En curso</div></div>
                <div class="stat-box"><div class="stat-num" id="statNoRec" style="color:#ef4444">0</div><div class="stat-lbl">No reconocidos</div></div>
            </div>
        </div>

        <div class="info-card" style="flex:1">
            <div class="info-title"><i class="fas fa-history"></i> Historial</div>
            <div class="historial" id="historial">
                <div style="text-align:center;color:#6b7280;font-size:.82rem;padding:1rem">Sin registros aún</div>
            </div>
        </div>
    </div>
</div>

<script>
const CATEDRATICO_ID = <?= (int)$usuario_id ?>;
const ES_ADMIN = <?= ((int)$_SESSION['rol'] === 1) ? 'true' : 'false' ?>;

let modelosCargados    = false;
let estudiantes        = [];
let registradosHoy     = new Set();
let procesando         = false;
let intervaloDeteccion = null;
const stats = { total:0, duplicados:0, noRec:0 };

const video         = document.getElementById('video');
const canvasOverlay = document.getElementById('canvasOverlay');

// ---- Modelos ----
const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model';

async function cargarModelos() {
    setStatus('Cargando modelos de IA (puede tardar ~20 seg la primera vez)...', 'amarillo', true);
    try {
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68TinyNet.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
        ]);
        modelosCargados = true;
        setStatus('✅ Modelos listos. Iniciando cámara...', 'verde');
    } catch(e) {
        setStatus('❌ Error cargando modelos de IA: ' + e.message, 'rojo');
    }
}

// ---- Cámara ----
async function iniciarCamara() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video:{ width:{ideal:640}, height:{ideal:480}, facingMode:'user' }
        });
        video.srcObject = stream;
        await new Promise(r => video.onloadedmetadata = r);
        canvasOverlay.width  = video.videoWidth;
        canvasOverlay.height = video.videoHeight;
        setStatus('✅ Cámara activa. Selecciona un curso para comenzar.', 'verde');
    } catch(e) {
        setStatus('❌ Cámara no disponible: ' + e.message, 'rojo');
    }
}

// ---- Cursos ----
async function cargarCursos() {

    try {

        const action = 'get_cursos';

        const res = await fetch(
            `asistencia_api.php?action=${action}&catedratico_id=${CATEDRATICO_ID}`
        );

        const cursos = await res.json();

        const sel = document.getElementById('selectCurso');

        sel.innerHTML =
            '<option value="">— Seleccionar curso —</option>';

        cursos.forEach(c => {

            const op = document.createElement('option');

            op.value = c.id;

            op.textContent =
                `${c.nombre} · Salón ${c.salon} (${c.total_estudiantes} est.)`;

            sel.appendChild(op);
        });

        if (cursos.length === 1) {

            sel.value = cursos[0].id;

            await cargarEstudiantes();
        }

    } catch(e) {

        console.error(e);
    }
}
// ---- Estudiantes + descriptores ----
async function cargarEstudiantes() {
    const cursoId = document.getElementById('selectCurso').value;
    if (!cursoId) return;

    detenerDeteccion();
    estudiantes = [];
    registradosHoy.clear();

    setStatus('Cargando fotos y calculando descriptores faciales...', 'amarillo', true);

    try {
        const res  = await fetch(`biometrico_api.php?action=get_fotos_curso&curso_id=${cursoId}`);
        const data = await res.json();

        if (data.error) { setStatus('❌ ' + data.error, 'rojo'); return; }
        document.getElementById('statEstudiantes').textContent = data.length;

        let ok = 0;
        for (const est of data) {
            if (!est.foto_b64) continue;
            try {
                const img = await imgDesdeB64(est.foto_b64);
                const det = await faceapi
                    .detectSingleFace(img, new faceapi.TinyFaceDetectorOptions({ scoreThreshold: 0.3 }))
                    .withFaceLandmarks(true)
                    .withFaceDescriptor();
                if (det) {
                    estudiantes.push({ ...est, descriptor: det.descriptor });
                    ok++;
                }
            } catch(e) { console.warn(e); }
            setStatus(`Procesando fotos: ${ok} detectados de ${data.length}...`, 'amarillo', true);
        }

        if (estudiantes.length === 0) {
            setStatus('⚠️ Ninguna foto válida. Verifica que los estudiantes tengan foto registrada.', 'rojo');
            return;
        }

        setStatus(`✅ ${estudiantes.length}/${data.length} estudiantes listos. Detectando automáticamente...`, 'verde');
        iniciarDeteccion(cursoId);

    } catch(e) { setStatus('❌ ' + e.message, 'rojo'); }
}

function imgDesdeB64(b64) {
    return new Promise((res, rej) => {
        const img = new Image();
        img.onload  = () => res(img);
        img.onerror = rej;
        img.src     = 'data:image/jpeg;base64,' + b64;
    });
}

// ---- Detección automática ----
function iniciarDeteccion(cursoId) {
    detenerDeteccion();
    intervaloDeteccion = setInterval(() => detectar(cursoId), 2500);
}

function detenerDeteccion() {
    if (intervaloDeteccion) { clearInterval(intervaloDeteccion); intervaloDeteccion = null; }
}

async function detectar(cursoId) {
    if (procesando || !modelosCargados || !estudiantes.length) return;
    procesando = true;

    try {
        const det = await faceapi
            .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ scoreThreshold: 0.4 }))
            .withFaceLandmarks(true)
            .withFaceDescriptor();

        // Limpiar canvas
        const ctx = canvasOverlay.getContext('2d');
        ctx.clearRect(0, 0, canvasOverlay.width, canvasOverlay.height);

        if (!det) { procesando = false; return; }

        // Dibujar recuadro verde
        const dims    = faceapi.matchDimensions(canvasOverlay, video, true);
        const resized = faceapi.resizeResults(det, dims);
        faceapi.draw.drawDetections(canvasOverlay, resized);

        // Comparar con estudiantes
        const labeled = estudiantes.map(e =>
            new faceapi.LabeledFaceDescriptors(`${e.id}|${e.nombre} ${e.apellidos}`, [e.descriptor])
        );
        const matcher = new faceapi.FaceMatcher(labeled, 0.5);
        const match   = matcher.findBestMatch(det.descriptor);

        if (match.label === 'unknown') {
            stats.noRec++;
            document.getElementById('statNoRec').textContent = stats.noRec;
            procesando = false;
            return;
        }

        const [idStr, nombre] = match.label.split('|');
        const estId    = parseInt(idStr);
        const confianza = Math.round((1 - match.distance) * 100);
        const estData  = estudiantes.find(e => e.id === estId);

        if (registradosHoy.has(estId)) {
            setStatus(`⚠️ ${nombre} ya registró asistencia hoy (confianza: ${confianza}%).`, 'amarillo');
            mostrarResultado(estData, 'dup', confianza);
            stats.duplicados++;
            document.getElementById('statDuplicados').textContent = stats.duplicados;
            procesando = false;
            return;
        }

        // Registrar
        setStatus(`✅ Reconocido: ${nombre} (${confianza}%). Registrando asistencia...`, 'verde', true);
        const fecha = new Date().toISOString().split('T')[0];
        const fd    = new FormData();
        fd.append('usuario_id',    estId);
        fd.append('curso_id',      cursoId);
        fd.append('catedratico_id', CATEDRATICO_ID);
        fd.append('fecha',         fecha);

        const r    = await fetch('asistencia_api.php?action=registrar_qr', { method:'POST', body:fd });
        const data = await r.json();

        if (data.success) {
            registradosHoy.add(estId);
            setStatus(`✅ Asistencia registrada: ${nombre} (confianza: ${confianza}%)`, 'verde');
            mostrarResultado(estData, 'ok', confianza);
            agregarHistorial(estData, 'ok', confianza);
            stats.total++;
            document.getElementById('statTotal').textContent = stats.total;
            await new Promise(r => setTimeout(r, 3000)); // pausa 3s
        } else {
            registradosHoy.add(estId); // evitar reintentos
            setStatus('⚠️ ' + (data.error || 'Error al registrar'), 'amarillo');
        }

    } catch(e) { console.error(e); }
    finally { procesando = false; }
}

// ---- UI ----
function setStatus(msg, tipo = 'amarillo', pulsando = false) {
    document.getElementById('statusMsg').textContent = msg;
    document.getElementById('statusDot').className   = 'dot ' + tipo + (pulsando ? ' pulsando' : '');
}

function mostrarResultado(est, tipo, confianza) {
    const box  = document.getElementById('resultadoBox');
    const hora = new Date().toLocaleTimeString();
    const foto = est ? `data:image/jpeg;base64,${est.foto_b64}` : '';
    const color = tipo === 'ok' ? '#00d4a8' : '#f59e0b';
    const label = tipo === 'ok' ? '✅ Asistencia registrada' : '⚠️ Ya registrado hoy';

    box.innerHTML = est ? `
        <img src="${foto}" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid ${color}">
        <div style="font-size:.95rem;font-weight:700;color:${color}">${est.nombre} ${est.apellidos}</div>
        <div style="font-size:.8rem;color:#aaa">${label}</div>
        <div style="font-size:.72rem;color:#6b7280">Confianza: ${confianza}% · ${hora}</div>
    ` : '';
}

function agregarHistorial(est, tipo, confianza) {
    const cont = document.getElementById('historial');
    // Quitar mensaje inicial
    if (cont.firstChild && cont.firstChild.tagName !== 'DIV'.toUpperCase()) cont.innerHTML = '';
    const item     = document.createElement('div');
    item.className = 'historial-item';
    const badge    = tipo === 'ok'
        ? `<span class="historial-badge badge-ok">✅ ${confianza}%</span>`
        : `<span class="historial-badge badge-dup">⚠️ Ya reg.</span>`;
    item.innerHTML = `
        <div class="historial-avatar">${est.nombre.charAt(0)}</div>
        <div>
            <div class="historial-nombre">${est.nombre} ${est.apellidos}</div>
            <div class="historial-hora">${new Date().toLocaleTimeString()}</div>
        </div>
        ${badge}`;
    cont.insertBefore(item, cont.firstChild);
}

// ---- Inicio ----
document.getElementById('selectCurso').addEventListener('change', cargarEstudiantes);

(async () => {
    await cargarModelos();
    await iniciarCamara();
    await cargarCursos();
})();
</script>
</body>
</html>
