<?php
session_start();
require_once 'config.php';
requireSession('Administrador');
$usuario = $_SESSION['usuario'];
$pdo     = getDB();
$mensaje = '';
$tipo    = '';

// Eliminar usuario
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id !== (int)$_SESSION['usuario_id']) {
        $pdo->prepare("DELETE FROM roles_usuarios WHERE id_roles_usuario = ?")->execute([$id]);
        $mensaje = 'Usuario eliminado correctamente.';
        $tipo    = 'success';
    } else {
        $mensaje = 'No puedes eliminar tu propio usuario.';
        $tipo    = 'error';
    }
}

// Registrar usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar'])) {
    $nombre    = trim($_POST['nombre_usuario'] ?? '');
    $correo    = trim($_POST['correo'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $rol       = $_POST['rol'] ?? '';
    $password  = $_POST['contrasena'] ?? '';

    if (empty($nombre) || empty($correo) || empty($password) || !in_array($rol, ['Administrador','Catedrático'])) {
        $mensaje = 'Todos los campos son obligatorios.';
        $tipo    = 'error';
    } else {
        // Verificar duplicado
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM roles_usuarios WHERE nombre_usuario = ?");
        $stmt->execute([$nombre]);
        if ($stmt->fetchColumn() > 0) {
            $mensaje = 'El nombre de usuario ya está registrado.';
            $tipo    = 'error';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO roles_usuarios (nombre_usuario, contrasena, correo, telefono, rol) VALUES (?,?,?,?,?)")
                ->execute([$nombre, $hash, $correo, $telefono, $rol]);
            $mensaje = 'Usuario registrado exitosamente.';
            $tipo    = 'success';
        }
    }
}

// Obtener usuarios
$usuarios = $pdo->query("SELECT id_roles_usuario, nombre_usuario, correo, telefono, rol FROM roles_usuarios ORDER BY rol, nombre_usuario")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - UMG</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary:#1a2e4a; --accent:#2563eb; --success:#16a34a; --danger:#dc2626; --gray:#6b7280; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:#f0f4f8; font-family:'Segoe UI',sans-serif; color:#1f2937; }
        nav { background:var(--primary); padding:0 2rem; display:flex; justify-content:space-between; align-items:center; height:64px; box-shadow:0 2px 10px rgba(0,0,0,.3); }
        .nav-left { display:flex; align-items:center; gap:1rem; }
        .nav-left a { color:rgba(255,255,255,.7); text-decoration:none; font-size:.875rem; }
        .nav-left a:hover { color:white; }
        .nav-title { color:white; font-weight:700; font-size:1rem; }
        .btn-back { background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.2); color:white; padding:.4rem .9rem; border-radius:8px; text-decoration:none; font-size:.875rem; }
        .container { max-width:1200px; margin:0 auto; padding:2rem; }
        .alert { padding:.85rem 1rem; border-radius:10px; margin-bottom:1.5rem; font-size:.9rem; }
        .alert.success { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }
        .alert.error   { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
        .layout { display:grid; grid-template-columns:380px 1fr; gap:1.5rem; }
        .card { background:white; border-radius:14px; padding:1.75rem; box-shadow:0 4px 20px rgba(0,0,0,.07); }
        .card h2 { font-size:1.1rem; font-weight:700; margin-bottom:1.25rem; padding-bottom:.75rem; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; gap:.5rem; }
        .form-group { margin-bottom:1.1rem; }
        .form-group label { display:block; font-size:.875rem; font-weight:600; color:#374151; margin-bottom:.35rem; }
        .form-group input, .form-group select { width:100%; padding:.7rem 1rem; border:2px solid #e5e7eb; border-radius:9px; font-size:.9rem; outline:none; transition:border-color .2s; }
        .form-group input:focus, .form-group select:focus { border-color:var(--accent); }
        .btn-submit { width:100%; padding:.8rem; background:var(--accent); color:white; border:none; border-radius:9px; font-size:.95rem; font-weight:600; cursor:pointer; transition:background .2s; }
        .btn-submit:hover { background:var(--primary); }
        table { width:100%; border-collapse:collapse; }
        th { text-align:left; padding:.85rem 1rem; background:#f8fafc; color:var(--gray); font-size:.8rem; font-weight:600; text-transform:uppercase; border-bottom:2px solid #e5e7eb; }
        td { padding:.85rem 1rem; border-bottom:1px solid #f1f5f9; font-size:.9rem; }
        tr:hover td { background:#f8fafc; }
        .badge { display:inline-block; padding:.25rem .65rem; border-radius:20px; font-size:.75rem; font-weight:600; }
        .badge.admin  { background:#eff6ff; color:#1d4ed8; }
        .badge.cat    { background:#f5f3ff; color:#6d28d9; }
        .btn-del { background:#fef2f2; color:var(--danger); border:none; padding:.4rem .75rem; border-radius:7px; cursor:pointer; font-size:.8rem; transition:background .2s; }
        .btn-del:hover { background:#fee2e2; }
        .empty { text-align:center; padding:2rem; color:var(--gray); font-size:.9rem; }
        @media(max-width:900px) { .layout { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<nav>
    <div class="nav-left">
        <a class="btn-back" href="menu_admin.php"><i class="fas fa-arrow-left"></i> Menú</a>
        <span class="nav-title">Gestión de Usuarios</span>
    </div>
    <span style="color:rgba(255,255,255,.7);font-size:.875rem"><?= e($usuario) ?> · Administrador</span>
</nav>

<div class="container">
    <?php if ($mensaje): ?>
        <div class="alert <?= $tipo ?>"><?= e($mensaje) ?></div>
    <?php endif; ?>

    <div class="layout">
        <!-- Formulario -->
        <div class="card">
            <h2><i class="fas fa-user-plus" style="color:var(--accent)"></i> Nuevo Usuario</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Nombre de usuario</label>
                    <input type="text" name="nombre_usuario" required placeholder="ej: profe.garcia">
                </div>
                <div class="form-group">
                    <label>Correo electrónico</label>
                    <input type="email" name="correo" required placeholder="correo@umg.edu.gt">
                </div>
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" placeholder="5555-5555">
                </div>
                <div class="form-group">
                    <label>Rol</label>
                    <select name="rol" required>
                        <option value="">Seleccionar rol...</option>
                        <option value="Administrador">Administrador</option>
                        <option value="Catedrático">Catedrático</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="contrasena" required placeholder="Contraseña segura">
                </div>
                <button type="submit" name="registrar" class="btn-submit">
                    <i class="fas fa-plus"></i> Registrar Usuario
                </button>
            </form>
        </div>

        <!-- Tabla -->
        <div class="card">
            <h2><i class="fas fa-users" style="color:var(--accent)"></i> Usuarios Registrados (<?= count($usuarios) ?>)</h2>
            <?php if (empty($usuarios)): ?>
                <div class="empty"><i class="fas fa-users-slash"></i><br>No hay usuarios registrados</div>
            <?php else: ?>
            <div style="overflow-x:auto">
                <table>
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td><strong><?= e($u['nombre_usuario']) ?></strong></td>
                            <td><?= e($u['correo']) ?></td>
                            <td>
                                <span class="badge <?= $u['rol'] === 'Administrador' ? 'admin' : 'cat' ?>">
                                    <?= e($u['rol']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ((int)$u['id_roles_usuario'] !== (int)$_SESSION['usuario_id']): ?>
                                <button class="btn-del" onclick="confirmarEliminar(<?= $u['id_roles_usuario'] ?>, '<?= e($u['nombre_usuario']) ?>')">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                                <?php else: ?>
                                <span style="color:var(--gray);font-size:.8rem">— tú —</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function confirmarEliminar(id, nombre) {
    if (confirm('¿Eliminar al usuario "' + nombre + '"? Esta acción no se puede deshacer.')) {
        window.location.href = 'gestionar_usuarios.php?delete=' + id;
    }
}
</script>
</body>
</html>
