<?php

session_start();

require_once 'config.php';
require_once 'vendor/autoload.php';

// ======================================================
// PHPMailer MANUAL
// ======================================================
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';
require_once __DIR__ . '/phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

use Dompdf\Dompdf;
use Dompdf\Options;

header('Content-Type: application/json; charset=utf-8');

// ======================================================
// VALIDAR SESIÓN
// ======================================================
if (!isset($_SESSION['usuario_id'])) {

    echo json_encode([
        'success' => false,
        'message' => 'Sesión inválida'
    ]);

    exit;
}

// ======================================================
// LEER JSON
// ======================================================
$json = file_get_contents("php://input");

if (!$json) {

    echo json_encode([
        'success' => false,
        'message' => 'JSON vacío'
    ]);

    exit;
}

$data = json_decode($json, true);

if (!$data) {

    echo json_encode([
        'success' => false,
        'message' => 'JSON inválido'
    ]);

    exit;
}

if (!isset($data['curso_id'])) {

    echo json_encode([
        'success' => false,
        'message' => 'Curso inválido'
    ]);

    exit;
}

$curso_id = (int)$data['curso_id'];

try {

    $pdo = getDB();

    // ======================================================
    // OBTENER CURSO
    // ======================================================
    $stmtCurso = $pdo->prepare("
        SELECT
            c.nombre,
            c.salon,
            u.correo

        FROM cursos c

        INNER JOIN usuarios u
            ON c.catedratico_id = u.id

        WHERE c.id = ?
    ");

    $stmtCurso->execute([$curso_id]);

    $curso = $stmtCurso->fetch(PDO::FETCH_ASSOC);

    if (!$curso) {

        echo json_encode([
            'success' => false,
            'message' => 'Curso no encontrado'
        ]);

        exit;
    }

    // ======================================================
    // ESTUDIANTES PRESENTES
    // ======================================================
    $stmt = $pdo->prepare("
        SELECT
            u.nombre,
            u.apellidos,
            u.correo,
            d.fecha_hora

        FROM detecciones d

        INNER JOIN usuarios u
            ON u.id = d.estudiante_id

        WHERE d.curso_id = ?
        AND DATE(d.fecha_hora) = CURRENT_DATE
        AND u.rol_id = 2

        ORDER BY d.fecha_hora ASC
    ");

    $stmt->execute([$curso_id]);

    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = count($estudiantes);

    // ======================================================
    // HTML PDF
    // ======================================================
    $html = '

    <style>

    body{
        font-family: Arial;
        color:#0f172a;
    }

    .header{
        background:#0f172a;
        color:white;
        padding:25px;
        border-radius:10px;
    }

    .title{
        font-size:28px;
        font-weight:bold;
    }

    .card{
        margin-top:20px;
        background:#f1f5f9;
        padding:20px;
        border-radius:10px;
    }

    .total{
        font-size:40px;
        color:#22c55e;
        font-weight:bold;
    }

    table{
        width:100%;
        border-collapse:collapse;
        margin-top:25px;
    }

    th{
        background:#0f172a;
        color:white;
        padding:12px;
        text-align:left;
    }

    td{
        padding:10px;
        border-bottom:1px solid #ddd;
    }

    </style>

    <div class="header">

        <div class="title">
            Dashboard de Asistencia
        </div>

        <br>

        Curso:
        '.$curso['nombre'].'<br>

        Salón:
        '.$curso['salon'].'<br>

        Fecha:
        '.date('d/m/Y').'

    </div>

    <div class="card">

        <div class="total">
            '.$total.'
        </div>

        <div>
            Estudiantes presentes
        </div>

    </div>

    <table>

        <thead>

            <tr>
                <th>#</th>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Hora</th>
            </tr>

        </thead>

        <tbody>
    ';

    $i = 1;

    foreach ($estudiantes as $est) {

        $html .= '

        <tr>

            <td>'.$i++.'</td>

            <td>
                '.$est['nombre'].' '.$est['apellidos'].'
            </td>

            <td>
                '.$est['correo'].'
            </td>

            <td>
                '.date(
                    'H:i:s',
                    strtotime($est['fecha_hora'])
                ).'
            </td>

        </tr>
        ';
    }

    $html .= '

        </tbody>

    </table>
    ';

    // ======================================================
    // DOMPDF
    // ======================================================
    $options = new Options();

    $options->set(
        'isRemoteEnabled',
        true
    );

    $dompdf = new Dompdf($options);

    $dompdf->loadHtml($html);

    $dompdf->setPaper(
        'A4',
        'portrait'
    );

    $dompdf->render();

    // ======================================================
    // CREAR CARPETA
    // ======================================================
    if (!is_dir('reportes')) {

        mkdir('reportes');
    }

    // ======================================================
    // GUARDAR PDF
    // ======================================================
    $pdfPath =
        'reportes/asistencia.pdf';

    file_put_contents(
        $pdfPath,
        $dompdf->output()
    );

    // ======================================================
    // ENVIAR CORREO
    // ======================================================
    $mail = new PHPMailer(true);

    $mail->isSMTP();

    $mail->Host = 'smtp.gmail.com';

    $mail->SMTPAuth = true;

    $mail->Username =
        'sistemaderegistroumg@gmail.com';

    $mail->Password =
        'bwnzxmrzkxtfride';

    $mail->SMTPSecure = 'tls';

    $mail->Port = 587;

    $mail->CharSet = 'UTF-8';

    $mail->setFrom(
        'sistemaderegistroumg@gmail.com',
        'Sistema UMG'
    );

    $mail->addAddress(
        $curso['correo']
    );

    $mail->Subject =
        'Reporte de asistencia';

    $mail->Body =
        'Adjunto PDF de asistencia del curso.';

    $mail->addAttachment($pdfPath);

    $mail->send();

    echo json_encode([

        'success' => true,

        'message' =>
            'PDF generado y enviado correctamente'
    ]);

} catch (Exception $e) {

    echo json_encode([

        'success' => false,

        'message' => $e->getMessage()
    ]);
}
?>