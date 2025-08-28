<?php
$page_title = "Laporan Pemasukan";
include '../../templates/admin_header.php';

// Default date range: this month
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Fetch report data
$start_date = $mysqli->real_escape_string($start_date);
$end_date = $mysqli->real_escape_string($end_date);

$sql = "
    SELECT 
        COUNT(b.id) as total_bookings,
        SUM(p.amount) as total_revenue,
        AVG(p.amount) as average_revenue,
        COUNT(b.id) as confirmed_bookings
    FROM bookings b
    JOIN payments p ON b.id = p.booking_id
    WHERE b.status = 'confirmed' AND p.status = 'success'
    AND DATE(b.start_datetime) BETWEEN '$start_date' AND '$end_date'
";

$result = $mysqli->query($sql);
$summary = $result ? $result->fetch_assoc() : [];

$sql = "
    SELECT c.name as court_name, COUNT(b.id) as booking_count, SUM(p.amount) as revenue
    FROM bookings b
    JOIN courts c ON b.court_id = c.id
    JOIN payments p ON b.id = p.booking_id
    WHERE b.status = 'confirmed' AND p.status = 'success'
    AND DATE(b.start_datetime) BETWEEN '$start_date' AND '$end_date'
    GROUP BY c.name
    ORDER BY revenue DESC
";

$result = $mysqli->query($sql);
$revenue_by_court = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $revenue_by_court[] = $row;
    }
}

?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Filter Laporan</h5>
    </div>
    <div class="card-body">
        <form method="get">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Tanggal Mulai</label>
                    <input type="date" class="form-control" name="start_date" id="start_date" value="<?= htmlspecialchars($start_date) ?>">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">Tanggal Selesai</label>
                    <input type="date" class="form-control" name="end_date" id="end_date" value="<?= htmlspecialchars($end_date) ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Terapkan</button>
                    <a href="generate_report.php?start_date=<?= htmlspecialchars($start_date) ?>&end_date=<?= htmlspecialchars($end_date) ?>" target="_blank" class="btn btn-success">
                        <i class="fas fa-file-pdf me-2"></i>Export PDF
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Pendapatan Berdasarkan Lapangan</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Nama Lapangan</th>
                                <th class="text-end">Total Booking</th>
                                <th class="text-end">Total Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($revenue_by_court as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['court_name']) ?></td>
                                <td class="text-end"><?= number_format($row['booking_count']) ?></td>
                                <td class="text-end">Rp <?= number_format($row['revenue'], 0, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Ringkasan Laporan</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Total Pendapatan
                        <span class="badge bg-primary rounded-pill fs-6">Rp <?= number_format($summary['total_revenue'] ?? 0, 0, ',', '.') ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Total Booking (Lunas)
                        <span><?= number_format($summary['confirmed_bookings'] ?? 0) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Rata-rata per Transaksi
                        <span>Rp <?= number_format($summary['average_revenue'] ?? 0, 0, ',', '.') ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/admin_footer.php'; ?>
