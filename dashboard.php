<?php

session_start();
include 'mascota.php';
require_once 'config.php';

requireSession(1);

$usuario = $_SESSION['usuario'];

$pdo = getDB();

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Dashboard Inteligente UMG</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:Arial;
    background:#0f172a;
    color:white;
    min-height:100vh;
}

.topbar{

    background:#111827;

    padding:20px;

    display:flex;

    justify-content:space-between;

    align-items:center;

    box-shadow:0 5px 20px rgba(0,0,0,.3);
}

.logo-box{
    display:flex;
    align-items:center;
    gap:15px;
}

.logo-box img{
    width:60px;
}

.topbar h1{
    font-size:28px;
}

.btn-back{

    background:#2563eb;

    color:white;

    text-decoration:none;

    padding:12px 20px;

    border-radius:10px;

    font-weight:bold;
}

.container{
    padding:30px;
}

.buttons{

    display:grid;

    grid-template-columns:
        repeat(auto-fit,minmax(250px,1fr));

    gap:20px;

    margin-bottom:30px;
}

.report-btn{

    background:#1e293b;

    border:none;

    border-radius:18px;

    padding:30px;

    color:white;

    cursor:pointer;

    transition:.3s;

    text-align:left;

    box-shadow:0 10px 25px rgba(0,0,0,.2);
}

.report-btn:hover{

    transform:translateY(-6px);

    background:#334155;
}

.report-btn i{

    font-size:40px;

    margin-bottom:15px;

    color:#22c55e;
}

.report-btn h3{
    margin-bottom:10px;
    font-size:20px;
}

.report-btn p{
    color:#cbd5e1;
    font-size:14px;
    line-height:1.5;
}

.panel{

    background:#1e293b;

    border-radius:20px;

    padding:25px;

    box-shadow:0 10px 25px rgba(0,0,0,.25);
}

.panel h2{
    margin-bottom:20px;
}

.filtros{

    display:grid;

    grid-template-columns:
        repeat(auto-fit,minmax(220px,1fr));

    gap:15px;

    margin-bottom:25px;
}

select,
input{

    padding:14px;

    border:none;

    border-radius:12px;

    background:#334155;

    color:white;

    font-size:15px;
}

.acciones{

    display:flex;

    gap:15px;

    margin-bottom:25px;

    flex-wrap:wrap;
}

.btn{

    border:none;

    padding:14px 22px;

    border-radius:12px;

    cursor:pointer;

    color:white;

    font-weight:bold;

    transition:.3s;
}

.btn:hover{
    transform:scale(1.03);
}

.btn-generar{
    background:#22c55e;
}

.btn-pdf{
    background:#dc2626;
}

.btn-excel{
    background:#2563eb;
}

.resultado{

    margin-top:20px;

    overflow:auto;
}

table{

    width:100%;

    border-collapse:collapse;

    min-width:900px;
}

th{

    background:#334155;

    padding:14px;

    text-align:left;
}

td{

    padding:14px;

    border-bottom:
        1px solid rgba(255,255,255,.1);
}

tr:hover{
    background:rgba(255,255,255,.03);
}

.foto{

    width:60px;

    height:60px;

    border-radius:50%;

    object-fit:cover;

    border:3px solid #22c55e;
}

.presente{

    background:#166534;

    color:white;

    padding:7px 12px;

    border-radius:20px;

    font-size:12px;

    font-weight:bold;
}

.ausente{

    background:#991b1b;

    color:white;

    padding:7px 12px;

    border-radius:20px;

    font-size:12px;

    font-weight:bold;
}

.card-estadistica{

    background:#0f172a;

    border-radius:18px;

    padding:30px;

    text-align:center;

    margin-bottom:20px;
}

.card-estadistica h1{

    font-size:60px;

    color:#22c55e;

    margin-bottom:10px;
}

.card-estadistica p{

    color:#cbd5e1;
}

.tree{

    margin-top:20px;

    padding-left:20px;
}

.tree ul{
    list-style:none;
}

.tree li{
    margin:10px 0;
}

.tree .nivel1{
    color:#22c55e;
    font-weight:bold;
}

.tree .nivel2{
    color:#60a5fa;
    margin-left:20px;
}

