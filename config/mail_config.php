<?php

$mail = new PHPMailer(true);
$mail->Host       = 'smtp.gmail.com';
$mail->SMTPAuth   = true;
$mail->Username   = 'arenasportivamyid@gmail.com'; // email yang sudah dikonfigurasi
$mail->Password   = 'kzjs eljg mzda ouia'; // app password yang sudah ada
$mail->SMTPSecure = 'tls';
$mail->Port       = 587;
$mail->setFrom('arenasportivamyid@gmail.com', 'Arena Sportiva');
