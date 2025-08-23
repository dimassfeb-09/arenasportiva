<?php
session_start();
require_once __DIR__ . '/../../src/db_connect.php';


// Check if user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$message = '';
$message_type = '';

// Handle payment confirmation/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_id = $_POST['payment_id'];
    $action = $_POST['action']; // 'approve' or 'reject'
    
    if ($action === 'approve') {
        // Start transaction
        $mysqli->begin_transaction();
        
        try {
                         // Update payment status
             $stmt = $mysqli->prepare("UPDATE payments SET status = 'success' WHERE id = ?");
            $stmt->bind_param("i", $payment_id);
            $stmt->execute();
            $stmt->close();
            
            // Update booking status
            $stmt = $mysqli->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = (SELECT booking_id FROM payments WHERE id = ?)");
            $stmt->bind_param("i", $payment_id);
            $stmt->execute();
            $stmt->close();
            
            $mysqli->commit();
            $message = 'Pembayaran berhasil dikonfirmasi!';
            $message_type = 'success';
            
        } catch (Exception $e) {
            $mysqli->rollback();
            $message = 'Gagal mengkonfirmasi pembayaran: ' . $e->getMessage();
            $message_type = 'danger';
        }
        
         } elseif ($action === 'reject') {
         // Start transaction
         $mysqli->begin_transaction();
         
         try {
             // Get payment details first
             $stmt = $mysqli->prepare("SELECT p.paid_amount, p.booking_id, b.user_id FROM payments p JOIN bookings b ON p.booking_id = b.id WHERE p.id = ?");
             $stmt->bind_param("i", $payment_id);
             $stmt->execute();
             $result = $stmt->get_result();
             $payment = $result->fetch_assoc();
             $stmt->close();
             
             if (!$payment) {
                 throw new Exception('Data pembayaran tidak ditemukan');
             }
             
             // Update payment status
             $stmt = $mysqli->prepare("UPDATE payments SET status = 'failed' WHERE id = ?");
             $stmt->bind_param("i", $payment_id);
             $stmt->execute();
             $stmt->close();
             
             // Update booking status
             $stmt = $mysqli->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?");
             $stmt->bind_param("i", $payment['booking_id']);
             $stmt->execute();
             $stmt->close();
             
             // Tidak ada refund ke saldo, user harus hubungi admin
             $mysqli->commit();
             $message = 'Pembayaran ditolak. Silakan segera hubungi admin untuk pengembalian dana.';
             $message_type = 'warning';
         } catch (Exception $e) {
             $mysqli->rollback();
             $message = 'Gagal menolak pembayaran: ' . $e->getMessage();
             $message_type = 'danger';
         }
     }
}

// Get all payments with user and booking details
$stmt = $mysqli->prepare("
    SELECT p.*, u.name as user_name, u.phone as user_phone, b.start_datetime, c.name as court_name,
           p.proof_image, p.proof_url, p.discount
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN users u ON b.user_id = u.id
    JOIN courts c ON b.court_id = c.id
    ORDER BY p.created_at DESC
");
$stmt->execute();
$payments = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Transaksi - Admin Dashboard</title>
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
        
        .transactions-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .payment-proof {
            max-width: 100px;
            height: auto;
            border-radius: 5px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .payment-proof:hover {
            transform: scale(1.1);
        }

        #proofImage {
            max-height: 80vh;
            object-fit: contain;
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
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-success { background: #d4edda; color: #155724; }
        .status-failed { background: #f8d7da; color: #721c24; }
        
        .btn-action {
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
            border-radius: 20px;
        }
        
        .payment-proof {
            max-width: 100px;
            max-height: 100px;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .payment-proof:hover {
            transform: scale(1.1);
        }
        
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
                <a href="courts.php" class="nav-link">
                    <i class="fas fa-futbol me-2"></i>
                    Kelola Lapangan
                </a>
            </div>
            <div class="nav-item">
                <a href="transactions.php" class="nav-link active">
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
                    <i class="fas fa-exchange-alt me-2 text-primary"></i>
                    Kelola Transaksi
                </h4>
                <small class="text-muted">Konfirmasi atau tolak pembayaran user</small>
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

        <!-- Transactions Table -->
        <div class="transactions-card">
            <h5 class="mb-3">
                <i class="fas fa-list me-2"></i>
                Daftar Transaksi
            </h5>
            
            <?php if ($payments->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Lapangan</th>
                                <th>Tanggal & Jam</th>
                                                                 <th>Jumlah</th>
                                 <th>Discount</th>
                                 <th>Bukti Bayar</th>
                                <th>Status</th>
                                <th>Tanggal Bayar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($payment = $payments->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?= htmlspecialchars($payment['user_name']) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($payment['user_phone']) ?></small>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($payment['court_name']) ?></td>
                                    <td>
                                        <div>
                                            <strong><?= date('d/m/Y', strtotime($payment['start_datetime'])) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= date('H:i', strtotime($payment['start_datetime'])) ?></small>
                                        </div>
                                    </td>
                                                                         <td>
                                         <strong class="text-success">
                                             Rp <?= number_format($payment['paid_amount'], 0, ',', '.') ?>
                                         </strong>
                                     </td>
                                     <td>
                                         <?php if ($payment['discount'] > 0): ?>
                                             <span class="text-danger">
                                                 -Rp <?= number_format($payment['discount'], 0, ',', '.') ?>
                                             </span>
                                         <?php else: ?>
                                             <span class="text-muted">-</span>
                                         <?php endif; ?>
                                     </td>
                                                                         <td>
                                         <?php if (!empty($payment['proof_image'])): ?>
                                             <img src="../public/uploads/<?= htmlspecialchars($payment['proof_image']) ?>" 
                                                  alt="Bukti Pembayaran" 
                                                  class="payment-proof"
                                                  data-bs-toggle="modal" 
                                                  data-bs-target="#proofModal"
                                                  data-src="../public/uploads/<?= htmlspecialchars($payment['proof_image']) ?>"
                                                  onerror="this.onerror=null; this.src='../public/assets/img/no-image.png';">
                                         <?php else: ?>
                                             <span class="text-muted">Tidak ada</span>
                                         <?php endif; ?>
                                     </td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        switch ($payment['status']) {
                                            case 'pending': $status_class = 'status-pending'; break;
                                            case 'success': $status_class = 'status-success'; break;
                                            case 'failed': $status_class = 'status-failed'; break;
                                        }
                                        ?>
                                        <span class="status-badge <?= $status_class ?>">
                                            <?= ucfirst($payment['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= date('d/m/Y H:i', strtotime($payment['created_at'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($payment['status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                                                <button type="submit" name="action" value="approve" 
                                                        class="btn btn-success btn-action me-1"
                                                        onclick="return confirm('Konfirmasi pembayaran ini?')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="submit" name="action" value="reject" 
                                                        class="btn btn-danger btn-action"
                                                        onclick="return confirm('Tolak pembayaran ini? Dana akan dikembalikan ke saldo user.')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">Selesai</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>Belum ada transaksi</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Proof Image Modal -->
    <div class="modal fade" id="proofModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bukti Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="proofImage" src="" alt="Bukti Pembayaran" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle proof image modal
        document.addEventListener('DOMContentLoaded', function() {
            const proofModal = document.getElementById('proofModal');
            if (proofModal) {
                proofModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const src = button.getAttribute('data-src');
                    const modalImg = document.getElementById('proofImage');
                    modalImg.src = src;
                });
            }
        });
    </script>
</body>
</html>
