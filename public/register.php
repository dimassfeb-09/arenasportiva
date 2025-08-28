<?php
session_start();
require_once __DIR__ . '/../src/db_connect.php';
require_once __DIR__ . '/../src/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Validasi sederhana
    if (empty($name) || empty($phone) || empty($email) || empty($username) || empty($password)) {
        $_SESSION['error_message'] = 'Semua field harus diisi.';
        header('Location: index.php?auth=1&tab=register');
        exit();
    }

    $result = registerUser($name, $phone, $email, $username, $password);

    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
        header('Location: index.php?auth=1');
    } else {
        $_SESSION['error_message'] = $result['message'];
        header('Location: index.php?auth=1&tab=register');
    }
    exit();
} else {
    header('Location: index.php');
    exit();
}
?>