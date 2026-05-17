<?php

// ============================================================
// CONFIGURACIÓN GENERAL DEL SISTEMA
// SUPABASE + PHP + PDO
// ============================================================

// ============================================================
// INICIAR SESIÓN
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// MOSTRAR ERRORES
// ============================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// ============================================================
// DATOS DE SUPABASE
// ============================================================

define('DB_HOST', 'aws-1-us-east-2.pooler.supabase.com');
define('DB_PORT', '5432');
define('DB_NAME', 'postgres');

define('DB_USER', 'postgres.nzsoiurryswdsbcfcwsu');
define('DB_PASS', '854384Est@*');

// ============================================================
// CONEXIÓN PDO
// ============================================================

function getDB() {

    try {

        $dsn = "pgsql:host=" . DB_HOST .
               ";port=" . DB_PORT .
               ";dbname=" . DB_NAME .
               ";sslmode=require";

        $pdo = new PDO(
            $dsn,
            DB_USER,
            DB_PASS
        );

        // ====================================================
        // CONFIGURACIONES PDO
        // ====================================================

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        return $pdo;

    } catch (PDOException $e) {

        die("❌ ERROR REAL DE CONEXIÓN: " . $e->getMessage());
    }
}

// ============================================================
// ESCAPAR TEXTO
// ============================================================

function e($texto) {

    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

// ============================================================
// VALIDAR SESIÓN
// ============================================================

function requireSession($rol = null) {

    if (!isset($_SESSION['usuario'])) {

        die("❌ Usuario no autenticado");
    }

    if ($rol && (!isset($_SESSION['rol']) || $_SESSION['rol'] !== $rol)) {

        die("❌ Acceso denegado");
    }
}

// ============================================================
// RESPUESTA JSON
// ============================================================

function jsonResponse($success, $message, $extra = []) {

    // ========================================================
    // LIMPIAR BUFFER
    // ========================================================

    if (ob_get_length()) {
        ob_clean();
    }

    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));

    exit;
}

?>