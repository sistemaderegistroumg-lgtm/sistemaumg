<?php

session_start();

header('Content-Type: application/json');

require_once 'config.php';

// =====================================================
// VALIDAR MÉTODO
// =====================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);

    exit;
}

// =====================================================
// DATOS DEL FORMULARIO
// =====================================================
$correo = trim($_POST['correo'] ?? '');

$password = trim($_POST['contrasena'] ?? '');

// =====================================================
// VALIDAR CAMPOS
// =====================================================
if (empty($correo) || empty($password)) {

    echo json_encode([
        'success' => false,
        'message' => 'Todos los campos son obligatorios'
    ]);

    exit;
}

try {

    // =====================================================
    // CONEXIÓN
    // =====================================================
    $pdo = getDB();

    // =====================================================
    // BUSCAR USUARIO POR CORREO
    // =====================================================
    $stmt = $pdo->prepare("
        SELECT
            u.id,
            u.nombre,
            u.apellidos,
            u.correo,
            u.password,
            u.rol_id,
            r.nombre_rol
        FROM usuarios u
        INNER JOIN roles r
            ON r.id = u.rol_id
        WHERE u.correo = ?
        LIMIT 1
    ");

    $stmt->execute([$correo]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // =====================================================
    // VALIDAR USUARIO
    // =====================================================
    if (!$user) {

        echo json_encode([
            'success' => false,
            'message' => 'Usuario no encontrado'
        ]);

        exit;
    }

    // =====================================================
    // VALIDAR CONTRASEÑA
    // =====================================================
    if (!password_verify($password, $user['password'])) {

        echo json_encode([
            'success' => false,
            'message' => 'Contraseña incorrecta'
        ]);

        exit;
    }

    // =====================================================
    // CREAR SESIÓN SEGURA
    // =====================================================
    session_regenerate_id(true);

    $_SESSION['usuario_id'] = $user['id'];

    $_SESSION['usuario'] = $user['correo'];

    $_SESSION['nombre'] =
        $user['nombre'] . ' ' . $user['apellidos'];

    $_SESSION['rol'] = intval($user['rol_id']);

    $_SESSION['nombre_rol'] = $user['nombre_rol'];

    // =====================================================
    // REDIRECCIÓN SEGÚN ROL
    // =====================================================
    switch ($_SESSION['rol']) {

        case 1:
            $redirect = 'menu_admin.php';
            break;

        case 2:
            $redirect = 'menu_estudiante.php';
            break;

        case 3:
            $redirect = 'menu_catedratico.php';
            break;

        default:

            echo json_encode([
                'success' => false,
                'message' => 'Rol inválido'
            ]);

            exit;
    }

    // =====================================================
    // RESPUESTA EXITOSA
    // =====================================================
    echo json_encode([
        'success'  => true,
        'message'  => 'Inicio de sesión correcto',
        'redirect' => $redirect
    ]);

} catch (PDOException $e) {

    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>