<?php
session_start();
require_once 'config.php';
include 'mascota.php';

requireSession(1);

$usuario = $_SESSION['usuario'];
$pdo     = getDB();

$mensaje = '';
$tipo    = '';

// =========================
// ELIMINAR CURSO
// =========================
if (isset($_GET['delete'])) {

    $id = (int)$_GET['delete'];

    $pdo->prepare("
        DELETE FROM cursos
        WHERE id = ?
    ")->execute([$id]);

    $mensaje = 'Curso eliminado correctamente.';
    $tipo    = 'success';
}

// =========================
// CREAR CURSO
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear'])) {

    $nombre = trim($_POST['nombre'] ?? '');
    $salon  = trim($_POST['salon'] ?? '');
    $inicio = $_POST['horario_inicio'] ?? '';
    $fin    = $_POST['horario_fin'] ?? '';
    $cat_id = (int)($_POST['catedratico_id'] ?? 0);

    if (
        empty($nombre) ||
        empty($salon) ||
        $cat_id <= 0
    ) {

        $mensaje = 'Todos los campos obligatorios deben completarse.';
        $tipo    = 'error';

    } else {

        $stmt = $pdo->prepare("
            INSERT INTO cursos
            (
                nombre,
                salon,
                horario_inicio,
                horario_fin,
                catedratico_id
            )
            VALUES (?,?,?,?,?)
        ");

        $stmt->execute([
            $nombre,
            $salon,
            $inicio,
            $fin,
            $cat_id
        ]);

        $mensaje = 'Curso creado correctamente.';
        $tipo    = 'success';
    }
}

// =========================
// CATEDRÁTICOS
// =========================
$catedraticos = $pdo->query("
    SELECT
        id,
        nombre,
        apellidos
    FROM usuarios
    WHERE rol_id = 3
    ORDER BY nombre
")->fetchAll(PDO::FETCH_ASSOC);

// =========================
// CURSOS
// =========================
$cursos = $pdo->query("
    SELECT
        c.id,
        c.nombre,
        c.salon,
        c.horario_inicio,
        c.horario_fin,

        CONCAT(u.nombre, ' ', u.apellidos) AS catedratico,

        COUNT(ce.estudiante_id) AS total_estudiantes

    FROM cursos c

    LEFT JOIN usuarios u
        ON c.catedratico_id = u.id

    LEFT JOIN curso_estudiante ce
        ON c.id = ce.curso_id

    GROUP BY
        c.id,
        u.nombre,
        u.apellidos

    ORDER BY c.nombre
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Gestión de Cursos - UMG</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>

:root{
    --primary:#1a2e4a;
    --accent:#2563eb;
    --danger:#dc2626;
    --success:#16a34a;
    --gray:#6b7280;
    --light:#f0f4f8;
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    background:var(--light);
    font-family:'Segoe UI',sans-serif;
    color:#1f2937;
}

/* ================= NAV ================= */

nav{
    background:linear-gradient(135deg,#1a2e4a,#2563eb);
    padding:0 2rem;
    height:70px;

    display:flex;
    justify-content:space-between;
    align-items:center;

    box-shadow:0 4px 20px rgba(0,0,0,.25);
}

.nav-left{
    display:flex;
    align-items:center;
    gap:1rem;
}

.logo{
    width:48px;
    height:48px;
    border-radius:50%;
    background:white;
    padding:4px;
    object-fit:cover;
}

.nav-title{
    color:white;
    font-size:1.1rem;
    font-weight:700;
}

.btn-back{
    background:rgba(255,255,255,.15);
    border:1px solid rgba(255,255,255,.25);
    color:white;

    padding:.45rem .9rem;
    border-radius:10px;

    text-decoration:none;
    font-size:.85rem;

    transition:.2s;
}

.btn-back:hover{
    background:rgba(255,255,255,.25);
}

.nav-user{
    color:rgba(255,255,255,.8);
    font-size:.9rem;
}

/* ================= CONTAINER ================= */

.container{
    max-width:1300px;
    margin:auto;
    padding:2rem;
}

/* ================= ALERT ================= */

.alert{
    padding:1rem;
    border-radius:12px;
    margin-bottom:1.5rem;
    font-size:.92rem;
    font-weight:600;
}

.alert.success{
    background:#f0fdf4;
    border:1px solid #bbf7d0;
    color:#166534;
}

.alert.error{
    background:#fef2f2;
    border:1px solid #fecaca;
    color:#991b1b;
}

/* ================= GRID ================= */

.layout{
    display:grid;
    grid-template-columns:380px 1fr;
    gap:1.5rem;
}

.card{
    background:white;
    border-radius:18px;
    padding:1.8rem;

    box-shadow:0 8px 30px rgba(0,0,0,.08);
}

.card h2{
    font-size:1.15rem;
    margin-bottom:1.5rem;

    display:flex;
    align-items:center;
    gap:.6rem;

    color:var(--primary);
}

/* ================= FORM ================= */

.form-group{
    margin-bottom:1rem;
}

.form-group label{
    display:block;
    margin-bottom:.45rem;
    font-size:.88rem;
    font-weight:600;
}

.form-group input,
.form-group select{

    width:100%;
    padding:.8rem 1rem;

    border:2px solid #e5e7eb;
    border-radius:12px;

    font-size:.92rem;

    transition:.2s;
}

.form-group input:focus,
.form-group select:focus{
    outline:none;
    border-color:var(--accent);
}

.form-row{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:.8rem;
    margin-bottom:1rem;
}

.btn-submit{
    width:100%;

    border:none;
    border-radius:12px;

    background:linear-gradient(135deg,#2563eb,#1a2e4a);
    color:white;

    padding:.95rem;

    font-size:.95rem;
    font-weight:700;

    cursor:pointer;
    transition:.2s;
}

.btn-submit:hover{
    transform:translateY(-1px);
}

/* ================= CURSOS ================= */

.courses-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(280px,1fr));
    gap:1rem;
}

.course-card{

    position:relative;

    background:#f8fafc;
    border:1px solid #e5e7eb;

    border-radius:16px;
    padding:1.3rem;

    transition:.2s;
}

.course-card:hover{
    transform:translateY(-3px);
    box-shadow:0 10px 20px rgba(0,0,0,.08);
}

.course-name{
    font-size:1rem;
    font-weight:700;
    color:var(--primary);
    margin-bottom:.5rem;
}

.course-meta{
    font-size:.85rem;
    color:var(--gray);
    margin-bottom:.35rem;
}

.badges{
    display:flex;
    gap:.5rem;
    flex-wrap:wrap;
    margin-top:.8rem;
}

.badge{
    padding:.35rem .7rem;
    border-radius:999px;
    font-size:.75rem;
    font-weight:700;
}

.badge-cat{
    background:#dbeafe;
    color:#1d4ed8;
}

.badge-count{
    background:#dcfce7;
    color:#166534;
}

.btn-del{
    position:absolute;
    top:.8rem;
    right:.8rem;

    background:#fef2f2;
    color:var(--danger);

    border:none;
    border-radius:10px;

    width:35px;
    height:35px;

    cursor:pointer;
    transition:.2s;
}

.btn-del:hover{
    background:#fee2e2;
}

/* ================= RESPONSIVE ================= */

@media(max-width:900px){

    .layout{
        grid-template-columns:1fr;
    }

    nav{
        padding:0 1rem;
    }

    .container{
        padding:1rem;
    }
}

</style>
</head>

<body>

<nav>

    <div class="nav-left">

        <img src="logo_umg.png" class="logo">

        <a class="btn-back" href="menu_admin.php">
            <i class="fas fa-arrow-left"></i>
            Menú
        </a>

        <span class="nav-title">
            Gestión de Cursos
        </span>

    </div>

    <div class="nav-user">
        <i class="fas fa-user-circle"></i>
        <?= e($usuario) ?>
    </div>

</nav>

<div class="container">

<?php if($mensaje): ?>
<div class="alert <?= $tipo ?>">
    <?= e($mensaje) ?>
</div>
<?php endif; ?>

<div class="layout">

<!-- ================= FORM ================= -->

<div class="card">

<h2>
    <i class="fas fa-plus-circle"></i>
    Nuevo Curso
</h2>

<form method="POST">

    <div class="form-group">
        <label>Nombre del Curso</label>
        <input type="text" name="nombre" required>
    </div>

    <div class="form-group">
        <label>Salón</label>
        <input type="text" name="salon" required>
    </div>

    <div class="form-row">

        <div>
            <label>Hora Inicio</label>
            <input type="time" name="horario_inicio">
        </div>

        <div>
            <label>Hora Fin</label>
            <input type="time" name="horario_fin">
        </div>

    </div>

    <div class="form-group">

        <label>Catedrático</label>

        <select name="catedratico_id" required>

            <option value="">
                Seleccionar
            </option>

            <?php foreach($catedraticos as $c): ?>

           <option value="<?= $c['id'] ?>">
    <?= e($c['nombre'] . ' ' . $c['apellidos']) ?>
</option>

            <?php endforeach; ?>

        </select>

    </div>

    <button class="btn-submit" name="crear">
        <i class="fas fa-save"></i>
        Crear Curso
    </button>

</form>

</div>

<!-- ================= LISTA ================= -->

<div class="card">

<h2>
    <i class="fas fa-book"></i>
    Cursos Registrados (<?= count($cursos) ?>)
</h2>

<?php if(!$cursos): ?>

<p>No hay cursos registrados.</p>

<?php else: ?>

<div class="courses-grid">

<?php foreach($cursos as $c): ?>

<div class="course-card">

<button
class="btn-del"
onclick="if(confirm('¿Eliminar curso?')) location.href='?delete=<?= $c['id'] ?>'">

<i class="fas fa-trash"></i>

</button>

<div class="course-name">
    <?= e($c['nombre']) ?>
</div>

<div class="course-meta">
    <i class="fas fa-location-dot"></i>
    <?= e($c['salon']) ?>
</div>

<?php if($c['horario_inicio']): ?>

<div class="course-meta">
    <i class="fas fa-clock"></i>
    <?= e($c['horario_inicio']) ?>
    -
    <?= e($c['horario_fin']) ?>
</div>

<?php endif; ?>

<div class="badges">

    <div class="badge badge-cat">
        👨‍🏫 <?= e($c['catedratico'] ?? 'Sin asignar') ?>
    </div>

    <div class="badge badge-count">
        👥 <?= $c['total_estudiantes'] ?> estudiantes
    </div>

</div>

</div>

<?php endforeach; ?>

</div>

<?php endif; ?>

</div>

</div>
</div>

</body>
</html>