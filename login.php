<?php
session_start();
include 'mascota.php';
// Si ya tiene sesión activa, redirigir según rol
if (isset($_SESSION['usuario'])) {
    if ($_SESSION['rol'] === 'Administrador') {
        header("Location: menu_admin.php");
    } else {
        header("Location: menu_catedratico.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UMG - Iniciar Sesión</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0a1628 0%, #1a2e4a 50%, #0d2137 100%);
            font-family: 'Segoe UI', sans-serif;
        }
        .login-card {
            background: white;
            border-radius: 16px;
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4);
        }
        .logo-section {
            text-align: center;
            margin-bottom: 2rem;
        }
        .logo-section img {
            width: 80px;
            margin-bottom: 0.75rem;
        }
        .logo-section h1 {
            font-size: 1.4rem;
            color: #1a2e4a;
            font-weight: 700;
        }
        .logo-section p {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        .form-group {
            margin-bottom: 1.2rem;
        }
        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.4rem;
        }
        .input-wrapper {
            position: relative;
        }
        .input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 0.9rem;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: border-color 0.2s;
            outline: none;
        }
        .form-group input:focus {
            border-color: #1a2e4a;
        }
        .btn-login {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, #1a2e4a, #2563eb);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
            margin-top: 0.5rem;
        }
        .btn-login:hover { opacity: 0.9; }
        .btn-login:disabled { opacity: 0.6; cursor: not-allowed; }
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            display: none;
        }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-error.show { display: block; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-section">
            <img src="logo_umg.png" alt="UMG">
            <h1>Universidad Mariano Gálvez</h1>
            <p>Sistema de Control de Asistencia</p>
        </div>

        <div class="alert alert-error" id="alertError">
            <i class="fas fa-exclamation-circle"></i> <span id="alertMsg"></span>
        </div>

        <form id="loginForm">
            <div class="form-group">
                <label for="usuario">Usuario</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" id="usuario" name="nombre_usuario" placeholder="Ingresa tu usuario" required>
                </div>
            </div>
            <div class="form-group">
                <label for="contrasena">Contraseña</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="contrasena" name="contrasena" placeholder="Ingresa tu contraseña" required>
                </div>
            </div>
            <button type="submit" class="btn-login" id="btnLogin">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </button>
        </form>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnLogin');
            const alertEl = document.getElementById('alertError');
            const alertMsg = document.getElementById('alertMsg');

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
            alertEl.classList.remove('show');

            const formData = new FormData(this);

            try {
                const res = await fetch('auth.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    btn.innerHTML = '<i class="fas fa-check"></i> Redirigiendo...';
                    window.location.href = data.redirect;
                } else {
                    alertMsg.textContent = data.message;
                    alertEl.classList.add('show');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Iniciar Sesión';
                }
            } catch (err) {
                alertMsg.textContent = 'Error de conexión. Intenta de nuevo.';
                alertEl.classList.add('show');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Iniciar Sesión';
            }
        });
    </script>
</body>
</html>
