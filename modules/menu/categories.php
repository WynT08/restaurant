<?php
$page_title = 'Quản lý danh mục';
include '../../includes/header.php';
requirePermission('manager');

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $query = "DELETE FROM categories WHERE category_id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    if ($stmt->execute()) {
        setAlert('Xóa danh mục thành công', 'success');
    } else {
        setAlert('Có lỗi xảy ra', 'danger');
    }
    header("Location: categories.php");
    exit();
}

// Get all categories
$query = "SELECT c.*, COUNT(mi.item_id) as item_count 
          FROM categories c 
          LEFT JOIN menu_items mi ON c.category_id = mi.category_id 
          GROUP BY c.category_id 
          ORDER BY c.display_order";
$categories = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Danh mục thực đơn</h1>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" onclick="window.location.href='items.php'"><i class="fas fa-utensils"></i> Quản lý món</button>
            <button class="btn btn-success" onclick="window.location.href='add_item.php'"><i class="fas fa-plus"></i> Thêm món</button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="fas fa-folder-plus"></i> Thêm danh mục
            </button>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="80">Thứ tự</th>
                            <th>Hình ảnh</th>
                            <th>Tên danh mục</th>
                            <th>Mô tả</th>
                            <th>Số món</th>
                            <th>Trạng thái</th>
                            <th width="150">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?php echo $cat['display_order']; ?></td>
                            <td>
                                <?php if ($cat['image']): ?>
                                    <img src="<?php echo SITE_URL . '/uploads/categories/' . $cat['image']; ?>" 
                                         alt="" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center" 
                                         style="width:  50px; height: 50px;">
                                        <i class="fas fa-image text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($cat['category_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars(substr($cat['description'] ?? '', 0, 50)); ?></td>
                            <td><span class="badge bg-info"><?php echo $cat['item_count']; ?> món</span></td>
                            <td>
                                <?php 
                                // Bảng categories không có cột status trong schema seed; mặc định xem là active
                                $cat_status = $cat['status'] ?? 'active';
                                ?>
                                <?php if ($cat_status === 'active'): ?>
                                    <span class="badge bg-success">Hoạt động</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Tạm ẩn</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="editCategory(<?php echo $cat['category_id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?delete=<?php echo $cat['category_id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Xóa danh mục này? ')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="add-category-form" method="POST" action="ajax_save_category.php" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm danh mục mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tên danh mục *</label>
                        <input type="text" name="category_name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Mô tả</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Hình ảnh</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Thứ tự hiển thị</label>
                        <input type="number" name="display_order" class="form-control" value="0">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="active">Hoạt động</option>
                            <option value="inactive">Tạm ẩn</option>
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

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="edit-category-form" method="POST" action="ajax_save_category.php" enctype="multipart/form-data">
                <input type="hidden" name="category_id" id="edit-category-id">
                <div class="modal-header">
                    <h5 class="modal-title">Sửa danh mục</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tên danh mục *</label>
                        <input type="text" name="category_name" id="edit-category-name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mô tả</label>
                        <textarea name="description" id="edit-description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hình ảnh (tùy chọn)</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Thứ tự hiển thị</label>
                        <input type="number" name="display_order" id="edit-display-order" class="form-control" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" id="edit-status" class="form-select">
                            <option value="active">Hoạt động</option>
                            <option value="inactive">Tạm ẩn</option>
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

<?php include '../../includes/footer.php'; ?>

<script>
function editCategory(id) {
    $.getJSON('ajax_get_category.php', { id: id }, function(res) {
        if (res && res.success) {
            const c = res.data;
            $('#edit-category-id').val(c.category_id);
            $('#edit-category-name').val(c.category_name);
            $('#edit-description').val(c.description);
            $('#edit-display-order').val(c.display_order);
            $('#edit-status').val(c.status || 'active');
            const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
            modal.show();
        } else {
            alert(res.message || 'Không tải được dữ liệu danh mục');
        }
    }).fail(function() {
        alert('Lỗi tải dữ liệu');
    });
}

// Submit add/edit via AJAX để tránh hiển thị JSON thô
$('#add-category-form, #edit-category-form').on('submit', function(e) {
    e.preventDefault();
    const form = this;
    const formData = new FormData(form);
    $.ajax({
        url: $(form).attr('action'),
        method: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(res) {
            try {
                if (typeof res === 'string') res = JSON.parse(res);
            } catch (_) {}
            if (res && res.success) {
                // đóng modal và reload
                const modalEl = form.closest('.modal');
                const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modal.hide();
                location.reload();
            } else {
                alert((res && res.message) || 'Lưu thất bại');
            }
        },
        error: function() {
            alert('Không thể lưu danh mục');
        }
    });
});
</script>