<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$reservation_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($reservation_id <= 0) {
    header('Location: list.php');
    exit();
}

// Fetch reservation
$stmt = $db->prepare("SELECT * FROM reservations WHERE reservation_id = :id LIMIT 1");
$stmt->bindParam(':id', $reservation_id, PDO::PARAM_INT);
$stmt->execute();
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation) {
    setAlert('Không tìm thấy đặt bàn', 'danger');
    header('Location: list.php');
    exit();
}

// Fetch tables for select
$tables = $db->query("SELECT * FROM restaurant_tables ORDER BY table_number")->fetchAll(PDO::FETCH_ASSOC);

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $table_id = !empty($_POST['table_id']) ? (int) $_POST['table_id'] : null;
        $customer_email = !empty($_POST['customer_email']) ? $_POST['customer_email'] : null;
        $special_requests = !empty($_POST['special_requests']) ? $_POST['special_requests'] : null;
        $status = $_POST['status'];

        $query = "UPDATE reservations SET
            customer_name = :customer_name,
            customer_phone = :customer_phone,
            customer_email = :customer_email,
            table_id = :table_id,
            reservation_date = :reservation_date,
            reservation_time = :reservation_time,
            number_of_guests = :number_of_guests,
            special_requests = :special_requests,
            status = :status
            WHERE reservation_id = :id";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':customer_name', $_POST['customer_name']);
        $stmt->bindParam(':customer_phone', $_POST['customer_phone']);
        $stmt->bindParam(':customer_email', $customer_email);
        if ($table_id === null) {
            $stmt->bindValue(':table_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':table_id', $table_id, PDO::PARAM_INT);
        }
        $stmt->bindParam(':reservation_date', $_POST['reservation_date']);
        $stmt->bindParam(':reservation_time', $_POST['reservation_time']);
        $stmt->bindParam(':number_of_guests', $_POST['number_of_guests'], PDO::PARAM_INT);
        $stmt->bindParam(':special_requests', $special_requests);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $reservation_id, PDO::PARAM_INT);
        $stmt->execute();

        setAlert('Cập nhật đặt bàn thành công', 'success');
        header('Location: list.php');
        exit();
    } catch (Exception $e) {
        setAlert('Có lỗi xảy ra: ' . $e->getMessage(), 'danger');
    }
}

$page_title = 'Sửa đặt bàn #' . $reservation_id;
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Sửa đặt bàn #<?php echo $reservation_id; ?></h1>
        <div class="d-flex gap-2">
            <a href="view_reservation.php?id=<?php echo $reservation_id; ?>" class="btn btn-secondary"><i class="fas fa-eye"></i> Chi tiết</a>
            <a href="list.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Danh sách</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tên khách hàng *</label>
                        <input type="text" name="customer_name" class="form-control" value="<?php echo htmlspecialchars($reservation['customer_name']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Số điện thoại *</label>
                        <input type="tel" name="customer_phone" class="form-control" value="<?php echo htmlspecialchars($reservation['customer_phone']); ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="customer_email" class="form-control" value="<?php echo htmlspecialchars($reservation['customer_email']); ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Ngày *</label>
                        <input type="date" name="reservation_date" class="form-control" value="<?php echo htmlspecialchars($reservation['reservation_date']); ?>" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Giờ *</label>
                        <input type="time" name="reservation_time" class="form-control" value="<?php echo htmlspecialchars($reservation['reservation_time']); ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Số khách *</label>
                        <input type="number" name="number_of_guests" class="form-control" min="1" max="50" value="<?php echo (int)$reservation['number_of_guests']; ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Chọn bàn</label>
                        <select name="table_id" class="form-select">
                            <option value="">-- Chưa chọn --</option>
                            <?php foreach ($tables as $table): ?>
                                <option value="<?php echo $table['table_id']; ?>" <?php echo ($reservation['table_id'] == $table['table_id']) ? 'selected' : ''; ?>>
                                    <?php echo $table['table_number']; ?> (<?php echo $table['capacity']; ?> chỗ - <?php echo ucfirst($table['location']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" class="form-select">
                            <?php
                            $statuses = [
                                'pending' => 'Chờ xác nhận',
                                'confirmed' => 'Đã xác nhận',
                                'completed' => 'Hoàn thành',
                                'cancelled' => 'Đã hủy',
                                'no_show' => 'Không đến'
                            ];
                            foreach ($statuses as $key => $label):
                            ?>
                            <option value="<?php echo $key; ?>" <?php echo $reservation['status'] === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Yêu cầu đặc biệt</label>
                    <textarea name="special_requests" class="form-control" rows="3"><?php echo htmlspecialchars($reservation['special_requests']); ?></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu</button>
                    <a href="list.php" class="btn btn-secondary">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
