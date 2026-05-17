<?php
// Incluimos la configuración para tener acceso a las rutas
require_once 'config.php';

// Iniciamos sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lógica de redirección:
if (isset($_SESSION['usuario'])) {
    // Si ya hay sesión, enviarlo al menú (ajusta según tu rol)
    // Aquí podrías incluso hacer un switch si tuvieras diferentes menús por rol
    header("Location: menu_admin.php"); 
    exit;
} else {
    // Si NO hay sesión, enviarlo al login
    header("Location: login.php");
    exit;
}
?>