<?php
session_start();
require_once __DIR__ . '/../src/db_connect.php';

// Check if user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$message = '';
$message_type = '';

// Handle coupon update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_coupon'])) {
    $user_id = $_POST['user_id'];
    $coupon_discount = (int)$_POST['coupon_discount'];
    $stmt = $mysqli->prepare("UPDATE users SET coupon_discount = ? WHERE id = ?");
    $stmt->bind_param("ii", $coupon_discount, $user_id);
    if ($stmt->execute()) {
        $message = 'Coupon/diskon user berhasil diupdate!';
        $message_type = 'success';
    } else {
        $message = 'Gagal update coupon user.';
        $message_type = 'danger';
    }
    $stmt->close();
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $user_id = $_POST['user_id'];
    $new_password = $_POST['new_password'];
    
    if (strlen($new_password) >= 6) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            $message = 'Password user berhasil direset!';
            $message_type = 'success';
        } else {
            $message = 'Gagal mereset password user.';
            $message_type = 'danger';
        }
        $stmt->close();
    } else {
        $message = 'Password minimal 6 karakter!';
        $message_type = 'danger';
    }
}

// Handle tambah user baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name = trim($_POST['add_name']);
    $email = trim($_POST['add_email']);
    $phone = trim($_POST['add_phone']);
    $password = $_POST['add_password'];
    if ($name && $email && $phone && strlen($password) >= 6) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'user')");
        $stmt->bind_param("ssss", $name, $email, $phone, $hashed_password);
        if ($stmt->execute()) {
            $message = 'User baru berhasil ditambahkan!';
            $message_type = 'success';
        } else {
            $message = 'Gagal menambah user baru.';
            $message_type = 'danger';
        }
        $stmt->close();
    } else {
        $message = 'Data tidak lengkap atau password kurang dari 6 karakter!';
        $message_type = 'danger';
    }
}

