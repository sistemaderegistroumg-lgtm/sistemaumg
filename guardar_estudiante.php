<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Limpiar cualquier salida previa
    if (ob_get_length()) {
        ob_clean();
    }

    // Verificar la sesión del administrador
    requireSession(1);

    $pdo = getDB();

    // Sanitización y captura de datos POST
    $nombre    = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $correo    = trim($_POST['correo'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $carrera   = trim($_POST['carrera'] ?? '');
    $semestre  = (int)($_POST['semestre'] ?? 0);
    $seccion   = trim($_POST['seccion'] ?? '');
    $fotoB64   = $_POST['foto'] ?? '';

    // 1. Validación de campos obligatorios
    if (!$nombre || !$apellidos || !$correo || !$carrera || !$semestre || !$seccion || !$fotoB64) {
        echo json_encode([
            'success' => false,
            'message' => 'Todos los campos son obligatorios.'
        ]);
        exit;
    }

    // =================================================================
    // VALIDADOR DE DOMINIO INSTITUCIONAL OBLIGATORIO (@miumg.edu.gt)
    // =================================================================
    // Pasamos el correo a minúsculas para evitar evasiones tipo @Miumg.Edu.Gt
    $correoMinusculas = strtolower($correo);
    if (!str_ends_with($correoMinusculas, '@miumg.edu.gt')) {
        echo json_encode([
            'success' => false,
            'message' => 'Acceso denegado: El correo electrónico debe pertenecer obligatoriamente al dominio institucional @miumg.edu.gt'
        ]);
        exit;
    }
    // =================================================================

    // Directorio de almacenamiento para las fotos
    $fotosDir = __DIR__ . '/fotos/';
    if (!is_dir($fotosDir)) {
        if (!mkdir($fotosDir, 0755, true)) {
            throw new Exception("No se pudo crear el directorio de fotos. Verifique permisos.");
        }
    }

    // Procesamiento y decodificación de la imagen Base64
    if (strpos($fotoB64, ',') === false) {
        echo json_encode([
            'success' => false,
            'message' => 'El formato de la foto no incluye la cabecera Data URL.'
        ]);
        exit;
    }

    $parts = explode(',', $fotoB64);
    if (count($parts) !== 2) {
        echo json_encode([
            'success' => false,
            'message' => 'Formato de foto inválido.'
        ]);
        exit;
    }

    // Reemplazar espacios por signos '+' que se dañan en la transmisión HTTP regular
    $base64Data = str_replace(' ', '+', $parts[1]);
    $fotoData = base64_decode($base64Data, true);
    
    if ($fotoData === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al decodificar los datos binarios de la foto.'
        ]);
        exit;
    }

    // Generar nombre único para el archivo físico
    $nombreFoto = 'foto_' . uniqid('', true) . '.jpg';
    $rutaFoto   = 'fotos/' . $nombreFoto;

    // Guardar archivo
    $resultadoEscritura = file_put_contents($fotosDir . $nombreFoto, $fotoData);
    if ($resultadoEscritura === false) {
        throw new Exception("Error del sistema al escribir el archivo de imagen en el disco.");
    }

    // =================================================================
    // GENERACIÓN DE CONTRASEÑA AUTOMÁTICA Y VERIFICACIÓN
    // =================================================================
    $passwordTextoPlano = $telefono; 
    $passwordEncriptada = password_hash($passwordTextoPlano, PASSWORD_DEFAULT);
    $checkCorreo = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
    $checkCorreo->execute([$correo]);
    
    if ($checkCorreo->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'El correo electrónico ya se encuentra registrado en el sistema.'
        ]);
        exit;
    }

    // Iniciar transacción para asegurar consistencia atómica entre tablas
    $pdo->beginTransaction();

    // =================================================================
    // REGISTRO DEL ESTUDIANTE CON ROL ASIGNADO (rol_id = 2)
    // =================================================================
    $stmt = $pdo->prepare("
        INSERT INTO usuarios 
        (nombre, apellidos, correo, telefono, carrera, semestre, seccion, foto, password, rol_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $nombre,
        $apellidos,
        $correo,
        $telefono,
        $carrera,
        $semestre,
        $seccion,
        $rutaFoto,
        $passwordEncriptada,
        2 // Forzar rol estudiante
    ]);

    $newId = $pdo->lastInsertId();

    // =================================================================
    // GENERACIÓN REAL DE HASH CORREGIDO PARA EXAMEN UMG (SHA-256 EXACTO)
    // =================================================================
    $hashCertificado = hash('sha256', 'UMG_CERT_' . $newId . '_' . $correo . '_' . time());

    // =================================================================
    // INSERTAR EL REGISTRO DEL CERTIFICADO DE UNA VEZ EN LA BASE DE DATOS
    // =================================================================
    $stmtCert = $pdo->prepare("
        INSERT INTO certificados (usuario_id, hash_certificado, estado, fecha_emision)
        VALUES (?, ?, 'VALIDO', NOW())
    ");
    $stmtCert->execute([$newId, $hashCertificado]);

    // Confirmar inserciones concurrentes
    $pdo->commit();

    $correoOk = false;
    session_write_close();

    // =================================================================
    // CREACIÓN DE ARCHIVO JSON PARA LA COLA ASÍNCRONA
    // =================================================================
    $payload = [
        'id'               => $newId,
        'nombre'           => $nombre,
        'apellidos'        => $apellidos,
        'correo'           => $correo,
        'telefono'         => $telefono,
        'hash_certificado' => $hashCertificado, 
        'carrera'          => $carrera,
        'semestre'         => $semestre,
        'seccion'          => $seccion,
        'foto'             => $rutaFoto
    ];

    // Definir directorio de la cola asíncrona
    $jobDir = __DIR__ . '/jobs_pdf/';
    if (!is_dir($jobDir)) {
        mkdir($jobDir, 0777, true);
    }

    // Guardar físicamente el archivo JSON en la cola usando el ID del usuario
    $archivoJsonRuta = $jobDir . 'job_' . $newId . '.json';
    $jsonGuardado = file_put_contents($archivoJsonRuta, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // =================================================================
    // ENVIAR AL WORKER EN TIEMPO REAL (TRIGGER DE GENERACIÓN)
    // =================================================================
    if ($jsonGuardado !== false) {
        try {
            $ch = curl_init('http://127.0.0.1/proyecto_umg_2/worker_pdf.php?ejecucion_inmediata=1');
            $curlOptions = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS     => json_encode($payload)
            ];
            curl_setopt_array($ch, $curlOptions);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $correoOk = true; 
            }
        } catch (Throwable $e) {
            $correoOk = false; 
        }
    }

    // Url opcional de WhatsApp para retorno del JS
    $urlWhatsapp = "https://api.whatsapp.com/send?phone=" . $telefono . "&text=" . urlencode(
"Universidad Mariano Gálvez de Guatemala

Estimado(a) " . $nombre . " " . $apellidos . ",

Le informamos que su registro académico fue realizado correctamente en el sistema institucional.

Su carnet digital universitario se encuentra actualmente en proceso de generación y será enviado a su correo electrónico institucional en breve,si no llega a la bandeja de entrada podria revisar spam.

Atentamente,
Universidad Mariano Gálvez de Guatemala"
);

    // Respuesta final enviada a la vista
    echo json_encode([
        'success'        => true,
        'message'        => 'Estudiante registrado con éxito, certificado insertado en BD, JSON en cola y disparador enviado.',
        'id'             => $newId,
        'correo_enviado' => $correoOk,
        'whatsapp'       => $urlWhatsapp 
    ]);
    exit;

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'ERROR REAL DETECTADO EN EL SERVIDOR',
        'error'   => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine()
    ]);
    exit;
}