<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$pdo    = getDB();
$action = $_GET['action'] ?? '';

try {
    switch ($action) {

        // -------------------------------------------------------
        // GET: Cursos del catedrático
        // -------------------------------------------------------
        case 'get_cursos':
            $catedratico_id = (int)($_GET['catedratico_id'] ?? $_SESSION['usuario_id'] ?? 0);
            $stmt = $pdo->prepare("
                SELECT c.id, c.nombre, c.salon,
                       c.horario_inicio, c.horario_fin,
                       COUNT(ce.estudiante_id) AS total_estudiantes
                FROM cursos c
                LEFT JOIN curso_estudiante ce ON c.id = ce.curso_id
                WHERE c.catedratico_id = ?
                GROUP BY c.id
                ORDER BY c.nombre
            ");
            $stmt->execute([$catedratico_id]);
            echo json_encode($stmt->fetchAll());
            break;

        // -------------------------------------------------------
        // GET: Todos los cursos (para admin)
        // -------------------------------------------------------
        case 'get_todos_cursos':
            $stmt = $pdo->query("
                SELECT c.id, c.nombre, c.salon, c.horario_inicio, c.horario_fin,
                       ru.nombre_usuario AS catedratico,
                       COUNT(ce.estudiante_id) AS total_estudiantes
                FROM cursos c
                LEFT JOIN roles_usuarios ru ON c.catedratico_id = ru.id_roles_usuario
                LEFT JOIN curso_estudiante ce ON c.id = ce.curso_id
                GROUP BY c.id
                ORDER BY c.nombre
            ");
            echo json_encode($stmt->fetchAll());
            break;

        // -------------------------------------------------------
        // GET: Estudiantes de un curso con estado de asistencia
        // -------------------------------------------------------
        case 'get_estudiantes':
            $curso_id = (int)($_GET['curso_id'] ?? 0);
            $fecha    = $_GET['fecha'] ?? date('Y-m-d');

            if ($curso_id <= 0) {
                echo json_encode(['error' => 'curso_id inválido']);
                break;
            }

            $stmt = $pdo->prepare("
                SELECT
                    u.id,
                    u.nombre,
                    u.apellidos,
                    u.correo,
                    u.foto,
                    IFNULL((
                        SELECT ad.presente
                        FROM asistencia_detalle ad
                        JOIN asistencias a ON ad.asistencia_id = a.id
                        WHERE ad.estudiante_id = u.id
                          AND a.curso_id = :cid
                          AND a.fecha = :fecha
                        LIMIT 1
                    ), 0) AS presente,
                    (
                        SELECT ad.hora_registro
                        FROM asistencia_detalle ad
                        JOIN asistencias a ON ad.asistencia_id = a.id
                        WHERE ad.estudiante_id = u.id
                          AND a.curso_id = :cid2
                          AND a.fecha = :fecha2
                        LIMIT 1
                    ) AS hora_registro
                FROM curso_estudiante ce
                JOIN usuarios u ON ce.estudiante_id = u.id
                WHERE ce.curso_id = :cid3
                ORDER BY u.apellidos, u.nombre
            ");
            $stmt->execute([
                ':cid'   => $curso_id, ':fecha'  => $fecha,
                ':cid2'  => $curso_id, ':fecha2' => $fecha,
                ':cid3'  => $curso_id
            ]);
            $estudiantes = $stmt->fetchAll();

            // Construir URL completa de foto
            foreach ($estudiantes as &$est) {
                if (!empty($est['foto'])) {
                    $est['foto_url'] = FOTOS_URL . basename($est['foto']);
                } else {
                    $est['foto_url'] = '';
                }
            }
            echo json_encode($estudiantes);
            break;

        // -------------------------------------------------------
        // POST: Guardar / actualizar asistencia del curso
        // -------------------------------------------------------
        case 'guardar_asistencia':
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                throw new Exception('JSON inválido');
            }

            $curso_id       = (int)($data['curso_id'] ?? 0);
            $fecha          = $data['fecha'] ?? date('Y-m-d');
            $catedratico_id = (int)($data['catedratico_id'] ?? $_SESSION['usuario_id'] ?? 0);
            $asistencias    = $data['asistencias'] ?? [];

            if ($curso_id <= 0 || empty($asistencias)) {
                throw new Exception('Datos incompletos');
            }

            $pdo->beginTransaction();

            // Buscar o crear registro principal de asistencia
            $stmt = $pdo->prepare("SELECT id FROM asistencias WHERE curso_id = ? AND fecha = ? LIMIT 1");
            $stmt->execute([$curso_id, $fecha]);
            $asistencia = $stmt->fetch();

            if ($asistencia) {
                $asistencia_id = $asistencia['id'];
                // Limpiar detalles previos para reescribir
                $pdo->prepare("DELETE FROM asistencia_detalle WHERE asistencia_id = ?")->execute([$asistencia_id]);
            } else {
                $pdo->prepare("INSERT INTO asistencias (curso_id, fecha, catedratico_id) VALUES (?,?,?)")
                    ->execute([$curso_id, $fecha, $catedratico_id]);
                $asistencia_id = $pdo->lastInsertId();
            }

            // Insertar detalles
            $stmtDet = $pdo->prepare("
                INSERT INTO asistencia_detalle (asistencia_id, estudiante_id, presente, hora_registro)
                VALUES (?, ?, ?, NOW())
            ");
            foreach ($asistencias as $a) {
                $stmtDet->execute([
                    $asistencia_id,
                    (int)$a['estudiante_id'],
                    $a['presente'] ? 1 : 0
                ]);
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'asistencia_id' => $asistencia_id, 'message' => 'Asistencia guardada correctamente.']);
            break;

        // -------------------------------------------------------
        // POST: Registrar asistencia por QR
        // -------------------------------------------------------
        case 'registrar_qr':
            $estudiante_id  = (int)($_POST['usuario_id'] ?? 0);
            $curso_id       = (int)($_POST['curso_id'] ?? 0);
            $catedratico_id = (int)($_POST['catedratico_id'] ?? $_SESSION['usuario_id'] ?? 1);
            $fecha          = $_POST['fecha'] ?? date('Y-m-d');

            if ($estudiante_id <= 0 || $curso_id <= 0) {
                echo json_encode(['error' => 'Datos inválidos para el registro QR.']);
                break;
            }

            // Verificar que el estudiante existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
            $stmt->execute([$estudiante_id]);
            if (!$stmt->fetch()) {
                echo json_encode(['error' => 'Estudiante no encontrado en la base de datos.']);
                break;
            }

            $pdo->beginTransaction();

            // Buscar o crear asistencia principal
            $stmt = $pdo->prepare("SELECT id FROM asistencias WHERE curso_id = ? AND fecha = ? LIMIT 1");
            $stmt->execute([$curso_id, $fecha]);
            $row = $stmt->fetch();

            if ($row) {
                $asistencia_id = $row['id'];
            } else {
                $pdo->prepare("INSERT INTO asistencias (curso_id, fecha, catedratico_id) VALUES (?,?,?)")
                    ->execute([$curso_id, $fecha, $catedratico_id]);
                $asistencia_id = $pdo->lastInsertId();
            }

            // Verificar si ya registró hoy
            $stmt = $pdo->prepare("SELECT id FROM asistencia_detalle WHERE asistencia_id = ? AND estudiante_id = ?");
            $stmt->execute([$asistencia_id, $estudiante_id]);

            if ($stmt->fetch()) {
                $pdo->rollBack();
                echo json_encode(['error' => '⚠️ Este estudiante ya registró su asistencia hoy.']);
            } else {
                $pdo->prepare("INSERT INTO asistencia_detalle (asistencia_id, estudiante_id, presente, hora_registro) VALUES (?,?,1,NOW())")
                    ->execute([$asistencia_id, $estudiante_id]);
                $pdo->commit();

                // Obtener nombre del estudiante
                $stmt = $pdo->prepare("SELECT nombre, apellidos FROM usuarios WHERE id = ?");
                $stmt->execute([$estudiante_id]);
                $est = $stmt->fetch();

                echo json_encode([
                    'success'   => '✅ Asistencia registrada correctamente.',
                    'nombre'    => $est ? $est['nombre'] . ' ' . $est['apellidos'] : ''
                ]);
            }
            break;

        // -------------------------------------------------------
        // GET: Catedráticos (para selector en cursos)
        // -------------------------------------------------------
        case 'get_catedraticos':
            $stmt = $pdo->query("SELECT id_roles_usuario AS id, nombre_usuario FROM roles_usuarios WHERE rol = 'Catedrático' ORDER BY nombre_usuario");
            echo json_encode($stmt->fetchAll());
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida: ' . $action]);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
