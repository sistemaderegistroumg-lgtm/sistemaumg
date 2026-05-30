<?php
session_start();
require_once 'config.php';
include 'mascota.php';
requireSession();



$rol = (int)$_SESSION['rol'];

$menu_url = $rol == 1
    ? 'menu_admin.php'
    : 'menu_catedratico.php';

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
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{
            font-family:'Montserrat',sans-serif;
            background:
                linear-gradient(rgba(10,20,35,.82), rgba(10,20,35,.92)),
                url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?q=80&w=1600&auto=format&fit=crop');
            background-size:cover;
            background-position:center;
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:20px;
            color:#fff;
        }

        .container{
            width:100%;
            max-width:560px;

            background:rgba(255,255,255,.08);
            border:1px solid rgba(255,255,255,.15);

            backdrop-filter:blur(14px);
            -webkit-backdrop-filter:blur(14px);

            border-radius:26px;
            padding:2rem;

            box-shadow:
                0 10px 40px rgba(0,0,0,.45),
                inset 0 1px 0 rgba(255,255,255,.08);

            text-align:center;
            position:relative;
            overflow:hidden;
        }

        .container::before{
            content:'';
            position:absolute;
            inset:0;
            background:linear-gradient(
                135deg,
                rgba(0,212,168,.08),
                transparent 40%,
                rgba(37,99,235,.12)
            );
            pointer-events:none;
        }

        .top-bar{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:1rem;
            gap:10px;
        }

        .btn-menu{
            display:inline-flex;
            align-items:center;
            gap:.5rem;

            padding:.7rem 1.1rem;

            background:rgba(255,255,255,.12);
            border:1px solid rgba(255,255,255,.2);

            color:#fff;
            text-decoration:none;

            border-radius:12px;
            font-size:.85rem;
            font-weight:600;

            transition:.25s;
        }

        .btn-menu:hover{
            background:#00d4a8;
            color:#111;
            transform:translateY(-2px);
            box-shadow:0 8px 20px rgba(0,212,168,.35);
        }

        .user-box{
            font-size:.8rem;
            color:rgba(255,255,255,.75);
        }

        .logo{
            width:95px;
            margin-bottom:.8rem;
            filter:drop-shadow(0 0 15px rgba(255,255,255,.25));
        }

        h1{
            font-size:1.8rem;
            color:#00e5b0;
            margin-bottom:.35rem;
            font-weight:700;
        }

        .subtitle{
            color:#d1d5db;
            font-size:.88rem;
            margin-bottom:1.5rem;
        }

        .selector-row{
            margin-bottom:1rem;
        }

        select{
            width:100%;

            padding:.85rem 1rem;

            background:rgba(255,255,255,.1);
            border:1px solid rgba(255,255,255,.2);

            color:white;
            border-radius:14px;

            font-size:.9rem;
            outline:none;
        }

        select option{
            background:#1a2e4a;
        }

        .info-box{
            background:rgba(255,255,255,.06);
            border:1px solid rgba(255,255,255,.08);

            border-radius:14px;

            padding:1rem;
            margin-bottom:1.3rem;

            font-size:.85rem;
            color:#e5e7eb;
        }

        #reader{
            width:100%;
            max-width:340px;

            margin:0 auto 1rem;

            border-radius:20px;
            overflow:hidden;

            border:3px solid rgba(0,212,168,.4);

            box-shadow:
                0 0 25px rgba(0,212,168,.35),
                0 0 60px rgba(37,99,235,.2);
        }

        #output{
            margin-bottom:1rem;

            padding:1rem;

            border-radius:14px;

            font-size:.95rem;
            font-weight:600;

            background:rgba(0,0,0,.28);

            color:#00ffbf;

            min-height:56px;

            display:flex;
            align-items:center;
            justify-content:center;

            border:1px solid rgba(255,255,255,.06);
        }

        #output.error{
            color:#ff6b6b;
        }

        .student-info{
            display:none;

            background:rgba(255,255,255,.08);
            border:1px solid rgba(255,255,255,.08);

            border-radius:16px;

            padding:1rem;

            margin-bottom:1rem;
        }

        .student-name{
            font-size:1.15rem;
            font-weight:700;
            color:#00e5b0;
            margin-bottom:.35rem;
        }

        .btns{
            display:flex;
            justify-content:center;
            flex-wrap:wrap;
            gap:.8rem;
        }

        .btn{
            border:none;
            border-radius:14px;

            padding:.85rem 1.3rem;

            font-size:.88rem;
            font-weight:700;

            cursor:pointer;

            transition:.25s;

            display:inline-flex;
            align-items:center;
            gap:.45rem;
        }

        .btn-primary{
            background:#00d4a8;
            color:#111;
        }

        .btn-primary:hover{
            background:#00f5c3;
            transform:translateY(-2px);
            box-shadow:0 10px 20px rgba(0,212,168,.35);
        }

        .btn-secondary{
            background:rgba(255,255,255,.12);
            border:1px solid rgba(255,255,255,.2);
            color:#fff;
        }

        .btn-secondary:hover{
            background:rgba(255,255,255,.22);
            transform:translateY(-2px);
        }

        @media(max-width:600px){

            .container{
                padding:1.3rem;
            }

            h1{
                font-size:1.45rem;
            }

            .top-bar{
                flex-direction:column;
            }

            .btn-menu{
                width:100%;
                justify-content:center;
            }
        }
    </style>
