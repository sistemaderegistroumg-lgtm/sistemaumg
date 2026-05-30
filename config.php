<?php

// ============================================================
// SESIÓN
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// ERRORES (IMPORTANTE PARA JSON)
// ============================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// ============================================================
// SUPABASE CONFIG
// ============================================================

define('DB_HOST', 'aws-1-us-east-2.pooler.supabase.com');
define('DB_PORT', '5432');
define('DB_NAME', 'postgres');

define('DB_USER', 'postgres.nzsoiurryswdsbcfcwsu');
define('DB_PASS', '854384Est@*');

// ============================================================
// URL BASE DEL PROYECTO
// ============================================================

define('BASE_URL', 'http://localhost/proyecto_umg_2/');

// ============================================================
// CONEXIÓN PDO
// ============================================================

function getDB() {

    try {

        $dsn = "pgsql:host=" . DB_HOST .
               ";port=" . DB_PORT .
               ";dbname=" . DB_NAME .
               ";sslmode=require";

        $pdo = new PDO($dsn, DB_USER, DB_PASS);

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        return $pdo;

    } catch (PDOException $e) {

        jsonResponse(false, "Error de conexión a base de datos");
    }
}

// ============================================================
// ESCAPAR TEXTO
// ============================================================

function e($texto) {
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

// ============================================================
// RESPUESTA JSON (ÚNICA SALIDA SEGURA)
// ============================================================

function jsonResponse($success, $message, $extra = []) {

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

// ============================================================
// VALIDAR SESIÓN (API SAFE)
// ============================================================

function requireSession($rol = null) {

    if (!isset($_SESSION['usuario_id'])) {
        jsonResponse(false, "No autenticado");
    }

    if ($rol !== null) {

        if (!isset($_SESSION['rol']) || intval($_SESSION['rol']) !== intval($rol)) {
            jsonResponse(false, "Acceso denegado");
        }
    }
}

?>