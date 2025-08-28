<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arena Sportiva - Booking Lapangan Olahraga</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
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
                                <li><a class="dropdown-item" href="/admin/login.php"><i class="fas fa-user-shield me-2"></i>Login as Admin</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Auth Offcanvas -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="authOffcanvas">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold">Login / Register</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav nav-pills nav-justified mb-4" id="authTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab">
                        <i class="fas fa-user-plus me-2"></i>Register
                    </button>
                </li>
            </ul>
            <div class="tab-content" id="authTabContent">
                <div class="tab-pane fade show active" id="login" role="tabpanel">
                    <?php if (!empty($_SESSION['login_error'])): ?>
                        <div class="alert alert-danger small mb-3">
                            <?= htmlspecialchars($_SESSION['login_error']) ?>
                        </div>
                        <?php unset($_SESSION['login_error']); ?>
                    <?php endif; ?>
                    
                    <form action="index.php" method="POST" class="needs-validation" novalidate>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="loginUsername" name="username" placeholder="Username" required>
                            <label for="loginUsername"><i class="fas fa-user me-2"></i>Username</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="password" class="form-control pe-5" id="loginPassword" name="password" placeholder="Password" required>
                            <label for="loginPassword"><i class="fas fa-lock me-2"></i>Password</label>
                            <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y text-muted px-3" 
                                    style="z-index: 10;" tabindex="-1" 
                                    onclick="togglePassword('loginPassword', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-3 mb-3">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                        <div class="text-center">
                            <a href="forgot_password.php" class="text-decoration-none">
                                <i class="fas fa-key me-1"></i>Lupa Password?
                            </a>
                        </div>
                    </form>
                </div>
                <div class="tab-pane fade" id="register" role="tabpanel">
                    <form action="register.php" method="POST" class="needs-validation" novalidate>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="registerName" name="name" placeholder="Name" required>
                            <label for="registerName"><i class="fas fa-user me-2"></i>Nama Lengkap</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="registerUsername" name="username" placeholder="Username" required>
                            <label for="registerUsername"><i class="fas fa-user me-2"></i>Username</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="tel" class="form-control" id="registerPhone" name="phone" placeholder="08XXXXXXXXXX" 
                                   pattern="08[0-9]{8,11}" required>
                            <label for="registerPhone"><i class="fas fa-phone me-2"></i>Nomor Telepon</label>
                            <div class="invalid-feedback">
                                Masukkan nomor telepon yang valid (contoh: 081234567890)
                            </div>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="registerEmail" name="email" placeholder="name@example.com" required>
                            <label for="registerEmail"><i class="fas fa-envelope me-2"></i>Email</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="password" class="form-control pe-5" id="registerPassword" name="password" 
                                   placeholder="Password" minlength="6" required>
                            <label for="registerPassword"><i class="fas fa-lock me-2"></i>Password</label>
                            <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y text-muted px-3" 
                                    style="z-index: 10;" tabindex="-1" 
                                    onclick="togglePassword('registerPassword', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                            <div class="invalid-feedback">
                                Password minimal 6 karakter
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success w-100 py-3">
                            <i class="fas fa-user-plus me-2"></i>Register
                        </button>
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


    
    
    <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="container mt-3">
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['success_message']) ?>
            </div>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="container mt-3">
            <div class="alert alert-danger">
                <?= htmlspecialchars($_SESSION['error_message']) ?>
            </div>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>