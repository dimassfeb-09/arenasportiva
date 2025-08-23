<?php
session_start();
require_once __DIR__ . '/../src/db_connect.php';

// Check if user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Get statistics
$stats = [];

// Total users
$stmt = $mysqli->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$stmt->execute();
$result = $stmt->get_result();
$stats['users'] = $result->fetch_assoc()['total'];
$stmt->close();

// Total courts
$stmt = $mysqli->prepare("SELECT COUNT(*) as total FROM courts");
$stmt->execute();
$result = $stmt->get_result();
$stats['courts'] = $result->fetch_assoc()['total'];
$stmt->close();

// Total bookings
$stmt = $mysqli->prepare("SELECT COUNT(*) as total FROM bookings");
$stmt->execute();
$result = $stmt->get_result();
$stats['bookings'] = $result->fetch_assoc()['total'];
$stmt->close();

// Pending payments
$stmt = $mysqli->prepare("SELECT COUNT(*) as total FROM payments WHERE status = 'pending'");
$stmt->execute();
$result = $stmt->get_result();
$stats['pending_payments'] = $result->fetch_assoc()['total'];
$stmt->close();

// Total revenue
$stmt = $mysqli->prepare("SELECT SUM(paid_amount) as total FROM payments WHERE status = 'success'");
$stmt->execute();
$result = $stmt->get_result();
$stats['revenue'] = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Monthly revenue (last 30 days)
$stmt = $mysqli->prepare("SELECT SUM(paid_amount) as total FROM payments WHERE status = 'success' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stmt->execute();
$result = $stmt->get_result();
$stats['monthly_revenue'] = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Daily revenue for chart (last 30 days)
$stmt = $mysqli->prepare("
    SELECT DATE(created_at) as date, SUM(paid_amount) as daily_revenue 
    FROM payments 
    WHERE status = 'success' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at) 
    ORDER BY date ASC
");
$stmt->execute();
$daily_revenue = $stmt->get_result();
$stmt->close();

// Recent bookings
$stmt = $mysqli->prepare("
    SELECT b.*, u.name as user_name, c.name as court_name, p.status as payment_status 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN courts c ON b.court_id = c.id 
    LEFT JOIN payments p ON b.id = p.booking_id 
    ORDER BY b.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_bookings = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Arena Sportiva</title>
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
        
        .welcome-text {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.users { border-left-color: var(--info-color); }
        .stat-card.courts { border-left-color: var(--success-color); }
        .stat-card.bookings { border-left-color: var(--warning-color); }
        .stat-card.payments { border-left-color: var(--primary-color); }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--secondary-color);
            font-weight: 500;
        }
        
        .recent-bookings {
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
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-success { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        
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
                <a href="dashboard.php" class="nav-link active">
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
            <div class="welcome-text">
                <i class="fas fa-sun me-2 text-warning"></i>
                Selamat Datang, <?= htmlspecialchars($_SESSION['name']) ?>!
            </div>
            <div>
                <a href="logout.php" class="btn btn-outline-danger">
                    <i class="fas fa-sign-out-alt me-1"></i>
                    Logout
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card users">
                <div class="stat-icon text-info">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number text-info"><?= number_format($stats['users']) ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            
            <div class="stat-card courts">
                <div class="stat-icon text-success">
                    <i class="fas fa-futbol"></i>
                </div>
                <div class="stat-number text-success"><?= number_format($stats['courts']) ?></div>
                <div class="stat-label">Total Lapangan</div>
            </div>
            
            <div class="stat-card bookings">
                <div class="stat-icon text-warning">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-number text-warning"><?= number_format($stats['bookings']) ?></div>
                <div class="stat-label">Total Booking</div>
            </div>
            
            <div class="stat-card payments">
                <div class="stat-icon text-danger">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number text-danger"><?= number_format($stats['pending_payments']) ?></div>
                <div class="stat-label">Pending Payment</div>
            </div>
        </div>

        <!-- Revenue Cards -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="stat-card" style="border-left-color: #28a745;">
                    <div class="stat-icon text-success">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-number text-success">Rp <?= number_format($stats['revenue'], 0, ',', '.') ?></div>
                    <div class="stat-label">Total Pendapatan</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card" style="border-left-color: #17a2b8;">
                    <div class="stat-icon text-info">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-number text-info">Rp <?= number_format($stats['monthly_revenue'], 0, ',', '.') ?></div>
                    <div class="stat-label">Omset 30 Hari Terakhir</div>
                </div>
            </div>
        </div>

        <!-- Revenue Chart -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="stat-card">
                    <h5 class="mb-3">
                        <i class="fas fa-chart-line me-2"></i>
                        Grafik Pendapatan 30 Hari Terakhir
                    </h5>
                    <canvas id="revenueChart" width="400" height="50" style="max-height:120px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="recent-bookings">
            <h5 class="mb-3">
                <i class="fas fa-history me-2"></i>
                Booking Terbaru
            </h5>
            
            <?php if ($recent_bookings->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Lapangan</th>
                                <th>Tanggal</th>
                                <th>Jam</th>
                                <th>Status</th>
                                <th>Payment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($booking['user_name']) ?></td>
                                    <td><?= htmlspecialchars($booking['court_name']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($booking['start_datetime'])) ?></td>
                                    <td><?= date('H:i', strtotime($booking['start_datetime'])) ?></td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        switch ($booking['status']) {
                                            case 'pending': $status_class = 'status-pending'; break;
                                            case 'confirmed': $status_class = 'status-success'; break;
                                            case 'rejected': $status_class = 'status-rejected'; break;
                                        }
                                        ?>
                                        <span class="status-badge <?= $status_class ?>">
                                            <?= ucfirst($booking['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $payment_class = '';
                                        switch ($booking['payment_status']) {
                                            case 'pending': $payment_class = 'status-pending'; break;
                                            case 'success': $payment_class = 'status-success'; break;
                                            case 'failed': $payment_class = 'status-rejected'; break;
                                        }
                                        ?>
                                        <span class="status-badge <?= $payment_class ?>">
                                            <?= ucfirst($booking['payment_status'] ?? 'pending') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>Belum ada booking</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Revenue Chart
        const ctx = document.getElementById('revenueChart').getContext('2d');
        
        // Prepare data for chart
        const chartData = {
            labels: [],
            datasets: [{
                label: 'Pendapatan Harian',
                data: [],
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4,
                fill: true
            }]
        };

        // Get data from PHP
        <?php if ($daily_revenue->num_rows > 0): ?>
            <?php while ($row = $daily_revenue->fetch_assoc()): ?>
                chartData.labels.push('<?= date('d/m', strtotime($row['date'])) ?>');
                chartData.datasets[0].data.push(<?= $row['daily_revenue'] ?>);
            <?php endwhile; ?>
        <?php endif; ?>

        new Chart(ctx, {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
