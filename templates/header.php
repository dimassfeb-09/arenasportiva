<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arena Sportiva - Booking Lapangan Olahraga</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top flix-nav">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/img/logo.png" alt="Arena Sportiva" height="45">
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="history.php">Order History</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact Us</a>
                    </li>
                    <li class="nav-item ms-2">
                        <?php if(isset($_SESSION['user_id'])): ?>
                        <!-- User Profile Dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-2"></i>
                                <span class="d-none d-sm-inline"><?= htmlspecialchars($_SESSION['name']) ?></span>
                                <?php
                                // Tampilkan saldo diskon di navbar
                                if (isset($_SESSION['user_id'])) {
                                    require_once __DIR__ . '/../src/db_connect.php';
                                    $stmt = $mysqli->prepare("SELECT coupon_discount FROM users WHERE id = ?");
                                    $stmt->bind_param('i', $_SESSION['user_id']);
                                    $stmt->execute();
                                    $stmt->bind_result($coupon_discount_nav);
                                    $stmt->fetch();
                                    $stmt->close();
                                    echo '<span class="badge bg-success ms-2">Diskon: Rp ' . number_format($coupon_discount_nav, 0, ',', '.') . '</span>';
                                }
                                ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="history.php"><i class="fas fa-history me-2"></i>Riwayat Transaksi</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                Login / Register
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#" data-bs-toggle="offcanvas" data-bs-target="#authOffcanvas">User Login</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/booking/admin/login.php"><i class="fas fa-user-shield me-2"></i>Login as Admin</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Auth Offcanvas -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="authOffcanvas">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Login / Register</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav nav-tabs" id="authTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">Login</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab">Register</button>
                </li>
            </ul>
            <div class="tab-content mt-3" id="authTabContent">
                <div class="tab-pane fade show active" id="login" role="tabpanel">
                    <?php if (!empty($_SESSION['login_error'])): ?>
                        <div class="alert alert-danger small mb-3">
                            <?= htmlspecialchars($_SESSION['login_error']) ?>
                        </div>
                        <?php unset($_SESSION['login_error']); ?>
                    <?php endif; ?>
                    <?php if (!empty($_SESSION['login_error'])): ?>
                        <div class="alert alert-danger small mb-3">
                            <?= htmlspecialchars($_SESSION['login_error']) ?>
                        </div>
                        <?php unset($_SESSION['login_error']); ?>
                    <?php endif; ?>
                    <form action="index.php" method="POST">
                        <div class="mb-3">
                            <label for="loginUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="loginUsername" name="phone" placeholder="Masukkan username Anda" required>
                            <input type="hidden" name="identifier" value="">
                        </div>
                        <div class="mb-3">
                            <label for="loginPassword" class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="loginPassword" name="password" required>
                                <button type="button" class="btn btn-outline-secondary" tabindex="-1" onclick="togglePassword('loginPassword', this)"><i class="fa fa-eye"></i></button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                        <div class="text-center mt-2">
                            <a href="forgot_password.php" class="small">Lupa Password?</a>
                        </div>
                    </form>
                </div>
                <div class="tab-pane fade" id="register" role="tabpanel">
                    <form action="register.php" method="POST">
                        <div class="mb-3">
                            <label for="registerUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="registerUsername" name="userId" placeholder="Masukkan username Anda" required>
                        </div>
                        <div class="mb-3">
                            <label for="registerPhone" class="form-label">Nomor Telepon</label>
                            <input type="text" class="form-control" id="registerPhone" name="phone" placeholder="08XXXXXXXXXX" required>
                        </div>
                        <div class="mb-3">
                            <label for="registerEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="registerEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="registerPassword" class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="registerPassword" name="password" required>
                                <button type="button" class="btn btn-outline-secondary" tabindex="-1" onclick="togglePassword('registerPassword', this)"><i class="fa fa-eye"></i></button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Register</button>
                    </form>
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
    </script>
    <!-- Toast: Login hint when trying to book -->
    <?php if (!isset($_SESSION['user_id'])): ?>
    <div class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 1100;">
        <div id="bookingLoginToast" class="toast align-items-center text-bg-dark border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="2500">
            <div class="d-flex">
                <div class="toast-body">
                    Ingin booking? Login terlebih dahulu ya!
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        // Detect any element that opens the auth offcanvas
        const triggers = document.querySelectorAll('[data-booking-trigger="1"]');
        const toastEl = document.getElementById('bookingLoginToast');
        let toast;
        if (toastEl && window.bootstrap && bootstrap.Toast) {
          toast = new bootstrap.Toast(toastEl);
        }
        triggers.forEach(function (el) {
          el.addEventListener('click', function () {
            if (toast) {
              toast.show();
            }
          }, { capture: true });
        });
      });
    </script>
    <?php endif; ?>

    <!-- Main Content Wrapper -->
    <div style="padding-top: 76px;">
