<?php
session_start();
require_once 'config.php';
include 'mascota.php';
requireSession(1);
$usuario = $_SESSION['usuario'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Estudiantes - UMG</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>
    <style>
        :root { --primary:#1a2e4a; --accent:#2563eb; --gray:#6b7280; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:#f0f4f8; font-family:'Segoe UI',sans-serif; color:#1f2937; }
        nav { background:var(--primary); padding:0 2rem; display:flex; justify-content:space-between; align-items:center; height:64px; box-shadow:0 2px 10px rgba(0,0,0,.3); }
        .nav-left { display:flex; align-items:center; gap:1rem; }
        .nav-title { color:white; font-weight:700; }
        .btn-back { background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.2); color:white; padding:.4rem .9rem; border-radius:8px; text-decoration:none; font-size:.875rem; }
        .container { max-width:960px; margin:0 auto; padding:2rem; }
        .card { background:white; border-radius:16px; padding:2rem; box-shadow:0 4px 20px rgba(0,0,0,.07); }
        .card-title { font-size:1.25rem; font-weight:700; margin-bottom:1.5rem; padding-bottom:.75rem; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; gap:.5rem; color:var(--primary); }
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; }
        .form-group { display:flex; flex-direction:column; gap:.35rem; }
        .form-group.full { grid-column:span 2; }
        label { font-size:.875rem; font-weight:600; color:#374151; }
        input, select { padding:.7rem 1rem; border:2px solid #e5e7eb; border-radius:9px; font-size:.9rem; outline:none; transition:border-color .2s; width:100%; }
        input:focus, select:focus { border-color:var(--accent); }
        /* Cámara */
        .camera-section { grid-column:span 2; background:#f8fafc; border-radius:14px; border:2px dashed #d1d5db; padding:1.5rem; position:relative; }
        .camera-label { position:absolute; top:0; left:0; background:var(--accent); color:white; font-size:.7rem; font-weight:700; padding:.3rem 1rem; border-bottom-right-radius:10px; letter-spacing:.05em; }
        .camera-layout { display:flex; gap:1.5rem; align-items:flex-start; justify-content:center; margin-top:1.5rem; flex-wrap:wrap; }
        .camera-feed video, .camera-feed canvas, .camera-preview img { width:220px; height:280px; border-radius:12px; object-fit:cover; background:#e5e7eb; display:block; }
        .camera-controls { display:flex; gap:.75rem; justify-content:center; margin-top:1rem; flex-wrap:wrap; }
        /* Botones */
        .btn { padding:.7rem 1.5rem; border:none; border-radius:9px; font-size:.9rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:.4rem; transition:.2s; }
        .btn-primary { background:var(--accent); color:white; } .btn-primary:hover { background:var(--primary); }
        .btn-warning { background:#f59e0b; color:white; display:none; } .btn-warning:hover { background:#d97706; }
        .btn-success { background:#16a34a; color:white; } .btn-success:hover { background:#15803d; }
        .btn-full { width:100%; justify-content:center; grid-column:span 2; margin-top:.5rem; }
        .btn-full.submit { background:var(--primary); color:white; font-size:1rem; padding:.9rem; }
        /* Carnet preview */
        .carnet-section { display:none; margin-top:1.5rem; text-align:center; }
        canvas#carnetCanvas { display:none; }
        #carnetPreviewImg { max-width:480px; width:100%; border-radius:14px; box-shadow:0 10px 30px rgba(0,0,0,.15); margin:1rem auto; display:block; }
        .alert { padding:.85rem 1rem; border-radius:10px; margin-bottom:1rem; font-size:.9rem; display:none; }
        .alert.success { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; display:block; }
        .alert.error   { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; display:block; }
        @media(max-width:640px) { .form-grid { grid-template-columns:1fr; } .form-group.full, .btn-full { grid-column:span 1; } }
    </style>
</head>
<body>
<nav>
    <div class="nav-left">
        <a class="btn-back" href="menu_admin.php"><i class="fas fa-arrow-left"></i> Menú</a>
        <span class="nav-title">Registro de Estudiantes</span>
    </div>
    <span style="color:rgba(255,255,255,.7);font-size:.875rem"><?= htmlspecialchars($usuario) ?></span>
</nav>

<div class="container">
    <div class="card">
        <h2 class="card-title"><i class="fas fa-user-plus" style="color:var(--accent)"></i> Nuevo Registro Estudiantil</h2>

        <div id="alertMsg" class="alert"></div>

        <form id="registroForm">
            <div class="form-grid">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Nombre</label>
                    <input type="text" name="nombre" required placeholder="Primer nombre">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Apellidos</label>
                    <input type="text" name="apellidos" required placeholder="Apellidos">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Correo UMG</label>
                    <input type="email" name="correo" required placeholder="correo@umg.edu.gt">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Teléfono</label>
                    <input type="tel" name="telefono" required placeholder="5555-5555">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-graduation-cap"></i> Carrera</label>
                    <select name="carrera" required>
                        <option value="">Seleccionar...</option>
                        <option>Ingeniería en Sistemas</option>
                        <option>Ingeniería Industrial</option>
                        <option>Derecho</option>
                        <option>Psicología</option>
                        <option>Administración</option>
                        <option>Contaduría Pública</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> Semestre</label>
                    <input type="number" name="semestre" min="1" max="12" required placeholder="1-12">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-users"></i> Sección</label>
                    <input type="text" name="seccion" required placeholder="A, B, C...">
                </div>

                <div class="camera-section">
                    <span class="camera-label">FOTO BIOMÉTRICA</span>
                    <div class="camera-layout">
                        <div class="camera-feed">
                            <video id="video" autoplay playsinline></video>
                            <canvas id="canvas" style="display:none"></canvas>
                        </div>
                        <div class="camera-preview">
                            <img id="fotoPreview" src="" alt="Vista previa" style="display:none">
                        </div>
                    </div>
                    <div class="camera-controls">
                        <button type="button" id="btnCapturar" class="btn btn-primary"><i class="fas fa-camera"></i> Tomar Foto</button>
                        <button type="button" id="btnCambiar" class="btn btn-warning"><i class="fas fa-sync-alt"></i> Cambiar Foto</button>
                    </div>
                    <input type="hidden" id="fotoInput" name="foto">
                </div>

                <button type="button" id="btnGenerar" class="btn btn-success btn-full">
                    <i class="fas fa-id-card"></i> Generar Carnet Preview
                </button>
                <button type="submit" class="btn btn-full submit">
                    <i class="fas fa-save"></i> Guardar Registro y Enviar Correo
                </button>
            </div>
        </form>

        <div class="carnet-section" id="carnetSection">
            <h3 style="margin-bottom:.75rem;color:var(--primary)">Vista previa del carnet</h3>
            <img id="carnetPreviewImg" src="" alt="Carnet">
            <div style="margin-top:.75rem">
                <button class="btn btn-primary" id="btnDescargar"><i class="fas fa-download"></i> Descargar Carnet</button>
            </div>
        </div>
    </div>
</div>

<canvas id="carnetCanvas" width="640" height="400"></canvas>

<script>
const video      = document.getElementById('video');
const canvas     = document.getElementById('canvas');
const fotoInput  = document.getElementById('fotoInput');
const fotoPreview = document.getElementById('fotoPreview');
const btnCapturar = document.getElementById('btnCapturar');
const btnCambiar  = document.getElementById('btnCambiar');
let stream;
let carnetDataURL = null;

async function iniciarCamara() {
    try {
        stream = await navigator.mediaDevices.getUserMedia({ video:{ facingMode:'user', width:640, height:480 } });
        video.srcObject = stream;
        video.style.display = 'block';
    } catch(e) { 
        showAlert('No se pudo acceder a la cámara: ' + e.message, 'error'); 
    }
}

function detenerCamara() {
    if (stream) stream.getTracks().forEach(t => t.stop());
}

btnCapturar.addEventListener('click', () => {
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);
    const b64 = canvas.toDataURL('image/jpeg', 0.85);
    fotoInput.value = b64;
    fotoPreview.src = b64;
    fotoPreview.style.display = 'block';
    video.style.display = 'none';
    btnCapturar.style.display = 'none';
    btnCambiar.style.display = 'inline-flex';
    detenerCamara();
});

btnCambiar.addEventListener('click', () => {
    fotoPreview.style.display = 'none';
    video.style.display = 'block';
    btnCapturar.style.display = 'inline-flex';
    btnCambiar.style.display = 'none';
    fotoInput.value = '';
    carnetDataURL = null;
    iniciarCamara();
});

// Generar carnet
document.getElementById('btnGenerar').addEventListener('click', async () => {
    const nombre    = document.querySelector('[name=nombre]').value.trim();
    const apellidos = document.querySelector('[name=apellidos]').value.trim();
    const carrera   = document.querySelector('[name=carrera]').value;
    const semestre  = document.querySelector('[name=semestre]').value;
    const seccion   = document.querySelector('[name=seccion]').value;
    const foto      = fotoInput.value;

    if (!nombre || !apellidos || !carrera || !semestre || !foto) {
        showAlert('Completa todos los campos y toma la foto antes de generar el carnet.', 'error');
        return;
    }
    await generarCarnet(nombre, apellidos, carrera, semestre, seccion, foto, Date.now());
});

async function generarCarnet(nombre, apellidos, carrera, semestre, seccion, foto, id) {
    const cvs = document.getElementById('carnetCanvas');
    const ctx = cvs.getContext('2d');
    ctx.clearRect(0,0,cvs.width,cvs.height);

    // Fondo
    ctx.fillStyle = '#f0f4f8';
    ctx.fillRect(0,0,cvs.width,cvs.height);
   
    // Fondo degradado superior
    const grad = ctx.createLinearGradient(0,0,cvs.width,0);
    grad.addColorStop(0, '#0f172a');
    grad.addColorStop(1, '#2563eb');

    ctx.fillStyle = grad;
    ctx.fillRect(0,0,cvs.width,80);

    ctx.fillStyle = 'white';
    ctx.textAlign = 'center';

    ctx.font = 'bold 22px Segoe UI';
    ctx.fillText('UNIVERSIDAD MARIANO GÁLVEZ', cvs.width / 2, 38);

    ctx.font = '14px Segoe UI';
    ctx.fillText('Carné de Identificación Estudiantil', cvs.width / 2, 62);

    // Línea decorativa
    ctx.strokeStyle = 'rgba(255,255,255,0.3)';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(120, 78);
    ctx.lineTo(cvs.width - 40, 78);
    ctx.stroke();

    // Foto circular
    const fotoImg = new Image();
    fotoImg.src = foto;
    await new Promise(r => { fotoImg.onload = r; fotoImg.onerror = r; });
    ctx.save();
    ctx.beginPath();
    ctx.arc(110,195,75,0,Math.PI*2);
    ctx.closePath();
    ctx.clip();
    ctx.drawImage(fotoImg,35,120,150,150);
    ctx.restore();
    ctx.strokeStyle = '#2563eb';
    ctx.lineWidth = 4;
    ctx.beginPath();
    ctx.arc(110,195,75,0,Math.PI*2);
    ctx.stroke();

    // Datos
    ctx.textAlign = 'left';
    ctx.fillStyle = '#1a2e4a';
    ctx.font = 'bold 18px Arial';
    ctx.fillText(nombre + ' ' + apellidos, 210, 115);
    ctx.font = '13px Arial';
    ctx.fillStyle = '#374151';
    const datos = [
        ['Carrera:', carrera],
        ['Semestre:', semestre + '°'],
        ['Sección:', seccion],
        ['ID:', id.toString()]
    ];
    datos.forEach(([lbl,val],i) => {
        ctx.fillStyle = '#6b7280'; ctx.font = '11px Arial';
        ctx.fillText(lbl, 210, 145 + i*28);
        ctx.fillStyle = '#1f2937'; ctx.font = 'bold 13px Arial';
        ctx.fillText(val, 280, 145 + i*28);
    });

    // QR
    const qrData = ['UMG', `Nombre: ${nombre} ${apellidos}`, `Carrera: ${carrera}`, `Semestre: ${semestre}`, `Sección: ${seccion}`, `ID: ${id}`].join('\n');
    const qr = new QRious({ value: qrData, size:120, level:'H', background:'white', foreground:'#1a2e4a' });
    const qrImg = new Image();
    qrImg.src = qr.toDataURL();
    await new Promise(r => { qrImg.onload = r; qrImg.onerror = r; });
    ctx.drawImage(qrImg, 490, 100, 130, 130);
    ctx.fillStyle = '#6b7280'; ctx.font = '10px Arial'; ctx.textAlign='center';
    ctx.fillText('Escanear para verificar', 555, 244);

    // Barra inferior
    ctx.fillStyle = '#2563eb';
    ctx.fillRect(0, cvs.height-30, cvs.width, 30);
    ctx.fillStyle='white'; ctx.font='11px Arial';
    ctx.fillText('Sede La Florida, Zona 19 · Guatemala', cvs.width/2, cvs.height-10);

    carnetDataURL = cvs.toDataURL('image/png');
    document.getElementById('carnetPreviewImg').src = carnetDataURL;
    document.getElementById('carnetSection').style.display = 'block';
    document.getElementById('carnetSection').scrollIntoView({behavior:'smooth'});
}

document.getElementById('btnDescargar').addEventListener('click', () => {
    if (!carnetDataURL) return;
    const link = document.createElement('a');
    link.download = 'carnet_umg.png';
    link.href = carnetDataURL;
    link.click();
});

// Enviar formulario
document.getElementById('registroForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    if (!fotoInput.value) { showAlert('Toma la foto del estudiante primero.', 'error'); return; }

    const btn = this.querySelector('button[type=submit]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

    const fd = new FormData(this);
    fd.append('foto', fotoInput.value);

    // Adjuntar carnet si fue generado
    if (carnetDataURL) {
        const carnetBlob = await (await fetch(carnetDataURL)).blob();
        fd.append('carnet', carnetBlob, 'carnet.png');
    }

    try {
        const res = await fetch('guardar_estudiante.php', { method:'POST', body:fd });
        const data = await res.json();

        if (data.success) {
            showAlert('✅ ' + data.message, 'success');

            // ABRIR WHATSAPP SI EXISTE EN LA RESPUESTA
            if (data.whatsapp) {
                window.open(data.whatsapp, "_blank");
            }

            // Generar carnet final con ID real retornado por la DB
            const nombre = fd.get('nombre');
            const apellidos = fd.get('apellidos');

            await generarCarnet(
                nombre,
                apellidos,
                fd.get('carrera'),
                fd.get('semestre'),
                fd.get('seccion'),
                fd.get('foto'),
                data.id
            );

            this.reset();
            fotoPreview.style.display = 'none';
            fotoInput.value = '';
            btnCambiar.style.display = 'none';
            btnCapturar.style.display = 'inline-flex';
            iniciarCamara();
        } else {
            showAlert('❌ ' + (data.message || 'Error al guardar.'), 'error');
        }
    } catch(err) {
        showAlert('❌ Error de conexión o formato: ' + err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Guardar Registro y Enviar Correo';
    }
});

function showAlert(msg, tipo) {
    const el = document.getElementById('alertMsg');
    el.textContent = msg;
    el.className = 'alert ' + tipo;
    el.scrollIntoView({behavior:'smooth'});
    setTimeout(() => el.className = 'alert', 6000);
}

// Inicialización de la app
iniciarCamara();
</script>
</body>
</html>