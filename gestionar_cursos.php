<?php
session_start();
require_once 'config.php';
include 'mascota.php';
requireSession('Administrador');

$usuario = $_SESSION['usuario'];
$pdo     = getDB();
$mensaje = '';
$tipo    = '';

// Eliminar curso
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM cursos WHERE id = ?")->execute([$id]);
    $mensaje = 'Curso eliminado.';
    $tipo    = 'success';
}

// Crear curso
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear'])) {
    $nombre   = trim($_POST['nombre'] ?? '');
    $salon    = trim($_POST['salon'] ?? '');
    $inicio   = $_POST['horario_inicio'] ?? '';
    $fin      = $_POST['horario_fin'] ?? '';
    $cat_id   = (int)($_POST['catedratico_id'] ?? 0);

    if (empty($nombre) || empty($salon) || $cat_id <= 0) {
        $mensaje = 'Nombre, salón y catedrático son obligatorios.';
        $tipo    = 'error';
    } else {
        $pdo->prepare("
            INSERT INTO cursos (nombre, salon, horario_inicio, horario_fin, catedratico_id)
            VALUES (?,?,?,?,?)
        ")->execute([$nombre, $salon, $inicio, $fin, $cat_id]);

        $mensaje = 'Curso creado correctamente.';
        $tipo    = 'success';
    }
}

/*
    🔥 IMPORTANTE: en Supabase tu tabla es:
    roles_usuarios:
    id (NO id_roles_usuario)
*/
$catedraticos = $pdo->query("
    SELECT id, nombre_usuario
    FROM roles_usuarios
    WHERE rol = 'Catedrático'
    ORDER BY nombre_usuario
")->fetchAll(PDO::FETCH_ASSOC);

// Cursos
$cursos = $pdo->query("
    SELECT 
        c.id,
        c.nombre,
        c.salon,
        c.horario_inicio,
        c.horario_fin,
        ru.nombre_usuario AS catedratico,
        COUNT(ce.estudiante_id) AS total_estudiantes
    FROM cursos c
    LEFT JOIN roles_usuarios ru 
        ON c.catedratico_id = ru.id
    LEFT JOIN curso_estudiante ce 
        ON c.id = ce.curso_id
    GROUP BY c.id, ru.nombre_usuario
    ORDER BY c.nombre
")->fetchAll(PDO::FETCH_ASSOC);


?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestión de Cursos - UMG</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* 🔥 NO TOQUÉ TU ESTÉTICA */
:root { --primary:#1a2e4a; --accent:#2563eb; --danger:#dc2626; --gray:#6b7280; }
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#f0f4f8; font-family:'Segoe UI',sans-serif; color:#1f2937; }
nav { background:var(--primary); padding:0 2rem; display:flex; justify-content:space-between; align-items:center; height:64px; box-shadow:0 2px 10px rgba(0,0,0,.3); }
.nav-left { display:flex; align-items:center; gap:1rem; }
.nav-title { color:white; font-weight:700; }
.btn-back { background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.2); color:white; padding:.4rem .9rem; border-radius:8px; text-decoration:none; font-size:.875rem; }
.container { max-width:1200px; margin:0 auto; padding:2rem; }
.alert { padding:.85rem 1rem; border-radius:10px; margin-bottom:1.5rem; font-size:.9rem; }
.alert.success { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }
.alert.error   { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
.layout { display:grid; grid-template-columns:360px 1fr; gap:1.5rem; }
.card { background:white; border-radius:14px; padding:1.75rem; box-shadow:0 4px 20px rgba(0,0,0,.07); }
.card h2 { font-size:1.1rem; font-weight:700; margin-bottom:1.25rem; padding-bottom:.75rem; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; gap:.5rem; }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; }
.form-group { margin-bottom:1rem; }
.form-group label { display:block; font-size:.875rem; font-weight:600; color:#374151; margin-bottom:.35rem; }
.form-group input, .form-group select { width:100%; padding:.7rem 1rem; border:2px solid #e5e7eb; border-radius:9px; font-size:.9rem; }
.btn-submit { width:100%; padding:.8rem; background:var(--accent); color:white; border:none; border-radius:9px; }
.courses-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:1rem; }
.course-card { background:#f8fafc; border-radius:12px; padding:1.25rem; border:1px solid #e5e7eb; position:relative; }
.course-name { font-weight:700; }
.course-meta { font-size:.82rem; color:var(--gray); }
.badge-cat { background:#eff6ff; color:#1d4ed8; padding:.2rem .6rem; border-radius:20px; font-size:.75rem; }
.badge-count { background:#f0fdf4; color:#166534; padding:.2rem .6rem; border-radius:20px; font-size:.75rem; }
.btn-del { position:absolute; top:.75rem; right:.75rem; background:#fef2f2; color:var(--danger); border:none; padding:.35rem .6rem; border-radius:7px; }
</style>
</head>

<body>

<nav>
    <div class="nav-left">
        <a class="btn-back" href="menu_admin.php">← Menú</a>
        <span class="nav-title">Gestión de Cursos</span>
    </div>
    <span style="color:rgba(255,255,255,.7);font-size:.875rem"><?= e($usuario) ?></span>
</nav>

<div class="container">

<?php if ($mensaje): ?>
    <div class="alert <?= $tipo ?>"><?= e($mensaje) ?></div>
<?php endif; ?>

<div class="layout">

<!-- FORM -->
<div class="card">
<h2>Nuevo Curso</h2>

<form method="POST">
    <div class="form-group">
        <label>Nombre</label>
        <input type="text" name="nombre" required>
    </div>

    <div class="form-group">
        <label>Salón</label>
        <input type="text" name="salon" required>
    </div>

    <div class="form-row">
        <input type="time" name="horario_inicio">
        <input type="time" name="horario_fin">
    </div>

    <div class="form-group">
        <label>Catedrático</label>
        <select name="catedratico_id" required>
            <option value="">Seleccionar</option>
            <?php foreach ($catedraticos as $c): ?>
                <option value="<?= $c['id'] ?>">
                    <?= e($c['nombre_usuario']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <button class="btn-submit" name="crear">Crear</button>
</form>
</div>

<!-- LISTA -->
<div class="card">
<h2>Cursos (<?= count($cursos) ?>)</h2>

<?php if (!$cursos): ?>
    <p>No hay cursos</p>
<?php endif; ?>

<div class="courses-grid">
<?php foreach ($cursos as $c): ?>
<div class="course-card">

<button class="btn-del"
onclick="if(confirm('¿Eliminar?')) location.href='?delete=<?= $c['id'] ?>'">🗑</button>

<div class="course-name"><?= e($c['nombre']) ?></div>
<div class="course-meta">📍 <?= e($c['salon']) ?></div>

<?php if ($c['horario_inicio']): ?>
<div class="course-meta">⏰ <?= e($c['horario_inicio']) ?> - <?= e($c['horario_fin']) ?></div>
<?php endif; ?>

<div class="badge-cat">👨‍🏫 <?= e($c['catedratico'] ?? 'Sin asignar') ?></div>
<div class="badge-count">👥 <?= $c['total_estudiantes'] ?></div>

</div>
<?php endforeach; ?>
</div>

</div>

</div>
</div>

</body>
</html>