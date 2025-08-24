<?php
require_once __DIR__ . '/../src/auth.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  if ($email !== '') {
    require_once __DIR__ . '/../src/db_connect.php';
    $stmt = $mysqli->prepare("SELECT id, name FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($user_id, $name);
    if ($stmt->fetch()) {
      $stmt->close();
      // Generate token dan expiry
      $token = bin2hex(random_bytes(24));
      $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 jam
      $stmt2 = $mysqli->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
      $stmt2->bind_param('ssi', $token, $expiry, $user_id);
      $stmt2->execute();
      $stmt2->close();
      // Kirim email link reset
      $reset_link = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/reset_password.php?token=' . $token;
      $subject = 'Reset Password Arena Sportiva';
      $body = "Halo $name,\n\nKlik link berikut untuk reset password akun Anda:\n$reset_link\n\nLink berlaku 1 jam.";
      @mail($email, $subject, $body, "From: admin@arenasportiva.com\r\n");
      $message = 'Link reset password telah dikirim ke email Anda.';
    } else {
      $error = 'Email tidak ditemukan.';
    }
  } else {
    $error = 'Email wajib diisi.';
  }
}

include __DIR__ . '/../templates/header.php';
?>
<div class="container" style="max-width:520px; margin-top: 24px;">
  <div class="card shadow-sm border-0">
    <div class="card-body p-4">
      <div class="text-center mb-3">
        <img src="assets/img/logo.png" alt="Logo" style="width:64px; height:auto;">
      </div>
      <h4 class="text-center mb-2">Lupa Password</h4>
      <p class="text-center text-muted mb-4">Masukkan email Anda untuk menerima link reset password.</p>

      <?php if (!empty($message)): ?>
        <div class="alert alert-success small mb-3">
          <?= htmlspecialchars($message) ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger small mb-3">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="post" novalidate>
        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <input id="email" name="email" type="email" class="form-control" placeholder="email@contoh.com" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Kirim Link Reset</button>
      </form>

      <div class="text-center mt-3">
        <a href="index.php" class="small">Kembali ke Beranda</a>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>