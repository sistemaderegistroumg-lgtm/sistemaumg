```php id="7h0j2n"
<?php

session_start();

require_once 'config.php';
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

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

$query = "";
$params = [];
$titulo = "Reporte";

// =====================================================
// REPORTE 1
// =====================================================

if($reporte == 1){

    $titulo =
        'Histórico por Puerta';

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

// =====================================================
// REPORTE 2
// =====================================================

elseif($reporte == 2){

    $titulo =
        'Ingresos por Fecha';

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

// =====================================================
// REPORTE 3
// =====================================================

elseif($reporte == 3){

    $titulo =
        'Histórico por Salón';

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

// =====================================================
// REPORTE 4
// =====================================================

elseif($reporte == 4){

    $titulo =
        'Salón por Fecha';

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

// =====================================================
// REPORTE 5
// =====================================================

elseif($reporte == 5){

    $titulo =
        'Estadísticas';

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
// HTML PDF
// =====================================================

$html = '

<style>

body{
    font-family:Arial;
    color:#1e293b;
}

.header{
    background:#0f172a;
    color:white;
    padding:20px;
    border-radius:10px;
}

.logo{
    width:100px;
}

.title{
    font-size:28px;
    font-weight:bold;
    margin-top:10px;
}

.subtitle{
    margin-top:5px;
    font-size:14px;
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

.footer{
    margin-top:30px;
    font-size:12px;
    color:#64748b;
}

</style>

<div class="header">
';

if(file_exists('logo_umg.png')){

    $logo =
        base64_encode(
            file_get_contents('logo_umg.png')
        );

    $html .= '

    <img src="data:image/png;base64,'.$logo.'"
         class="logo">

    ';
}

$html .= '

    <div class="title">
        UNIVERSIDAD MARIANO GÁLVEZ
    </div>

    <div class="subtitle">
        '.$titulo.'
        <br>
        Fecha:
        '.date('d/m/Y H:i:s').'
    </div>

</div>

<table>

<thead>

<tr>
';

// =====================================================
// ENCABEZADOS TABLA
// =====================================================

if(!empty($datos)){

    foreach(array_keys($datos[0]) as $campo){

        $html .= '

        <th>
            '.strtoupper($campo).'
        </th>

        ';
    }

    $html .= '

    </tr>

    </thead>

    <tbody>

    ';

    // =================================================
    // DATOS
    // =================================================

    foreach($datos as $fila){

        $html .= '<tr>';

        foreach($fila as $valor){

            $html .= '

            <td>
                '.$valor.'
            </td>

            ';
        }

        $html .= '</tr>';
    }

    $html .= '

    </tbody>

    ';
}

$html .= '

</table>

<div class="footer">

    Sistema Inteligente de Asistencia
    Biométrica UMG

</div>

';

// =====================================================
// DOMPDF
// =====================================================

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

// =====================================================
// DESCARGAR PDF
// =====================================================

$archivo =

    'Reporte_'.date('Ymd_His').'.pdf';

$dompdf->stream(

    $archivo,

    [
        "Attachment" => true
    ]
);

exit;

?>
```
