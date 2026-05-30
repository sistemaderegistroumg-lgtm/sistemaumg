<?php

session_start();

require_once 'config.php';

require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

if(!isset($_SESSION['usuario_id'])){

    die('Sesión inválida');
}

$pdo = getDB();

// =====================================================
// PARÁMETROS
// =====================================================

$reporte =
    $_GET['reporte'] ?? 0;

$fecha =
    $_GET['fecha'] ?? date('Y-m-d');

$salon =
    $_GET['salon'] ?? '';

// =====================================================
// CONSULTAS
// =====================================================

$query = "";

$params = [];

$titulo = "Reporte";

if($reporte == 1){

    $titulo =
        'Historico_Puerta';

    $query = "

        SELECT

            u.nombre,
            u.apellidos,
            u.correo,

            TO_CHAR(
                d.fecha_hora,
                'DD/MM/YYYY HH24:MI:SS'
            ) AS hora

        FROM detecciones d

        INNER JOIN usuarios u
            ON u.id = d.estudiante_id

        ORDER BY d.fecha_hora DESC

    ";
}

elseif($reporte == 2){

    $titulo =
        'Ingresos_Fecha';

    $query = "

        SELECT

            u.nombre,
            u.apellidos,
            u.correo,

            TO_CHAR(
                d.fecha_hora,
                'HH24:MI:SS'
            ) AS hora

        FROM detecciones d

        INNER JOIN usuarios u
            ON u.id = d.estudiante_id

        WHERE DATE(d.fecha_hora)=?

        ORDER BY d.fecha_hora ASC

    ";

    $params[] = $fecha;
}

elseif($reporte == 3){

    $titulo =
        'Historico_Salon';

    $query = "

        SELECT

            u.nombre,
            u.apellidos,
            u.correo,

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

    ";

    $params[] = $salon;
}

elseif($reporte == 4){

    $titulo =
        'Salon_Fecha';

    $query = "

        SELECT

            u.nombre,
            u.apellidos,
            u.correo,

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

    ";

    $params[] = $salon;
    $params[] = $fecha;
}

elseif($reporte == 5){

    $titulo =
        'Estadisticas';

    $query = "

        SELECT

            COUNT(*) AS total_ingresos

        FROM detecciones

        WHERE DATE(fecha_hora)=CURRENT_DATE

    ";
}

// =====================================================
// EJECUTAR QUERY
// =====================================================

$stmt = $pdo->prepare($query);

$stmt->execute($params);

$datos =
    $stmt->fetchAll(PDO::FETCH_ASSOC);

// =====================================================
// CREAR EXCEL
// =====================================================

$excel = new Spreadsheet();

$sheet =
    $excel->getActiveSheet();

$sheet->setTitle(
    'Reporte UMG'
);

// =====================================================
// LOGO UMG
// =====================================================

if(file_exists('logo_umg.png')){

    $drawing = new Drawing();

    $drawing->setName(
        'Logo UMG'
    );

    $drawing->setDescription(
        'Universidad Mariano Gálvez'
    );

    $drawing->setPath(
        'logo_umg.png'
    );

    $drawing->setHeight(80);

    $drawing->setCoordinates(
        'A1'
    );

    $drawing->setWorksheet(
        $sheet
    );
}

// =====================================================
// ENCABEZADOS
// =====================================================

$sheet->mergeCells('C1:F1');

$sheet->setCellValue(
    'C1',
    'UNIVERSIDAD MARIANO GÁLVEZ'
);

$sheet->mergeCells('C2:F2');

$sheet->setCellValue(
    'C2',
    'REPORTE DE ASISTENCIA'
);

$sheet->mergeCells('C3:F3');

$sheet->setCellValue(
    'C3',
    'Fecha: '.date('d/m/Y H:i:s')
);

// =====================================================
// ESTILOS
// =====================================================

$sheet->getStyle('C1')->getFont()->setBold(true);
$sheet->getStyle('C1')->getFont()->setSize(18);

$sheet->getStyle('C2')->getFont()->setBold(true);
$sheet->getStyle('C2')->getFont()->setSize(14);

$sheet->getStyle('C3')->getFont()->setItalic(true);

// =====================================================
// FILA INICIAL
// =====================================================

$filaEncabezado = 6;

// =====================================================
// ENCABEZADOS TABLA
// =====================================================

if(!empty($datos)){

    $col = 'A';

    foreach(array_keys($datos[0]) as $campo){

        $sheet->setCellValue(
            $col.$filaEncabezado,
            strtoupper($campo)
        );

        $sheet->getStyle(
            $col.$filaEncabezado
        )->getFont()->setBold(true);

        $col++;
    }

    // =================================================
    // DATOS
    // =================================================

    $fila = $filaEncabezado + 1;

    foreach($datos as $dato){

        $col = 'A';

        foreach($dato as $valor){

            $sheet->setCellValue(
                $col.$fila,
                $valor
            );

            $col++;
        }

        $fila++;
    }

    // =================================================
    // AUTO SIZE
    // =================================================

    foreach(range('A','G') as $column){

        $sheet->getColumnDimension(
            $column
        )->setAutoSize(true);
    }
}

// =====================================================
// NOMBRE ARCHIVO
// =====================================================

$archivo =

    $titulo.'_'.date('Ymd_His').'.xlsx';

// =====================================================
// HEADERS
// =====================================================

header(
    'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
);

header(
    'Content-Disposition: attachment; filename="'.$archivo.'"'
);

header(
    'Cache-Control: max-age=0'
);

// =====================================================
// DESCARGAR
// =====================================================

$writer =
    new Xlsx($excel);

$writer->save('php://output');

exit;

?>

