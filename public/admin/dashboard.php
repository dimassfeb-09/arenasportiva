<?php
$page_title = "Dashboard";
include '../../templates/admin_header.php';

// Get statistics using mysqli
$stats = [];

// Total users
$result = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
$stats['users'] = $result->fetch_assoc()['count'];

// Total courts
$result = $mysqli->query("SELECT COUNT(*) as count FROM courts");
$stats['courts'] = $result->fetch_assoc()['count'];

// Total bookings
$result = $mysqli->query("SELECT COUNT(*) as count FROM bookings");
$stats['bookings'] = $result->fetch_assoc()['count'];

// Total revenue
$result = $mysqli->query("SELECT COALESCE(SUM(p.amount), 0) as total FROM payments p WHERE p.status = 'success'");
$stats['revenue'] = $result->fetch_assoc()['total'];

// Daily revenue for chart (last 30 days)
$result = $mysqli->query("
    SELECT DATE(b.start_datetime) as date, COALESCE(SUM(p.amount), 0) as daily_revenue 
    FROM bookings b
    LEFT JOIN payments p ON b.id = p.booking_id AND p.status = 'success'
    WHERE b.start_datetime >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(b.start_datetime) 
    ORDER BY date ASC
");
$daily_revenue = [];
while ($row = $result->fetch_assoc()) {
    $daily_revenue[] = $row;
}

// Recent bookings
$result = $mysqli->query("
    SELECT b.*, u.name as user_name, c.name as court_name, p.status as payment_status, p.amount
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN courts c ON b.court_id = c.id
    LEFT JOIN payments p ON b.id = p.booking_id 
    ORDER BY b.created_at DESC 
    LIMIT 5
");
$recent_bookings = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recent_bookings[] = $row;
    }
}
?>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="stat-icon bg-primary-soft">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h5><?= number_format($stats['users']) ?></h5>
                    <p>Total Pengguna</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="stat-icon bg-success-soft">
                    <i class="fas fa-futbol"></i>
                </div>
                <div class="stat-info">
                    <h5><?= number_format($stats['courts']) ?></h5>
                    <p>Total Lapangan</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="stat-icon bg-warning-soft">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-info">
                    <h5><?= number_format($stats['bookings']) ?></h5>
                    <p>Total Booking</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="stat-icon bg-danger-soft">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-info">
                    <h5>Rp <?= number_format($stats['revenue'], 0, ',', '.') ?></h5>
                    <p>Total Pendapatan</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Revenue Chart -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                Grafik Pendapatan (30 Hari Terakhir)
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Bookings -->
<div class="card">
    <div class="card-header">
        Booking Terbaru
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Pengguna</th>
                        <th>Lapangan</th>
                        <th>Tanggal & Waktu</th>
                        <th>Status</th>
                        <th>Pembayaran</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recent_bookings) > 0): ?>
                        <?php foreach ($recent_bookings as $booking): ?>
                            <tr>
                                <td>#<?= $booking['id'] ?></td>
                                <td><?= htmlspecialchars($booking['user_name']) ?></td>
                                <td><?= htmlspecialchars($booking['court_name']) ?></td>
                                <td><?= date('d/m/Y, H:i', strtotime($booking['start_datetime'])) ?></td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($booking['status']) ?>">
                                        <?= ucfirst($booking['status']) ?>
                                    </span>
                                </td>
                                <td>
                                     <span class="status-badge status-<?= strtolower($booking['payment_status'] == 'paid' ? 'success' : 'pending') ?>">
                                        <?= ucfirst($booking['payment_status']) ?>
                                    </span>
                                </td>
                                <td class="fw-bold">Rp <?= number_format($booking['amount'] ?? 0, 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Belum ada data booking.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    
    const chartData = {
        labels: <?= json_encode(array_column($daily_revenue, 'date')) ?>,
        datasets: [{
            label: 'Pendapatan Harian',
            data: <?= json_encode(array_column($daily_revenue, 'daily_revenue')) ?>,
            borderColor: '#074173',
            backgroundColor: 'rgba(7, 65, 115, 0.1)',
            tension: 0.4,
            fill: true
        }]
    };

    new Chart(ctx, {
        type: 'line',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
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
});
</script>

<?php include '../../templates/admin_footer.php'; ?>
