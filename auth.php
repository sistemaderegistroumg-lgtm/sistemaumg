<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'Método no permitido']);
    exit;
}

$usuario  = trim($_POST['nombre_usuario'] ?? '');
$password = trim($_POST['contrasena'] ?? '');

if ($usuario === '' || $password === '') {
    echo json_encode(['success'=>false,'message'=>'Campos requeridos']);
    exit;
}

try {
    $pdo = getDB();

    // ✅ CAMBIO IMPORTANTE: id → NO id_roles_usuario
    $stmt = $pdo->prepare("
        SELECT id, nombre_usuario, contrasena, rol
        FROM roles_usuarios
        WHERE nombre_usuario = ?
        LIMIT 1
    ");
    $stmt->execute([$usuario]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success'=>false,'message'=>'Usuario no encontrado']);
        exit;
    }

    // password verify
    if (!password_verify($password, $row['contrasena'])) {
        echo json_encode(['success'=>false,'message'=>'Contraseña incorrecta']);
        exit;
    }

    $_SESSION['usuario']    = $row['nombre_usuario'];
    $_SESSION['rol']        = $row['rol'];
    $_SESSION['usuario_id'] = $row['id'];

    session_regenerate_id(true);

    echo json_encode([
        'success' => true,
        'redirect' => ($row['rol'] === 'Administrador')
            ? 'menu_admin.php'
            : 'menu_catedratico.php'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}