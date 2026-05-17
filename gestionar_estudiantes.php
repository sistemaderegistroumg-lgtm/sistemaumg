<?php
session_start();

require_once 'config.php';
include 'mascota.php';

if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

requireSession('Administrador');

$usuario = $_SESSION['usuario'];
$pdo     = getDB();

$flash = null;

// ======================================================
// ASIGNAR ESTUDIANTE A CURSO
// ======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar'])) {

    $curso_id = (int)($_POST['curso_id'] ?? 0);
    $est_id   = (int)($_POST['estudiante_id'] ?? 0);

    try {

        // Verificar si ya existe
        $check = $pdo->prepare("
            SELECT id
            FROM curso_estudiante
            WHERE curso_id = ?
            AND estudiante_id = ?
        ");

        $check->execute([
            $curso_id,
            $est_id
        ]);

        if ($check->fetch()) {

            $flash = [
                'tipo' => 'error',
                'msg'  => 'El estudiante ya está asignado a este curso.'
            ];

        } else {

            $stmt = $pdo->prepare("
                INSERT INTO curso_estudiante
                (
                    curso_id,
                    estudiante_id
                )
                VALUES
                (
                    ?, ?
                )
            ");

            $stmt->execute([
                $curso_id,
                $est_id
            ]);

            $flash = [
                'tipo' => 'success',
                'msg'  => 'Estudiante asignado al curso correctamente.'
            ];
        }

    } catch (Throwable $e) {

        $flash = [
            'tipo' => 'error',
            'msg'  => 'Error al asignar: ' . $e->getMessage()
        ];
    }
}

// ======================================================
// QUITAR CURSO
// ======================================================
if (isset($_GET['quitar'])) {

    try {

        $pdo->prepare("
            DELETE FROM curso_estudiante
            WHERE curso_id = ?
            AND estudiante_id = ?
        ")->execute([
            (int)$_GET['curso_id'],
            (int)$_GET['quitar']
        ]);

        $flash = [
            'tipo' => 'success',
            'msg'  => 'Estudiante removido del curso.'
        ];

    } catch (Throwable $e) {

        $flash = [
            'tipo' => 'error',
            'msg'  => 'Error al remover estudiante.'
        ];
    }
}

