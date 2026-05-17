<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "config.php";

$pdo = getDB();

// 🔐 Seguridad: si no hay sesión, fuera
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_SESSION['usuario_id'];
$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password = $_POST['password'];

    if (strlen($password) < 6) {
        $mensaje = "❌ La contraseña debe tener al menos 6 caracteres";
    } else {

        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("
            UPDATE usuarios
            SET contrasena = ?, password_creada = TRUE
            WHERE id = ?
        ");

        $stmt->execute([$hash, $id]);

        $mensaje = "✅ Contraseña creada correctamente. Ya puedes iniciar sesión.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Crear contraseña</title>

<style>
body{
    font-family:Segoe UI;
    background:#f0f4f8;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}

.card{
    background:white;
    padding:30px;
    border-radius:12px;
    box-shadow:0 4px 20px rgba(0,0,0,.1);
    width:320px;
    text-align:center;
}

input{
    width:100%;
    padding:10px;
    margin-top:10px;
}

button{
    width:100%;
    padding:10px;
    margin-top:10px;
    background:#2563eb;
    color:white;
    border:none;
    border-radius:8px;
    cursor:pointer;
}

button:hover{
    background:#1a2e4a;
}

.msg{
    margin-top:10px;
    font-size:14px;
}
</style>
</head>

<body>

<div class="card">

<h2>Crear contraseña</h2>
<p>Debes crear tu contraseña por primera vez</p>

<form method="POST">
    <input type="password" name="password" placeholder="Nueva contraseña" required>
    <button>Guardar</button>
</form>

<div class="msg"><?= $mensaje ?></div>

</div>

</body>
</html>