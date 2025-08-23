<?php
require_once __DIR__ . '/../src/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $result = registerUser($name, $phone, $email, $password);
    
    if ($result['success']) {
        header('Location: index.php?message=' . urlencode($result['message']));
        exit();
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport"  
        content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Daftar – Booking Lapangan</title>
  <link 
    rel="stylesheet" 
    href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" 
    crossorigin="anonymous">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="card-auth text-center">
    <img src="assets/img/logo.png" alt="Logo" class="mb-4" style="width:80px;">
    <h4 class="mb-4">Daftar Pelanggan</h4>

    <?php if($errors): ?>
      <div class="alert alert-danger small">
        <?= implode('<br>', $errors) ?>
      </div>
    <?php endif; ?>

    <form method="post">
      <div class="form-group text-left">
        <label>Nama Lengkap</label>
        <input name="name" type="text" class="form-control" placeholder="Nama lengkap" required>
      </div>
      <div class="form-group text-left">
        <label>Nomor Telepon</label>
        <input name="phone" type="text" class="form-control" placeholder="08xxxxxxxxxx" required>
      </div>
      <div class="form-group text-left">
        <label>Password</label>
        <input name="password" type="password" class="form-control" placeholder="Password" required>
      </div>
      <button type="submit" class="btn btn-auth btn-block">Daftar</button>
    </form>

    <p class="mt-3 small">
      Sudah punya akun? <a href="login.php">Login di sini</a>
    </p>
  </div>
</body>
</html>
