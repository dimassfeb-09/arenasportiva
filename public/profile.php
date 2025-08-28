<?php
session_start();
require_once __DIR__ . '/../src/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_name'])) {
        $new_name = trim($_POST['new_name']);
        if (empty($new_name)) {
            $message = 'Nama tidak boleh kosong!';
            $message_type = 'danger';
        } else {
            $update_stmt = $mysqli->prepare("UPDATE users SET name = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_name, $user_id);
            if ($update_stmt->execute()) {
                $_SESSION['success_message'] = 'Nama berhasil diperbarui!';
                header('Location: profile.php');
                exit();
            } else {
                $message = 'Gagal memperbarui nama.';
                $message_type = 'danger';
            }
            $update_stmt->close();
        }
    }

    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (!password_verify($current_password, $user['password'])) {
            $message = 'Password saat ini salah!';
            $message_type = 'danger';
        } elseif ($new_password !== $confirm_password) {
            $message = 'Password baru dan konfirmasi tidak cocok!';
            $message_type = 'danger';
        } elseif (strlen($new_password) < 6) {
            $message = 'Password baru minimal 6 karakter!';
            $message_type = 'danger';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            if ($update_stmt->execute()) {
                $_SESSION['success_message'] = 'Password berhasil diubah!';
                header('Location: profile.php?tab=password');
                exit();
            } else {
                $message = 'Gagal mengubah password.';
                $message_type = 'danger';
            }
            $update_stmt->close();
        }
    }
}

if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    $message_type = 'success';
    unset($_SESSION['success_message']);
}

$active_tab = $_GET['tab'] ?? 'profile';

include __DIR__ . '/../templates/header.php';
?>

<div class="profile-page py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-3">
                <div class="profile-nav-card text-center mb-4">
                    <div class="profile-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h5 class="mb-1"><?= htmlspecialchars($user['name']) ?></h5>
                    <p class="text-muted small">Member Sejak <?= date('d F Y', strtotime($user['created_at'])) ?></p>
                </div>
                <div class="profile-nav-card">
                    <div class="nav flex-column nav-pills" role="tablist">
                        <a class="nav-link <?= $active_tab === 'profile' ? 'active' : '' ?>" href="?tab=profile" role="tab"><i class="fas fa-user-edit me-2"></i>Edit Profil</a>
                        <a class="nav-link <?= $active_tab === 'password' ? 'active' : '' ?>" href="?tab=password" role="tab"><i class="fas fa-key me-2"></i>Ubah Password</a>
                        <a class="nav-link" href="history.php"><i class="fas fa-history me-2"></i>Riwayat Transaksi</a>
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="profile-content-card">
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="tab-content">
                        <!-- Profile Tab -->
                        <div class="tab-pane fade <?= $active_tab === 'profile' ? 'show active' : '' ?>" id="profile" role="tabpanel">
                            <h4>Informasi Akun</h4>
                            <hr>
                            <form method="post">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Nama Lengkap</label>
                                        <input type="text" name="new_name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Username</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Email</label>
                                        <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Nomor Telepon</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>" disabled>
                                    </div>
                                </div>
                                <button type="submit" name="update_name" class="btn btn-primary">Simpan Perubahan</button>
                            </form>
                        </div>

                        <!-- Password Tab -->
                        <div class="tab-pane fade <?= $active_tab === 'password' ? 'show active' : '' ?>" id="password" role="tabpanel">
                            <h4>Ubah Password</h4>
                            <hr>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Password Saat Ini</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Password Baru</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                <button type="submit" name="change_password" class="btn btn-primary">Ubah Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>