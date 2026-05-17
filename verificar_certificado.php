<?php
require_once "config.php";
$pdo = getDB();

$resultado = "";
$tipo = "";

function logVerificacion($pdo, $id, $estado) {
    $stmt = $pdo->prepare("
        INSERT INTO log_verificaciones (certificado_id, resultado, ip)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$id, $estado, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0']);
}

// =========================================================================
// EXTRACCIÓN GARANTIZADA DE METADATOS DEL PDF
// =========================================================================
function extraerHashDesdePDF($pdfPath) {
    if (!file_exists($pdfPath)) {
        return null;
    }

    $content = file_get_contents($pdfPath);

    // Método principal: Extrae el contenido directo del tag /Keywords inyectado por TCPDF
    if (preg_match('/\/Keywords\s*\(([^)]+)\)/', $content, $matches)) {
        return trim($matches[1]);
    }
    
    // Método Alternativo en UTF-16BE (Por si TCPDF codifica el texto del metadato)
    if (preg_match('/\/Keywords\s*<([0-9a-fA-F]+)>/', $content, $matches)) {
        $hex = $matches[1];
        $str = hex2bin($hex);
        // Quitar bytes de control de UTF-16 si existen
        if (substr($str, 0, 2) == "\xFE\xFF") {
            $str = substr($str, 2);
            $str = mb_convert_encoding($str, 'UTF-8', 'UTF-16BE');
        }
        return trim($str);
    }

    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hash = '';

    // Proceso 1: Si subió un archivo
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['pdf_file']['tmp_name'];
        $fileName = $_FILES['pdf_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExtension !== 'pdf') {
            $resultado = "❌ El archivo debe ser un formato PDF válido.";
            $tipo = "error";
        } else {
            $hash = extraerHashDesdePDF($fileTmpPath);
            if (!$hash) {
                $resultado = "❌ No se pudo encontrar un hash o firma válida en los metadatos del PDF.";
                $tipo = "error";
            }
        }
    } 
    
    // Proceso 2: Si usó el input de texto manual
    if (!$hash && isset($_POST['hash'])) {
        $hash = trim($_POST['hash']);
    }

    // Validación Final en la Base de Datos
    if ($hash) {
        $stmt = $pdo->prepare("
            SELECT id, estado, nombre
            FROM certificados
            WHERE hash_certificado = ?
        ");

        $stmt->execute([$hash]);
        $cert = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cert) {
            $resultado = "❌ Certificado inválido o no registrado en el sistema.";
            $tipo = "error";
        } else {
            if ($cert['estado'] === 'VALIDO') {
                $resultado = "✅ Certificado válido perteneciente a: " . $cert['nombre'];
                $tipo = "success";
            } else {
                $resultado = "⚠ El certificado ingresado está REVOCADO.";
                $tipo = "warning";
            }

            logVerificacion($pdo, $cert['id'], $cert['estado']);
        }
    } elseif (empty($resultado)) {
        $resultado = "❌ Acción requerida: Ingrese un hash o suba un carnet.";
        $tipo = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificador Oficial UMG</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0f172a, #1e3a8a);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin:0;
            color: white;
        }
        .card {
            background: white;
            color: black;
            padding: 30px;
            border-radius: 20px;
            width: 420px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .success { color: #16a34a; background: #e8f5e9; padding: 12px; border-radius: 10px; }
        .error { color: #dc2626; background: #ffebee; padding: 12px; border-radius: 10px; }
        .warning { color: #d97706; background: #fff8e1; padding: 12px; border-radius: 10px; }
        
        .tabs {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
            border-bottom: 2px solid #f1f5f9;
        }
        .tab-btn {
            background: none;
            border: none;
            padding: 10px;
            font-weight: bold;
            color: #94a3b8;
            cursor: pointer;
        }
        .tab-btn.active {
            color: #1e3a8a;
            border-bottom: 2px solid #1e3a8a;
        }
        .pane { display: none; }
        .pane.active { display: block; }

        input[type="text"], input[type="file"] {
            width: 90%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
        }
        button[type="submit"] {
            width: 95%;
            padding: 12px;
            border: none;
            background: #1e3a8a;
            color: white;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
        }
        button[type="submit"]:hover { background: #1d4ed8; }
        hr { border: 0; border-top: 1px solid #f1f5f9; margin: 20px 0; }
    </style>
</head>
<body>

<div class="card">
    <h2>🔐 Verificador de Carnets UMG</h2>

    <div class="tabs">
        <button class="tab-btn active" onclick="switchView('upload-pane', this)">Subir PDF</button>
        <button class="tab-btn" onclick="switchView('text-pane', this)">Escanear QR / Hash</button>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <div id="upload-pane" class="pane active">
            <p style="font-size: 13px; color:#64748b;">Suba el archivo PDF original del carnet universitario:</p>
            <input type="file" name="pdf_file" accept=".pdf">
        </div>

        <div id="text-pane" class="pane">
            <p style="font-size: 13px; color:#64748b;">Ingrese el Hash de verificación del documento:</p>
            <input type="text" id="hash_input" name="hash" placeholder="Ejemplo: d3b07384d113ed1d47...">
        </div>
        <br>
        <button type="submit">Verificar Documento</button>
    </form>

    <hr>

    <?php if($resultado): ?>
        <h3 class="<?= $tipo ?>"><?= $resultado ?></h3>
    <?php else: ?>
        <p style="color: #64748b; font-size:13px;">Seleccione un método para validar la autenticidad del documento.</p>
    <?php endif; ?>
</div>

<script>
function switchView(paneId, btn) {
    document.querySelectorAll('.pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    
    document.getElementById(paneId).classList.add('active');
    btn.classList.add('active');

    const txtInput = document.getElementById('hash_input');
    if(paneId === 'text-pane') {
        txtInput.setAttribute('required', 'required');
    } else {
        txtInput.removeAttribute('required');
    }
}
</script>
</body>
</html>