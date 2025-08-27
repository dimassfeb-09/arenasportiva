<?php
// Halaman reset password via link email
require_once __DIR__ . '/../src/db_connect.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['token'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    if (!$token || strlen($new_password) < 6) {
        $error = 'Token tidak valid atau password kurang dari 6 karakter.';
    } else {
        // Cari user berdasarkan token
        // Debug: Tampilkan token yang diterima
        error_log("Received token: " . $token);
        
        $stmt = $mysqli->prepare("SELECT id, reset_token, reset_token_expiry FROM users WHERE reset_token = ? LIMIT 1");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $stmt->bind_result($user_id, $db_token, $expiry);
        if ($stmt->fetch()) {
            error_log("Found user with ID: " . $user_id);
            error_log("Token in DB: " . $db_token);
            error_log("Expiry time: " . $expiry);
            
            // Cek apakah token sudah expired
            if (strtotime($expiry) < time()) {
                $error = 'Token sudah kadaluarsa. Silakan request reset password baru.';
                $stmt->close();
                return;
            }
            
            $stmt->close();
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt2 = $mysqli->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
            $stmt2->bind_param('si', $hashed, $user_id);
            $stmt2->execute();
            $stmt2->close();
            $message = 'Password berhasil direset. Silakan login.';
        } else {
            $error = 'Token tidak valid atau sudah kadaluarsa.';
        }
    }
}

include __DIR__ . '/../templates/header.php';
?>
<div class="container" style="max-width:520px; margin-top:24px;">
  <div class="card shadow-sm border-0">
    <div class="card-body p-4">
      <h4 class="text-center mb-2">Reset Password</h4>
      <?php if ($message): ?>
        <div class="alert alert-success text-center small mb-3"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-danger text-center small mb-3"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if (!$message): ?>
      <form method="post" onsubmit="return validateResetForm();">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <div class="mb-3">
          <label for="new_password" class="form-label">Password Baru</label>
          <div class="input-group">
            <input id="new_password" name="new_password" type="password" class="form-control" required minlength="6" autocomplete="new-password">
            <button type="button" class="btn btn-outline-secondary" tabindex="-1" onclick="togglePassword('new_password', this)"><i class="fa fa-eye"></i></button>
          </div>
        </div>
        <div class="mb-3">
          <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
          <div class="input-group">
            <input id="confirm_password" name="confirm_password" type="password" class="form-control" required minlength="6" autocomplete="new-password">
            <button type="button" class="btn btn-outline-secondary" tabindex="-1" onclick="togglePassword('confirm_password', this)"><i class="fa fa-eye"></i></button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary w-100">Reset Password</button>
      </form>
      <script>
      function togglePassword(id, el) {
        var inp = document.getElementById(id);
        if (inp.type === 'password') {
          inp.type = 'text';
          el.querySelector('i').classList.remove('fa-eye');
          el.querySelector('i').classList.add('fa-eye-slash');
        } else {
          inp.type = 'password';
          el.querySelector('i').classList.remove('fa-eye-slash');
          el.querySelector('i').classList.add('fa-eye');
        }
      }
      function validateResetForm() {
        var pw = document.getElementById('new_password').value;
        var cpw = document.getElementById('confirm_password').value;
        if (pw !== cpw) {
          alert('Konfirmasi password tidak sama!');
          return false;
        }
        return true;
      }
      </script>
      <?php endif; ?>
      <div class="text-center mt-3">
        <a href="index.php" class="small">Kembali ke Beranda</a>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>
