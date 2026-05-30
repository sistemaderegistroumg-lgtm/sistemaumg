<?php
session_start();
require_once 'config.php';
include 'mascota.php';
requireSession(1);
$usuario = $_SESSION['usuario'];
$pdo     = getDB();

// Asignar estudiante a curso
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar'])) {
    $curso_id = (int)$_POST['curso_id'];
    $est_id   = (int)$_POST['estudiante_id'];
    try {
        $pdo->prepare("INSERT INTO curso_estudiante (curso_id, estudiante_id)
VALUES (?, ?)
ON CONFLICT DO NOTHING")->execute([$curso_id, $est_id]);
        $flash = ['tipo'=>'success','msg'=>'Estudiante asignado al curso correctamente.'];
    } catch(Exception $e) {
        $flash = ['tipo'=>'error','msg'=>'Error al asignar: '.$e->getMessage()];
    }
}

// Quitar estudiante de curso
if (isset($_GET['quitar'])) {
    $pdo->prepare("DELETE FROM curso_estudiante WHERE curso_id=? AND estudiante_id=?")->execute([(int)$_GET['curso_id'],(int)$_GET['quitar']]);
    $flash = ['tipo'=>'success','msg'=>'Estudiante removido del curso.'];
}


if (isset($_GET['delete'])) {

    $id = (int)$_GET['delete'];

    try {

        $pdo->beginTransaction();

        // 1. eliminar certificados
        $pdo->prepare("DELETE FROM certificados WHERE usuario_id = ?")
            ->execute([$id]);

        // 2. eliminar relaciones cursos
        $pdo->prepare("DELETE FROM curso_estudiante WHERE estudiante_id = ?")
            ->execute([$id]);

        // 3. eliminar foto
        $row = $pdo->prepare("SELECT foto FROM usuarios WHERE id=?");
        $row->execute([$id]);
        $r = $row->fetch();

        if ($r && $r['foto'] && file_exists(__DIR__.'/'.$r['foto'])) {
            unlink(__DIR__.'/'.$r['foto']);
        }

        // 4. eliminar usuario
        $pdo->prepare("DELETE FROM usuarios WHERE id=?")
            ->execute([$id]);

        $pdo->commit();

        $flash = ['tipo'=>'success','msg'=>'Estudiante eliminado correctamente.'];

    } catch (Exception $e) {

        $pdo->rollBack();

        $flash = ['tipo'=>'error','msg'=>$e->getMessage()];
    }
}


// Búsqueda
$buscar = trim($_GET['q'] ?? '');

$params = [];

// ======================================================
// SOLO ESTUDIANTES (ROL 2)
// ======================================================

$where = "WHERE u.rol_id = 2";

// ======================================================
// BÚSQUEDA
// ======================================================

if ($buscar !== '') {

    $where .= "
        AND (
            u.nombre LIKE ?
            OR u.apellidos LIKE ?
            OR u.correo LIKE ?
        )
    ";

    $params = [
        "%$buscar%",
        "%$buscar%",
        "%$buscar%"
    ];
}

// ======================================================
// CONSULTA
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

    ORDER BY
        u.apellidos,
        u.nombre

    LIMIT 100
");

$estudiantes->execute($params);

$estudiantes = $estudiantes->fetchAll(PDO::FETCH_ASSOC);

