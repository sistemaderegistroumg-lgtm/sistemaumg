<?php

require_once __DIR__ . '/tcpdf/tcpdf.php';

function generarCarnetPDF($data, $pdo = null) {

    $logo = __DIR__ . '/logo_umg.png';
    $pem  = __DIR__ . '/umg.pem';

    if (!file_exists($logo)) {
        throw new Exception("No existe logo_umg.png");
    }

    // =========================
    // HASH
    // =========================
    $hashCert = $data['hash_certificado'] ?? null;

    if (!$hashCert && $pdo) {
        $stmt = $pdo->prepare("
            SELECT hash_certificado
            FROM certificados
            WHERE usuario_id = ?
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$data['id']]);
        $hashCert = $stmt->fetchColumn();
    }

    if (!$hashCert) {
        throw new Exception("No existe hash_certificado");
    }

    // =========================
    // PDF CONFIG
    // =========================
    $pdf = new TCPDF('L', 'mm', [85, 54], true, 'UTF-8', false);
    
    // CAMBIO CLAVE: Guardamos el hash en las palabras clave del documento
    $pdf->SetCreator('UMG Sistema de Certificados');
    $pdf->SetAuthor('Universidad Mariano Gálvez');
    $pdf->SetTitle('Carnet Estudiantil Digital');
    $pdf->SetKeywords($hashCert); // <-- Guardado estratégico para la extracción directa

    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false, 0);
    $pdf->AddPage();

    // =========================
    // FONDO
    // =========================
    $pdf->SetFillColor(15, 32, 80);
    $pdf->Rect(0, 0, 85, 54, 'F');

    // tarjeta central
    $pdf->SetFillColor(245, 247, 255);
    $pdf->RoundedRect(2, 2, 81, 50, 3, '1111', 'F');

    // =========================
    // HEADER
    // =========================
    $pdf->SetFillColor(0, 45, 120);
    $pdf->RoundedRect(2, 2, 81, 10, 3, '1111', 'F');

    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 7);

    $pdf->SetXY(2, 4);
    $pdf->Cell(81, 4, 'UNIVERSIDAD MARIANO GÁLVEZ DE GUATEMALA', 0, 0, 'C');

    // =========================
    // LOGO
    // =========================
    $pdf->Image($logo, 4, 13, 10);

    // =========================
    // FOTO (CORREGIDA, MÁS PEQUEÑA)
    // =========================
    $foto = __DIR__ . '/' . ($data['foto'] ?? '');

    if (!empty($data['foto']) && file_exists($foto)) {
        $pdf->SetDrawColor(0, 45, 120);
        $pdf->SetLineWidth(0.4);

        // círculo más pequeño
        $pdf->Circle(12, 30, 8, 0, 360, 'D');

        // foto más pequeña
        $pdf->Image($foto, 4.5, 22.5, 15, 15);
    }

    // =========================
    // DATOS (COMPACTOS Y LIMPIOS)
    // =========================
    $nombre = strtoupper(($data['nombre'] ?? '') . ' ' . ($data['apellidos'] ?? ''));

    $pdf->SetTextColor(20, 20, 20);
    $pdf->SetFont('helvetica', 'B', 7);

    $pdf->SetXY(22, 14);
    $pdf->Cell(60, 3, $nombre, 0, 1);

    $pdf->SetFont('helvetica', '', 5);

    $pdf->SetXY(22, 18);
    $pdf->Cell(60, 3, 'Carrera: ' . ($data['carrera'] ?? ''), 0, 1);

    $pdf->SetXY(22, 21);
    $pdf->Cell(60, 3, 'Semestre: ' . ($data['semestre'] ?? ''), 0, 1);

    $pdf->SetXY(22, 24);
    $pdf->Cell(60, 3, 'Sección: ' . ($data['seccion'] ?? ''), 0, 1);

    $pdf->SetXY(22, 27);
    $pdf->Cell(60, 3, 'ID: ' . ($data['id'] ?? ''), 0, 1);

    // =========================
    // HASH (VISIBLE LIMPIO)
    // =========================
    $pdf->SetFillColor(230, 235, 255);
    $pdf->RoundedRect(3, 33, 52, 8, 2, '1111', 'F');

    $pdf->SetTextColor(0, 45, 120);
    $pdf->SetFont('helvetica', 'B', 5);

    $pdf->SetXY(4, 34);
    $pdf->Cell(50, 3, 'HASH DE VERIFICACIÓN', 0, 1);

    $pdf->SetFont('helvetica', '', 4.8);
    $pdf->SetXY(4, 37);
    $pdf->Cell(50, 3, substr($hashCert, 0, 28) . '...', 0, 1);

    // =========================
    // QR
    // =========================
    $url = "http://localhost/proyecto_umg_2/verificar_certificado.php?hash=" . $hashCert;
    $qr = "CARNET UMG\nHASH:\n" . $hashCert . "\n\nVERIFICAR:\n" . $url;

    $pdf->write2DBarcode($qr, 'QRCODE,H', 62, 22, 18, 18);

    $pdf->SetFont('helvetica', '', 4);
    $pdf->SetXY(60, 41);
    $pdf->MultiCell(20, 3, 'ESCANEAR PARA VALIDAR', 0, 'C');

    // =========================
    // FIRMA DIGITAL (SI EXISTE PEM)
    // =========================
    if (file_exists($pem)) {
        try {
            $pdf->setSignature(
                'file://' . $pem,
                'file://' . $pem,
                '',
                '',
                2,
                [
                    'Name' => 'Universidad Mariano Gálvez',
                    'Location' => 'Guatemala',
                    'Reason' => 'Documento Oficial Firmado Digitalmente',
                    'ContactInfo' => 'UMG'
                ]
            );
        } catch (Throwable $e) {
            error_log("Firma error: " . $e->getMessage());
        }
    }

    // =========================
    // FOOTER (FIRMA CERTIFICADA)
    // =========================
    $pdf->SetFillColor(0, 45, 120);
    $pdf->RoundedRect(2, 46, 81, 6, 3, '1111', 'F');

    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 5);

    $pdf->SetXY(4, 47);
    $pdf->Cell(45, 3, 'FIRMA DIGITAL CERTIFICADA', 0, 0);

    $pdf->SetFont('helvetica', '', 4);
    $pdf->SetXY(4, 49);
    $pdf->Cell(45, 2, '✔ Documento verificado criptográficamente', 0, 0);

    $pdf->SetXY(55, 47);
    $pdf->Cell(25, 3, date("d/m/Y H:i"), 0, 0, 'R');

    // =========================
    // GUARDAR
    // =========================
    $dir = __DIR__ . '/carnets/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $file = $dir . 'carnet_' . $data['id'] . '.pdf';
    $pdf->Output($file, 'F');

    if (!file_exists($file)) {
        throw new Exception("Error generando PDF");
    }

    return $file;
}