// Get all users
$stmt = $mysqli->prepare("
    SELECT u.id, u.name, u.phone, u.email, u.coupon_discount,
           COUNT(b.id) as total_bookings,
           SUM(CASE WHEN b.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
           SUM(CASE WHEN b.status = 'rejected' THEN 1 ELSE 0 END) as rejected_bookings
    FROM users u
    LEFT JOIN bookings b ON u.id = b.user_id
    WHERE u.role = 'user'
    GROUP BY u.id ORDER BY u.created_at DESC
");
$stmt->execute();
$users = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #dc3545;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
                <form class="mb-3" method="get">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Cari user (nama/email/telepon)" value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-primary" type="submit">Cari</button>
                    </div>
                </form>

        }
        
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
        }
        
        .sidebar-header {
            background: rgba(255, 255, 255, 0.1);
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-brand {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            text-decoration: none;
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .nav-item {
            margin-bottom: 0.5rem;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 0 25px 25px 0;
            margin-right: 1rem;
        }
        
        .nav-link:hover, .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        
        .top-bar {
            background: white;
            padding: 1rem 2rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .users-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--secondary-color);
            background: #f8f9fa;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        
        .btn-action {
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
            border-radius: 20px;
        }
        
        .stats-mini {
            display: flex;
            gap: 0.5rem;
            font-size: 0.8rem;
        }
        
        .stat-mini {
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            font-weight: 500;
        }
        
        .stat-bookings { background: #e3f2fd; color: #1976d2; }
        .stat-confirmed { background: #e8f5e8; color: #2e7d32; }
        .stat-rejected { background: #ffebee; color: #c62828; }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
        <!-- Modal Edit Coupon -->
        <div class="modal fade" id="editCouponModal" tabindex="-1" aria-labelledby="editCouponLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="post">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editCouponLabel">Edit Coupon/Diskon User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="user_id" id="modal_user_id">
                            <div class="mb-3">
                                <label for="coupon_discount" class="form-label">Nominal Diskon (Rp)</label>
                                <input type="number" class="form-control" name="coupon_discount" id="modal_coupon_discount" min="0" value="0">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" name="update_coupon">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-brand">
                <i class="fas fa-shield-alt me-2"></i>
                Admin Panel
            </a>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-item">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="courts.php" class="nav-link">
                    <i class="fas fa-futbol me-2"></i>
                    Kelola Lapangan
                </a>
            </div>
            <div class="nav-item">
                <a href="transactions.php" class="nav-link">
                    <i class="fas fa-exchange-alt me-2"></i>
                    Kelola Transaksi
                </a>
            </div>
            <div class="nav-item">
                <a href="users.php" class="nav-link active">
                    <i class="fas fa-users me-2"></i>
                    Kelola User
                </a>
            </div>
            <div class="nav-item">
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    Logout
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div>
                <h4 class="mb-0">
                    <i class="fas fa-users me-2 text-primary"></i>
                    Kelola User
                </h4>
                <small class="text-muted">Kelola data user dan bantu reset password</small>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-outline-primary me-2">
                    <i class="fas fa-arrow-left me-1"></i>
                    Kembali
                </a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Users Table -->
        <div class="users-card">
            <h5 class="mb-3">
                <i class="fas fa-list me-2"></i>
                Daftar User
            </h5>
            
            <div class="d-flex justify-content-end mb-3">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-user-plus me-1"></i> Tambah User
                </button>
            </div>
            
            <?php if ($users->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Kontak</th>
                                <th>Email</th>
                                <th>Coupon/Diskon</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['name']) ?></td>
                                <td><?= htmlspecialchars($user['phone']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= isset($user['coupon_discount']) ? 'Rp ' . number_format($user['coupon_discount'], 0, ',', '.') : '-' ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#editCouponModal" onclick="setCouponModal(<?= $user['id'] ?>, <?= $user['coupon_discount'] ?>)">Edit Coupon</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                            <?php while ($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-3">
                                                <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <strong><?= htmlspecialchars($user['name']) ?></strong>
                                                <br>
                                                <small class="text-muted">ID: <?= $user['id'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?= htmlspecialchars($user['phone']) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <strong class="text-success">
                                            <!-- Balance dihapus -->
                                        </strong>
                                    </td>
                                    <td>
                                        <div class="stats-mini">
                                            <span class="stat-mini stat-bookings">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?= $user['total_bookings'] ?>
                                            </span>
                                            <span class="stat-mini stat-confirmed">
                                                <i class="fas fa-check me-1"></i>
                                                <?= $user['confirmed_bookings'] ?>
                                            </span>
                                            <span class="stat-mini stat-rejected">
                                                <i class="fas fa-times me-1"></i>
                                                <?= $user['rejected_bookings'] ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-active">
                                            <i class="fas fa-circle me-1"></i>
                                            Aktif
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <button class="btn btn-warning btn-action" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#resetPasswordModal"
                                                data-user-id="<?= $user['id'] ?>"
                                                data-user-name="<?= htmlspecialchars($user['name']) ?>">
                                            <i class="fas fa-key"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <script>
                    function setCouponModal(userId, couponDiscount) {
                        document.getElementById('modal_user_id').value = userId;
                        document.getElementById('modal_coupon_discount').value = couponDiscount;
                    }
                    </script>
                </div>
            <?php else: ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-users fa-3x mb-3"></i>
                    <p>Belum ada user terdaftar</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-key me-2"></i>
                        Reset Password User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">User:</label>
                            <p id="resetUserName" class="form-control-plaintext fw-bold"></p>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">Password Baru</label>
                            <input type="text" class="form-control" id="newPassword" name="new_password" 
                                   placeholder="Masukkan password baru" required minlength="6">
                            <small class="form-text text-muted">Password minimal 6 karakter</small>
                        </div>
                        <input type="hidden" name="user_id" id="resetUserId">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="reset_password" class="btn btn-warning">
                            <i class="fas fa-key me-2"></i>
                            Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addUserLabel">Tambah User Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="userName" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="userName" name="add_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="userPhone" class="form-label">Nomor Telepon</label>
                            <input type="text" class="form-control" id="userPhone" name="add_phone" required>
                        </div>
                        <div class="mb-3">
                            <label for="userEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="userEmail" name="add_email" required>
                        </div>
                        <div class="mb-3">
                            <label for="userPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="userPassword" name="add_password" required minlength="6">
                            <small class="form-text text-muted">Password minimal 6 karakter</small>
                        </div>
                        <div class="mb-3">
                            <label for="couponDiscount" class="form-label">Nominal Diskon (Rp)</label>
                            <input type="number" class="form-control" id="couponDiscount" name="coupon_discount" min="0" value="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success" name="add_user">
                            <i class="fas fa-user-plus me-2"></i>
                            Tambah User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle reset password modal
        document.addEventListener('DOMContentLoaded', function() {
            const resetModal = document.getElementById('resetPasswordModal');
            if (resetModal) {
                resetModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const userId = button.getAttribute('data-user-id');
                    const userName = button.getAttribute('data-user-name');
                    
                    document.getElementById('resetUserId').value = userId;
                    document.getElementById('resetUserName').textContent = userName;
                });
            }
        });
    </script>
</body>
</html>
