<?php
require_once __DIR__ . '/../src/db_connect.php';
require_once __DIR__ . '/../src/auth.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  if ($email !== '') {
    $stmt = $mysqli->prepare("SELECT id, name FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($user_id, $name);
    if ($stmt->fetch()) {
      $stmt->close();
      
      $token = bin2hex(random_bytes(24));
      $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 jam
      $stmt2 = $mysqli->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
      $stmt2->bind_param('ssi', $token, $expiry, $user_id);
      $stmt2->execute();
      $stmt2->close();
      
      $reset_link = 'https://arenasportiva.my.id/reset_password.php?token=' . $token;
      
      $mail = new PHPMailer(true);
      
      try {
          require '../config/mail_config.php';

          $mail->addAddress($email);
          $mail->isHTML(true);
          $mail->Subject = 'Reset Password Arena Sportiva';
          $mail->Body    = "Halo $name,<br><br>Klik link berikut untuk reset password akun Anda:<br>
                          <a href='$reset_link'>Reset Password</a><br><br>Link berlaku 1 jam.";

          if ($mail->send()) {
              $message = 'Link reset password telah dikirim ke email Anda.';
              $message_type = 'success';
          } else {
              $message = "Gagal mengirim email. Error: " . $mail->ErrorInfo;
              $message_type = 'danger';
          }
      } catch (Exception $e) {
          error_log("Failed to send email to: " . $email . ". Mailer Error: {$mail->ErrorInfo}");
          $message = "Gagal mengirim email. Error: {$mail->ErrorInfo}";
          $message_type = 'danger';
      }
    } else {
      $message = 'Email tidak ditemukan.';
      $message_type = 'danger';
    }
  } else {
    $message = 'Email wajib diisi.';
    $message_type = 'danger';
  }
}

include __DIR__ . '/../templates/header.php';
?>

<div class="container py-5" style="max-width:520px;">
    <div class="card shadow-lg border-0 rounded-3">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <img src="assets/img/logo.png" alt="Logo Arena Sportiva" class="mb-3" style="width: 80px; height: auto;">
                <h4 class="fw-bold mb-2">Lupa Password?</h4>
                <p class="text-muted">Masukkan email Anda untuk menerima link reset password.</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="post" novalidate>
                <div class="mb-3">
                    <label for="email" class="form-label">Alamat Email</label>
                    <input id="email" name="email" type="email" class="form-control form-control-lg" placeholder="email@contoh.com" required>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100">Kirim Link Reset</button>
            </form>

            <div class="text-center mt-4">
                <a href="index.php" class="text-decoration-none">Kembali ke Beranda</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
