<?php
session_start();
require_once __DIR__ . '/../src/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle password change
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $whatsapp_verification = $_POST['whatsapp_verification'];
    
    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        $message = 'Password saat ini salah!';
        $message_type = 'danger';
    }
    // Check if new password matches confirmation
    elseif ($new_password !== $confirm_password) {
        $message = 'Password baru dan konfirmasi password tidak cocok!';
        $message_type = 'danger';
    }
    // Check if WhatsApp number matches
    elseif ($whatsapp_verification !== $user['phone']) {
        $message = 'Nomor WhatsApp tidak sesuai dengan yang terdaftar!';
        $message_type = 'danger';
    }
    // Validate new password length
    elseif (strlen($new_password) < 6) {
        $message = 'Password baru minimal 6 karakter!';
        $message_type = 'danger';
    }
    else {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update_stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($update_stmt->execute()) {
            $message = 'Password berhasil diubah!';
            $message_type = 'success';
        } else {
            $message = 'Gagal mengubah password. Silakan coba lagi.';
            $message_type = 'danger';
        }
        $update_stmt->close();
    }
}

include __DIR__ . '/../templates/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-user-circle me-2"></i>
                        Profile User
                    </h4>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- User Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="text-primary mb-3">Informasi Akun</h5>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nama Lengkap:</label>
                                <p class="form-control-plaintext"><?= htmlspecialchars($user['name']) ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nomor Telepon:</label>
                                <p class="form-control-plaintext"><?= htmlspecialchars($user['phone']) ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Email:</label>
                                <p class="form-control-plaintext"><?= htmlspecialchars($user['email']) ?></p>
                            </div>
                            <!-- Hapus tampilan saldo akun -->
                        </div>
                        <div class="col-md-6">
                            <div class="text-center">
                                <div class="profile-avatar mb-3">
                                    <i class="fas fa-user-circle fa-6x text-primary"></i>
                                </div>
                                <h6 class="text-muted">Member sejak</h6>
                                <p class="text-primary fw-bold"><?= date('d F Y', strtotime($user['created_at'])) ?></p>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Change Password Form -->
                    <div class="mt-4">
                        <h5 class="text-primary mb-3">Ubah Password</h5>
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Password Saat Ini</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="whatsapp_verification" class="form-label">Verifikasi Nomor WhatsApp</label>
                                        <input type="text" class="form-control" id="whatsapp_verification" name="whatsapp_verification" placeholder="08XXXXXXXXXX" required>
                                        <small class="form-text text-muted">Masukkan nomor WhatsApp yang terdaftar untuk verifikasi</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">Password Baru</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                            </div>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="fas fa-key me-2"></i>
                                    Ubah Password
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Quick Actions -->
                    <div class="mt-4">
                        <h5 class="text-primary mb-3">Aksi Cepat</h5>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <a href="history.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-history me-2"></i>
                                    Riwayat Transaksi
                                </a>
                            </div>
                            <div class="col-md-4 mb-2">
                                <a href="index.php#courts" class="btn btn-outline-success w-100">
                                    <i class="fas fa-calendar-plus me-2"></i>
                                    Booking Lapangan
                                </a>
                            </div>
                            <div class="col-md-4 mb-2">
                                <a href="contact.php" class="btn btn-outline-info w-100">
                                    <i class="fas fa-headset me-2"></i>
                                    Hubungi Admin
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
