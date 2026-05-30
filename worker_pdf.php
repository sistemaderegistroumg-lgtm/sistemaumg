<?php

// ======================================================
// OCULTAR WARNINGS TCPDF PHP 8
// ======================================================
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);
ini_set('display_errors', 0);

// ======================================================
// ARCHIVOS
// ======================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/funciones.php';

// ======================================================
// PHPMailer
// ======================================================
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';
require_once __DIR__ . '/phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ======================================================
// INICIO
// ======================================================
echo "============================\n";
echo "WORKER UMG PDF + EMAIL\n";
echo "============================\n\n";

// ======================================================
// CARPETA JOBS
// ======================================================
$jobDir = __DIR__ . '/jobs_pdf/';

if (!is_dir($jobDir)) {
    die("❌ No existe carpeta jobs_pdf\n");
}

// ======================================================
// LEER JSONS
// ======================================================
$files = glob($jobDir . '*.json');

echo "Archivos en cola: " . count($files) . "\n\n";

if (!$files) {
    die("No hay archivos en cola\n");
}

// ======================================================
// RECORRER JOBS
// ======================================================
foreach ($files as $file) {

    echo "----------------------------\n";
    echo "Procesando: $file\n";

    // ==================================================
    // LEER JSON
    // ==================================================
    $data = json_decode(
        file_get_contents($file),
        true
    );

    if (!$data) {
        echo "❌ JSON inválido\n";
        unlink($file);
        continue;
    }

    try {

        // ==================================================
        // VALIDAR HASH
        // ==================================================
        if (empty($data['hash_certificado'])) {
            throw new Exception("No existe hash_certificado");
        }

        // ==================================================
        // MOSTRAR HASH
        // ==================================================
        echo "🔐 HASH: " . $data['hash_certificado'] . "\n";

        // ==================================================
        // GENERAR PDF
        // ==================================================
        echo "📄 Generando PDF...\n";

        $pdfPath = generarCarnetPDF($data);

        echo "✔ PDF generado\n";

        // ==================================================
        // VALIDAR PDF
        // ==================================================
        if (!file_exists($pdfPath)) {
            throw new Exception("No se encontró el PDF");
        }

        // ==================================================
        // ENVIAR EMAIL
        // ==================================================
        echo "📧 Enviando correo...\n";

        $mail = new PHPMailer(true);

        // ==================================================
        // SMTP CONFIG
        // ==================================================
        $mail->isSMTP();

        $mail->Host = 'smtp.gmail.com';

        $mail->SMTPAuth = true;

        $mail->Username =
            'sistemaderegistroumg@gmail.com';

        $mail->Password =
            'bwnzxmrzkxtfride';

        $mail->SMTPSecure =
            PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port = 587;

        $mail->CharSet = 'UTF-8';

        $mail->Timeout = 60;

        // ==================================================
        // SSL FIX XAMPP
        // ==================================================
        $mail->SMTPOptions = [

            'ssl' => [

                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true

            ]

        ];

        $mail->SMTPDebug = 0;

        // ==================================================
        // REMITENTE
        // ==================================================
        $mail->setFrom(
            'sistemaderegistroumg@gmail.com',
            'Universidad Mariano Gálvez'
        );

        // ==================================================
        // DESTINATARIO
        // ==================================================
        $mail->addAddress(
            $data['correo'],
            $data['nombre']
        );

        // ==================================================
        // ADJUNTO
        // ==================================================
        $mail->addAttachment(
            $pdfPath,
            'Carnet_UMG.pdf'
        );

        // ==================================================
        // ASUNTO
        // ==================================================
        $mail->Subject = 'Carnet Digital UMG';

        $mail->isHTML(true);

        // ==================================================
        // BODY HTML
        // ==================================================
        $mail->Body = '

        <div style="font-family: Arial;">

            <h2 style="color:#003399;">
                Universidad Mariano Gálvez
            </h2>

            <p>
                Estimado(a)
                <b>' .
                    $data['nombre'] . ' ' .
                    $data['apellidos'] .
                '</b>
            </p>

            <p>
                Su carnet digital institucional
                fue generado correctamente.
            </p>

            <p>
                El documento incluye:
                Firma digital,
                Código QR y
                Hash de validación.
            </p>

            <p>
                <b>HASH:</b><br>
                ' . $data['hash_certificado'] . '
            </p>

            <p>
                Adjuntamos su carnet PDF.
            </p>

            <hr>

            <small>
                Universidad Mariano Gálvez de Guatemala
            </small>

        </div>';

        // ==================================================
        // TEXTO PLANO
        // ==================================================
        $mail->AltBody =
            "Universidad Mariano Gálvez\n\n" .
            "Su carnet digital fue generado.\n" .
            "HASH: " .
            $data['hash_certificado'];

        // ==================================================
        // ENVIAR
        // ==================================================
        $mail->send();

        echo "✔ Correo enviado\n";

        // ==================================================
        // ELIMINAR JOB
        // ==================================================
        unlink($file);

        echo "✔ Archivo eliminado de cola\n";

    } catch (Throwable $e) {

        echo "❌ ERROR: " .
             $e->getMessage() .
             "\n";
    }

    echo "\n";
}

// ======================================================
// FIN
// ======================================================
echo "============================\n";
echo "PROCESO TERMINADO\n";
echo "============================\n";
?>