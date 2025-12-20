<?php
$page_title = 'Quản lý bàn';
include '../../includes/header.php';

// Handle add table
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    try {
        $query = "INSERT INTO restaurant_tables (table_number, capacity, location, status)
                  VALUES (:table_number, :capacity, :location, 'available')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':table_number', $_POST['table_number']);
        $stmt->bindParam(':capacity', $_POST['capacity']);
        $stmt->bindParam(':location', $_POST['location']);
        
        if ($stmt->execute()) {
            setAlert('Thêm bàn thành công', 'success');
        }
    } catch (Exception $e) {
        setAlert('Có lỗi xảy ra: ' . $e->getMessage(), 'danger');
    }
}

// Get all tables
$query = "SELECT * FROM restaurant_tables ORDER BY table_number";
$tables = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Mark tables with reservations in next 3h as occupied (pending/confirmed)
$reservedStmt = $db->prepare("SELECT DISTINCT table_id FROM reservations
    WHERE table_id IS NOT NULL
      AND status IN ('pending','confirmed')
      AND CONCAT(reservation_date, ' ', reservation_time) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 HOUR)");
$reservedStmt->execute();
$reservedTables = array_column($reservedStmt->fetchAll(PDO::FETCH_ASSOC), 'table_id');
foreach ($tables as &$t) {
    if (in_array($t['table_id'], $reservedTables, true)) {
        $t['status'] = 'occupied';
    }
}
unset($t);

// Count statistics
$stats = [
    'available' => 0,
    'occupied' => 0,
    'reserved' => 0,
    'maintenance' => 0
];

