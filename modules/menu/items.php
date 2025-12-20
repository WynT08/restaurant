<?php
$page_title = 'Quản lý món ăn';
include '../../includes/header.php';
requirePermission('manager');

// Get category filter
$category_filter = isset($_GET['category']) ? $_GET['category'] : 'all';

// Get all menu items
$query = "SELECT mi.*, c.category_name 
          FROM menu_items mi 
          LEFT JOIN categories c ON mi.category_id = c.category_id";

if ($category_filter != 'all') {
    $query .= " WHERE mi.category_id = :category_id";
}

$query .= " ORDER BY mi.display_order, mi. item_name";

$stmt = $db->prepare($query);
if ($category_filter != 'all') {
    $stmt->bindParam(':category_id', $category_filter);
}
$stmt->execute();
$items = $stmt->fetchAll(PDO:: FETCH_ASSOC);

// Get categories for filter
$categories = $db->query("SELECT * FROM categories ORDER BY display_order")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Quản lý món ăn</h1>
        <div class="d-flex gap-2">
            <a href="categories.php" class="btn btn-outline-primary">
                <i class="fas fa-folder"></i> Danh mục
            </a>
            <a href="add_item.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Thêm món mới
            </a>
        </div>
    </div>
    
    <!-- Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <select class="form-select" id="category-filter" onchange="filterByCategory(this.value)">
                        <option value="all">Tất cả danh mục</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>" <?php echo $category_filter == $cat['category_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control" id="search-items" placeholder="Tìm kiếm món... ">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Items Grid -->
    <div class="row g-3">
        <?php foreach ($items as $item): ?>
        <div class="col-lg-3 col-md-4 col-sm-6">
            <div class="card menu-item-card">
                <div class="item-image">
                    <?php if ($item['image']): ?>
                        <img src="<?php echo SITE_URL . '/uploads/menu_images/' . $item['image']; ?>" alt="">
                    <?php else: ?>
                        <div class="no-image">
                            <i class="fas fa-utensils"></i>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!$item['is_available']): ?>
                        <div class="item-badge unavailable">Hết hàng</div>
                    <?php endif; ?>
                    
                    <div class="item-actions">
                        <a href="edit_item.php?id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-info">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button class="btn btn-sm btn-danger" onclick="deleteItem(<?php echo $item['item_id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="mb-0"><?php echo htmlspecialchars($item['item_name']); ?></h6>
                        <?php if ($item['is_vegetarian']): ?>
                            <span class="badge bg-success" title="Món chay">
                                <i class="fas fa-leaf"></i>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <p class="text-muted small mb-2">
                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($item['category_name']); ?>
                    </p>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <strong class="text-primary"><?php echo formatMoney($item['price']); ?></strong>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" 
                                   <?php echo $item['is_available'] ? 'checked' : ''; ?>
                                   onchange="toggleAvailability(<?php echo $item['item_id']; ?>, this.checked)">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function filterByCategory(categoryId) {
    window.location.href = 'items.php?category=' + categoryId;
}

function toggleAvailability(itemId, isAvailable) {
    $.post('ajax_toggle_availability.php', {
        item_id: itemId,
        is_available: isAvailable ?  1 : 0
    }, function(response) {
        if (response.success) {
            showToast('Cập nhật thành công', 'success');
        }
    }, 'json');
}

function deleteItem(itemId) {
    if (confirm('Bạn có chắc muốn xóa món này?')) {
        $.post('ajax_delete_item.php', { item_id: itemId }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.message);
            }
        }, 'json');
    }
}

// Search
$('#search-items').on('keyup', function() {
    const value = $(this).val().toLowerCase();
    $('.menu-item-card').each(function() {
        const text = $(this).text().toLowerCase();
        $(this).parent().toggle(text.indexOf(value) > -1);
    });
});
</script>

<?php include '../../includes/footer.php'; ?>