<?php
session_start();
require_once 'config.php';

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Verificar TCPDF
if (!file_exists(TCPDF_PATH)) {
    die('<h2>Error: No se encontró la librería TCPDF en: ' . TCPDF_PATH . '</h2>');
}
require_once TCPDF_PATH;

// Limpiar cualquier output previo
if (ob_get_level()) ob_end_clean();

$pdo      = getDB();
$curso_id = (int)filter_input(INPUT_GET, 'curso_id', FILTER_VALIDATE_INT);
$fecha    = filter_input(INPUT_GET, 'fecha') ?: date('Y-m-d');

if ($curso_id <= 0) {
    die('ID de curso no válido.');
}

// Datos del curso
$stmt = $pdo->prepare("SELECT nombre, salon FROM cursos WHERE id = ?");
$stmt->execute([$curso_id]);
$curso = $stmt->fetch();
if (!$curso) { die('Curso no encontrado.'); }

// Estudiantes con asistencia
$stmt = $pdo->prepare("
    SELECT u.nombre, u.apellidos, u.correo, u.foto,
           IFNULL((
               SELECT ad.presente
               FROM asistencias a
               JOIN asistencia_detalle ad ON a.id = ad.asistencia_id
               WHERE ad.estudiante_id = u.id AND a.curso_id = ? AND a.fecha = ?
               LIMIT 1
           ), 0) AS presente,
           (
               SELECT ad.hora_registro
               FROM asistencias a
               JOIN asistencia_detalle ad ON a.id = ad.asistencia_id
               WHERE ad.estudiante_id = u.id AND a.curso_id = ? AND a.fecha = ?
               LIMIT 1
           ) AS hora_registro
    FROM curso_estudiante ce
    JOIN usuarios u ON ce.estudiante_id = u.id
    WHERE ce.curso_id = ?
    ORDER BY u.apellidos, u.nombre
");
$stmt->execute([$curso_id, $fecha, $curso_id, $fecha, $curso_id]);
$estudiantes = $stmt->fetchAll();

// Estadísticas
$total    = count($estudiantes);
$presentes = count(array_filter($estudiantes, fn($e) => $e['presente']));
$ausentes  = $total - $presentes;
$pct       = $total > 0 ? round(($presentes/$total)*100) : 0;

// ---- Crear PDF ----
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Sistema UMG');
$pdf->SetAuthor('Universidad Mariano Gálvez');
$pdf->SetTitle('Asistencia - ' . $curso['nombre']);
$pdf->SetMargins(15, 30, 15);
$pdf->SetHeaderMargins(10, 5);
$pdf->SetAutoPageBreak(true, 20);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Header personalizado
$pdf->setHeaderData('', 0, '', '');
$pdf->SetHeaderMargin(5);
$pdf->AddPage();

// Logo + encabezado
$logoUrl = 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3b/LogoUMG.png/320px-LogoUMG.png';

$html = '
<style>
  body    { font-family: DejaVu Serif, serif; font-size:10pt; color:#2c3e50; }
  .titulo { font-size:16pt; font-weight:bold; color:#003366; text-align:center; margin-bottom:3px; }
  .sub    { font-size:11pt; color:#1c3f60; text-align:center; margin-bottom:2px; }
  .fecha  { font-size:9pt; color:#555; text-align:center; margin-bottom:12px; }
  .info   { font-size:9.5pt; background:#f4f6f9; padding:8px 10px; border-left:4px solid #003366; margin-bottom:14px; }
  .stats  { font-size:9pt; color:#333; margin-bottom:12px; }
  table   { width:100%; border-collapse:collapse; }
  th      { background:#003366; color:white; padding:7px 8px; font-size:9pt; text-align:left; }
  td      { padding:7px 8px; font-size:9pt; border-bottom:1px solid #e0e0e0; }
  tr:nth-child(even) td { background:#f8f9fa; }
  .presente { color:#166534; font-weight:bold; }
  .ausente  { color:#991b1b; font-weight:bold; }
  .footer { font-size:8pt; color:#aaa; text-align:center; margin-top:20px; }
</style>

<div class="titulo">Universidad Mariano Gálvez</div>
<div class="sub">Registro de Asistencia Estudiantil</div>
<div class="fecha">Generado: ' . date('d/m/Y H:i:s') . '</div>

<div class="info">
  <b>Curso:</b> ' . htmlspecialchars($curso['nombre']) . ' &nbsp;|&nbsp;
  <b>Salón:</b> ' . htmlspecialchars($curso['salon']) . ' &nbsp;|&nbsp;
  <b>Fecha:</b> ' . $fecha . '
</div>

<div class="stats">
  Total: <b>' . $total . '</b> &nbsp;·&nbsp;
  Presentes: <b style="color:#166534">' . $presentes . '</b> &nbsp;·&nbsp;
  Ausentes: <b style="color:#991b1b">' . $ausentes . '</b> &nbsp;·&nbsp;
  Asistencia: <b>' . $pct . '%</b>
</div>

<table>
  <thead>
    <tr>
      <th>#</th>
      <th>Nombre completo</th>
      <th>Correo</th>
      <th>Estado</th>
      <th>Hora</th>
    </tr>
  </thead>
  <tbody>';

$n = 1;
foreach ($estudiantes as $est) {
    $nombre   = htmlspecialchars(ucwords(strtolower($est['nombre'] . ' ' . $est['apellidos'])));
    $correo   = htmlspecialchars($est['correo']);
    $estado   = $est['presente'] ? '<span class="presente">PRESENTE</span>' : '<span class="ausente">AUSENTE</span>';
    $hora     = $est['presente'] && $est['hora_registro'] ? $est['hora_registro'] : '--:--:--';
    $html    .= "<tr><td>$n</td><td>$nombre</td><td>$correo</td><td>$estado</td><td>$hora</td></tr>";
    $n++;
}

$html .= '
  </tbody>
</table>
<div class="footer">Documento generado automáticamente · Sistema de Asistencia Biométrica UMG · ' . date('d/m/Y') . '</div>';

$pdf->writeHTML($html, true, false, true, false, '');

// Descargar
while (ob_get_level()) ob_end_clean();
$nombreArchivo = 'Asistencia_' . preg_replace('/[^a-zA-Z0-9]/', '_', $curso['nombre']) . '_' . $fecha . '.pdf';
$pdf->Output($nombreArchivo, 'D');
exit;
