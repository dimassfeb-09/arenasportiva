<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

$mail = new PHPMailer(true);
$mail->Host       = 'smtp.gmail.com';
$mail->SMTPAuth   = true;
$mail->Username   = 'arenasportivamyid@gmail.com';
$mail->Password   = 'kzjs eljg mzda ouia'; // app password
$mail->SMTPSecure = 'tls';
$mail->Port       = 587;
$mail->setFrom('arenasportivamyid@gmail.com', 'Arena Sportiva');
