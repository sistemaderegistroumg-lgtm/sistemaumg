<?php
session_start();
require_once 'config.php';
include 'mascota.php';
requireSession('Administrador');

$id  = (int)($_GET['id'] ?? 0);
$pdo = getDB();

$stmt = $pdo->prepare("
    SELECT u.*,
           GROUP_CONCAT(c.nombre ORDER BY c.nombre SEPARATOR '||') AS cursos
    FROM usuarios u
    LEFT JOIN curso_estudiante ce ON u.id = ce.estudiante_id
    LEFT JOIN cursos c ON ce.curso_id = c.id
    WHERE u.id = ?
    GROUP BY u.id
");
$stmt->execute([$id]);
$est = $stmt->fetch();

if (!$est): ?>
    <div style="color:red;padding:1rem">Estudiante no encontrado.</div>
<?php return; endif;

$fotoSrc = '';
if (!empty($est['foto']) && file_exists(__DIR__.'/'.$est['foto'])) {
    $fotoSrc = BASE_URL . '/' . $est['foto'];
}
?>
<?php if ($fotoSrc): ?>
    <img class="detail-foto" src="<?= e($fotoSrc) ?>" alt="Foto">
<?php else: ?>
    <div style="width:80px;height:80px;border-radius:50%;background:#dbeafe;color:#1d4ed8;display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;margin:0 auto 1rem">
        <?= strtoupper(substr($est['nombre'],0,1)) ?>
    </div>
<?php endif; ?>

<div style="text-align:center;margin-bottom:1rem">
    <strong style="font-size:1.1rem"><?= e($est['nombre'].' '.$est['apellidos']) ?></strong><br>
    <span style="font-size:.875rem;color:#6b7280"><?= e($est['correo']) ?></span>
</div>

<div class="detail-grid">
    <div class="detail-item">
        <label>Teléfono</label>
        <span><?= e($est['telefono'] ?: '—') ?></span>
    </div>
    <div class="detail-item">
        <label>Carrera</label>
        <span><?= e($est['carrera'] ?: '—') ?></span>
    </div>
    <div class="detail-item">
        <label>Semestre</label>
        <span><?= $est['semestre'] ? $est['semestre'].'°' : '—' ?></span>
    </div>
    <div class="detail-item">
        <label>Sección</label>
        <span><?= e($est['seccion'] ?: '—') ?></span>
    </div>
    <div class="detail-item">
        <label>ID sistema</label>
        <span>#<?= $est['id'] ?></span>
    </div>
    <div class="detail-item">
        <label>Registrado</label>
        <span><?= date('d/m/Y', strtotime($est['created_at'])) ?></span>
    </div>
</div>

<div class="cursos-list">
    <label style="font-size:.75rem;color:#6b7280;font-weight:700;text-transform:uppercase">Cursos asignados</label>
    <div style="margin-top:.5rem">
        <?php
        $cursos = array_filter(explode('||', $est['cursos'] ?? ''));
        if (empty($cursos)): ?>
            <span style="color:#9ca3af;font-size:.875rem">Sin cursos asignados</span>
        <?php else: foreach($cursos as $c): ?>
            <span class="curso-tag"><?= e($c) ?></span>
        <?php endforeach; endif; ?>
    </div>
</div>
