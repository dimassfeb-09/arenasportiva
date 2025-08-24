<?php
session_start();
require_once __DIR__ . '/../src/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $result = registerUser($name, $phone, $email, $password);

    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
    } else {
        $_SESSION['error_message'] = $result['message'];
    }

    header("Location: index.php");
    exit();
}

// kalau akses langsung tanpa POST, balikin ke home
header("Location: index.php");
exit();