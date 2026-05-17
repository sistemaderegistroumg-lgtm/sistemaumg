<?php

$pagina = basename($_SERVER['PHP_SELF']);

$mensaje = "👋 Bienvenido al Sistema UMG.";

if ($pagina == 'menu_admin.php') {

    $mensaje = "
    👋 Bienvenido administrador.<br><br>

    Desde aquí puedes acceder a todos los módulos del sistema UMG.
    ";

} elseif ($pagina == 'registro_estudiante.php') {

    $mensaje = "
    👨‍🎓 Aquí puedes registrar nuevos estudiantes.<br><br>

    📷 Recuerda tomar la foto biométrica antes de guardar.
    ";

} elseif ($pagina == 'gestionar_estudiantes.php') {

    $mensaje = "
    📚 Aquí puedes buscar, editar y eliminar estudiantes.<br><br>

    También puedes asignarlos a cursos.
    ";

} elseif ($pagina == 'gestionar_usuarios.php') {

    $mensaje = "
    👨‍🏫 Aquí administras usuarios del sistema.<br><br>

    Puedes crear administradores y catedráticos.
    ";

} elseif ($pagina == 'gestionar_cursos.php') {

    $mensaje = "
    📖 Aquí puedes crear cursos y asignar catedráticos.<br><br>

    También puedes administrar horarios y salones.
    ";

} elseif ($pagina == 'lector_qr.php') {

    $mensaje = "
    📷 Escanea códigos QR para registrar asistencia.<br><br>

    Verifica que la cámara esté activa.
    ";

} elseif ($pagina == 'verificar_certificado.php') {

    $mensaje = "
    🛡️ Verifica certificados digitales emitidos por UMG.<br><br>

   
    ";

}

?>

<!-- =========================
     MASCOTA VIRTUAL
========================= -->

<div id="mascota-container">

    <div id="mensaje-mascota">
        <?= $mensaje ?>
    </div>

    <img src="mascota.jpg" id="mascota" alt="Mascota">

</div>

<style>

#mascota-container{
    position:fixed;
    bottom:20px;
    right:20px;
    z-index:99999;
}

#mascota{
    width:110px;
    height:110px;
    border-radius:50%;
    object-fit:cover;
    cursor:pointer;

    border:4px solid white;

    box-shadow:0 8px 25px rgba(0,0,0,.25);

    transition:.3s;
}

#mascota:hover{
    transform:scale(1.08);
}

#mensaje-mascota{

    position:absolute;

    bottom:125px;
    right:0;

    width:280px;

    background:white;

    padding:15px;

    border-radius:15px;

    box-shadow:0 8px 25px rgba(0,0,0,.18);

    font-family:'Segoe UI',sans-serif;

    font-size:14px;

    line-height:1.6;

    color:#1f2937;

    display:none;

    animation:fadeIn .25s ease;
}

#mascota-container:hover #mensaje-mascota{
    display:block;
}

@keyframes fadeIn{

    from{
        opacity:0;
        transform:translateY(10px);
    }

    to{
        opacity:1;
        transform:translateY(0);
    }
}

</style>