</style>

</head>

<body>

<div class="topbar">

    <div class="logo-box">

        <img src="logo_umg.png">

        <h1>
            Dashboard Inteligente UMG
        </h1>

    </div>

    <a href="menu_admin.php"
       class="btn-back">

        <i class="fas fa-arrow-left"></i>
        Regresar

    </a>

</div>

<div class="container">

    <!-- BOTONES -->

    <div class="buttons">

        <button class="report-btn"
                onclick="seleccionarReporte(1)">

            <i class="fas fa-door-open"></i>

            <h3>
                Histórico por Puerta
            </h3>

            <p>
                Árbol histórico de ingresos
                por puerta de acceso
            </p>

        </button>

        <button class="report-btn"
                onclick="seleccionarReporte(2)">

            <i class="fas fa-calendar-day"></i>

            <h3>
                Ingresos por Fecha
            </h3>

            <p>
                Reporte por fecha y puerta
            </p>

        </button>

        <button class="report-btn"
                onclick="seleccionarReporte(3)">

            <i class="fas fa-school"></i>

            <h3>
                Histórico por Salón
            </h3>

            <p>
                Historial completo por salón
            </p>

        </button>

        <button class="report-btn"
                onclick="seleccionarReporte(4)">

            <i class="fas fa-clock"></i>

            <h3>
                Salón por Fecha
            </h3>

            <p>
                Reporte por salón y fecha
            </p>

        </button>

        <button class="report-btn"
                onclick="seleccionarReporte(5)">

            <i class="fas fa-chart-pie"></i>

            <h3>
                Estadísticas
            </h3>

            <p>
                Dashboard en tiempo real
            </p>

        </button>

    </div>

    <!-- PANEL -->

    <div class="panel">

        <h2 id="tituloReporte">
            Selecciona un reporte
        </h2>

        <div class="filtros">

            <!-- INSTALACIÓN -->

            <select id="instalacion">

                <option value="">
                    Instalación
                </option>

                <option value="Campus Central">
                    Campus Central
                </option>

            </select>

            <!-- PUERTA -->

            <select id="puerta">

                <option value="">
                    Puerta
                </option>

                <option value="Puerta Norte">
                    Puerta Norte
                </option>

                <option value="Puerta Sur">
                    Puerta Sur
                </option>

            </select>

            <!-- SALÓN -->

            <select id="salon">

                <option value="">
                    Salón
                </option>

                <?php

                $stmt = $pdo->query("
                    SELECT salon
                    FROM cursos
                    GROUP BY salon
                    ORDER BY salon
                ");

                while($row = $stmt->fetch()){

                    echo '
                    <option value="'.$row['salon'].'">
                        '.$row['salon'].'
                    </option>
                    ';
                }

                ?>

            </select>

            <!-- FECHA -->

            <input type="date"
                   id="fecha">

            <!-- ORDEN -->

            <select id="orden">

                <option value="ASC">
                    Ascendente
                </option>

                <option value="DESC">
                    Descendente
                </option>

            </select>

        </div>

        <!-- BOTONES -->

        <div class="acciones">

            <button class="btn btn-generar"
                    onclick="generarReporte()">

                <i class="fas fa-search"></i>
                Generar Reporte

            </button>

            <button class="btn btn-pdf"
                    onclick="descargarPDF()">

                <i class="fas fa-file-pdf"></i>
                Descargar PDF

            </button>

            <button class="btn btn-excel"
                    onclick="descargarExcel()">

                <i class="fas fa-file-excel"></i>
                Descargar Excel

            </button>

        </div>

        <!-- RESULTADO -->

        <div class="resultado"
             id="resultado">

            <p>
                Aquí aparecerán los reportes
            </p>

        </div>

    </div>

</div>

<script>

let reporteActual = 0;

// =====================================================
// SELECCIONAR REPORTE
// =====================================================

function seleccionarReporte(id){

    reporteActual = id;

    const titulo =
        document.getElementById(
            'tituloReporte'
        );

    if(id == 1){

        titulo.innerHTML =
        'Reporte Histórico por Puerta';
    }

    if(id == 2){

        titulo.innerHTML =
        'Reporte por Fecha y Puerta';
    }

    if(id == 3){

        titulo.innerHTML =
        'Reporte Histórico por Salón';
    }

    if(id == 4){

        titulo.innerHTML =
        'Reporte por Fecha y Salón';
    }

    if(id == 5){

        titulo.innerHTML =
        'Dashboard Estadístico';
    }
}

// =====================================================
// GENERAR REPORTE
// =====================================================

async function generarReporte(){

    const fecha =
        document.getElementById(
            'fecha'
        ).value;

    const salon =
        document.getElementById(
            'salon'
        ).value;

    const puerta =
        document.getElementById(
            'puerta'
        ).value;

    const instalacion =
        document.getElementById(
            'instalacion'
        ).value;

    const orden =
        document.getElementById(
            'orden'
        ).value;

    const resultado =
        document.getElementById(
            'resultado'
        );

    resultado.innerHTML =
        'Cargando reporte...';

    try{

        const res = await fetch(
            'dashboard_api.php',
            {

                method:'POST',

                headers:{
                    'Content-Type':
                    'application/json'
                },

                body:JSON.stringify({

                    reporte:reporteActual,

                    fecha:fecha,

                    salon:salon,

                    puerta:puerta,

                    instalacion:instalacion,

                    orden:orden
                })
            }
        );

        const data =
            await res.json();

        console.log(data);

        if(!data.success){

            resultado.innerHTML =
                data.message;

            return;
        }

        // =================================================
        // ESTADÍSTICAS
        // =================================================

        if(reporteActual == 5){

            resultado.innerHTML = `

            <div class="card-estadistica">

                <h1>
                    ${data.total}
                </h1>

                <p>
                    Total de ingresos hoy
                </p>

            </div>

            `;

            return;
        }

        // =================================================
        // ÁRBOL HISTÓRICO
        // =================================================

        if(reporteActual == 1){

            let html = `

            <div class="tree">

                <ul>

                    <li class="nivel1">
                        ${puerta}
                    </li>

            `;

            data.fechas.forEach(fecha => {

                html += `

                <li class="nivel2">
                    📅 ${fecha}
                </li>

                `;
            });

            html += `
                </ul>
            </div>
            `;

            resultado.innerHTML = html;

            return;
        }

        // =================================================
        // TABLA
        // =================================================

        let html = `

        <table>

            <thead>

                <tr>

                    <th>Foto</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Hora</th>
                    <th>Salón</th>
                    <th>Estado</th>

                </tr>

            </thead>

            <tbody>

        `;

        data.reporte.forEach(item => {

            html += `

            <tr>

                <td>

                    <img
                    src="${item.foto}"
                    class="foto">

                </td>

                <td>

                    ${item.nombre}
                    ${item.apellidos}

                </td>

                <td>

                    ${item.correo}

                </td>

                <td>

                    ${item.hora}

                </td>

                <td>

                    ${item.salon ?? '-'}

                </td>

                <td>

                    <span class="presente">

                        PRESENTE

                    </span>

                </td>

            </tr>

            `;
        });

        html += `

            </tbody>

        </table>

        `;

        resultado.innerHTML = html;

    }catch(error){

        console.error(error);

        resultado.innerHTML =
            'Error generando reporte';
    }
}

// =====================================================
// PDF
// =====================================================

function descargarPDF(){

    const fecha =
        document.getElementById(
            'fecha'
        ).value;

    const salon =
        document.getElementById(
            'salon'
        ).value;

    const puerta =
        document.getElementById(
            'puerta'
        ).value;

    window.open(

        'dashboard_pdf.php?reporte='
        + reporteActual
        + '&fecha=' + fecha
        + '&salon=' + salon
        + '&puerta=' + puerta
    );
}

// =====================================================
// EXCEL
// =====================================================

function descargarExcel(){

    const fecha =
        document.getElementById(
            'fecha'
        ).value;

    const salon =
        document.getElementById(
            'salon'
        ).value;

    const puerta =
        document.getElementById(
            'puerta'
        ).value;

    window.open(

        'dashboard_excel.php?reporte='
        + reporteActual
        + '&fecha=' + fecha
        + '&salon=' + salon
        + '&puerta=' + puerta
    );
}

</script>

</body>
</html>