<?php
session_start();
require_once 'config.php';
include 'mascota.php';
// ✅ función para evitar error (si no existe en config.php)
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

requireSession('Administrador');
$usuario = $_SESSION['usuario'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrador - UMG</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1a2e4a;
            --accent:  #2563eb;
            --danger:  #dc2626;
            --gray:    #6b7280;
            --light:   #f8fafc;
            --shadow:  0 4px 20px rgba(0,0,0,0.08);
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:#f0f4f8; font-family:'Segoe UI',sans-serif; color:#1f2937; }

        nav {
            background: var(--primary);
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 64px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        .nav-brand { display:flex; align-items:center; gap:0.75rem; color:white; text-decoration:none; }
        .nav-brand img { height:38px; border-radius:50%; }
        .nav-brand span { font-weight:700; font-size:1rem; }
        .nav-user { display:flex; align-items:center; gap:1rem; }
        .nav-user .user-info { color:rgba(255,255,255,0.85); font-size:0.875rem; text-align:right; }
        .nav-user .user-info strong { display:block; color:white; }
        .avatar {
            width:38px; height:38px; border-radius:50%;
            background:var(--accent); color:white;
            display:flex; align-items:center; justify-content:center;
            font-weight:700; font-size:1rem;
        }
        .btn-logout {
            background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2);
            color:white; padding:0.4rem 0.9rem; border-radius:8px;
            text-decoration:none; font-size:0.875rem; cursor:pointer;
            transition:background 0.2s;
        }
        .btn-logout:hover { background:rgba(220,38,38,0.7); }

        .container { max-width:1200px; margin:0 auto; padding:2rem; }
        .page-title { font-size:1.75rem; font-weight:700; color:var(--primary); margin-bottom:0.4rem; }
        .page-subtitle { color:var(--gray); font-size:0.95rem; margin-bottom:2rem; }

        .cards-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(300px,1fr)); gap:1.5rem; }

        .card {
            background:white; border-radius:14px;
            padding:1.75rem; box-shadow:var(--shadow);
            border-top:4px solid var(--accent);
            transition:transform 0.25s, box-shadow 0.25s;
            display:flex; flex-direction:column;
        }
        .card:hover { transform:translateY(-5px); box-shadow:0 12px 30px rgba(0,0,0,0.12); }

        .card-icon {
            width:52px; height:52px; border-radius:12px;
            background:linear-gradient(135deg,var(--primary),var(--accent));
            color:white; display:flex; align-items:center; justify-content:center;
            font-size:1.4rem; margin-bottom:1.2rem;
        }
        .card h3 { font-size:1.1rem; font-weight:700; margin-bottom:0.5rem; color:#1f2937; }
        .card p  { color:var(--gray); font-size:0.875rem; line-height:1.6; flex:1; }
        .card-link {
            display:inline-flex; align-items:center; gap:0.4rem;
            background:var(--accent); color:white;
            padding:0.65rem 1.25rem; border-radius:8px;
            text-decoration:none; font-weight:600; font-size:0.875rem;
            margin-top:1.2rem; transition:background 0.2s; width:fit-content;
        }
        .card-link:hover { background:var(--primary); }

        .card.green  { border-top-color:#16a34a; } .card.green  .card-icon { background:linear-gradient(135deg,#166534,#16a34a); }
        .card.purple { border-top-color:#7c3aed; } .card.purple .card-icon { background:linear-gradient(135deg,#5b21b6,#7c3aed); }
        .card.orange { border-top-color:#ea580c; } .card.orange .card-icon { background:linear-gradient(135deg,#9a3412,#ea580c); }
        .card.teal   { border-top-color:#0d9488; } .card.teal   .card-icon { background:linear-gradient(135deg,#115e59,#0d9488); }
        .card.green  .card-link  { background:#16a34a; }  .card.green  .card-link:hover  { background:#166534; }
        .card.purple .card-link  { background:#7c3aed; }  .card.purple .card-link:hover  { background:#5b21b6; }
        .card.orange .card-link  { background:#ea580c; }  .card.orange .card-link:hover  { background:#9a3412; }
        .card.teal   .card-link  { background:#0d9488; }  .card.teal   .card-link:hover  { background:#115e59; }

        @media(max-width:768px) {
            .cards-grid { grid-template-columns:1fr; }
            .container { padding:1.25rem; }
        }
    </style>
</head>
<body>
<nav>
    <a class="nav-brand" href="menu_admin.php">
        <!-- ✅ logo local -->
        <img src="logo_umg.png" alt="UMG">
        <span>UMG · Sistema de Asistencia</span>
    </a>
    <div class="nav-user">
        <div class="user-info">
            <strong><?= e($usuario) ?></strong>
            Administrador
        </div>
        <div class="avatar"><?= strtoupper(substr($usuario,0,1)) ?></div>
        <a class="btn-logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a>
    </div>
</nav>

<div class="container">
    <h1 class="page-title">Panel de Administración</h1>
    <p class="page-subtitle">Gestiona todos los módulos del sistema desde aquí</p>

    <div class="cards-grid">
        <div class="card">
            <div class="card-icon"><i class="fas fa-user-plus"></i></div>
            <h3>Registro de Estudiantes</h3>
            <p>Registra nuevos estudiantes con foto biométrica tomada desde la cámara. Genera carnet con código QR automáticamente y envía confirmación por correo.</p>
            <a href="registro_estudiante.php" class="card-link">Acceder <i class="fas fa-arrow-right"></i></a>
        </div>

        <div class="card green">
            <div class="card-icon"><i class="fas fa-users"></i></div>
            <h3>Gestión de Estudiantes</h3>
            <p>Consulta, edita y actualiza la información académica y biométrica de los estudiantes registrados en el sistema.</p>
            <a href="gestionar_estudiantes.php" class="card-link">Acceder</a>
        </div>

        <div class="card purple">
            <div class="card-icon"><i class="fas fa-user-cog"></i></div>
            <h3>Gestión de Usuarios</h3>
            <p>Crea y administra los usuarios del sistema (Administradores y Catedráticos).</p>
            <a href="gestionar_usuarios.php" class="card-link">Acceder</a>
        </div>

        <div class="card teal">
    <div class="card-icon"><i class="fas fa-shield-alt"></i></div>
    <h3>Verificador de Certificados</h3>
    <p>Valida si un carnet o certificado fue emitido por la Universidad Mariano Gálvez y si cuenta con firma digital UMG.pem.</p>
    <a href="verificar_certificado.php" class="card-link">
        Verificar <i class="fas fa-check-circle"></i>
    </a>
</div>

        <div class="card orange">
            <div class="card-icon"><i class="fas fa-book"></i></div>
            <h3>Gestión de Cursos</h3>
            <p>Crea y administra cursos, salones y horarios.</p>
            <a href="gestionar_cursos.php" class="card-link">Acceder</a>
        </div>

        <div class="card teal">
            <div class="card-icon"><i class="fas fa-qrcode"></i></div>
            <h3>Chequeo QR</h3>
            <p>Escanea códigos QR para asistencia.</p>
            <a href="lector_qr.php" class="card-link">Acceder</a>
        </div>
    </div>
</div>
</body>
</html>