foreach ($tables as $table) {
    $stats[$table['status']]++;
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Quản lý bàn</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTableModal">
            <i class="fas fa-plus"></i> Thêm bàn mới
        </button>
    </div>
    
    <!-- Statistics -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h2 class="text-success mb-0"><?php echo $stats['available']; ?></h2>
                    <p class="text-muted mb-0">Bàn trống</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <h2 class="text-danger mb-0"><?php echo $stats['occupied']; ?></h2>
                    <p class="text-muted mb-0">Đang sử dụng</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h2 class="text-warning mb-0"><?php echo $stats['reserved']; ?></h2>
                    <p class="text-muted mb-0">Đã đặt</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-secondary">
                <div class="card-body text-center">
                    <h2 class="text-secondary mb-0"><?php echo $stats['maintenance']; ?></h2>
                    <p class="text-muted mb-0">Bảo trì</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <select id="location-filter" class="form-select">
                        <option value="all">Tất cả vị trí</option>
                        <option value="indoor">Trong nhà</option>
                        <option value="outdoor">Ngoài trời</option>
                        <option value="vip">VIP</option>
                        <option value="balcony">Ban công</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <select id="status-filter" class="form-select">
                        <option value="all">Tất cả trạng thái</option>
                        <option value="available">Trống</option>
                        <option value="occupied">Đang dùng</option>
                        <option value="reserved">Đã đặt</option>
                        <option value="maintenance">Bảo trì</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tables Grid -->
    <div class="row g-3" id="tables-grid">
        <?php foreach ($tables as $table): ?>
        <?php
        $status_classes = [
            'available' => 'success',
            'occupied' => 'danger',
            'reserved' => 'warning',
            'maintenance' => 'secondary'
        ];
        $status_icons = [
            'available' => 'fa-check-circle',
            'occupied' => 'fa-users',
            'reserved' => 'fa-calendar-check',
            'maintenance' => 'fa-wrench'
        ];
        $status_text = [
            'available' => 'Trống',
            'occupied' => 'Đang dùng',
            'reserved' => 'Đã đặt',
            'maintenance' => 'Bảo trì'
        ];
        ?>
        <div class="col-lg-2 col-md-3 col-sm-4 col-6 table-item" 
             data-location="<?php echo $table['location']; ?>" 
             data-status="<?php echo $table['status']; ?>">
            <div class="card table-card border-<?php echo $status_classes[$table['status']]; ?>">
                <div class="card-body text-center p-3">
                    <div class="table-icon mb-2">
                        <i class="fas fa-table fa-2x text-<?php echo $status_classes[$table['status']]; ?>"></i>
                    </div>
                    <h5 class="mb-1"><?php echo htmlspecialchars($table['table_number']); ?></h5>
                    <?php if (!empty($table['table_name'] ?? '')): ?>
                    <small class="text-muted d-block"><?php echo htmlspecialchars($table['table_name']); ?></small>
                    <?php endif; ?>
                    <div class="my-2">
                        <span class="badge bg-light text-dark">
                            <i class="fas fa-users"></i> <?php echo $table['capacity']; ?> chỗ
                        </span>
                        <span class="badge bg-light text-dark">
                            <i class="fas fa-map-marker-alt"></i> <?php echo ucfirst($table['location']); ?>
                        </span>
                    </div>
                    <span class="badge bg-<?php echo $status_classes[$table['status']]; ?> w-100">
                        <i class="fas <?php echo $status_icons[$table['status']]; ?>"></i>
                        <?php echo $status_text[$table['status']]; ?>
                    </span>
                    
                    <div class="btn-group btn-group-sm w-100 mt-2" role="group">
                        <button class="btn btn-outline-primary" onclick="changeStatus(<?php echo $table['table_id']; ?>)">
                            <i class="fas fa-sync"></i>
                        </button>
                        <button class="btn btn-outline-info" onclick="editTable(<?php echo $table['table_id']; ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="deleteTable(<?php echo $table['table_id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Add Table Modal -->
<div class="modal fade" id="addTableModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm bàn mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Số bàn *</label>
                        <input type="text" name="table_number" class="form-control" required placeholder="VD: T01, B01">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Sức chứa *</label>
                        <input type="number" name="capacity" class="form-control" required min="1" max="50" value="4">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Vị trí *</label>
                        <select name="location" class="form-select" required>
                            <option value="indoor">Trong nhà</option>
                            <option value="outdoor">Ngoài trời</option>
                            <option value="vip">VIP</option>
                            <option value="balcony">Ban công</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Status Modal -->
<div class="modal fade" id="changeStatusModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Đổi trạng thái bàn</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="change-status-table-id">
                <div class="d-grid gap-2">
                    <button class="btn btn-success" onclick="updateTableStatus('available')">
                        <i class="fas fa-check-circle"></i> Trống
                    </button>
                    <button class="btn btn-danger" onclick="updateTableStatus('occupied')">
                        <i class="fas fa-users"></i> Đang dùng
                    </button>
                    <button class="btn btn-warning" onclick="updateTableStatus('reserved')">
                        <i class="fas fa-calendar-check"></i> Đã đặt
                    </button>
                    <button class="btn btn-secondary" onclick="updateTableStatus('maintenance')">
                        <i class="fas fa-wrench"></i> Bảo trì
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.table-card {
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: pointer;
}
.table-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}
</style>

<script>
let changeStatusModal;

$(document).ready(function() {
    changeStatusModal = new bootstrap.Modal(document.getElementById('changeStatusModal'));

    // Filter tables
    $('#location-filter, #status-filter').on('change', function() {
        const location = $('#location-filter').val();
        const status = $('#status-filter').val();
        
        $('.table-item').each(function() {
            const itemLocation = $(this).data('location');
            const itemStatus = $(this).data('status');
            
            let showItem = true;
            
            if (location !== 'all' && itemLocation !== location) {
                showItem = false;
            }
            
            if (status !== 'all' && itemStatus !== status) {
                showItem = false;
            }
            
            $(this).toggle(showItem);
        });
    });
});

function changeStatus(tableId) {
    $('#change-status-table-id').val(tableId);
    changeStatusModal.show();
}

function updateTableStatus(status) {
    const tableId = $('#change-status-table-id').val();
    
    $.post('ajax_update_table_status.php', {
        table_id: tableId,
        status: status
    }, function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert(response.message);
        }
    }, 'json');
}

function editTable(tableId) {
    window.location.href = 'edit_table.php?id=' + tableId;
}

function deleteTable(tableId) {
    if (confirm('Bạn có chắc muốn xóa bàn này?')) {
        $.post('ajax_delete_table.php', { table_id: tableId }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.message);
            }
        }, 'json');
    }
}
</script>

<?php include '../../includes/footer.php'; ?>