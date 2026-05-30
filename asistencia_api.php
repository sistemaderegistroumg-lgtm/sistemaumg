<?php

session_start();

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

// ======================================================
// VALIDAR SESIÓN
// ======================================================

if (!isset($_SESSION['usuario_id'])) {

    http_response_code(401);

    echo json_encode([
        'error' => 'No autorizado'
    ]);

    exit;
}

// ======================================================
// CONEXIÓN
// ======================================================

$pdo = getDB();

// ======================================================
// ACTION
// ======================================================

$action = $_GET['action'] ?? '';

try {

    // ======================================================
    // GET CURSOS DEL CATEDRÁTICO
    // ======================================================

   if ($action === 'get_cursos') {

    $usuario_id = (int) $_SESSION['usuario_id'];

    $rol_id = (int) ($_SESSION['rol'] ?? 0);

    // ======================================================
    // ADMINISTRADOR → VE TODOS LOS CURSOS
    // ======================================================

    if ($rol_id === 1) {

        $stmt = $pdo->query("

            SELECT

                c.id,
                c.nombre,
                c.salon,
                c.horario_inicio,
                c.horario_fin,

                u.nombre || ' ' || u.apellidos
                    AS catedratico,

                COUNT(ce.estudiante_id)
                    AS total_estudiantes

            FROM cursos c

            LEFT JOIN usuarios u
                ON c.catedratico_id = u.id

            LEFT JOIN curso_estudiante ce
                ON c.id = ce.curso_id

            GROUP BY

                c.id,
                c.nombre,
                c.salon,
                c.horario_inicio,
                c.horario_fin,
                u.nombre,
                u.apellidos

            ORDER BY c.nombre
        ");

        echo json_encode($stmt->fetchAll());

        exit;
    }

    // ======================================================
    // CATEDRÁTICO → SOLO SUS CURSOS
    // ======================================================

    $stmt = $pdo->prepare("

        SELECT

            c.id,
            c.nombre,
            c.salon,
            c.horario_inicio,
            c.horario_fin,

            COUNT(ce.estudiante_id)
                AS total_estudiantes

        FROM cursos c

        LEFT JOIN curso_estudiante ce
            ON c.id = ce.curso_id

        WHERE c.catedratico_id = ?

        GROUP BY

            c.id,
            c.nombre,
            c.salon,
            c.horario_inicio,
            c.horario_fin

        ORDER BY c.nombre
    ");

    $stmt->execute([$usuario_id]);

    echo json_encode($stmt->fetchAll());

    exit;
}

    // ======================================================
    // GET ESTUDIANTES
    // ======================================================

    if ($action === 'get_estudiantes') {

        $curso_id = (int)($_GET['curso_id'] ?? 0);

        if ($curso_id <= 0) {

            echo json_encode([
                'error' => 'curso_id inválido'
            ]);

            exit;
        }

        $stmt = $pdo->prepare("
            SELECT

                u.id,
                u.nombre,
                u.apellidos,
                u.correo,
                u.foto,

                EXISTS(
                    SELECT 1
                    FROM detecciones d
                    WHERE d.estudiante_id = u.id
                    AND d.curso_id = ce.curso_id
                    AND DATE(d.fecha_hora) = CURRENT_DATE
                ) AS presente,

                (
                    SELECT TO_CHAR(d.fecha_hora, 'HH24:MI:SS')
                    FROM detecciones d
                    WHERE d.estudiante_id = u.id
                    AND d.curso_id = ce.curso_id
                    AND DATE(d.fecha_hora) = CURRENT_DATE
                    LIMIT 1
                ) AS hora_registro

            FROM curso_estudiante ce

            INNER JOIN usuarios u
                ON ce.estudiante_id = u.id

            WHERE ce.curso_id = ?

            ORDER BY u.apellidos, u.nombre
        ");

        $stmt->execute([$curso_id]);

        echo json_encode($stmt->fetchAll());

        exit;
    }

    // ======================================================
    // REGISTRAR DETECCIÓN QR / FACIAL
    // ======================================================

    if ($action === 'registrar_qr') {

        $estudiante_id = (int)($_POST['usuario_id'] ?? 0);

        $curso_id = (int)($_POST['curso_id'] ?? 0);

        $tipo = $_POST['tipo'] ?? 'QR';

        $confianza = $_POST['confianza'] ?? null;

        if ($estudiante_id <= 0 || $curso_id <= 0) {

            echo json_encode([
                'error' => 'Datos inválidos'
            ]);

            exit;
        }

        // ======================================================
        // VERIFICAR SI YA FUE DETECTADO HOY
        // ======================================================

        $stmt = $pdo->prepare("
            SELECT id
            FROM detecciones
            WHERE estudiante_id = ?
            AND curso_id = ?
            AND DATE(fecha_hora) = CURRENT_DATE
            LIMIT 1
        ");

        $stmt->execute([
            $estudiante_id,
            $curso_id
        ]);

        if ($stmt->fetch()) {

            echo json_encode([
                'error' => 'El estudiante ya fue detectado hoy'
            ]);

            exit;
        }

        // ======================================================
        // GUARDAR DETECCIÓN
        // ======================================================

        $stmt = $pdo->prepare("
            INSERT INTO detecciones (

                estudiante_id,
                curso_id,
                fecha_hora,
                tipo,
                confianza

            )
            VALUES (

                ?,
                ?,
                NOW(),
                ?,
                ?
            )
        ");

        $stmt->execute([

            $estudiante_id,
            $curso_id,
            $tipo,
            $confianza
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Detección registrada correctamente'
        ]);

        exit;
    }

    // ======================================================
    // CONFIRMAR ASISTENCIA OFICIAL
    // ======================================================

    if ($action === 'guardar_asistencia') {

        $json = file_get_contents('php://input');

        $data = json_decode($json, true);

        if (!$data) {

            throw new Exception('JSON inválido');
        }

        $curso_id = (int)($data['curso_id'] ?? 0);

        $fecha = $data['fecha'] ?? date('Y-m-d');

        $catedratico_id = (int)(
            $data['catedratico_id']
            ?? $_SESSION['usuario_id']
            ?? 0
        );

        if ($curso_id <= 0) {

            throw new Exception('Curso inválido');
        }

        $pdo->beginTransaction();

        // ======================================================
        // VERIFICAR SI YA EXISTE
        // ======================================================

        $stmt = $pdo->prepare("
            SELECT id
            FROM asistencias
            WHERE curso_id = ?
            AND fecha = ?
            LIMIT 1
        ");

        $stmt->execute([
            $curso_id,
            $fecha
        ]);

        $row = $stmt->fetch();

        if ($row) {

            $asistencia_id = $row['id'];

            // Limpiar detalles anteriores

            $pdo->prepare("
                DELETE FROM asistencia_detalle
                WHERE asistencia_id = ?
            ")->execute([$asistencia_id]);

        } else {

            // ======================================================
            // CREAR CABECERA
            // ======================================================

            $stmt = $pdo->prepare("
                INSERT INTO asistencias (

                    curso_id,
                    fecha,
                    catedratico_id

                )
                VALUES (?, ?, ?)
                RETURNING id
            ");

            $stmt->execute([

                $curso_id,
                $fecha,
                $catedratico_id
            ]);

            $asistencia_id = $stmt->fetchColumn();
        }

        // ======================================================
        // OBTENER TODOS LOS ESTUDIANTES
        // ======================================================

        $stmt = $pdo->prepare("
            SELECT
                u.id,

                EXISTS(
                    SELECT 1
                    FROM detecciones d
                    WHERE d.estudiante_id = u.id
                    AND d.curso_id = ce.curso_id
                    AND DATE(d.fecha_hora) = ?
                ) AS presente,

                (
                    SELECT d.fecha_hora
                    FROM detecciones d
                    WHERE d.estudiante_id = u.id
                    AND d.curso_id = ce.curso_id
                    AND DATE(d.fecha_hora) = ?
                    LIMIT 1
                ) AS hora_registro

            FROM curso_estudiante ce

            INNER JOIN usuarios u
                ON ce.estudiante_id = u.id

            WHERE ce.curso_id = ?
        ");

        $stmt->execute([
            $fecha,
            $fecha,
            $curso_id
        ]);

        $estudiantes = $stmt->fetchAll();

        // ======================================================
        // INSERTAR DETALLE OFICIAL
        // ======================================================

        $stmtDet = $pdo->prepare("
            INSERT INTO asistencia_detalle (

                asistencia_id,
                estudiante_id,
                presente,
                hora_registro

            )
            VALUES (?, ?, ?, ?)
        ");

        foreach ($estudiantes as $e) {

            $stmtDet->execute([

                $asistencia_id,

                $e['id'],

                $e['presente'] ? 1 : 0,

                $e['hora_registro']
            ]);
        }

        $pdo->commit();

        echo json_encode([

            'success' => true,

            'message' => 'Asistencia oficial confirmada correctamente',

            'pdf' =>
                'generar_pdf.php?curso_id=' .
                $curso_id .
                '&fecha=' .
                $fecha
        ]);

        exit;
    }

    // ======================================================
    // GET CATEDRÁTICOS
    // ======================================================

    if ($action === 'get_catedraticos') {

        $stmt = $pdo->query("
            SELECT
                id,
                nombre || ' ' || apellidos AS nombre_usuario
            FROM usuarios
            WHERE rol_id = 3
            ORDER BY nombre
        ");

        echo json_encode($stmt->fetchAll());

        exit;
    }

    // ======================================================
    // ACCIÓN INVÁLIDA
    // ======================================================

    echo json_encode([
        'error' => 'Acción no válida'
    ]);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {

        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        'error' => $e->getMessage()
    ]);
}