<?php
$page_title = 'POS - Point of Sale';
include '../../includes/header.php';

try {
    // Get categories; if display_order missing, fall back to name
    $query = "SELECT * FROM categories ORDER BY display_order, category_name";
    $categories = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $query = "SELECT * FROM categories ORDER BY category_name";
    $categories = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
}

try {
    // Get available tables
    $query = "SELECT * FROM restaurant_tables WHERE status = 'available' ORDER BY table_number";
    $tables = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $tables = [];
}
?>

<div class="pos-container">
    <!-- Left side - Menu -->
    <div class="pos-menu-section">
        <div class="pos-header">
            <h4><i class="fas fa-utensils"></i> Thực đơn</h4>
            <input type="text" id="search-menu" class="form-control" placeholder="Tìm món... ">
        </div>
        
        <!-- Categories tabs -->
        <div class="categories-tabs">
            <button class="category-btn active" data-category="all">
                <i class="fas fa-th"></i> Tất cả
            </button>
            <?php foreach ($categories as $cat): ?>
            <button class="category-btn" data-category="<?php echo $cat['category_id']; ?>">
                <?php echo htmlspecialchars($cat['category_name']); ?>
            </button>
            <?php endforeach; ?>
        </div>
        
        <!-- Menu items grid -->
        <div id="menu-items-grid" class="menu-items-grid">
            <!-- Loaded via AJAX -->
        </div>
    </div>
    
    <!-- Right side - Order Cart -->
    <div class="pos-cart-section">
        <div class="pos-header">
            <h4><i class="fas fa-shopping-cart"></i> Đơn hàng</h4>
            <div class="order-info">
                <select id="order-type" class="form-select form-select-sm mb-2">
                    <option value="dine_in">Tại chỗ</option>
                    <option value="takeaway">Mang về</option>
                    <option value="delivery">Giao hàng</option>
                </select>
                <select id="table-select" class="form-select form-select-sm">
                    <option value="">Chọn bàn... </option>
                    <?php foreach ($tables as $table): ?>
                    <option value="<?php echo $table['table_id']; ?>">
                        <?php echo $table['table_number']; ?> (<?php echo $table['capacity']; ?> chỗ)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <!-- Cart items -->
        <div id="cart-items" class="cart-items">
            <div class="empty-cart">
                <i class="fas fa-shopping-basket"></i>
                <p>Chưa có món nào</p>
            </div>
        </div>
        
        <!-- Cart summary -->
        <div class="cart-summary">
            <div class="summary-row">
                <span>Tạm tính:</span>
                <span id="subtotal">0đ</span>
            </div>
            <div class="summary-row">
                <span>Thuế (10%):</span>
                <span id="tax">0đ</span>
            </div>
            <div class="summary-row">
                <span>Giảm giá:</span>
                <input type="number" id="discount" class="form-control form-control-sm" value="0" min="0" style="width: 100px;">
            </div>
            <div class="summary-row total-row">
                <strong>Tổng cộng:</strong>
                <strong id="total">0đ</strong>
            </div>
        </div>
        
        <!-- Action buttons -->
        <div class="cart-actions">
            <button class="btn btn-outline-danger" id="btn-clear">
                <i class="fas fa-trash"></i> Xóa
            </button>
            <button class="btn btn-outline-secondary" id="btn-hold">
                <i class="fas fa-pause"></i> Giữ
            </button>
            <button class="btn btn-warning" id="btn-kitchen">
                <i class="fas fa-paper-plane"></i> Gửi bếp
            </button>
            <button class="btn btn-success" id="btn-payment">
                <i class="fas fa-money-bill"></i> Thanh toán
            </button>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thanh toán</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="payment-summary mb-3">
                    <h4 class="text-center">Tổng tiền: <span id="payment-total" class="text-primary">0đ</span></h4>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Phương thức thanh toán</label>
                    <select id="payment-method" class="form-select">
                        <option value="cash">Tiền mặt</option>
                        <option value="card">Thẻ</option>
                        <option value="momo">MoMo</option>
                        <option value="bank_transfer">Chuyển khoản</option>
                    </select>
                </div>
                
                <div class="mb-3" id="cash-payment-section">
                    <label class="form-label">Tiền khách đưa</label>
                    <input type="number" id="customer-paid" class="form-control" min="0">
                    <small class="text-muted">Tiền thừa:  <span id="change-amount">0đ</span></small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Tên khách hàng (tùy chọn)</label>
                    <input type="text" id="customer-name" class="form-control">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Số điện thoại (tùy chọn)</label>
                    <input type="text" id="customer-phone" class="form-control">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Ghi chú</label>
                    <textarea id="order-notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-success" id="btn-confirm-payment">
                    <i class="fas fa-check"></i> Xác nhận thanh toán
                </button>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
<script>
    window.SITE_URL = '<?php echo SITE_URL; ?>';
</script>