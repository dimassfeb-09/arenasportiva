<?php
$page_title = "Kelola Transaksi";
include '../../templates/admin_header.php';

$message = '';
$message_type = '';

$sql = "SELECT b.*, u.name as user_name, u.phone as user_phone, 
    c.name as court_name,
    p.amount as payment_amount, p.status as payment_status, p.proof_url as proof_of_payment,
    c.price_per_hour * b.duration_hours as subtotal,
    CASE 
        WHEN b.duration_hours >= 6 THEN ROUND((c.price_per_hour * b.duration_hours) * 0.10)
        ELSE 0
    END as duration_discount,
    CASE 
        WHEN b.duration_hours >= 6 THEN 
            (c.price_per_hour * b.duration_hours) - 
            ROUND((c.price_per_hour * b.duration_hours) * 0.10)
        ELSE 
            c.price_per_hour * b.duration_hours
    END as total_price,
    CASE 
        WHEN b.status = 'cancelled' AND b.cancel_reason = 'user_cancelled' THEN 'Cancelled by User'
        WHEN b.status = 'cancelled' OR p.status = 'failed' THEN 'Cancelled'
        WHEN p.status IS NULL THEN 'Belum Bayar'
        ELSE p.status
    END as display_status
FROM bookings b
LEFT JOIN users u ON b.user_id = u.id
LEFT JOIN courts c ON b.court_id = c.id
LEFT JOIN payments p ON b.id = p.booking_id
ORDER BY b.created_at DESC";

$result = $mysqli->query($sql);

// Handle payment confirmation/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $booking_id = $_POST['booking_id'];
        $action = $_POST['action']; // 'approve' or 'reject'

        $mysqli->begin_transaction();

        try {
            $booking_id = (int)$booking_id;
            
            if ($action === 'approve') {
                // Cek status booking terlebih dahulu
                $check_stmt = $mysqli->prepare("SELECT status FROM bookings WHERE id = ?");
                $check_stmt->bind_param('i', $booking_id);
                $check_stmt->execute();
                $booking_result = $check_stmt->get_result();
                $booking_data = $booking_result->fetch_assoc();
                $check_stmt->close();

                if ($booking_data['status'] !== 'pending') {
                    throw new Exception("Booking tidak dalam status pending");
                }

                // Update booking and payment status
                $query1 = "UPDATE bookings SET status = 'confirmed' WHERE id = ? AND status = 'pending'";
                $stmt1 = $mysqli->prepare($query1);
                $stmt1->bind_param('i', $booking_id);
                
                $query2 = "UPDATE payments SET status = 'success' WHERE booking_id = ?";
                $stmt2 = $mysqli->prepare($query2);
                $stmt2->bind_param('i', $booking_id);
                
                if (!$stmt1->execute()) {
                    throw new Exception("Gagal mengupdate status booking");
                }
                if (!$stmt2->execute()) {
                    throw new Exception("Gagal mengupdate status pembayaran");
                }
                
                $stmt1->close();
                $stmt2->close();
                
                $message = 'Booking berhasil dikonfirmasi!';
                $message_type = 'success';
            } elseif ($action === 'reject') {
                // Update booking and payment status
                $query1 = "UPDATE bookings SET status = 'rejected' WHERE id = ? AND status = 'pending'";
                $stmt1 = $mysqli->prepare($query1);
                $stmt1->bind_param('i', $booking_id);
                
                $query2 = "UPDATE payments SET status = 'failed' WHERE booking_id = ?";
                $stmt2 = $mysqli->prepare($query2);
                $stmt2->bind_param('i', $booking_id);
                
                if (!$stmt1->execute()) {
                    throw new Exception("Gagal mengupdate status booking");
                }
                if (!$stmt2->execute()) {
                    throw new Exception("Gagal mengupdate status pembayaran");
                }
                
                $stmt1->close();
                $stmt2->close();
                
                $message = 'Booking berhasil ditolak.';
                $message_type = 'warning';
            }

            $mysqli->commit();
            } catch (Exception $e) {
                $mysqli->rollback();
                throw $e;
            }
        } catch (Exception $e) {
            if ($mysqli->connect_errno) {
                $mysqli->rollback();
            }
            $message = 'Gagal memproses permintaan: ' . $e->getMessage();
            $message_type = 'danger';
        }
}

// Get all transactions
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all';

