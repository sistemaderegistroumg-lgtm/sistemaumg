<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/funciones.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';
require_once __DIR__ . '/phpmailer/Exception.php';

header('Content-Type: text/plain; charset=utf-8');

echo "============================\n";
echo "WORKER UMG INICIADO\n";
echo "============================\n\n";

$jobs = __DIR__ . '/jobs_pdf/';

if (!is_dir($jobs)) {
    die("ERROR: No existe carpeta jobs_pdf\n");
}

$files = glob($jobs . '*.json');

echo "Archivos en cola: " . count($files) . "\n\n";

$pdo = getDB(); // 👈 IMPORTANTE

foreach ($files as $file) {

    echo "----------------------------\n";
    echo "Procesando: $file\n";

    $data = json_decode(file_get_contents($file), true);

    if (!$data) {
        echo "❌ JSON inválido\n";
        unlink($file);
        continue;
    }

    try {

        // =========================
        // PDF
        // =========================
        echo "📄 Generando PDF...\n";

        $pdfPath = generarCarnetPDF($data, $pdo); // 👈 FIX

        if (!file_exists($pdfPath)) {
            throw new Exception("PDF no generado");
        }

        echo "✔ PDF generado\n";

        // =========================
        // EMAIL
        // =========================
        echo "📧 Enviando correo...\n";

        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sistemaderegistroumg@gmail.com';
        $mail->Password = 'bwnzxmrzkxtfride';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('sistemaderegistroumg@gmail.com', 'UMG Guatemala');
        $mail->addAddress($data['correo']);

        $mail->isHTML(true);
        $mail->Subject = "Carnet UMG";

        $mail->Body = "Su carnet fue generado correctamente.";

        $mail->addAttachment($pdfPath);

        $mail->send();

        echo "✔ EMAIL OK\n";

    } catch (Throwable $e) {

        echo "❌ ERROR: " . $e->getMessage() . "\n";

        file_put_contents(
            __DIR__ . '/error_log.txt',
            date('Y-m-d H:i:s') . " - " . $e->getMessage() . PHP_EOL,
            FILE_APPEND
        );
    }

    unlink($file);
    echo "✔ Eliminado\n";
}

echo "\nFIN\n";