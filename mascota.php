<?php

$pagina = basename($_SERVER['PHP_SELF']);

$mensaje = "👋 Bienvenido al Sistema UMG.";

// =====================================================
// MENÚ ADMINISTRADOR
// =====================================================

if ($pagina == 'menu_admin.php') {

    $mensaje = "
    👋 Bienvenido administrador.<br><br>

    Desde aquí puedes acceder a todos los módulos del sistema
    biométrico de asistencia de UMG.<br><br>

    📌 Selecciona una opción para comenzar.
    ";

// =====================================================
// REGISTRO ESTUDIANTES
// =====================================================

} elseif ($pagina == 'registro_estudiante.php') {

    $mensaje = "
    👨‍🎓 Aquí puedes registrar nuevos estudiantes.<br><br>

    📷 Recuerda tomar la fotografía biométrica antes de guardar.<br><br>

    🪪 El sistema generará automáticamente un carnet con código QR.
    ";

// =====================================================
// GESTIÓN ESTUDIANTES
// =====================================================

} elseif ($pagina == 'gestionar_estudiantes.php') {

    $mensaje = "
    📚 Aquí puedes administrar estudiantes registrados.<br><br>

    ✏️ Puedes editar información académica y biométrica.<br><br>

    🗑️ También puedes eliminarlos o asignarlos a cursos.
    ";

// =====================================================
// GESTIÓN USUARIOS
// =====================================================

} elseif ($pagina == 'gestionar_usuarios.php') {

    $mensaje = "
    👨‍🏫 Aquí administras los usuarios del sistema.<br><br>

    🔐 Puedes crear administradores y catedráticos.<br><br>

    ⚙️ Gestiona permisos y accesos del sistema.
    ";

// =====================================================
// GESTIÓN CURSOS
// =====================================================

} elseif ($pagina == 'gestionar_cursos.php') {

    $mensaje = "
    📖 Aquí puedes crear y administrar cursos.<br><br>

    🏫 Asigna salones, horarios y catedráticos.<br><br>

    📅 Mantén organizada la planificación académica.
    ";

// =====================================================
// LECTOR QR
// =====================================================

} elseif ($pagina == 'lector_qr.php') {

    $mensaje = "
    📷 Escanea códigos QR para registrar asistencia.<br><br>

    📱 Verifica que la cámara esté activa.<br><br>

    ✅ El sistema validará automáticamente al estudiante.
    ";

// =====================================================
// VERIFICAR CERTIFICADO
// =====================================================

} elseif ($pagina == 'verificar_certificado.php') {

    $mensaje = "
    🛡️ Aquí puedes verificar certificados o carnets digitales.<br><br>

    🔍 El sistema validará si fueron emitidos oficialmente por UMG.<br><br>

    ✅ Garantiza autenticidad y seguridad documental.
    ";

// =====================================================
// RECONOCIMIENTO FACIAL
// =====================================================

} elseif ($pagina == 'reconocimiento_facial.php') {

    $mensaje = "
    📸 Bienvenido al módulo de asistencia facial.<br><br>

    🤖 El sistema detectará automáticamente rostros usando Face API.<br><br>

    ✅ Verifica que la cámara esté encendida y el rostro sea visible.
    ";

// =====================================================
// CONFIRMACIÓN ASISTENCIA
// =====================================================

} elseif ($pagina == 'confirmacion_asistencia.php') {

    $mensaje = "
    ✅ Aquí puedes confirmar oficialmente la asistencia detectada.<br><br>

    👨‍🏫 Revisa cada estudiante antes de validar.<br><br>

    📋 Solo los estudiantes confirmados quedarán registrados.
    ";

// =====================================================
// DASHBOARD
// =====================================================

} elseif ($pagina == 'dashboard.php') {

    $mensaje = "
    📊 Bienvenido al Dashboard del sistema.<br><br>

    📈 Consulta estadísticas, historial de asistencia y reportes.<br><br>

    📅 Analiza información en tiempo real del sistema biométrico.
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
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 99999;
}

#mascota{
    width: 110px;
    height: 110px;
    border-radius: 50%;
    object-fit: cover;
    cursor: pointer;
    border: 4px solid white;
    box-shadow: 0 8px 25px rgba(0,0,0,.25);
    transition: .3s;
}

#mascota:hover{
    transform: scale(1.08);
}

#mensaje-mascota{
    position: absolute;
    bottom: 125px;
    right: 0;
    width: 300px;
    background: white;
    padding: 15px;
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0,0,0,.18);
    font-family: 'Segoe UI', sans-serif;
    font-size: 14px;
    line-height: 1.6;
    color: #1f2937;
    display: none;
    animation: fadeIn .25s ease;
}

#mascota-container:hover #mensaje-mascota{
    display: block;
}

@keyframes fadeIn{
    from{
        opacity: 0;
        transform: translateY(10px);
    }

    to{
        opacity: 1;
        transform: translateY(0);
    }
}

</style>