// ======================================================
// ELIMINAR ESTUDIANTE
// ======================================================
if (isset($_GET['delete'])) {

    $id = (int)$_GET['delete'];

    try {

        $pdo->beginTransaction();

        // ==============================================
        // OBTENER FOTO
        // ==============================================
        $row = $pdo->prepare("
            SELECT foto
            FROM usuarios
            WHERE id = ?
        ");

        $row->execute([$id]);

        $r = $row->fetch();

        // ==============================================
        // ELIMINAR RELACIONES CURSOS
        // ==============================================
        $pdo->prepare("
            DELETE FROM curso_estudiante
            WHERE estudiante_id = ?
        ")->execute([$id]);

        // ==============================================
        // ELIMINAR CERTIFICADOS
        // ==============================================
        $pdo->prepare("
            DELETE FROM certificados
            WHERE usuario_id = ?
        ")->execute([$id]);

        // ==============================================
        // ELIMINAR USUARIO
        // ==============================================
        $pdo->prepare("
            DELETE FROM usuarios
            WHERE id = ?
        ")->execute([$id]);

        // ==============================================
        // ELIMINAR FOTO
        // ==============================================
        if (
            $r &&
            !empty($r['foto']) &&
            file_exists(__DIR__ . '/' . $r['foto'])
        ) {

            unlink(__DIR__ . '/' . $r['foto']);
        }

        $pdo->commit();

        $flash = [
            'tipo' => 'success',
            'msg'  => 'Estudiante eliminado correctamente.'
        ];

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $flash = [
            'tipo' => 'error',
            'msg'  => 'Error al eliminar: ' . $e->getMessage()
        ];
    }
}

// ======================================================
// BÚSQUEDA
// ======================================================
$buscar = trim($_GET['q'] ?? '');

$params = [];
$where  = '';

if ($buscar !== '') {

    $where = "
        WHERE
            u.nombre ILIKE ?
            OR u.apellidos ILIKE ?
            OR u.correo ILIKE ?
    ";

    $params = [
        "%$buscar%",
        "%$buscar%",
        "%$buscar%"
    ];
}

// ======================================================
// LISTADO
// ======================================================
$estudiantes = $pdo->prepare("
    SELECT
        u.*,
        COUNT(ce.curso_id) AS cursos_asignados
    FROM usuarios u
    LEFT JOIN curso_estudiante ce
        ON u.id = ce.estudiante_id
    $where
    GROUP BY u.id
    ORDER BY u.apellidos, u.nombre
    LIMIT 100
");

$estudiantes->execute($params);

$estudiantes = $estudiantes->fetchAll();

// ======================================================
// CURSOS
// ======================================================
$cursos = $pdo->query("
    SELECT
        id,
        nombre,
        salon
    FROM cursos
    ORDER BY nombre
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">
<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Gestión de Estudiantes - UMG</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>

:root{
    --primary:#1a2e4a;
    --accent:#2563eb;
    --danger:#dc2626;
    --success:#16a34a;
    --gray:#6b7280;
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    background:#f0f4f8;
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
    box-shadow:0 2px 10px rgba(0,0,0,.3);
    position:sticky;
    top:0;
    z-index:100;
}

.nav-left{
    display:flex;
    align-items:center;
    gap:1rem;
}

.nav-title{
    color:white;
    font-weight:700;
}

.btn-back{
    background:rgba(255,255,255,.1);
    border:1px solid rgba(255,255,255,.2);
    color:white;
    padding:.4rem .9rem;
    border-radius:8px;
    text-decoration:none;
    font-size:.875rem;
}

.container{
    max-width:1300px;
    margin:0 auto;
    padding:2rem;
}

.alert{
    padding:.85rem 1rem;
    border-radius:10px;
    margin-bottom:1rem;
    font-size:.9rem;
}

.alert.success{
    background:#f0fdf4;
    color:#166534;
    border:1px solid #bbf7d0;
}

.alert.error{
    background:#fef2f2;
    color:#991b1b;
    border:1px solid #fecaca;
}

.card{
    background:white;
    border-radius:14px;
    box-shadow:0 4px 20px rgba(0,0,0,.07);
    overflow:hidden;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    text-align:left;
    padding:.85rem 1rem;
    background:#f8fafc;
    color:var(--gray);
    font-size:.78rem;
    font-weight:700;
    text-transform:uppercase;
}

td{
    padding:.85rem 1rem;
    border-bottom:1px solid #f1f5f9;
    font-size:.875rem;
}

.student-cell{
    display:flex;
    align-items:center;
    gap:.75rem;
}

.avatar-img{
    width:40px;
    height:40px;
    border-radius:50%;
    object-fit:cover;
}

.avatar-fallback{
    width:40px;
    height:40px;
    border-radius:50%;
    background:#dbeafe;
    color:#1d4ed8;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:700;
}

.actions{
    display:flex;
    gap:.5rem;
}

.btn-sm{
    padding:.35rem .75rem;
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-size:.78rem;
    font-weight:600;
}

.btn-view{
    background:#f0fdf4;
    color:#16a34a;
}

.btn-assign{
    background:#eff6ff;
    color:#2563eb;
}

.btn-del{
    background:#fef2f2;
    color:#dc2626;
}

.empty{
    text-align:center;
    padding:3rem;
    color:var(--gray);
}

</style>

</head>

<body>

<nav>

    <div class="nav-left">

        <a class="btn-back"
        href="menu_admin.php">

            <i class="fas fa-arrow-left"></i>
            Menú

        </a>

        <span class="nav-title">
            Gestión de Estudiantes
        </span>

    </div>

    <span style="color:rgba(255,255,255,.7)">
        <?= e($usuario) ?>
    </span>

</nav>

<div class="container">

<?php if ($flash): ?>

    <div class="alert <?= e($flash['tipo']) ?>">

        <?= e($flash['msg']) ?>

    </div>

<?php endif; ?>

<div class="card">

<?php if (empty($estudiantes)): ?>

    <div class="empty">

        <i class="fas fa-users-slash"
        style="font-size:2rem;margin-bottom:.5rem"></i>

        <br>

        No hay estudiantes registrados.

    </div>

<?php else: ?>

<div style="overflow-x:auto">

<table>

<thead>

<tr>
    <th>Estudiante</th>
    <th>Carrera</th>
    <th>Cursos</th>
    <th>Acciones</th>
</tr>

</thead>

<tbody>

<?php foreach($estudiantes as $est): ?>

<?php

$fotoSrc = '';

if (
    !empty($est['foto']) &&
    file_exists(__DIR__ . '/' . $est['foto'])
) {
    $fotoSrc = $est['foto'];
}

?>

<tr>

<td>

<div class="student-cell">

<?php if ($fotoSrc): ?>

    <img class="avatar-img"
    src="<?= e($fotoSrc) ?>"
    alt="foto">

<?php else: ?>

    <div class="avatar-fallback">

        <?= strtoupper(substr($est['nombre'],0,1)) ?>

    </div>

<?php endif; ?>

<div>

<div style="font-weight:600">

<?= e($est['nombre'].' '.$est['apellidos']) ?>

</div>

<div style="font-size:.78rem;color:#6b7280">

<?= e($est['correo']) ?>

</div>

</div>

</div>

</td>

<td>

<?= e($est['carrera'] ?? '—') ?>

</td>

<td>

<?= (int)$est['cursos_asignados'] ?>

</td>

<td>

<div class="actions">

<button
class="btn-sm btn-view"
onclick="verDetalle(<?= $est['id'] ?>)">

<i class="fas fa-eye"></i>

</button>

<button
class="btn-sm btn-del"
onclick="eliminarEstudiante(
<?= $est['id'] ?>,
'<?= e($est['nombre'].' '.$est['apellidos']) ?>'
)">

<i class="fas fa-trash"></i>

</button>

</div>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<?php endif; ?>

</div>

</div>

<script>

function eliminarEstudiante(id, nombre)
{
    if (
        confirm(
            `¿Eliminar al estudiante "${nombre}"?\n\n` +
            `También se eliminarán:\n` +
            `• Certificados\n` +
            `• Cursos asignados\n` +
            `• Fotografía\n\n` +
            `Esta acción no se puede deshacer.`
        )
    ) {

        window.location.href =
            `gestionar_estudiantes.php?delete=${id}`;
    }
}

function verDetalle(id)
{
    window.location.href =
        `estudiante_detalle.php?id=${id}`;
}

</script>

</body>
</html>