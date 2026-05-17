<?php
session_start();
require_once __DIR__ . '/config.php';

$pdo  = getDB();
$est  = $pdo->query("SELECT id, nombre, foto FROM usuarios LIMIT 1")->fetch();

echo "Estudiante: " . $est['nombre'] . "<br>";
echo "Ruta foto: " . $est['foto'] . "<br>";

$rutaFoto = __DIR__ . '/' . $est['foto'];
echo "Existe el archivo: " . (file_exists($rutaFoto) ? "SÍ" : "NO") . "<br>";

if (file_exists($rutaFoto)) {
    $b64 = base64_encode(file_get_contents($rutaFoto));
    echo "Tamaño base64: " . strlen($b64) . " chars<br>";
    
    // Llamar a la API
    $ch = curl_init('http://www.server.daossystem.pro:3405/Rostro/Segmentar');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['RostroA' => $b64]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);
    
    echo "Error cURL: " . ($err ?: "ninguno") . "<br>";
    echo "Respuesta API: " . $resp . "<br>";
}