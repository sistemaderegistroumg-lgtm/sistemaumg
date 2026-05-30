<?php

session_start();

require_once 'config.php';

header('Content-Type: application/json');

// =====================================================
// VALIDAR SESIÓN
// =====================================================

if(!isset($_SESSION['usuario_id'])){

    echo json_encode([
        'success' => false,
        'message' => 'Sesión inválida'
    ]);

    exit;
}

$pdo = getDB();

// =====================================================
// LEER JSON
// =====================================================

$data = json_decode(
    file_get_contents("php://input"),
    true
);

$reporte =
    $data['reporte'] ?? 0;

$fecha =
    $data['fecha'] ?? date('Y-m-d');

$salon =
    $data['salon'] ?? '';

$puerta =
    $data['puerta'] ?? '';

$instalacion =
    $data['instalacion'] ?? '';

try{

    // =====================================================
    // REPORTE 1
    // HISTÓRICO POR PUERTA
    // =====================================================

    if($reporte == 1){

        $stmt = $pdo->query("

            SELECT

                u.nombre,
                u.apellidos,
                u.correo,
                u.foto,

                d.puerta,

                TO_CHAR(
                    d.fecha_hora,
                    'DD/MM/YYYY HH24:MI:SS'
                ) AS hora

            FROM detecciones d

            INNER JOIN usuarios u
                ON u.id = d.estudiante_id

            ORDER BY d.fecha_hora DESC

            LIMIT 100

        ");

        $reporteData =
            $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([

            'success' => true,

            'reporte' => $reporteData
        ]);

        exit;
    }

    // =====================================================
    // REPORTE 2
    // INGRESOS POR FECHA Y PUERTA
    // =====================================================

    if($reporte == 2){

        $stmt = $pdo->prepare("

            SELECT

                u.nombre,
                u.apellidos,
                u.correo,
                u.foto,

                d.puerta,

                TO_CHAR(
                    d.fecha_hora,
                    'HH24:MI:SS'
                ) AS hora

            FROM detecciones d

            INNER JOIN usuarios u
                ON u.id = d.estudiante_id

            WHERE DATE(d.fecha_hora)=?

            ORDER BY d.fecha_hora ASC

        ");

        $stmt->execute([$fecha]);

        $reporteData =
            $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([

            'success' => true,

            'reporte' => $reporteData
        ]);

        exit;
    }

    // =====================================================
    // REPORTE 3
    // HISTÓRICO POR SALÓN
    // =====================================================

    if($reporte == 3){

        $stmt = $pdo->prepare("

            SELECT

                u.nombre,
                u.apellidos,
                u.correo,
                u.foto,

                c.nombre AS curso,
                c.salon,

                TO_CHAR(
                    d.fecha_hora,
                    'DD/MM/YYYY HH24:MI:SS'
                ) AS hora

            FROM detecciones d

            INNER JOIN usuarios u
                ON u.id = d.estudiante_id

            INNER JOIN cursos c
                ON c.id = d.curso_id

            WHERE c.salon = ?

            ORDER BY d.fecha_hora DESC

        ");

        $stmt->execute([$salon]);

        $reporteData =
            $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([

            'success' => true,

            'reporte' => $reporteData
        ]);

        exit;
    }

    // =====================================================
    // REPORTE 4
    // SALÓN POR FECHA
    // =====================================================

    if($reporte == 4){

        $stmt = $pdo->prepare("

            SELECT

                u.nombre,
                u.apellidos,
                u.correo,
                u.foto,

                c.nombre AS curso,
                c.salon,

                TO_CHAR(
                    d.fecha_hora,
                    'HH24:MI:SS'
                ) AS hora

            FROM detecciones d

            INNER JOIN usuarios u
                ON u.id = d.estudiante_id

            INNER JOIN cursos c
                ON c.id = d.curso_id

            WHERE c.salon = ?
            AND DATE(d.fecha_hora)=?

            ORDER BY d.fecha_hora ASC

        ");

        $stmt->execute([
            $salon,
            $fecha
        ]);

        $reporteData =
            $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([

            'success' => true,

            'reporte' => $reporteData
        ]);

        exit;
    }

    // =====================================================
    // REPORTE 5
    // ESTADÍSTICAS
    // =====================================================

    if($reporte == 5){

        // TOTAL INGRESOS HOY
        $stmt = $pdo->query("

            SELECT COUNT(*) AS total

            FROM detecciones

            WHERE DATE(fecha_hora)=CURRENT_DATE

        ");

        $totalHoy =
            $stmt->fetch()['total'];

        // TOTAL CURSOS
        $stmt2 = $pdo->query("

            SELECT COUNT(*) AS total

            FROM cursos

        ");

        $totalCursos =
            $stmt2->fetch()['total'];

        // TOTAL USUARIOS
        $stmt3 = $pdo->query("

            SELECT COUNT(*) AS total

            FROM usuarios

        ");

        $totalUsuarios =
            $stmt3->fetch()['total'];

        $reporteData = [

            [
                'nombre' => 'Ingresos Hoy',
                'correo' => 'Sistema',
                'foto'   => 'logo_umg.png',
                'hora'   => $totalHoy
            ],

            [
                'nombre' => 'Cursos',
                'correo' => 'Sistema',
                'foto'   => 'logo_umg.png',
                'hora'   => $totalCursos
            ],

            [
                'nombre' => 'Usuarios',
                'correo' => 'Sistema',
                'foto'   => 'logo_umg.png',
                'hora'   => $totalUsuarios
            ]
        ];

        echo json_encode([

            'success' => true,

            'reporte' => $reporteData
        ]);

        exit;
    }

    // =====================================================
    // REPORTE INVÁLIDO
    // =====================================================

    echo json_encode([

        'success' => false,

        'message' => 'Reporte inválido'
    ]);

}catch(Exception $e){

    echo json_encode([

        'success' => false,

        'message' => $e->getMessage()
    ]);
}
?>