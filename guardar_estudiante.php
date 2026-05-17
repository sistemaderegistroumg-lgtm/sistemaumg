<?php

session_start();

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

ob_start();

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

try {

    // =========================
    // VALIDAR SESIÓN
    // =========================
    requireSession('Administrador');

    $pdo = getDB();
    $pdo->beginTransaction();

    // =========================
    // DATOS
    // =========================
    $nombre     = trim($_POST['nombre'] ?? '');
    $apellidos  = trim($_POST['apellidos'] ?? '');
    $correo     = trim($_POST['correo'] ?? '');
    $telefono   = trim($_POST['telefono'] ?? '');
    $carrera    = trim($_POST['carrera'] ?? '');
    $semestre   = trim($_POST['semestre'] ?? '');
    $seccion    = trim($_POST['seccion'] ?? '');
    $fotoB64    = $_POST['foto'] ?? '';

    // =========================
    // VALIDACIÓN CAMPOS
    // =========================
    if (
        !$nombre || !$apellidos || !$correo || !$telefono ||
        !$carrera || !$semestre || !$seccion || !$fotoB64
    ) {
        throw new Exception("Todos los campos son obligatorios");
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Correo inválido");
    }

    if (!preg_match('/^[0-9+\-\s]+$/', $telefono)) {
        throw new Exception("Teléfono inválido");
    }

    // =========================
    // DUPLICADO EMAIL
    // =========================
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);

    if ($stmt->fetch()) {
        throw new Exception("El correo ya está registrado");
    }

    // =========================
    // CARPETA FOTOS
    // =========================
    $dirFotos = __DIR__ . '/fotos/';
    if (!is_dir($dirFotos)) {
        mkdir($dirFotos, 0755, true);
    }

    // =========================
    // PROCESAR IMAGEN BASE64
    // =========================
    $img = explode(',', $fotoB64);

    if (!isset($img[1])) {
        throw new Exception("Imagen inválida");
    }

    $dataImg = base64_decode($img[1]);

    if ($dataImg === false) {
        throw new Exception("Error procesando imagen");
    }

    // MIME VALIDATION
    $finfo = finfo_open();
    $mime = finfo_buffer($finfo, $dataImg, FILEINFO_MIME_TYPE);
    finfo_close($finfo);

    $permitidos = ['image/jpeg', 'image/png'];

    if (!in_array($mime, $permitidos)) {
        throw new Exception("Formato de imagen no permitido");
    }

    // EXTENSIÓN DINÁMICA
    $ext = ($mime === 'image/png') ? 'png' : 'jpg';

    $fotoName = 'foto_' . bin2hex(random_bytes(8)) . '.' . $ext;

    file_put_contents($dirFotos . $fotoName, $dataImg);

    $fotoPath = 'fotos/' . $fotoName;

    // =========================
    // INSERTAR USUARIO
    // =========================
    $stmt = $pdo->prepare("
        INSERT INTO usuarios
        (nombre, apellidos, correo, telefono, carrera, semestre, seccion, foto)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        RETURNING id
    ");

    $stmt->execute([
        $nombre,
        $apellidos,
        $correo,
        $telefono,
        $carrera,
        $semestre,
        $seccion,
        $fotoPath
    ]);

    $id = $stmt->fetchColumn();

    if (!$id) {
        throw new Exception("No se pudo obtener el ID del usuario");
    }

    // =========================
    // HASH CERTIFICADO
    // =========================
    $hashCertificado = hash(
        'sha256',
        $id . $nombre . $apellidos . $correo . microtime(true)
    );

    // =========================
    // INSERTAR CERTIFICADO
    // =========================
    $stmtCert = $pdo->prepare("
        INSERT INTO certificados
        (usuario_id, hash_certificado, estado, fecha_emision)
        VALUES (?, ?, 'VALIDO', NOW())
    ");

    $stmtCert->execute([$id, $hashCertificado]);

    // =========================
    // COMMIT
    // =========================
    $pdo->commit();

    // =========================
    // JOB PARA WORKER PDF
    // =========================
    $jobDir = __DIR__ . '/jobs_pdf/';
    if (!is_dir($jobDir)) {
        mkdir($jobDir, 0755, true);
    }

    $jobData = [
        'id' => $id,
        'nombre' => $nombre,
        'apellidos' => $apellidos,
        'correo' => $correo,
        'telefono' => $telefono,
        'carrera' => $carrera,
        'semestre' => $semestre,
        'seccion' => $seccion,
        'foto' => $fotoPath,
        'hash_certificado' => $hashCertificado
    ];

    file_put_contents(
        $jobDir . $id . '.json',
        json_encode($jobData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );

    // =========================
    // MENSAJE WHATSAPP
    // =========================
    $mensaje =
        "UNIVERSIDAD MARIANO GÁLVEZ DE GUATEMALA\n\n" .
        "Estimado(a) $nombre $apellidos,\n\n" .
        "Su registro fue procesado correctamente.\n\n" .
        "ID: $id\n" .
        "Carrera: $carrera\n" .
        "Semestre: $semestre\n" .
        "Sección: $seccion\n\n" .
        "✔ Firma digital\n✔ QR de validación\n✔ Hash de seguridad\n\n" .
        "UMG Guatemala";

    $telefonoClean = preg_replace('/[^0-9]/', '', $telefono);

    if (substr($telefonoClean, 0, 3) !== '502') {
        $telefonoClean = '502' . $telefonoClean;
    }

    $whatsapp = "https://wa.me/$telefonoClean?text=" . urlencode($mensaje);

    // =========================
    // RESPUESTA
    // =========================
    ob_clean();

    echo json_encode([
        'success' => true,
        'message' => 'Registro exitoso. Carnet en proceso.',
        'id' => $id,
        'hash' => $hashCertificado,
        'whatsapp' => $whatsapp
    ]);

} catch (Throwable $e) {

    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    ob_clean();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

exit;
?>