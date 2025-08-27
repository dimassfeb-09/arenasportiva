<?php
use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);

// Pakai SMTP, bukan mail() bawaan PHP
$mail->isSMTP();
$mail->Host       = 'smtp.gmail.com';
$mail->SMTPAuth   = true;
$mail->Username   = 'arenasportivamyid@gmail.com'; // email Gmail
$mail->Password   = 'kzjs eljg mzda ouia'; // app password
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port       = 587;

$mail->setFrom('arenasportivamyid@gmail.com', 'Arena Sportiva');
