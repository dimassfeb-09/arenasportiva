<?php
// Simplified confirmation screen.
// TODO: Implement real token verification and activation logic.
$token = isset($_GET['token']) ? trim($_GET['token']) : '';


if (!empty($token)) {
  // TODO: Implement real token verification and activation logic.
  // Contoh: Ambil email user dari database berdasarkan token
  require_once __DIR__ . '/../src/db_connect.php';
  $stmt = $mysqli->prepare("SELECT email, name FROM users WHERE email_token = ? LIMIT 1");
  $stmt->bind_param('s', $token);
  $stmt->execute();
  $stmt->bind_result($email, $name);
  if ($stmt->fetch()) {
    $stmt->close();
    // Aktifkan akun (misal: update kolom is_active)
    $stmt2 = $mysqli->prepare("UPDATE users SET is_active = 1, email_token = NULL WHERE email = ?");
    $stmt2->bind_param('s', $email);
    $stmt2->execute();
    $stmt2->close();

    // Kirim email notifikasi ke user
    $subject = 'Akun Anda telah aktif - Arena Sportiva';
    $body = "Halo $name,\n\nAkun Anda di Arena Sportiva telah aktif dan siap digunakan. Silakan login untuk mulai booking lapangan.\n\nTerima kasih.";
    // Gunakan mail() bawaan PHP (pastikan server mendukung)
    @mail($email, $subject, $body, "From: admin@arenasportiva.com\r\n");

    $message = 'Email Anda telah berhasil dikonfirmasi! Akun Anda sudah aktif, silakan login.';
    $isValid = true;
  } else {
    $message = 'Token tidak valid atau akun sudah aktif.';
    $isValid = false;
  }
} else {
  $message = 'Token tidak valid.';
  $isValid = false;
}

include __DIR__ . '/../templates/header.php';
?>
<div class="container" style="max-width:520px; margin-top:24px;">
  <div class="card shadow-sm border-0">
    <div class="card-body p-4">
      <div class="text-center mb-3">
        <img src="assets/img/logo.png" alt="Logo" style="width:64px; height:auto;">
      </div>
      <h4 class="text-center mb-2">Konfirmasi Email</h4>

      <div class="alert <?= $isValid ? 'alert-success' : 'alert-warning' ?> small mb-0 text-center">
        <?= htmlspecialchars($message) ?>
      </div>

      <div class="text-center mt-3">
        <a href="index.php" class="btn btn-primary btn-sm">Kembali ke Beranda</a>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>