$sql = "SELECT b.*, u.name as user_name, u.phone as user_phone, c.name as court_name,
    p.amount as payment_amount, p.status as payment_status, p.proof_url as proof_of_payment,
    c.price_per_hour * b.duration_hours as subtotal,
    CASE 
        WHEN b.duration_hours >= 6 THEN ROUND((c.price_per_hour * b.duration_hours) * 0.10)
        WHEN b.duration_hours >= 4 THEN ROUND((c.price_per_hour * b.duration_hours) * 0.05)
        ELSE 0
    END as duration_discount,
    CASE 
        WHEN b.duration_hours >= 6 THEN 
            (c.price_per_hour * b.duration_hours) - 
            ROUND((c.price_per_hour * b.duration_hours) * 0.10)
        WHEN b.duration_hours >= 4 THEN 
            (c.price_per_hour * b.duration_hours) - 
            ROUND((c.price_per_hour * b.duration_hours) * 0.05)
        ELSE 
            c.price_per_hour * b.duration_hours
    END as total_price,
    CASE 
        WHEN b.status = 'cancelled' AND b.cancel_reason = 'user_cancelled' THEN 'Cancelled by User'
        WHEN b.status = 'cancelled' OR p.status = 'failed' THEN 'Cancelled'
        WHEN b.status = 'pending' AND p.status IS NULL THEN 'Pending'
        WHEN p.status IS NULL THEN 'Belum Bayar'
        WHEN p.status = 'success' AND b.status = 'confirmed' THEN 'Success'
        ELSE p.status
    END as display_status
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN courts c ON b.court_id = c.id
    LEFT JOIN payments p ON b.id = p.booking_id";

$where_clauses = [];

if (!empty($search)) {
    $search = $mysqli->real_escape_string($search);
    $where_clauses[] = "(u.name LIKE '%$search%' OR c.name LIKE '%$search%')";
}

if ($status_filter !== 'all') {
    $status = $mysqli->real_escape_string($status_filter);
    switch($status) {
        case 'unpaid':
            $where_clauses[] = "p.status IS NULL";
            break;
        case 'user_cancelled':
            $where_clauses[] = "b.status = 'cancelled' AND b.cancel_reason = 'user_cancelled'";
            break;
        case 'cancelled':
            $where_clauses[] = "(b.status = 'cancelled' AND b.cancel_reason != 'user_cancelled') OR p.status = 'failed'";
            break;
        default:
            $where_clauses[] = "p.status = '$status'";
    }
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$sql .= " ORDER BY b.created_at DESC";
$result = $mysqli->query($sql);

$bookings = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}

?>

<?php if ($message): ?>
<div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Daftar Transaksi</h5>
        <div class="d-flex">
            <form class="me-2" method="get">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Cari..." value="<?= htmlspecialchars($search) ?>">
                    <select class="form-select" name="status" onchange="this.form.submit()">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>Semua Status</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="success" <?= $status_filter === 'success' ? 'selected' : '' ?>>Success</option>
                        <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        <option value="user_cancelled" <?= $status_filter === 'user_cancelled' ? 'selected' : '' ?>>Cancelled by User</option>
                        <option value="unpaid" <?= $status_filter === 'unpaid' ? 'selected' : '' ?>>Belum Bayar</option>
                    </select>
                    <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Pengguna</th>
                        <th>Detail Booking</th>
                        <th>Total</th>
                        <th>Bukti Bayar</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($bookings) > 0): ?>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td>#<?= $booking['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($booking['user_name']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($booking['user_phone']) ?></small>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($booking['court_name']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($booking['start_datetime'])) ?></small>
                                </td>
                                <td>
                                    <?php if ($booking['duration_discount'] > 0): ?>
                                        <span class="text-decoration-line-through text-muted">
                                            Rp <?= number_format($booking['subtotal'], 0, ',', '.') ?>
                                        </span><br>
                                        <small class="text-success d-block">
                                            Diskon Durasi (<?= $booking['duration_hours'] >= 6 ? '10%' : '5%' ?>): 
                                            Rp <?= number_format($booking['duration_discount'], 0, ',', '.') ?>
                                        </small>
                                        <span class="fw-bold text-success">
                                            Rp <?= number_format($booking['total_price'], 0, ',', '.') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="fw-bold">
                                            Rp <?= number_format($booking['total_price'], 0, ',', '.') ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($booking['proof_of_payment'])): ?>
                                        <a href="<?= htmlspecialchars($booking['proof_of_payment']) ?>" target="_blank">
                                            <img src="<?= htmlspecialchars($booking['proof_of_payment']) ?>" alt="Bukti" height="40" class="rounded">
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $status_class = match(strtolower($booking['display_status'])) {
                                        'cancelled by user' => 'danger',
                                        'cancelled' => 'danger',
                                        'belum bayar' => 'secondary',
                                        'pending' => 'warning',
                                        'success' => 'success',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $status_class ?>">
                                        <?= $booking['display_status'] ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php if ($booking['payment_status'] === 'pending'): ?>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                Aksi
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <form method="POST" onsubmit="return confirm('Konfirmasi pembayaran ini?');">
                                                        <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                                        <button type="submit" name="action" value="approve" class="dropdown-item"><i class="fas fa-check me-2"></i>Konfirmasi</button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form method="POST" onsubmit="return confirm('Tolak pembayaran ini?');">
                                                        <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                                        <button type="submit" name="action" value="reject" class="dropdown-item text-danger"><i class="fas fa-times me-2"></i>Tolak</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Selesai</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Tidak ada data transaksi.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../templates/admin_footer.php'; ?>
