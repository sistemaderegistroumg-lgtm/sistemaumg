<?php
session_start();
include 'mascota.php';
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['error' => 'No autorizado']); exit;
}

$action = $_GET['action'] ?? '';

// URL base de la API biométrica
define('API_BIOMETRICA', 'http://www.server.daossystem.pro:3405');

/**
 * Hace una petición POST a la API biométrica con JSON
 */
function llamarAPI(string $endpoint, array $body): array {
    $url  = API_BIOMETRICA . $endpoint;
    $json = json_encode($body);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $json,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json; charset=UTF-8'],
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['error' => 'No se pudo conectar con la API biométrica: ' . $error];
    }
    if ($httpCode !== 200) {
        return ['error' => 'API respondió con código ' . $httpCode];
    }

    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => 'Respuesta inválida de la API'];
    }
    return $decoded;
}

// -------------------------------------------------------
// Segmentar: recibe Base64 de imagen, devuelve rostro recortado
// POST body: { "imagen": "base64..." }
// -------------------------------------------------------
if ($action === 'segmentar') {
    $body = json_decode(file_get_contents('php://input'), true);
    $imagenB64 = $body['imagen'] ?? '';

    if (empty($imagenB64)) {
        echo json_encode(['error' => 'Imagen requerida']); exit;
    }

    // Limpiar prefijo data:image/...;base64, si viene del canvas
    if (strpos($imagenB64, ',') !== false) {
        $imagenB64 = explode(',', $imagenB64)[1];
    }

    $resultado = llamarAPI('/Rostro/Segmentar', ['RostroA' => $imagenB64]);
    echo json_encode($resultado);
    exit;
}

// -------------------------------------------------------
// Verificar: compara dos rostros segmentados
// POST body: { "rostroA": "base64...", "rostroB": "base64..." }
// -------------------------------------------------------
if ($action === 'verificar') {
    $body   = json_decode(file_get_contents('php://input'), true);
    $rostroA = $body['rostroA'] ?? '';
    $rostroB = $body['rostroB'] ?? '';

    if (empty($rostroA) || empty($rostroB)) {
        echo json_encode(['error' => 'Se requieren dos rostros']); exit;
    }

    $resultado = llamarAPI('/Rostro/Verificar', [
        'RostroA' => $rostroA,
        'RostroB' => $rostroB,
    ]);
    echo json_encode($resultado);
    exit;
}

// -------------------------------------------------------
// Obtener fotos de estudiantes de un curso para comparar
// GET ?action=get_fotos_curso&curso_id=X
// -------------------------------------------------------
if ($action === 'get_fotos_curso') {
    $curso_id = (int)($_GET['curso_id'] ?? 0);
    if ($curso_id <= 0) {
        echo json_encode(['error' => 'curso_id inválido']); exit;
    }

    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT u.id, u.nombre, u.apellidos, u.foto
        FROM curso_estudiante ce
        JOIN usuarios u ON ce.estudiante_id = u.id
        WHERE ce.curso_id = ?
        ORDER BY u.apellidos, u.nombre
    ");
    $stmt->execute([$curso_id]);
    $estudiantes = $stmt->fetchAll();

    // Convertir fotos a Base64 para enviarlas al JS
    foreach ($estudiantes as &$est) {
        $rutaFoto = __DIR__ . '/' . $est['foto'];
        if (!empty($est['foto']) && file_exists($rutaFoto)) {
            $est['foto_b64'] = base64_encode(file_get_contents($rutaFoto));
        } else {
            $est['foto_b64'] = null;
        }
        unset($est['foto']); // No exponer la ruta interna
    }

    echo json_encode($estudiantes);
    exit;
}

echo json_encode(['error' => 'Acción no válida: ' . $action]);
