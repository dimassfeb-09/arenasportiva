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

// Handle court status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $court_id = $_POST['court_id'];
    $new_status = $_POST['new_status'];
    
    $stmt = $mysqli->prepare("UPDATE courts SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $court_id);
    
    if ($stmt->execute()) {
        $message = 'Status lapangan berhasil diubah!';
        $message_type = 'success';
    } else {
        $message = 'Gagal mengubah status lapangan.';
        $message_type = 'danger';
    }
    $stmt->close();
}

// Get all courts with booking statistics
$stmt = $mysqli->prepare("
    SELECT c.*, 
           COUNT(b.id) as total_bookings,
           SUM(CASE WHEN b.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
           SUM(CASE WHEN b.status = 'pending' THEN 1 ELSE 0 END) as pending_bookings
    FROM courts c
    LEFT JOIN bookings b ON c.id = b.court_id
    GROUP BY c.id
    ORDER BY c.id ASC
");
$stmt->execute();
$courts = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Lapangan - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #dc3545;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
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
        
        .courts-card {
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
        
        .court-image {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-available { background: #d4edda; color: #155724; }
        .status-maintenance { background: #fff3cd; color: #856404; }
        .status-unavailable { background: #f8d7da; color: #721c24; }
        
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
        .stat-pending { background: #fff3cd; color: #856404; }
        
        .court-type-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .type-futsal { background: #e3f2fd; color: #1976d2; }
        .type-badminton { background: #f3e5f5; color: #7b1fa2; }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-available { background: #d4edda; color: #155724; }
        .status-maintenance { background: #fff3cd; color: #856404; }
        .status-unavailable { background: #f8d7da; color: #721c24; }
        
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
                <a href="courts.php" class="nav-link active">
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
                <a href="users.php" class="nav-link">
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
                    <i class="fas fa-futbol me-2 text-primary"></i>
                    Kelola Lapangan
                </h4>
                <small class="text-muted">Kelola status dan informasi lapangan</small>
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

        <!-- Courts Table -->
        <div class="courts-card">
            <h5 class="mb-3">
                <i class="fas fa-list me-2"></i>
                Daftar Lapangan
            </h5>
            
            <?php if ($courts->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Lapangan</th>
                                <th>Jenis</th>
                                <th>Harga</th>
                                <th>Status</th>
                                <th>Statistik Booking</th>
                                <th>Deskripsi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($court = $courts->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="../assets/img/<?= $court['type'] ?>.jpg" 
                                                 alt="<?= $court['name'] ?>" 
                                                 class="court-image me-3">
                                            <div>
                                                <strong><?= htmlspecialchars($court['name']) ?></strong>
                                                <br>
                                                <small class="text-muted">ID: <?= $court['id'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="court-type-badge type-<?= $court['type'] ?>">
                                            <?= ucfirst($court['type']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="text-success">
                                            Rp <?= number_format($court['price_per_hour'], 0, ',', '.') ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        switch ($court['status']) {
                                            case 'available': $status_class = 'status-available'; break;
                                            case 'maintenance': $status_class = 'status-maintenance'; break;
                                            case 'unavailable': $status_class = 'status-unavailable'; break;
                                        }
                                        ?>
                                        <span class="status-badge <?= $status_class ?>">
                                            <?= ucfirst($court['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="stats-mini">
                                            <span class="stat-mini stat-bookings">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?= $court['total_bookings'] ?>
                                            </span>
                                            <span class="stat-mini stat-confirmed">
                                                <i class="fas fa-check me-1"></i>
                                                <?= $court['confirmed_bookings'] ?>
                                            </span>
                                            <span class="stat-mini stat-pending">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= $court['pending_bookings'] ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= htmlspecialchars(substr($court['description'], 0, 50)) ?>...
                                        </small>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-outline-secondary btn-action dropdown-toggle" 
                                                    type="button" 
                                                    data-bs-toggle="dropdown">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="court_id" value="<?= $court['id'] ?>">
                                                        <button type="submit" name="toggle_status" value="available" 
                                                                class="dropdown-item <?= $court['status'] === 'available' ? 'active' : '' ?>"
                                                                onclick="return confirm('Set status lapangan menjadi Available?')">
                                                            <i class="fas fa-check-circle me-2"></i>
                                                            Available
                                                        </button>
                                                        <input type="hidden" name="new_status" value="available">
                                                    </form>
                                                </li>
                                                <li>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="court_id" value="<?= $court['id'] ?>">
                                                        <button type="submit" name="toggle_status" value="maintenance" 
                                                                class="dropdown-item <?= $court['status'] === 'maintenance' ? 'active' : '' ?>"
                                                                onclick="return confirm('Set status lapangan menjadi Maintenance?')">
                                                            <i class="fas fa-tools me-2"></i>
                                                            Maintenance
                                                        </button>
                                                        <input type="hidden" name="new_status" value="maintenance">
                                                    </form>
                                                </li>
                                                <li>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="court_id" value="<?= $court['id'] ?>">
                                                        <button type="submit" name="toggle_status" value="unavailable" 
                                                                class="dropdown-item <?= $court['status'] === 'unavailable' ? 'active' : '' ?>"
                                                                onclick="return confirm('Set status lapangan menjadi Unavailable?')">
                                                            <i class="fas fa-times-circle me-2"></i>
                                                            Unavailable
                                                        </button>
                                                        <input type="hidden" name="new_status" value="unavailable">
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-futbol fa-3x mb-3"></i>
                    <p>Belum ada lapangan terdaftar</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
