<?php

require_once 'config.php';

// ============================================
// INICIAR SESIÓN
// ============================================
if (session_status() === PHP_SESSION_NONE) {

    session_start();
}

// ============================================
// VALIDAR SESIÓN
// ============================================
if (isset($_SESSION['usuario_id'])) {

    switch ($_SESSION['rol']) {

        // ====================================
        // ADMINISTRADOR
        // ====================================
        case 1:

            header("Location: menu_admin.php");
            exit;

        // ====================================
        // ESTUDIANTE
        // ====================================
        case 2:

            header("Location: menu_estudiante.php");
            exit;

        // ====================================
        // CATEDRÁTICO
        // ====================================
        case 3:

            header("Location: menu_catedratico.php");
            exit;

        // ====================================
        // ROL INVÁLIDO
        // ====================================
        default:

            session_destroy();

            header("Location: login.php");

            exit;
    }

} else {

    // ========================================
    // NO HAY SESIÓN
    // ========================================
    header("Location: login.php");

    exit;
}
?>