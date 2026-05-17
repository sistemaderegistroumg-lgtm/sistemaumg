<?php

require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';
require 'phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);

   $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'angelagabrielacujcujchoy@gmail.com';
        $mail->Password = 'rwmroskpisfatobt';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;


$mail->setFrom('angelagabrielacujcujchoy@gmail.com', 'Test');
$mail->addAddress('angelagabrielacujcujchoy@gmail.com');

$mail->Subject = "TEST";
$mail->Body = "Correo funcionando";

$mail->send();

echo "OK";