<?php

require_once __DIR__ . '/tcpdf/tcpdf.php';

/**
 * Genera el carnet estudiantil digital en formato ID-1 con firma electrónica avanzada.
 * * @param array $data Datos del estudiante
 * @param PDO|null $pdo Conexión a la base de datos
 * @return string Ruta física del archivo generado
 */
function generarCarnetPDF($data, $pdo = null)
{
    $logo = __DIR__ . '/logo_umg.png';
    $pem  = __DIR__ . '/umg.pem';

    if (!file_exists($logo)) {
        throw new Exception("No existe logo_umg.png");
    }

    // =====================================================
    // OBTENCIÓN Y AUTOSINCRONIZACIÓN DEL HASH CON LA BD
    // =====================================================
    $hashCert = $data['hash_certificado'] ?? null;
    $usuarioId = $data['id'] ?? null;

    // Si el hash no es válido, se calcula uno limpio estándar SHA-256
    if (!$hashCert || strlen($hashCert) !== 64 || strpos($hashCert, 'HASH-UMG-FIRM-') !== false) {
        $semilla = 'UMG_CERT_' . ($usuarioId ?? uniqid()) . '_' . ($data['correo'] ?? 'estudiante') . '_' . time();
        $hashCert = hash('sha256', $semilla);
    }

    // BLINDAJE CON LA BASE DE DATOS
    if ($pdo && $usuarioId) {
        try {
            $stmtCheck = $pdo->prepare("SELECT id FROM certificados WHERE usuario_id = ? LIMIT 1");
            $stmtCheck->execute([$usuarioId]);
            $existe = $stmtCheck->fetchColumn();

            if ($existe) {
                $stmtUpdate = $pdo->prepare("
                    UPDATE certificados 
                    SET hash_certificado = ?, estado = 'VALIDO', fecha_emision = NOW() 
                    WHERE usuario_id = ?
                ");
                $stmtUpdate->execute([$hashCert, $usuarioId]);
            } else {
                $stmtInsert = $pdo->prepare("
                    INSERT INTO certificados (usuario_id, hash_certificado, estado, fecha_emision)
                    VALUES (?, ?, 'VALIDO', NOW())
                ");
                $stmtInsert->execute([$usuarioId, $hashCert]);
            }
        } catch (Throwable $e) {
            error_log("Error de sincronización en generarCarnetPDF: " . $e->getMessage());
        }
    }

    // =====================================================
    // CONFIGURACIÓN DEL ESPACIO DE TRABAJO (TCPDF)
    // =====================================================
    $pdf = new TCPDF(
        'L',
        'mm',
        [85, 54], // Dimensiones estándar ID-1
        true,
        'UTF-8',
        false
    );

    $pdf->SetCreator('UMG_SISTEMA');
    $pdf->SetAuthor('Universidad Mariano Gálvez');
    $pdf->SetTitle('Carnet Estudiantil');
    
    $pdf->SetSubject('HASH:' . $hashCert);
    $pdf->SetKeywords($hashCert);

    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false);

    $pdf->AddPage();

    // Fondo General Oscuro
    $pdf->SetFillColor(15, 23, 42);
    $pdf->Rect(0, 0, 85, 54, 'F');

    // Tarjeta Central Blanca
    $pdf->SetFillColor(248, 250, 252);
    $pdf->RoundedRect(2, 2, 81, 50, 3, '1111', 'F');

    // Encabezado Azul Institucional
    $pdf->SetFillColor(11, 61, 145);
    $pdf->RoundedRect(2, 2, 81, 11, 3, '1111', 'F');

    // Logo Institucional
    $pdf->Image($logo, 4, 4, 7, 7);

    // Títulos Superiores
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 6);
    $pdf->SetXY(12, 4);
    $pdf->Cell(65, 3, 'UNIVERSIDAD MARIANO GÁLVEZ', 0, 1, 'C');

    $pdf->SetFont('helvetica', '', 4.8);
    $pdf->SetXY(12, 7.5);
    $pdf->Cell(65, 3, 'Carnet de Identificación Estudiantil', 0, 1, 'C');

    // =====================================================
    // RENDERIZADO DE FOTO DE PERFIL
    // =====================================================
    $foto = __DIR__ . '/' . ($data['foto'] ?? '');

    if (!empty($data['foto']) && file_exists($foto)) {
        $pdf->SetFillColor(255, 255, 255);
        $pdf->RoundedRect(4, 16, 18, 22, 2, '1111', 'F');
        $pdf->SetDrawColor(11, 61, 145);
        $pdf->RoundedRect(4, 16, 18, 22, 2, '1111', 'D');
        $pdf->Image($foto, 5, 17, 16, 20);
    }

    // =====================================================
    // DATOS DE FILIACIÓN ACADÉMICA
    // =====================================================
    $nombreCompleto = strtoupper(trim(($data['nombre'] ?? '') . ' ' . ($data['apellidos'] ?? '')));

    $pdf->SetTextColor(20, 20, 20);
    $pdf->SetFont('helvetica', 'B', 6);
    $pdf->SetXY(25, 16);
    $pdf->MultiCell(54, 4, $nombreCompleto, 0, 'L');

    $campos = [
        ['Etiqueta' => 'Carrera:', 'Valor' => $data['carrera'] ?? '', 'Y' => 24],
        ['Etiqueta' => 'Semestre:', 'Valor' => (isset($data['semestre']) ? $data['semestre'] . '°' : ''), 'Y' => 28],
        ['Etiqueta' => 'Sección:', 'Valor' => $data['seccion'] ?? '', 'Y' => 32],
        ['Etiqueta' => 'ID Usuario:', 'Valor' => $usuarioId ?? '', 'Y' => 36]
    ];

    foreach ($campos as $campo) {
        $pdf->SetFont('helvetica', '', 4.8);
        $pdf->SetXY(25, $campo['Y']);
        $pdf->Cell(13, 3, $campo['Etiqueta'], 0, 0);
        
        $pdf->SetFont('helvetica', 'B', 4.8);
        $pdf->Cell(41, 3, $campo['Valor'], 0, 1, 'L');
    }

    // =====================================================
    // HASH VISUAL INFERIOR
    // =====================================================
    $pdf->SetFillColor(226, 232, 240);
    $pdf->RoundedRect(4, 41, 48, 6, 1.5, '1111', 'F');

    $pdf->SetTextColor(30, 41, 59);
    $pdf->SetFont('helvetica', 'B', 4);
    $pdf->SetXY(5, 41.5);
    $pdf->Cell(46, 2, 'HASH DE VERIFICACIÓN', 0, 1);

    $pdf->SetFont('helvetica', '', 3.5);
    $pdf->SetXY(5, 44);
    $pdf->Cell(46, 2, substr($hashCert, 0, 40) . '...', 0, 1);

    // =====================================================
    // SISTEMA QR DE VALIDACIÓN
    // =====================================================
    $url = "http://localhost/proyecto_umg_2/verificar_certificado.php?hash=" . $hashCert;
    $qrText = "CARNET UMG\n" .
              "ID: " . ($usuarioId ?? '') . "\n" .
              "ESTUDIANTE: " . $nombreCompleto . "\n" .
              "VERIFICACIÓN: " . $url;

    $pdf->write2DBarcode($qrText, 'QRCODE,H', 58, 17, 20, 20);

    $pdf->SetTextColor(80, 80, 80);
    $pdf->SetFont('helvetica', '', 3.5);
    $pdf->SetXY(56, 38);
    $pdf->Cell(24, 2, 'ESCANEAR PARA VALIDAR', 0, 1, 'C');

    // =====================================================
    // PIE DE LA TARJETA
    // =====================================================
    $pdf->SetFillColor(11, 61, 145);
    $pdf->RoundedRect(2, 48, 81, 4, 2, '1111', 'F');

    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 3.8);
    $pdf->SetXY(4, 49);
    $pdf->Cell(52, 2, 'DOCUMENTO CON FIRMA DIGITAL CERTIFICADA', 0, 0);

    $pdf->SetXY(55, 49);
    $pdf->Cell(25, 2, date('d/m/Y H:i'), 0, 0, 'R');

    // =====================================================
    // PROCESAMIENTO DE FIRMA DIGITAL CRIPTOGRÁFICA
    // =====================================================
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
                    'Reason' => 'Firma Electrónica Avanzada de Carnet Oficial',
                    'ContactInfo' => 'Soporte Técnico UMG'
                ]
                // Eliminamos la 'A' para que la firma se incruste de forma estándar y no entre en conflicto con el búfer activo
            );
        } catch (Throwable $e) {
            error_log('Firma digital error: ' . $e->getMessage());
        }
    }

    // Escritura y guardado físico en disco
    $dir = __DIR__ . '/carnets/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $file = $dir . 'carnet_' . ($usuarioId ?? 'temp') . '.pdf';
    
    // Guardar el archivo localmente usando la bandera 'F'
    $pdf->Output($file, 'F');

    if (!file_exists($file)) {
        throw new Exception("Error generando archivo PDF en el servidor");
    }

    return $file;
}

