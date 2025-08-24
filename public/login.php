<?php
require_once __DIR__ . '/../src/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $phone = $_POST['phone'];
  $password = $_POST['password'];
  $result = loginUser($phone, $password);
  if ($result['success']) {
    header('Location: index.php');
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
  <title>Login – Booking Lapangan</title>
  <link 
    rel="stylesheet" 
    href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" 
    crossorigin="anonymous">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="card-auth text-center">
    <img src="assets/img/logo.png" alt="Logo" class="mb-4" style="width:80px;">
    <h4 class="mb-4">Login Pelanggan</h4>

    <?php if(!empty($error)): ?>
      <div class="alert alert-danger small">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="post">
      <div class="form-group text-left">
        <label>Nomor Telepon</label>
        <input name="phone" type="text" class="form-control" placeholder="08xxxxxxxxxx" required>
      </div>
      <div class="form-group text-left">
        <label>Password</label>
        <input name="password" type="password" class="form-control" placeholder="Password" required>
      </div>
      <button type="submit" class="btn btn-auth btn-block">Login</button>
    </form>

    <p class="mt-3 small">
      Belum punya akun? <a href="register.php">Daftar di sini</a>
    </p>
  </div>
</body>
</html>
