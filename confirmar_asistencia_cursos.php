<?php

session_start();

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// =====================================================
// VALIDAR SESIÓN
// =====================================================
if (!isset($_SESSION['usuario_id'])) {

    echo json_encode([
        "success" => false,
        "message" => "Sesión inválida"
    ]);

    exit;
}

// =====================================================
// CONEXIÓN
// =====================================================
$pdo = getDB();

// =====================================================
// LEER JSON
// =====================================================
$data = json_decode(
    file_get_contents("php://input"),
    true
);

// =====================================================
// VALIDAR DATOS
// =====================================================
if (
    !isset($data['curso_id']) ||
    !isset($data['estudiantes'])
) {

    echo json_encode([
        "success" => false,
        "message" => "Datos incompletos"
    ]);

    exit;
}

$curso_id = (int)$data['curso_id'];

try {

    foreach ($data['estudiantes'] as $est) {

        $estudiante_id =
            (int)$est['estudiante_id'];

        $presente =
            (int)$est['presente'];

        // =====================================================
        // SI ESTÁ PRESENTE
        // =====================================================
        if ($presente === 1) {

            // VERIFICAR SI YA EXISTE HOY
            $check = $pdo->prepare("
                SELECT id
                FROM detecciones
                WHERE estudiante_id = ?
                AND curso_id = ?
                AND DATE(fecha_hora) = CURRENT_DATE
            ");

            $check->execute([
                $estudiante_id,
                $curso_id
            ]);

            // SI NO EXISTE -> INSERTAR
            if (!$check->fetch()) {

                $insert = $pdo->prepare("
                    INSERT INTO detecciones (
                        estudiante_id,
                        curso_id,
                        fecha_hora
                    )
                    VALUES (?, ?, NOW())
                ");

                $insert->execute([
                    $estudiante_id,
                    $curso_id
                ]);
            }
        }
    }

    echo json_encode([
        "success" => true,
        "message" => "Asistencia guardada correctamente"
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>