</head>
<body>

<div class="container">

    <!-- TOP -->
    <div class="top-bar">

    <a class="btn btn-secondary" href="<?= $menu_url ?>">
            ← Regresar al Menú
        </a>

        <div class="user-box">
            Usuario ID: <?= $usuario_id ?>
        </div>

    </div>

    <!-- LOGO -->
    <img class="logo" src="logo_umg.png" alt="UMG">

    <h1>Asistencia por QR</h1>

    <p class="subtitle">
        Universidad Mariano Gálvez · Facultad de Ingeniería
    </p>

    <!-- CURSOS -->
    <div class="selector-row">
        <select id="selectCurso">
            <option value="">Cargando cursos...</option>
        </select>
    </div>

    <!-- FECHA -->
    <div class="info-box">
        📅 <span id="fechaHoy"></span>
        <br><br>
        Escanea el carnet del estudiante para registrar asistencia.
    </div>

    <!-- QR -->
    <div id="reader"></div>

    <!-- OUTPUT -->
    <div id="output">
        Esperando escaneo...
    </div>

    <!-- INFO -->
    <div class="student-info" id="studentInfo">
        <div class="student-name" id="studentName"></div>
        <div id="studentMsg"></div>
    </div>

    <!-- BOTONES -->
    <div class="btns">

       

        <button class="btn btn-primary" onclick="reiniciarScanner()">
            ↺ Nuevo Escaneo
        </button>

    </div>

</div>

<script>
const catedratico_id = <?= (int)$usuario_id ?>;

let cursoSeleccionado = 0;
let html5QrCode = null;
let escaneando = true;

// FECHA
const hoy = new Date();

document.getElementById('fechaHoy').textContent =
    hoy.toLocaleDateString('es-GT',{
        weekday:'long',
        year:'numeric',
        month:'long',
        day:'numeric'
    });

// CARGAR CURSOS
async function cargarCursos() {

    try {

        const res = await fetch(
            `asistencia_api.php?action=get_cursos&catedratico_id=${catedratico_id}`
        );

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

    } catch(e) {

        console.error(e);

    }
}

cargarCursos();

document.getElementById('selectCurso').addEventListener('change', function(){

    cursoSeleccionado = parseInt(this.value) || 0;

});

// INICIAR QR
function iniciarScanner(){

    html5QrCode = new Html5Qrcode("reader");

    html5QrCode.start(
        { facingMode: "environment" },
        {
            fps: 12,
            qrbox: {
                width:240,
                height:240
            }
        },
        onScanSuccess
    ).catch(err => {

        setOutput('❌ Error al iniciar cámara: ' + err, true);

    });

}

// ESCANEAR
function onScanSuccess(decodedText){

    if (!escaneando) return;

    escaneando = false;

    if (!cursoSeleccionado) {

        setOutput('⚠️ Selecciona un curso primero.', true);

        setTimeout(() => {
            escaneando = true;
        }, 2000);

        return;
    }

    setOutput('✅ QR detectado, registrando...');

    const match = decodedText.match(/ID:\s*(\d+)/i);

    if (!match) {

        setOutput(
            '❌ QR inválido. No se encontró el ID.',
            true
        );

        setTimeout(() => {
            escaneando = true;
        }, 3000);

        return;
    }

    const estudianteId = match[1];

    const fecha = new Date()
        .toISOString()
        .split('T')[0];

    const fd = new FormData();

    fd.append('usuario_id', estudianteId);
    fd.append('curso_id', cursoSeleccionado);
    fd.append('catedratico_id', catedratico_id);
    fd.append('fecha', fecha);

    fetch(
        'asistencia_api.php?action=registrar_qr',
        {
            method:'POST',
            body:fd
        }
    )
    .then(r => r.json())
    .then(data => {

        if (data.success) {

            setOutput(data.success);

            document.getElementById('studentName').textContent =
                data.nombre || '';

            document.getElementById('studentMsg').textContent =
                'Asistencia registrada correctamente';

            document.getElementById('studentInfo').style.display = 'block';

            localStorage.setItem('qr_update', Date.now());

        } else {

            setOutput(
                data.error || '❌ Error al registrar.',
                true
            );

        }

        setTimeout(() => {

            escaneando = true;

            document.getElementById('studentInfo').style.display = 'none';

        }, 4000);

    })
    .catch(err => {

        setOutput(
            '❌ Error de red: ' + err.message,
            true
        );

        setTimeout(() => {

            escaneando = true;

        }, 3000);

    });

}

function setOutput(msg, isError = false){

    const el = document.getElementById('output');

    el.textContent = msg;

    el.className = isError ? 'error' : '';

}

function reiniciarScanner(){

    escaneando = true;

    setOutput('Esperando escaneo...');

    document.getElementById('studentInfo').style.display = 'none';

}

iniciarScanner();
</script>

</body>
</html>