// Cursos para el modal de asignación
$cursos = $pdo->query("SELECT id, nombre, salon FROM cursos ORDER BY nombre")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Estudiantes - UMG</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--primary:#1a2e4a;--accent:#2563eb;--danger:#dc2626;--success:#16a34a;--gray:#6b7280;}
        *{margin:0;padding:0;box-sizing:border-box;}
        body{background:#f0f4f8;font-family:'Segoe UI',sans-serif;color:#1f2937;}
        nav{background:var(--primary);padding:0 2rem;display:flex;justify-content:space-between;align-items:center;height:64px;box-shadow:0 2px 10px rgba(0,0,0,.3);position:sticky;top:0;z-index:100;}
        .nav-left{display:flex;align-items:center;gap:1rem;}
        .nav-title{color:white;font-weight:700;}
        .btn-back{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);color:white;padding:.4rem .9rem;border-radius:8px;text-decoration:none;font-size:.875rem;}
        .container{max-width:1300px;margin:0 auto;padding:2rem;}
        .top-bar{display:flex;gap:1rem;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;}
        .search-wrap{flex:1;min-width:220px;position:relative;}
        .search-wrap i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray);}
        .search-input{width:100%;padding:.65rem 1rem .65rem 2.4rem;border:2px solid #e5e7eb;border-radius:9px;font-size:.9rem;outline:none;transition:border-color .2s;}
        .search-input:focus{border-color:var(--accent);}
        .btn-search{padding:.65rem 1.25rem;background:var(--accent);color:white;border:none;border-radius:9px;font-size:.875rem;font-weight:600;cursor:pointer;white-space:nowrap;}
        .btn-add{padding:.65rem 1.25rem;background:var(--success);color:white;border:none;border-radius:9px;font-size:.875rem;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:.4rem;white-space:nowrap;}
        .stats-bar{background:white;border-radius:12px;padding:1rem 1.5rem;margin-bottom:1.5rem;box-shadow:0 2px 8px rgba(0,0,0,.06);display:flex;gap:2rem;align-items:center;flex-wrap:wrap;}
        .stat{font-size:.875rem;color:var(--gray);}
        .stat strong{color:#1f2937;font-size:1.1rem;margin-right:.25rem;}
        .alert{padding:.85rem 1rem;border-radius:10px;margin-bottom:1rem;font-size:.9rem;}
        .alert.success{background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;}
        .alert.error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca;}
        .card{background:white;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.07);overflow:hidden;}
        table{width:100%;border-collapse:collapse;}
        th{text-align:left;padding:.85rem 1rem;background:#f8fafc;color:var(--gray);font-size:.78rem;font-weight:700;text-transform:uppercase;border-bottom:2px solid #e5e7eb;}
        td{padding:.85rem 1rem;border-bottom:1px solid #f1f5f9;font-size:.875rem;vertical-align:middle;}
        tr:hover td{background:#f8fafc;}
        .student-cell{display:flex;align-items:center;gap:.75rem;}
        .avatar-img{width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid #e5e7eb;flex-shrink:0;}
        .avatar-fallback{width:40px;height:40px;border-radius:50%;background:#dbeafe;color:#1d4ed8;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.875rem;flex-shrink:0;}
        .student-name{font-weight:600;color:#1f2937;}
        .student-email{font-size:.78rem;color:var(--gray);}
        .badge{display:inline-block;padding:.2rem .6rem;border-radius:20px;font-size:.72rem;font-weight:600;}
        .badge-blue{background:#eff6ff;color:#1d4ed8;}
        .badge-green{background:#f0fdf4;color:#166534;}
        .badge-gray{background:#f9fafb;color:#6b7280;}
        .actions{display:flex;gap:.5rem;}
        .btn-sm{padding:.3rem .7rem;border-radius:7px;border:none;cursor:pointer;font-size:.78rem;font-weight:600;display:inline-flex;align-items:center;gap:.3rem;transition:.2s;}
        .btn-assign{background:#eff6ff;color:var(--accent);}  .btn-assign:hover{background:#dbeafe;}
        .btn-view{background:#f0fdf4;color:var(--success);}   .btn-view:hover{background:#dcfce7;}
        .btn-del{background:#fef2f2;color:var(--danger);}     .btn-del:hover{background:#fee2e2;}
        .empty{text-align:center;padding:3rem;color:var(--gray);}
        /* Modal */
        .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;display:none;align-items:center;justify-content:center;}
        .modal-overlay.open{display:flex;}
        .modal{background:white;border-radius:16px;padding:2rem;width:90%;max-width:480px;position:relative;}
        .modal h3{font-size:1.1rem;font-weight:700;margin-bottom:1.25rem;padding-bottom:.75rem;border-bottom:1px solid #e5e7eb;}
        .modal-close{position:absolute;top:1rem;right:1rem;background:none;border:none;font-size:1.25rem;cursor:pointer;color:var(--gray);}
        .form-group{margin-bottom:1rem;}
        .form-group label{display:block;font-size:.875rem;font-weight:600;margin-bottom:.35rem;color:#374151;}
        .form-group select{width:100%;padding:.7rem 1rem;border:2px solid #e5e7eb;border-radius:9px;font-size:.9rem;outline:none;}
        .form-group select:focus{border-color:var(--accent);}
        .btn-modal-submit{width:100%;padding:.8rem;background:var(--accent);color:white;border:none;border-radius:9px;font-weight:600;cursor:pointer;font-size:.95rem;}
        .btn-modal-submit:hover{background:var(--primary);}
        /* Detalle estudiante */
        .detail-modal{max-width:600px;}
        .detail-foto{width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid var(--accent);margin:0 auto 1rem;display:block;}
        .detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-top:1rem;}
        .detail-item label{font-size:.75rem;color:var(--gray);font-weight:600;text-transform:uppercase;}
        .detail-item span{display:block;font-size:.9rem;font-weight:600;color:#1f2937;margin-top:.15rem;}
        .cursos-list{margin-top:1rem;}
        .curso-tag{display:inline-block;background:#eff6ff;color:#1d4ed8;padding:.25rem .75rem;border-radius:20px;font-size:.78rem;font-weight:600;margin:.2rem;}
        @media(max-width:768px){th:nth-child(3),td:nth-child(3),th:nth-child(4),td:nth-child(4){display:none;}.top-bar{flex-direction:column;align-items:stretch;}}
    </style>
</head>
<body>
<nav>
    <div class="nav-left">
        <a class="btn-back" href="menu_admin.php"><i class="fas fa-arrow-left"></i> Menú</a>
        <span class="nav-title">Gestión de Estudiantes</span>
    </div>
    <span style="color:rgba(255,255,255,.7);font-size:.875rem"><?= e($usuario) ?></span>
</nav>

<div class="container">
    <?php if (isset($flash)): ?>
        <div class="alert <?= $flash['tipo'] ?>"><?= e($flash['msg']) ?></div>
    <?php endif; ?>

    <div class="top-bar">
        <form method="GET" style="display:contents">
            <div class="search-wrap">
                <i class="fas fa-search"></i>
                <input class="search-input" type="text" name="q" value="<?= e($buscar) ?>" placeholder="Buscar por nombre, apellido o correo...">
            </div>
            <button class="btn-search" type="submit"><i class="fas fa-search"></i> Buscar</button>
        </form>
        <a class="btn-add" href="registro_estudiante.php"><i class="fas fa-user-plus"></i> Nuevo Estudiante</a>
    </div>

    <div class="stats-bar">
        <div class="stat"><strong><?= count($estudiantes) ?></strong> estudiantes<?= $buscar ? ' encontrados' : ' registrados' ?></div>
        <?php if ($buscar): ?>
            <div class="stat">Búsqueda: "<?= e($buscar) ?>" · <a href="gestionar_estudiantes.php" style="color:var(--accent)">Limpiar</a></div>
        <?php endif; ?>
    </div>

    <div class="card">
        <?php if (empty($estudiantes)): ?>
            <div class="empty">
                <i class="fas fa-users-slash" style="font-size:2rem;margin-bottom:.5rem"></i><br>
                <?= $buscar ? 'No se encontraron estudiantes con esa búsqueda.' : 'No hay estudiantes registrados aún.' ?>
            </div>
        <?php else: ?>
        <div style="overflow-x:auto">
        <table>
            <thead>
                <tr>
                    <th>Estudiante</th>
                    <th>Carrera</th>
                    <th>Semestre / Sección</th>
                    <th>Cursos</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($estudiantes as $est): ?>
                <?php
                $fotoSrc = '';
                if (!empty($est['foto']) && file_exists(__DIR__.'/'.$est['foto'])) {
                    $fotoSrc = BASE_URL . '/' . $est['foto'];
                }
                ?>
                <tr>
                    <td>
                        <div class="student-cell">
                            <?php if ($fotoSrc): ?>
                                <img class="avatar-img" src="<?= e($fotoSrc) ?>" alt="foto">
                            <?php else: ?>
                                <div class="avatar-fallback"><?= strtoupper(substr($est['nombre'],0,1)) ?></div>
                            <?php endif; ?>
                            <div>
                                <div class="student-name"><?= e($est['nombre'].' '.$est['apellidos']) ?></div>
                                <div class="student-email"><?= e($est['correo']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= e($est['carrera'] ?? '—') ?></td>
                    <td>
                        <?php if ($est['semestre']): ?>
                            <span class="badge badge-blue"><?= $est['semestre'] ?>° sem.</span>
                            <span class="badge badge-gray">Sección <?= e($est['seccion']) ?></span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $est['cursos_asignados']>0 ? 'badge-green' : 'badge-gray' ?>">
                            <?= $est['cursos_asignados'] ?> curso<?= $est['cursos_asignados']!=1?'s':'' ?>
                        </span>
                    </td>
                    <td>
                        <div class="actions">
                            <button class="btn-sm btn-view" onclick="verDetalle(<?= $est['id'] ?>)">
                                <i class="fas fa-eye"></i> Ver
                            </button>
                            <button class="btn-sm btn-assign" onclick="abrirAsignar(<?= $est['id'] ?>, '<?= e($est['nombre'].' '.$est['apellidos']) ?>')">
                                <i class="fas fa-plus"></i> Curso
                            </button>
                            <button class="btn-sm btn-del" onclick="eliminar(<?= $est['id'] ?>, '<?= e($est['nombre'].' '.$est['apellidos']) ?>')">
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

<!-- Modal asignar a curso -->
<div class="modal-overlay" id="modalAsignar">
    <div class="modal">
        <button class="modal-close" onclick="cerrarModal('modalAsignar')">&times;</button>
        <h3><i class="fas fa-user-plus" style="color:var(--accent)"></i> Asignar a Curso</h3>
        <p id="modalEstNombre" style="color:var(--gray);font-size:.875rem;margin-bottom:1rem"></p>
        <form method="POST">
            <input type="hidden" name="estudiante_id" id="inputEstId">
            <div class="form-group">
                <label>Seleccionar curso</label>
                <select name="curso_id" required>
                    <option value="">— Seleccionar —</option>
                    <?php foreach($cursos as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= e($c['nombre']) ?> · Salón <?= e($c['salon']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="asignar" class="btn-modal-submit">
                <i class="fas fa-check"></i> Asignar al Curso
            </button>
        </form>
    </div>
</div>

<!-- Modal detalle estudiante -->
<div class="modal-overlay" id="modalDetalle">
    <div class="modal detail-modal">
        <button class="modal-close" onclick="cerrarModal('modalDetalle')">&times;</button>
        <h3><i class="fas fa-id-card" style="color:var(--accent)"></i> Datos del Estudiante</h3>
        <div id="detalleContenido"><div style="text-align:center;padding:2rem;color:var(--gray)">Cargando...</div></div>
    </div>
</div>

<script>
function abrirAsignar(id, nombre) {
    document.getElementById('inputEstId').value = id;
    document.getElementById('modalEstNombre').textContent = nombre;
    document.getElementById('modalAsignar').classList.add('open');
}
function cerrarModal(id) {
    document.getElementById(id).classList.remove('open');
}
function eliminar(id, nombre) {
    if (confirm(`¿Eliminar al estudiante "${nombre}" y toda su información? Esta acción no se puede deshacer.`)) {
        window.location.href = `gestionar_estudiantes.php?delete=${id}`;
    }
}
async function verDetalle(id) {
    document.getElementById('modalDetalle').classList.add('open');
    document.getElementById('detalleContenido').innerHTML = '<div style="text-align:center;padding:2rem;color:var(--gray)"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';
    try {
        const res  = await fetch(`estudiante_detalle.php?id=${id}`);
        const html = await res.text();
        document.getElementById('detalleContenido').innerHTML = html;
    } catch(e) {
        document.getElementById('detalleContenido').innerHTML = '<div style="color:red;padding:1rem">Error al cargar los datos.</div>';
    }
}
// Cerrar al hacer click fuera
document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('open');
    });
});
</script>
</body>
</html>
