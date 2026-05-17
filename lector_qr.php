<?php
session_start();
require_once 'config.php';
include 'mascota.php';
requireSession();
$rol          = $_SESSION['rol'];
$menu_url     = $rol === 'Administrador' ? 'menu_admin.php' : 'menu_catedratico.php';
$usuario_id = (int)$_SESSION['usuario_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asistencia QR - UMG</title>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Montserrat',sans-serif;
            background:linear-gradient(135deg,#0f2027,#203a43,#2c5364);
            min-height:100vh; display:flex; align-items:center; justify-content:center;
            color:#f1f1f1;
        }
        .container {
            background:rgba(255,255,255,.07);
            border:1px solid rgba(255,255,255,.15);
            backdrop-filter:blur(14px);
            border-radius:20px; padding:2rem;
            width:95%; max-width:520px; text-align:center;
            box-shadow:0 0 30px rgba(0,0,0,.4);
        }
        .logo { width:65px; margin-bottom:.75rem; }
        h1 { font-size:1.6rem; color:#00d4a8; margin-bottom:.25rem; }
        .subtitle { font-size:.85rem; color:#ccc; margin-bottom:1.25rem; }

        #reader {
            width:100%; max-width:320px; margin:0 auto 1rem;
            border-radius:14px; overflow:hidden;
            box-shadow:0 0 20px rgba(0,212,168,.4);
        }

        #output {
            margin:0 0 1rem;
            padding:.85rem 1rem;
            border-radius:12px;
            font-size:.95rem;
            background:rgba(0,0,0,.3);
            color:#00ffbf;
            min-height:48px;
            display:flex; align-items:center; justify-content:center;
        }
        #output.error { color:#ff6b6b; }

        .student-info {
            background:rgba(255,255,255,.08);
            border-radius:12px; padding:1rem;
            margin-bottom:1rem; font-size:.9rem;
            display:none;
        }
        .student-name { font-size:1.1rem; font-weight:700; color:#00d4a8; }

        .info-box {
            background:rgba(255,255,255,.06);
            border-radius:12px; padding:.85rem 1rem;
            font-size:.85rem; color:#ddd; margin-bottom:1.25rem;
        }

        .selector-row {
            display:flex; gap:.75rem; margin-bottom:1rem; flex-wrap:wrap; justify-content:center;
        }
        select {
            flex:1; min-width:140px; padding:.55rem .9rem;
            background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.2);
            color:white; border-radius:10px; font-size:.85rem;
        }
        select option { background:#1a2e4a; }

        .btns { display:flex; gap:.75rem; justify-content:center; flex-wrap:wrap; }
        .btn {
            padding:.65rem 1.25rem;
            border:none; border-radius:10px;
            cursor:pointer; font-weight:600; font-size:.875rem;
            transition:.25s; text-decoration:none;
            display:inline-flex; align-items:center; gap:.4rem;
        }
        .btn-primary  { background:#00d4a8; color:#111; }
        .btn-primary:hover  { background:#00f5c3; }
        .btn-secondary { background:rgba(255,255,255,.12); color:white; border:1px solid rgba(255,255,255,.2); }
        .btn-secondary:hover { background:rgba(255,255,255,.2); }
    </style>
</head>
<body>
<div class="container">
    <img class="logo" src="https://upload.wikimedia.org/wikipedia/commons/thumb/3/3b/LogoUMG.png/320px-LogoUMG.png" alt="UMG">
    <h1>Asistencia por QR</h1>
    <p class="subtitle">Universidad Mariano Gálvez · Facultad de Ingeniería</p>

    <div class="selector-row">
        <select id="selectCurso">
            <option value="">Cargando cursos...</option>
        </select>
    </div>

    <div class="info-box">
        📅 <span id="fechaHoy"></span> &nbsp;·&nbsp; Escanea el carnet del estudiante
    </div>

    <div id="reader"></div>
    <div id="output">Esperando escaneo...</div>

    <div class="student-info" id="studentInfo">
        <div class="student-name" id="studentName"></div>
        <div id="studentMsg"></div>
    </div>

    <div class="btns">
        <a class="btn btn-secondary" href="<?= $menu_url ?>">← Menú</a>
        <button class="btn btn-primary" onclick="reiniciarScanner()">↺ Nuevo escaneo</button>
    </div>
</div>

<script>
const catedratico_id = <?= (int)$usuario_id ?>;
let cursoSeleccionado = 0;
let html5QrCode = null;
let escaneando = true;

// Fecha de hoy
const hoy = new Date();
document.getElementById('fechaHoy').textContent = hoy.toLocaleDateString('es-GT',{
    weekday:'long', year:'numeric', month:'long', day:'numeric'
});

// Cargar cursos
async function cargarCursos() {
    try {
        const res = await fetch(`asistencia_api.php?action=get_cursos&catedratico_id=${catedratico_id}`);
        const cursos = await res.json();
        const sel = document.getElementById('selectCurso');
        sel.innerHTML = '<option value="">— Seleccionar curso —</option>';
        cursos.forEach(c => {
            const op = document.createElement('option');
            op.value = c.id;
            op.textContent = `${c.nombre} · Salón ${c.salon}`;
            sel.appendChild(op);
        });
        if (cursos.length === 1) {
            sel.value = cursos[0].id;
            cursoSeleccionado = cursos[0].id;
        }
    } catch(e) { console.error(e); }
}
cargarCursos();
document.getElementById('selectCurso').addEventListener('change', function() {
    cursoSeleccionado = parseInt(this.value) || 0;
});

// Scanner QR
function iniciarScanner() {
    html5QrCode = new Html5Qrcode("reader");
    html5QrCode.start(
        { facingMode: "environment" },
        { fps: 12, qrbox: { width:240, height:240 } },
        onScanSuccess
    ).catch(err => {
        setOutput('❌ Error al iniciar cámara: ' + err, true);
    });
}

function onScanSuccess(decodedText) {
    if (!escaneando) return;
    escaneando = false;

    if (!cursoSeleccionado) {
        setOutput('⚠️ Selecciona un curso primero.', true);
        setTimeout(() => { escaneando = true; }, 2000);
        return;
    }

    setOutput('✅ QR detectado, registrando...');

    // Extraer ID del QR (formato: "...ID: 123...")
    const match = decodedText.match(/ID:\s*(\d+)/i);
    if (!match) {
        setOutput('❌ Formato de QR inválido. No se encontró el ID.', true);
        setTimeout(() => { escaneando = true; }, 3000);
        return;
    }
    const estudianteId = match[1];
    const fecha = new Date().toISOString().split('T')[0];

    const fd = new FormData();
    fd.append('usuario_id', estudianteId);
    fd.append('curso_id', cursoSeleccionado);
    fd.append('catedratico_id', catedratico_id);
    fd.append('fecha', fecha);

    fetch('asistencia_api.php?action=registrar_qr', { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                setOutput(data.success);
                document.getElementById('studentName').textContent = data.nombre || '';
                document.getElementById('studentMsg').textContent = 'Asistencia registrada correctamente';
                document.getElementById('studentInfo').style.display = 'block';
                // Notificar a la ventana de reportes si está abierta
                localStorage.setItem('qr_update', Date.now());
            } else {
                setOutput(data.error || '❌ Error al registrar.', true);
            }
            setTimeout(() => { escaneando = true; document.getElementById('studentInfo').style.display='none'; }, 4000);
        })
        .catch(err => {
            setOutput('❌ Error de red: ' + err.message, true);
            setTimeout(() => { escaneando = true; }, 3000);
        });
}

function setOutput(msg, isError = false) {
    const el = document.getElementById('output');
    el.textContent = msg;
    el.className = isError ? 'error' : '';
}

function reiniciarScanner() {
    escaneando = true;
    setOutput('Esperando escaneo...');
    document.getElementById('studentInfo').style.display = 'none';
}

iniciarScanner();
</script>
</body>
</html>