// =====================================================
// ENRUTADOR / PUNTO DE ACCESO API (EJECUCIÓN)
// =====================================================
// Aseguramos que este bloque esté fuera de la función original
try {
    // NOTA: Recuerda alimentar la variable $data con tu payload del Request (Ej: $_POST o json_decode)
    // Para pruebas puedes descomentar una simulación rápida:
    /*
    $data = [
        'id' => 12345,
        'nombre' => 'Susy',
        'apellidos' => 'Sample',
        'correo' => 'ssample@miumg.edu.gt',
        'carrera' => 'Ingeniería en Sistemas',
        'semestre' => 6,
        'seccion' => 'A',
        'foto' => 'foto_temp.png'
    ];
    */

    if (!isset($data)) {
        throw new Exception("No se han recibido los datos del estudiante.");
    }

    // Ejecución de la rutina del PDF
    $rutaArchivoFisico = generarCarnetPDF($data, $pdo ?? null);
    
    // Limpiar cualquier búfer de salida residual que pueda corromper el JSON
    if (ob_get_length()) ob_end_clean();

    // Devolvemos respuesta JSON limpia para React
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'success',
        'message' => 'Carnet generado correctamente',
        'file_url' => 'http://localhost/proyecto_umg_2/carnets/' . basename($rutaArchivoFisico)
    ]);

} catch (Throwable $e) {
    if (ob_get_length()) ob_end_clean();
    
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>