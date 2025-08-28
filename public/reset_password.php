<?php
require_once __DIR__ . '/../src/db_connect.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['token'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($token) || strlen($new_password) < 6 || $new_password !== $confirm_password) {
        $message = 'Token tidak valid, password kurang dari 6 karakter, atau konfirmasi password tidak cocok.';
        $message_type = 'danger';
    } else {
        $stmt = $mysqli->prepare("SELECT id, reset_token_expiry FROM users WHERE reset_token = ? LIMIT 1");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $stmt->bind_result($user_id, $expiry);
        if ($stmt->fetch()) {
            $stmt->close();
            
            if (strtotime($expiry) < time()) {
                $message = 'Token sudah kadaluarsa. Silakan request reset password baru.';
                $message_type = 'danger';
            } else {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt2 = $mysqli->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
                $stmt2->bind_param('si', $hashed, $user_id);
                $stmt2->execute();
                $stmt2->close();
                $message = 'Password berhasil direset. Silakan login.';
                $message_type = 'success';
            }
        } else {
            $message = 'Token tidak valid atau sudah kadaluarsa.';
            $message_type = 'danger';
        }
    }
}

include __DIR__ . '/../templates/header.php';
?>

<div class="container py-5" style="max-width:520px;">
    <div class="card shadow-lg border-0 rounded-3">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <img src="assets/img/logo.png" alt="Logo Arena Sportiva" class="mb-3" style="width: 80px; height: auto;">
                <h4 class="fw-bold mb-2">Reset Password</h4>
                <p class="text-muted">Masukkan password baru Anda.</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($message) || $message_type === 'danger'): // Only show form if no success message or if there's an error ?>
            <form method="post" onsubmit="return validateResetForm();">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <div class="mb-3">
                    <label for="new_password" class="form-label">Password Baru</label>
                    <div class="input-group">
                        <input id="new_password" name="new_password" type="password" class="form-control form-control-lg" required minlength="6" autocomplete="new-password">
                        <button type="button" class="btn btn-outline-secondary" tabindex="-1" onclick="togglePassword('new_password', this)"><i class="fa fa-eye"></i></button>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                    <div class="input-group">
                        <input id="confirm_password" name="confirm_password" type="password" class="form-control form-control-lg" required minlength="6" autocomplete="new-password">
                        <button type="button" class="btn btn-outline-secondary" tabindex="-1" onclick="togglePassword('confirm_password', this)"><i class="fa fa-eye"></i></button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100">Reset Password</button>
            </form>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="index.php" class="text-decoration-none">Kembali ke Beranda</a>
            </div>
        </div>
    </div>
</div>

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

<?php include __DIR__ . '/../templates/footer.php'; ?>