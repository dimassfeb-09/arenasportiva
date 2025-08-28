<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Set locale to Indonesian
setlocale(LC_TIME, 'id_ID.utf8', 'id_ID', 'id');

require_once __DIR__ . '/../src/db_connect.php';

$search_term = $_GET['search'] ?? '';
$user_id = $_SESSION['user_id'];

// Get user data including created_at
$user_stmt = $mysqli->prepare("SELECT name, created_at FROM users WHERE id = ?");
$user_stmt->bind_param('i', $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

$sql = "SELECT b.id, b.booking_code, b.start_datetime, b.duration_hours, b.status AS booking_status,
        c.name AS court_name, c.type AS court_type, c.price_per_hour,
        p.status AS payment_status, p.method, p.proof_url, p.created_at AS paid_at,
        CASE 
            WHEN b.duration_hours >= 6 THEN 
                (c.price_per_hour * b.duration_hours) - 
                ROUND((c.price_per_hour * b.duration_hours) * 0.10)
            WHEN b.duration_hours >= 4 THEN 
                (c.price_per_hour * b.duration_hours) - 
                ROUND((c.price_per_hour * b.duration_hours) * 0.05)
            ELSE 
                c.price_per_hour * b.duration_hours
        END as final_amount
        FROM bookings b
        JOIN courts c ON c.id = b.court_id
        LEFT JOIN payments p ON p.booking_id = b.id
        WHERE b.user_id = ?";

if (!empty($search_term)) {
    $sql .= " AND (b.booking_code LIKE ? OR c.name LIKE ?)";
    $search_param = "%{$search_term}%";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('iss', $user_id, $search_param, $search_param);
} else {
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function getStatusBadge($status) {
    switch (strtolower($status)) {
        case 'confirmed':
        case 'success':
            return '<span class="badge bg-success">' . htmlspecialchars($status) . '</span>';
        case 'pending':
            return '<span class="badge bg-warning text-dark">' . htmlspecialchars($status) . '</span>';
        case 'rejected':
        case 'failed':
        case 'cancelled':
            return '<span class="badge bg-danger">' . htmlspecialchars($status) . '</span>';
        case 'belum bayar':
            return '<span class="badge bg-secondary">Belum Bayar</span>';
        default:
            return '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>';
    }
}

include __DIR__ . '/../templates/header.php';
?>

<div class="profile-page py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-3">
                <div class="profile-nav-card text-center mb-4">
                    <div class="profile-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h5 class="mb-1"><?= htmlspecialchars($_SESSION['name']) ?></h5>
                    <p class="text-muted small">Member Sejak <?= date('d', strtotime($user['created_at'])) ?> <?= strftime('%B %Y', strtotime($user['created_at'])) ?></p>
                </div>
                <div class="profile-nav-card">
                    <div class="nav flex-column nav-pills" role="tablist">
                        <a class="nav-link" href="profile.php?tab=profile"><i class="fas fa-user-edit me-2"></i>Edit Profil</a>
                        <a class="nav-link" href="profile.php?tab=password"><i class="fas fa-key me-2"></i>Ubah Password</a>
                        <a class="nav-link active" href="history.php"><i class="fas fa-history me-2"></i>Riwayat Transaksi</a>
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="profile-content-card">
                    <h4 class="mb-4">Riwayat Transaksi</h4>

                    <form method="GET" action="history.php" class="mb-4">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Cari berdasarkan kode booking atau nama lapangan..." value="<?= htmlspecialchars($search_term) ?>">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                        </div>
                    </form>

                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Info:</strong>
                        <ul class="mb-0 mt-1">
                            <li>Jika pembayaran Anda di-cancel, segera hubungi admin untuk proses pengembalian dana.</li>
                            <li>Untuk melakukan pembayaran, klik tombol "Belum Bayar" pada status pembayaran.</li>
                        </ul>
                    </div>

                    <?php if (empty($rows)): ?>
                        <div class="alert alert-info">Tidak ada riwayat transaksi ditemukan.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Kode</th>
                                        <th>Lapangan</th>
                                        <th>Jadwal</th>
                                        <th>Total</th>
                                        <th>Status Booking</th>
                                        <th>Pembayaran</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td><code><?= htmlspecialchars($r['booking_code']) ?></code></td>
                                        <td><?= htmlspecialchars($r['court_name']) ?></td>
                                        <td><?= date('d M Y, H:i', strtotime($r['start_datetime'])) ?></td>
                                        <td><strong>Rp <?= number_format($r['final_amount'], 0, ',', '.') ?></strong></td>
                                        <td><?= getStatusBadge($r['booking_status']) ?></td>
                                        <td>
                                            <?php if (!$r['payment_status']): ?>
                                                <a href="payment.php?code=<?= urlencode($r['booking_code']) ?>" class="text-decoration-none">
                                                    <span class="badge bg-secondary">Belum Bayar</span>
                                                </a>
                                            <?php else: ?>
                                                <?= getStatusBadge($r['payment_status']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $modalData = array_merge($r, [
                                                'customer_name' => $_SESSION['name'],
                                                'final_amount' => $r['final_amount']
                                            ]);
                                            ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#ticketModal" 
                                                    data-booking='<?= json_encode($modalData) ?>'>
                                                <i class="fas fa-receipt me-1"></i> Detail
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tiket Booking -->
<div class="modal fade" id="ticketModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" id="ticketContent">
            <!-- Content loaded via JS -->
        </div>
    </div>
</div>

<script>
function getStatusColor(status) {
    switch(status.toLowerCase()) {
        case 'confirmed':
            return 'success';
        case 'pending':
            return 'warning';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}

document.addEventListener('DOMContentLoaded', function(){
    var ticketModal = document.getElementById('ticketModal');
    ticketModal.addEventListener('show.bs.modal', function(event){
        var button = event.relatedTarget;
        var data = JSON.parse(button.getAttribute('data-booking'));
        var qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=${encodeURIComponent(data.booking_code)}`;
        
        // Format the date
        var bookingDate = new Date(data.start_datetime);
        var formattedDate = new Intl.DateTimeFormat('id-ID', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }).format(bookingDate);

        var modalContent = `
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">E-TICKET</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <img src="assets/img/logo.png" alt="Arena Sportiva" height="40">
                    <h4 class="mt-2">Arena Sportiva</h4>
                </div>
                <div class="row">
                    <div class="col-8">
                        <p class="small mb-1">Nama Pemesan</p>
                        <h6 class="mb-3">${data.customer_name}</h6>
                        <p class="small mb-1">Kode Booking</p>
                        <h6 class="mb-0"><strong>${data.booking_code}</strong></h6>
                    </div>
                    <div class="col-4 text-end">
                        <img src="${qrCodeUrl}" alt="QR Code">
                    </div>
                </div>
                <hr>
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-muted">Lapangan</td>
                        <td class="text-end fw-bold">${data.court_name}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Jadwal</td>
                        <td class="text-end fw-bold">${formattedDate}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Durasi</td>
                        <td class="text-end fw-bold">${data.duration_hours} jam</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Total Bayar</td>
                        <td class="text-end fw-bold">Rp ${parseInt(data.final_amount).toLocaleString('id-ID')}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status</td>
                        <td class="text-end">
                            <span class="badge bg-${getStatusColor(data.booking_status)}">${data.booking_status}</span>
                        </td>
                    </tr>
                </table>
                <hr>
                <div class="text-center">
                    <p class="small text-muted">Tunjukkan e-ticket ini kepada petugas di lokasi. Screenshot atau cetak halaman ini sebagai bukti booking.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="window.print()"><i class="fas fa-print me-1"></i> Cetak</button>
            </div>
        `;
        document.getElementById('ticketContent').innerHTML = modalContent;
    });
});
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>