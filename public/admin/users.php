<?php
session_start();
require_once __DIR__ . '/../../src/db_connect.php';

// Redirect if not admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$message = '';
$message_type = '';

// Get message from URL parameters if they exist
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = $_GET['message'];
    $message_type = $_GET['type'];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Discount handling removed

        // Handle add/edit user
        if (isset($_POST['save_user'])) {
            $user_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
            $name = trim($_POST['name']);
            $username = trim($_POST['username']);
            $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
            $phone = trim($_POST['phone']);
            $password = $_POST['password'];

            if (!$name || !$username || !$email || !$phone) {
                throw new Exception('Semua field wajib diisi.');
            }

            if ($user_id) { // Edit user
                $name = $mysqli->real_escape_string($name);
                $username = $mysqli->real_escape_string($username);
                $email = $mysqli->real_escape_string($email);
                $phone = $mysqli->real_escape_string($phone);
                $user_id = (int)$user_id;
                
                if (!empty($password)) {
                    if (strlen($password) < 6) throw new Exception('Password minimal 6 karakter.');
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $hashed_password = $mysqli->real_escape_string($hashed_password);
                    $query = "UPDATE users SET name='$name', username='$username', 
                             email='$email', phone='$phone', password='$hashed_password' 
                             WHERE id=$user_id";
                } else {
                    $query = "UPDATE users SET name='$name', username='$username', 
                             email='$email', phone='$phone' 
                             WHERE id=$user_id";
                }
                
                if (!$mysqli->query($query)) {
                    throw new Exception('Gagal mengupdate user');
                }
                $message = 'User berhasil diupdate!';
            } else { // Add user
                if (empty($password) || strlen($password) < 6) {
                    throw new Exception('Password wajib diisi dan minimal 6 karakter.');
                }
                
                $name = $mysqli->real_escape_string($name);
                $username = $mysqli->real_escape_string($username);
                $email = $mysqli->real_escape_string($email);
                $phone = $mysqli->real_escape_string($phone);
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $hashed_password = $mysqli->real_escape_string($hashed_password);
                
                $query = "INSERT INTO users (name, username, email, phone, password, role) 
                         VALUES ('$name', '$username', '$email', '$phone', '$hashed_password', 'user')";
                         
                if (!$mysqli->query($query)) {
                    throw new Exception('Gagal menambah user');
                }
                $message = 'User baru berhasil ditambahkan!';
            }
            $message_type = 'success';
        }

        // Handle delete user
        if (isset($_POST['delete_user'])) {
            $user_id = (int)$_POST['user_id'];
            if ($user_id) {
                $mysqli->begin_transaction();
                try {
                    // Delete payments first
                    if (!$mysqli->query("DELETE FROM payments WHERE booking_id IN (SELECT id FROM bookings WHERE user_id = $user_id)")) {
                        throw new Exception('Gagal menghapus pembayaran user');
                    }
                    // Then delete bookings
                    if (!$mysqli->query("DELETE FROM bookings WHERE user_id = $user_id")) {
                        throw new Exception('Gagal menghapus booking user');
                    }
                    // Finally delete the user
                    if (!$mysqli->query("DELETE FROM users WHERE id = $user_id")) {
                        throw new Exception('Gagal menghapus user');
                    }
                    
                    $mysqli->commit();
                    $message = 'User berhasil dihapus!';
                    $message_type = 'success';
                } catch (Exception $e) {
                    $mysqli->rollback();
                    throw $e;
                }
            }
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

// Get all users with booking statistics
$search = $_GET['search'] ?? '';
$sql = "SELECT u.id, u.name, u.username, u.phone, u.email,
           COUNT(b.id) as total_bookings,
           SUM(CASE WHEN b.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings
    FROM users u
    LEFT JOIN bookings b ON u.id = b.user_id
    WHERE u.role = 'user'";

if (!empty($search)) {
    $search = $mysqli->real_escape_string($search);
    $sql .= " AND (u.name LIKE '%$search%' OR u.email LIKE '%$search%' 
              OR u.phone LIKE '%$search%' OR u.username LIKE '%$search%')";
}

$sql .= " GROUP BY u.id ORDER BY u.name ASC";
$result = $mysqli->query($sql);

if (!$result) {
    error_log("MySQL Error: " . $mysqli->error);
    throw new Exception('Gagal mengambil data pengguna');
}

$users = [];

// Include header after all processing
$page_title = "Kelola User";
include '../../templates/admin_header.php';
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

</script>

<!-- Add/Edit User Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel">...</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="user_id">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">No. Telepon</label>
                            <input type="text" class="form-control" id="phone" name="phone" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password">
                        <small class="form-text text-muted" id="passwordHelp"></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" name="save_user">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Daftar Pengguna</h5>
        <div class="d-flex">
            <form class="me-2" method="get">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Cari..." value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
                </div>
            </form>
            <button class="btn btn-primary" onclick="openUserModal('add')">
                <i class="fas fa-plus me-1"></i> Tambah
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Pengguna</th>
                        <th>Kontak</th>
                        <th>Total Booking</th>
                        <th>Booking Aktif</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="https://i.pravatar.cc/40?u=<?= $user['id'] ?>" class="rounded-circle me-3" alt="Avatar">
                                        <div>
                                            <strong><?= htmlspecialchars($user['name']) ?></strong>
                                            <div class="text-muted">@<?= htmlspecialchars($user['username']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($user['email']) ?></div>
                                    <div class="text-muted"><?= htmlspecialchars($user['phone']) ?></div>
                                </td>
                                <td><?= number_format($user['total_bookings']) ?></td>
                                <td><?= number_format($user['confirmed_bookings']) ?></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary me-1" onclick='openUserModal("edit", <?= json_encode($user) ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus user ini? Semua data terkait akan dihapus.');">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" name="delete_user" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Tidak ada data pengguna.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>


function openUserModal(mode, data = null) {
    const modal = new bootstrap.Modal(document.getElementById('userModal'));
    const form = document.getElementById('userModal').querySelector('form');
    const modalLabel = document.getElementById('userModalLabel');
    const passwordHelp = document.getElementById('passwordHelp');
    const passwordInput = document.getElementById('password');

    form.reset();
    document.getElementById('user_id').value = '';
    passwordInput.required = false;

    if (mode === 'add') {
        modalLabel.textContent = 'Tambah Pengguna Baru';
        passwordHelp.textContent = 'Password minimal 6 karakter.';
        passwordInput.required = true;
    } else if (mode === 'edit' && data) {
        modalLabel.textContent = 'Edit Pengguna';
        passwordHelp.textContent = 'Kosongkan jika tidak ingin mengubah password.';
        
        document.getElementById('user_id').value = data.id;
        document.getElementById('name').value = data.name;
        document.getElementById('username').value = data.username;
        document.getElementById('email').value = data.email;
        document.getElementById('phone').value = data.phone;
    }

    modal.show();
}
</script>

<?php include '../../templates/admin_footer.php'; ?>
