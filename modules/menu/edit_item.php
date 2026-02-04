<?php
$page_title = 'Sửa món ăn';
require_once '../../config/config.php';
require_once '../../config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();
$current_user = getCurrentUser($db);
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_role = $current_user['role'] ?? ($current_user['user_role'] ?? '');

requirePermission('manager');

$item_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($item_id <= 0) {
    setAlert('Thiếu ID món ăn', 'danger');
    header('Location: items.php');
    exit();
}

// Lấy thông tin món
$stmt = $db->prepare("SELECT * FROM menu_items WHERE item_id = :id LIMIT 1");
$stmt->bindParam(':id', $item_id, PDO::PARAM_INT);
$stmt->execute();
$item = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$item) {
    setAlert('Không tìm thấy món ăn', 'danger');
    header('Location: items.php');
    exit();
}

// Handle update POST before output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $image_name = $item['image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $upload_result = uploadFile($_FILES['image'], MENU_IMAGE_PATH);
            if ($upload_result['success']) {
                $image_name = $upload_result['filename'];
            }
        }

        $query = "UPDATE menu_items SET
            category_id = :category_id,
            item_name = :item_name,
            description = :description,
            price = :price,
            cost_price = :cost_price,
            image = :image,
            preparation_time = :preparation_time,
            calories = :calories,
            is_vegetarian = :is_vegetarian,
            is_spicy = :is_spicy,
            is_available = :is_available,
            display_order = :display_order
            WHERE item_id = :id";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':category_id', $_POST['category_id']);
        $stmt->bindParam(':item_name', $_POST['item_name']);
        $stmt->bindParam(':description', $_POST['description']);
        $stmt->bindParam(':price', $_POST['price']);
        $stmt->bindParam(':cost_price', $_POST['cost_price']);
        $stmt->bindParam(':image', $image_name);
        $stmt->bindParam(':preparation_time', $_POST['preparation_time']);
        $stmt->bindParam(':calories', $_POST['calories']);
        $is_vegetarian = isset($_POST['is_vegetarian']) ? 1 : 0;
        $stmt->bindParam(':is_vegetarian', $is_vegetarian);
        $is_spicy = isset($_POST['is_spicy']) ? 1 : 0;
        $stmt->bindParam(':is_spicy', $is_spicy);
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        $stmt->bindParam(':is_available', $is_available);
        $stmt->bindParam(':display_order', $_POST['display_order']);
        $stmt->bindParam(':id', $item_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            setAlert('Cập nhật món ăn thành công', 'success');
            header('Location: items.php');
            exit();
        }
    } catch (Exception $e) {
        setAlert('Có lỗi xảy ra: ' . $e->getMessage(), 'danger');
    }
}

// Lấy danh mục
$categories = $db->query("SELECT * FROM categories ORDER BY display_order")->fetchAll(PDO::FETCH_ASSOC);

// Now include header after all redirects and POST handling
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Sửa món ăn</h1>
        <a href="items.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Tên món ăn *</label>
                                <input type="text" name="item_name" class="form-control" value="<?php echo htmlspecialchars($item['item_name']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Danh mục *</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">Chọn danh mục</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['category_id']; ?>" <?php echo $item['category_id'] == $cat['category_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($item['description']); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Giá bán *</label>
                                <div class="input-group">
                                    <input type="number" name="price" class="form-control" required min="0" step="0.01" value="<?php echo htmlspecialchars($item['price']); ?>">
                                    <span class="input-group-text">đ</span>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Giá vốn</label>
                                <div class="input-group">
                                    <input type="number" name="cost_price" class="form-control" min="0" step="0.01" value="<?php echo htmlspecialchars($item['cost_price']); ?>">
                                    <span class="input-group-text">đ</span>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Thời gian chuẩn bị (phút)</label>
                                <input type="number" name="preparation_time" class="form-control" value="<?php echo htmlspecialchars($item['preparation_time']); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Calo</label>
                                <input type="number" name="calories" class="form-control" value="<?php echo htmlspecialchars($item['calories']); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Thứ tự hiển thị</label>
                                <input type="number" name="display_order" class="form-control" value="<?php echo htmlspecialchars($item['display_order']); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Ảnh hiện tại</label>
                                <?php if ($item['image']): ?>
                                    <div class="mb-2"><img src="<?php echo SITE_URL . '/uploads/menu_images/' . $item['image']; ?>" alt="" style="max-height:80px;"></div>
                                <?php else: ?>
                                    <p class="text-muted">Chưa có ảnh</p>
                                <?php endif; ?>
                                <input type="file" name="image" class="form-control" accept="image/*">
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="is_vegetarian" id="is_vegetarian" <?php echo $item['is_vegetarian'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_vegetarian"><i class="fas fa-leaf text-success"></i> Món chay</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="is_spicy" id="is_spicy" <?php echo $item['is_spicy'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_spicy"><i class="fas fa-pepper-hot text-danger"></i> Món cay</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="is_available" id="is_available" <?php echo $item['is_available'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_available">Có sẵn</label>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu</button>
                            <a href="items.php" class="btn btn-secondary">Hủy</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
