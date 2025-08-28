<?php
$page_title = "Kelola Lapangan";
include '../../templates/admin_header.php';

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Handle court status toggle
        if (isset($_POST['toggle_status'])) {
            $court_id = (int)$_POST['court_id'];
            $new_status = $mysqli->real_escape_string($_POST['new_status']);
            $query = "UPDATE courts SET status = '$new_status' WHERE id = $court_id";
            if ($mysqli->query($query)) {
                $message = 'Status lapangan berhasil diubah!';
                $message_type = 'success';
            } else {
                throw new Exception('Gagal mengubah status lapangan!');
            }
        }

        // Handle add court
        if (isset($_POST['add_court'])) {
            $name = trim($_POST['name']);
            $type = $_POST['type'];
            $price_per_hour = filter_var($_POST['price_per_hour'], FILTER_VALIDATE_INT);
            $description = trim($_POST['description']);

            if ($name && $type && $price_per_hour !== false && $price_per_hour > 0) {
                $name = $mysqli->real_escape_string($name);
                $type = $mysqli->real_escape_string($type);
                $description = $mysqli->real_escape_string($description);
                
                $query = "INSERT INTO courts (name, type, price_per_hour, description, status) 
                         VALUES ('$name', '$type', $price_per_hour, '$description', 'available')";
                if ($mysqli->query($query)) {
                    $message = 'Lapangan baru berhasil ditambahkan!';
                    $message_type = 'success';
                } else {
                    throw new Exception("Gagal menambah lapangan");
                }
            } else {
                throw new Exception('Data tidak lengkap atau harga tidak valid!');
            }
        }

        // Handle edit court
        if (isset($_POST['edit_court'])) {
            $court_id = filter_var($_POST['court_id'], FILTER_VALIDATE_INT);
            $name = trim($_POST['name']);
            $type = $_POST['type'];
            $price_per_hour = filter_var($_POST['price_per_hour'], FILTER_VALIDATE_INT);
            $description = trim($_POST['description']);

            if ($court_id && $name && $type && $price_per_hour !== false && $price_per_hour > 0) {
                $name = $mysqli->real_escape_string($name);
                $type = $mysqli->real_escape_string($type);
                $description = $mysqli->real_escape_string($description);
                
                $query = "UPDATE courts SET name = '$name', type = '$type', 
                         price_per_hour = $price_per_hour, description = '$description' 
                         WHERE id = $court_id";
                if ($mysqli->query($query)) {
                    $message = 'Lapangan berhasil diupdate!';
                    $message_type = 'success';
                } else {
                    throw new Exception("Gagal mengupdate lapangan");
                }
            } else {
                throw new Exception('Data tidak lengkap atau harga tidak valid!');
            }
        }

        // Handle delete court
        if (isset($_POST['delete_court'])) {
            $court_id = (int)$_POST['court_id'];
            if ($court_id) {
                // Check if court has any bookings
                $result = $mysqli->query("SELECT COUNT(*) as count FROM bookings WHERE court_id = $court_id");
                $count = $result->fetch_assoc()['count'];
                
                if ($count > 0) {
                    throw new Exception('Tidak dapat menghapus lapangan karena masih ada booking terkait.');
                }
                
                if ($mysqli->query("DELETE FROM courts WHERE id = $court_id")) {
                    $message = 'Lapangan berhasil dihapus!';
                    $message_type = 'success';
                } else {
                    throw new Exception('Gagal menghapus lapangan.');
                }
            }
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

// Get all courts with booking statistics
$search = $_GET['search'] ?? '';
$sql = "SELECT c.*, 
           COUNT(b.id) as total_bookings,
           SUM(CASE WHEN b.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings
    FROM courts c
    LEFT JOIN bookings b ON c.id = b.court_id";

if (!empty($search)) {
    $search = $mysqli->real_escape_string($search);
    $sql .= " WHERE c.name LIKE '%$search%' OR c.type LIKE '%$search%' OR c.description LIKE '%$search%'";
}

$sql .= " GROUP BY c.id ORDER BY c.id ASC";
$result = $mysqli->query($sql);

$courts = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $courts[] = $row;
    }
}
?>
<div class="modal fade" id="courtModal" tabindex="-1" aria-labelledby="courtModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="courtModalLabel">...</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="court_id" id="court_id">
                    <div class="mb-3">
                        <label for="courtName" class="form-label">Nama Lapangan</label>
                        <input type="text" class="form-control" id="courtName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="courtType" class="form-label">Jenis</label>
                        <select class="form-select" id="courtType" name="type" required>
                            <option value="futsal">Futsal</option>
                            <option value="badminton">Badminton</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="courtPrice" class="form-label">Harga per Jam</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" id="courtPrice" name="price_per_hour" min="1000" step="1000" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="courtDescription" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="courtDescription" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="modalSubmitButton" name="">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Daftar Lapangan</h5>
        <div class="d-flex">
            <form class="me-2" method="get">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Cari..." value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
                </div>
            </form>
            <button class="btn btn-primary" onclick="openCourtModal('add')">
                <i class="fas fa-plus me-1"></i> Tambah
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Lapangan</th>
                        <th>Jenis</th>
                        <th>Harga/Jam</th>
                        <th>Status</th>
                        <th>Total Booking</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($courts) > 0): ?>
                        <?php foreach ($courts as $court): ?>
                            <tr>
                                <td>#<?= $court['id'] ?></td>
                                <td><strong><?= htmlspecialchars($court['name']) ?></strong></td>
                                <td>
                                    <span class="badge bg-<?= $court['type'] === 'futsal' ? 'success' : 'info' ?> bg-opacity-10 text-<?= $court['type'] === 'futsal' ? 'success' : 'info' ?>">
                                        <?= ucfirst($court['type']) ?>
                                    </span>
                                </td>
                                <td>Rp <?= number_format($court['price_per_hour'], 0, ',', '.') ?></td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($court['status']) ?>">
                                        <?= ucfirst($court['status']) ?>
                                    </span>
                                </td>
                                <td><?= number_format($court['total_bookings']) ?></td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            Aksi
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="#" onclick='openCourtModal("edit", <?= json_encode($court) ?>)'><i class="fas fa-edit me-2"></i>Edit</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="court_id" value="<?= $court['id'] ?>">
                                                    <input type="hidden" name="new_status" value="available">
                                                    <button type="submit" name="toggle_status" class="dropdown-item">Set Available</button>
                                                </form>
                                            </li>
                                            <li>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="court_id" value="<?= $court['id'] ?>">
                                                    <input type="hidden" name="new_status" value="maintenance">
                                                    <button type="submit" name="toggle_status" class="dropdown-item">Set Maintenance</button>
                                                </form>
                                            </li>
                                            <li>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="court_id" value="<?= $court['id'] ?>">
                                                    <input type="hidden" name="new_status" value="unavailable">
                                                    <button type="submit" name="toggle_status" class="dropdown-item">Set Unavailable</button>
                                                </form>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form method="POST" onsubmit="return confirm('Yakin ingin menghapus lapangan ini?');">
                                                    <input type="hidden" name="court_id" value="<?= $court['id'] ?>">
                                                    <button type="submit" name="delete_court" class="dropdown-item text-danger"><i class="fas fa-trash me-2"></i>Hapus</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Tidak ada data lapangan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function openCourtModal(mode, data = null) {
    const modal = new bootstrap.Modal(document.getElementById('courtModal'));
    const form = document.getElementById('courtModal').querySelector('form');
    const modalLabel = document.getElementById('courtModalLabel');
    const submitButton = document.getElementById('modalSubmitButton');

    form.reset();
    document.getElementById('court_id').value = '';

    if (mode === 'add') {
        modalLabel.textContent = 'Tambah Lapangan Baru';
        submitButton.name = 'add_court';
        submitButton.textContent = 'Tambah';
        submitButton.className = 'btn btn-primary';
    } else if (mode === 'edit' && data) {
        modalLabel.textContent = 'Edit Lapangan';
        submitButton.name = 'edit_court';
        submitButton.textContent = 'Simpan Perubahan';
        submitButton.className = 'btn btn-primary';

        document.getElementById('court_id').value = data.id;
        document.getElementById('courtName').value = data.name;
        document.getElementById('courtType').value = data.type;
        document.getElementById('courtPrice').value = data.price_per_hour;
        document.getElementById('courtDescription').value = data.description;
    }

    modal.show();
}
</script>

<?php include '../../templates/admin_footer.php'; ?>
