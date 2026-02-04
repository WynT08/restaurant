<?php
// modules/contact/reply.php
require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

$db = new Database();
$conn = $db->getConnection();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo '<div class="alert alert-danger">Liên hệ không hợp lệ.</div>';
    require_once '../../includes/footer.php';
    exit();
}

// Lấy thông tin liên hệ
$stmt = $conn->prepare("SELECT * FROM contact WHERE id = ?");
$stmt->execute([$id]);
$contact = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$contact) {
    echo '<div class="alert alert-danger">Không tìm thấy liên hệ.</div>';
    require_once '../../includes/footer.php';
    exit();
}

// Xử lý phản hồi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = trim($_POST['response'] ?? '');
    $status = 'responded';
    $stmt = $conn->prepare("UPDATE contact SET response = ?, status = ?, responded_at = NOW() WHERE id = ?");
    $stmt->execute([$response, $status, $id]);
    echo '<div class="alert alert-success">Đã gửi phản hồi cho khách hàng.</div>';
    // Cập nhật lại dữ liệu
    $stmt = $conn->prepare("SELECT * FROM contact WHERE id = ?");
    $stmt->execute([$id]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<div class="main-content">
    <h2>Phản hồi liên hệ khách hàng</h2>
    <form method="post">
        <div class="mb-3">
            <label>Họ tên:</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($contact['name']) ?>" readonly>
        </div>
        <div class="mb-3">
            <label>Điện thoại:</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($contact['phone']) ?>" readonly>
        </div>
        <div class="mb-3">
            <label>Email:</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($contact['email']) ?>" readonly>
        </div>
        <div class="mb-3">
            <label>Chủ đề:</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($contact['subject']) ?>" readonly>
        </div>
        <div class="mb-3">
            <label>Nội dung liên hệ:</label>
            <textarea class="form-control" rows="3" readonly><?= htmlspecialchars($contact['message']) ?></textarea>
        </div>
        <div class="mb-3">
            <label>Phản hồi của nhà hàng:</label>
            <textarea name="response" class="form-control" rows="3" required><?= htmlspecialchars($contact['response']) ?></textarea>
        </div>
        <button type="submit" class="btn btn-success">Gửi phản hồi</button>
        <a href="list.php" class="btn btn-secondary">Quay lại danh sách</a>
    </form>
</div>
<?php require_once '../../includes/footer.php'; ?>
