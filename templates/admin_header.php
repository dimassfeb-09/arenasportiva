<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /booking/public/admin/login.php');
    exit();
}
require_once dirname(__FILE__) . '/../src/db_connect.php';

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Admin Panel' ?> - Arena Sportiva</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <div class="sidebar-wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <a href="dashboard.php" class="sidebar-brand">
                    <i class="fas fa-shield-alt me-2"></i>
                    <span>Admin Panel</span>
                </a>
            </div>
            
            <ul class="sidebar-nav">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?= ($page_title == 'Dashboard') ? 'active' : '' ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="courts.php" class="nav-link <?= ($page_title == 'Kelola Lapangan') ? 'active' : '' ?>">
                        <i class="fas fa-futbol"></i>
                        <span>Kelola Lapangan</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="transactions.php" class="nav-link <?= ($page_title == 'Kelola Transaksi') ? 'active' : '' ?>">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Kelola Transaksi</span>

                    </a>
                </li>
                <li class="nav-item">
                    <a href="users.php" class="nav-link <?= ($page_title == 'Kelola User') ? 'active' : '' ?>">
                        <i class="fas fa-users"></i>
                        <span>Kelola User</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="report.php" class="nav-link <?= ($page_title == 'Laporan Pemasukan') ? 'active' : '' ?>">
                        <i class="fas fa-chart-line"></i>
                        <span>Laporan</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="chat.php" class="nav-link <?= ($page_title == 'Chat dengan Pelanggan') ? 'active' : '' ?>">
                        <i class="fas fa-comments"></i>
                        <span>Chat</span>

                    </a>
                </li>
            </ul>

            <div class="sidebar-footer">
                 <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <header class="top-bar">
            <div class="d-flex align-items-center">
                <button class="btn btn-light d-lg-none me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title"><?= $page_title ?? 'Admin Panel' ?></h1>
            </div>
            
            <div class="d-flex align-items-center">
                <a href="logout.php" class="d-flex align-items-center text-decoration-none text-danger">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    <span>Logout</span>
                </a>
            </div>
        </header>
        
        <!-- Offcanvas Sidebar for mobile -->
        <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="sidebarOffcanvasLabel">Admin Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <!-- Sidebar content will be dynamically injected here by JS -->
            </div>
        </div>

        <main class="container-fluid mt-4">
