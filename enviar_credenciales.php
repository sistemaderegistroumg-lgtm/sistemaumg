<?php

require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';
require_once __DIR__ . '/phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarCredenciales(
    $correo,
    $nombre,
    $password,
    $rol
) {

    try {

        $mail = new PHPMailer(true);

        // ==================================================
        // SMTP
        // ==================================================

        $mail->isSMTP();

        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;

        $mail->Username   = 'sistemaderegistroumg@gmail.com';

        $mail->Password   = 'bwnzxmrzkxtfride';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port       = 587;

        $mail->CharSet    = 'UTF-8';

        // ==================================================
        // FIX XAMPP SSL
        // ==================================================

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ];

        // ==================================================
        // DESTINO
        // ==================================================

        $mail->setFrom(
            'sistemaderegistroumg@gmail.com',
            'Universidad Mariano Gálvez'
        );

        $mail->addAddress($correo, $nombre);

        // ==================================================
        // CONTENIDO
        // ==================================================

        $mail->isHTML(true);

        $mail->Subject = 'Credenciales de acceso UMG';

        $mail->Body = '

        <div style="font-family:Arial;padding:20px;">

            <h2 style="color:#003399;">
                Universidad Mariano Gálvez
            </h2>

            <p>
                Estimado(a)  <b>' . $nombre . '</b>
            </p>

            <p>
                Su cuenta fue creada correctamente.
            </p>

            <hr>

            <p>
                <b>Usuario:</b><br>
                ' . $correo . '
            </p>

            <p>
                <b>Contraseña:</b><br>
                ' . $password . '
            </p>

            <p>
                <b>Rol:</b><br>
                ' . $rol . '
            </p>

            <hr>

            <small>
                Universidad Mariano Gálvez de Guatemala
            </small>

        </div>';

        $mail->AltBody =
            "Usuario: $correo\n" .
            "Contraseña: $password\n" .
            "Rol: $rol";

        $mail->send();

        return true;

    } catch (Exception $e) {

        return false;
    }
}