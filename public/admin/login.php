<?php
session_start();
require_once __DIR__ . '/../../src/db_connect.php';
require_once __DIR__ . '/../../src/auth.php';


// Redirect if already logged in as admin
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $result = loginAdmin($username, $password);
    
    if ($result['success']) {
        header('Location: dashboard.php');
        exit();
    } else {
        $error = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Arena Sportiva</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="/booking/public/assets/css/login.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="card login-card">
            <div class="row g-0">
                <div class="col-lg-6">
                    <div class="login-form-side">
                        <div class="text-center mb-5">
                            <h1 class="h2 fw-bold">Admin Login</h1>
                            <p class="text-muted">Silakan masuk untuk melanjutkan</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger d-flex align-items-center" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <div>
                                    <?= htmlspecialchars($error) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required autofocus>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary">
                                    Login
                                </button>
                            </div>
                             <div class="text-center">
                                <a href="/" class="text-decoration-none"><i class="fas fa-arrow-left me-1"></i> Kembali ke Beranda</a>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-flex login-image-side">
                    <div>
                        <i class="fas fa-shield-alt brand-logo"></i>
                        <h2>Arena Sportiva</h2>
                        <p>Panel administrasi untuk mengelola pemesanan lapangan, pengguna, dan laporan.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
