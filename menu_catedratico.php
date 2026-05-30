<?php
session_start();

require_once 'config.php';

include 'mascota.php';

// =====================================================
// VALIDAR CATEDRÁTICO
// =====================================================

requireSession(3);

$usuario = $_SESSION['usuario'];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Catedrático - UMG</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>

        :root{
            --primary:#4a3f8c;
            --accent:#6c63ff;
            --gray:#6b7280;
            --light:#f8fafc;
            --shadow:0 4px 20px rgba(0,0,0,0.08);
        }

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{
            background:#f0f0ff;
            font-family:'Segoe UI',sans-serif;
            color:#1f2937;
        }

        nav{
            background:var(--primary);
            padding:0 2rem;
            display:flex;
            justify-content:space-between;
            align-items:center;
            height:64px;
            position:sticky;
            top:0;
            z-index:100;
            box-shadow:0 2px 10px rgba(0,0,0,0.3);
        }

        .nav-brand{
            display:flex;
            align-items:center;
            gap:0.75rem;
            color:white;
            text-decoration:none;
        }

        .nav-brand img{
            height:38px;
            border-radius:50%;
        }

        .nav-brand span{
            font-weight:700;
            font-size:1rem;
        }

        .nav-user{
            display:flex;
            align-items:center;
            gap:1rem;
        }

        .nav-user .user-info{
            color:rgba(255,255,255,0.85);
            font-size:0.875rem;
            text-align:right;
        }

        .nav-user .user-info strong{
            display:block;
            color:white;
        }

        .avatar{
            width:38px;
            height:38px;
            border-radius:50%;
            background:var(--accent);
            color:white;
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:700;
            font-size:1rem;
        }

        .btn-logout{
            background:rgba(255,255,255,0.1);
            border:1px solid rgba(255,255,255,0.2);
            color:white;
            padding:0.4rem 0.9rem;
            border-radius:8px;
            text-decoration:none;
            font-size:0.875rem;
            cursor:pointer;
            transition:background 0.2s;
        }

        .btn-logout:hover{
            background:rgba(220,38,38,0.7);
        }

        .container{
            max-width:1200px;
            margin:0 auto;
            padding:2rem;
        }

        .page-title{
            font-size:1.75rem;
            font-weight:700;
            color:var(--primary);
            margin-bottom:0.4rem;
        }

        .page-subtitle{
            color:var(--gray);
            font-size:0.95rem;
            margin-bottom:2rem;
        }

        .cards-grid{
            display:grid;
            grid-template-columns:repeat(auto-fill,minmax(300px,1fr));
            gap:1.5rem;
        }

        .card{
            background:white;
            border-radius:14px;
            padding:1.75rem;
            box-shadow:var(--shadow);
            border-top:4px solid var(--accent);
            transition:transform 0.25s, box-shadow 0.25s;
            display:flex;
            flex-direction:column;
        }

        .card:hover{
            transform:translateY(-5px);
            box-shadow:0 12px 30px rgba(0,0,0,0.12);
        }

        .card-icon{
            width:52px;
            height:52px;
            border-radius:12px;
            background:linear-gradient(135deg,var(--primary),var(--accent));
            color:white;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:1.4rem;
            margin-bottom:1.2rem;
        }

        .card h3{
            font-size:1.1rem;
            font-weight:700;
            margin-bottom:0.5rem;
            color:#1f2937;
        }

        .card p{
            color:var(--gray);
            font-size:0.875rem;
            line-height:1.6;
            flex:1;
        }

        .card-link{
            display:inline-flex;
            align-items:center;
            gap:0.4rem;
            background:var(--accent);
            color:white;
            padding:0.65rem 1.25rem;
            border-radius:8px;
            text-decoration:none;
            font-weight:600;
            font-size:0.875rem;
            margin-top:1.2rem;
            transition:background 0.2s;
            width:fit-content;
        }

        .card-link:hover{
            background:var(--primary);
        }

        .card.green{
            border-top-color:#16a34a;
        }

        .card.green .card-icon{
            background:linear-gradient(135deg,#166534,#16a34a);
        }

        .card.green .card-link{
            background:#16a34a;
        }

        .card.green .card-link:hover{
            background:#166534;
        }

        .card.purple{
            border-top-color:#7c3aed;
        }

        .card.purple .card-icon{
            background:linear-gradient(135deg,#5b21b6,#7c3aed);
        }

        .card.purple .card-link{
            background:#7c3aed;
        }

        .card.purple .card-link:hover{
            background:#5b21b6;
        }

        .card.orange{
            border-top-color:#ea580c;
        }

        .card.orange .card-icon{
            background:linear-gradient(135deg,#9a3412,#ea580c);
        }

        .card.orange .card-link{
            background:#ea580c;
        }

        .card.orange .card-link:hover{
            background:#9a3412;
        }

        .card.teal{
            border-top-color:#0d9488;
        }

        .card.teal .card-icon{
            background:linear-gradient(135deg,#115e59,#0d9488);
        }

        .card.teal .card-link{
            background:#0d9488;
        }

        .card.teal .card-link:hover{
            background:#115e59;
        }

        @media(max-width:768px){

            .cards-grid{
                grid-template-columns:1fr;
            }

            .container{
                padding:1.25rem;
            }
        }

    </style>
</head>

<body>

<nav>

    <a class="nav-brand" href="menu_catedratico.php">

        <img src="logo_umg.png" alt="UMG">

        <span>
            UMG · Sistema de Asistencia
        </span>

    </a>

    <div class="nav-user">

        <div class="user-info">

            <strong>
                <?= e($usuario) ?>
            </strong>

            Catedrático

        </div>

        <div class="avatar">

            <?= strtoupper(substr($usuario,0,1)) ?>

        </div>

        <a class="btn-logout" href="logout.php">

            <i class="fas fa-sign-out-alt"></i>

            Salir

        </a>

    </div>

</nav>

<div class="container">

    <h1 class="page-title">
        Panel del Catedrático
    </h1>

    <p class="page-subtitle">
        Gestiona asistencia, reportes y reconocimiento facial de tus cursos
    </p>

    <div class="cards-grid">

        <!-- QR -->

        <div class="card teal">

            <div class="card-icon">
                <i class="fas fa-qrcode"></i>
            </div>

            <h3>
                Chequeo de Asistencia QR
            </h3>

            <p>
                Escanea códigos QR de estudiantes para registrar
                asistencia automática en tiempo real.
            </p>

            <a href="lector_qr.php" class="card-link">

                Acceder

                <i class="fas fa-arrow-right"></i>

            </a>

        </div>

        <!-- RECONOCIMIENTO FACIAL -->

        <div class="card purple">

            <div class="card-icon">
                <i class="fas fa-camera"></i>
            </div>

            <h3>
                Asistencia Facial
            </h3>

            <p>
                Registro automático mediante reconocimiento facial
                utilizando validación biométrica en tiempo real.
            </p>

            <a href="reconocimiento_facial.php" class="card-link">

                Acceder

                <i class="fas fa-arrow-right"></i>

            </a>

        </div>

        <!-- CONFIRMAR ASISTENCIA -->

        <div class="card green">

            <div class="card-icon">
                <i class="fas fa-clipboard-check"></i>
            </div>

            <h3>
                Confirmación de Asistencia
            </h3>

            <p>
                Confirma oficialmente las asistencias detectadas
                mediante QR y reconocimiento facial.
            </p>

            <a href="confirmacion_asistencia.php" class="card-link">

                Acceder

                <i class="fas fa-arrow-right"></i>

            </a>

        </div>

        <!-- REPORTES -->

        <div class="card teal">

            <div class="card-icon">
                <i class="fas fa-chart-bar"></i>
            </div>

            <h3>
                Reportes de Asistencia
            </h3>

            <p>
                Consulta historial de asistencias, faltas,
                estudiantes presentes y genera reportes PDF.
            </p>

            <a href="reportes_asistencia.php" class="card-link">

                Ver Reportes

                <i class="fas fa-arrow-right"></i>

            </a>

        </div>

        <!-- CURSOS -->

        <div class="card orange">

            <div class="card-icon">
                <i class="fas fa-book"></i>
            </div>

            <h3>
                Mis Cursos
            </h3>

            <p>
                Visualiza los cursos asignados, horarios,
                salones y estudiantes inscritos.
            </p>

            <a href="mis_cursos.php" class="card-link">

                Abrir

                <i class="fas fa-arrow-right"></i>

            </a>

        </div>

        <!-- DASHBOARD -->

        <div class="card">

            <div class="card-icon">
                <i class="fas fa-chart-line"></i>
            </div>

            <h3>
                Dashboard Académico
            </h3>

            <p>
                Estadísticas de asistencia, porcentajes,
                gráficas por curso y monitoreo general.
            </p>

            <a href="dashboard.php" class="card-link">

                Abrir Dashboard

                <i class="fas fa-arrow-right"></i>

            </a>

        </div>

        <!-- ESTUDIANTES -->

        <div class="card green">

            <div class="card-icon">
                <i class="fas fa-users"></i>
            </div>

            <h3>
                Estudiantes Asignados
            </h3>

            <p>
                Consulta información de estudiantes,
                fotografías y estado de asistencia.
            </p>

            <a href="gestionar_estudiantes.php" class="card-link">

                Ver Estudiantes

                <i class="fas fa-arrow-right"></i>

            </a>

        </div>

        <!-- PERFIL -->

        <div class="card purple">

            <div class="card-icon">
                <i class="fas fa-user-circle"></i>
            </div>

            <h3>
                Mi Perfil
            </h3>

            <p>
                Actualiza tu información personal,
                contraseña y fotografía del sistema.
            </p>

            <a href="perfil.php" class="card-link">

                Editar Perfil

                <i class="fas fa-arrow-right"></i>

            </a>

        </div>

    </div>

</div>

</body>
</html>