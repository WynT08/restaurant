<?php
$page_title = 'Công thức món ăn';
include '../../includes/header.php';
requirePermission('manager');

// Get menu items with recipes
$query = "SELECT mi.*, c.category_name,
          (SELECT COUNT(*) FROM recipes WHERE item_id = mi.item_id) as recipe_count
          FROM menu_items mi
          LEFT JOIN categories c ON mi.category_id = c.category_id
          ORDER BY mi.item_name";
$items = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Get all ingredients for dropdown
$ingredients = $db->query("SELECT * FROM ingredients ORDER BY ingredient_name")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Công thức món ăn</h1>
        <a href="ingredients.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại kho
        </a>
    </div>
    
    <div class="row">
        <? php foreach ($items as $item): ?>
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0"><?php echo htmlspecialchars($item['item_name']); ?></h5>
                            <small class="text-muted"><?php echo htmlspecialchars($item['category_name']); ?></small>
                        </div>
                        <button class="btn btn-sm btn-primary" 
                                onclick="addIngredient(<?php echo $item['item_id']; ?>)">
                            <i class="fas fa-plus"></i> Thêm nguyên liệu
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <? php
                    // Get recipe for this item
                    $query = "SELECT r.*, i.ingredient_name, i.unit 
                              FROM recipes r
                              JOIN ingredients i ON r. ingredient_id = i.ingredient_id
                              WHERE r.item_id = :item_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':item_id', $item['item_id']);
                    $stmt->execute();
                    $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    
                    <? php if (count($recipes) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Nguyên liệu</th>
                                    <th>Số lượng</th>
                                    <th width="80">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_cost = 0;
                                foreach ($recipes as $recipe): 
                                    // Calculate ingredient cost
                                    $query = "SELECT unit_price FROM ingredients WHERE ingredient_id = :id";
                                    $stmt = $db->prepare($query);
                                    $stmt->bindParam(':id', $recipe['ingredient_id']);
                                    $stmt->execute();
                                    $ing = $stmt->fetch(PDO:: FETCH_ASSOC);
                                    $cost = $recipe['quantity'] * ($ing['unit_price'] ?? 0);
                                    $total_cost += $cost;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($recipe['ingredient_name']); ?></td>
                                    <td>
                                        <strong><?php echo $recipe['quantity']; ?></strong> 
                                        <?php echo $recipe['unit']; ?>
                                        <br>
                                        <small class="text-muted"><?php echo formatMoney($cost); ?></small>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-danger" 
                                                onclick="deleteRecipe(<?php echo $recipe['recipe_id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <? php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-info">
                                    <td><strong>Giá vốn ước tính:</strong></td>
                                    <td colspan="2">
                                        <strong class="text-danger"><?php echo formatMoney($total_cost); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            Giá bán: <?php echo formatMoney($item['price']); ?>
                                            | Lợi nhuận: <?php echo formatMoney($item['price'] - $total_cost); ?>
                                        </small>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted text-center mb-0">
                        <i class="fas fa-info-circle"></i> Chưa có công thức
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <? php endforeach; ?>
    </div>
</div>

<!-- Add Ingredient Modal -->
<div class="modal fade" id="addIngredientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="add-ingredient-form">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm nguyên liệu vào công thức</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="item-id" name="item_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Nguyên liệu *</label>
                        <select name="ingredient_id" class="form-select" id="ingredient-select" required>
                            <option value="">-- Chọn nguyên liệu --</option>
                            <?php foreach ($ingredients as $ing): ?>
                            <option value="<?php echo $ing['ingredient_id']; ?>" 
                                    data-unit="<?php echo $ing['unit']; ?>">
                                <?php echo htmlspecialchars($ing['ingredient_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Số lượng *</label>
                        <div class="input-group">
                            <input type="number" name="quantity" class="form-control" 
                                   required step="0.01" min="0.01">
                            <span class="input-group-text" id="unit-display">-</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Thêm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const addIngredientModal = new bootstrap.Modal(document.getElementById('addIngredientModal'));

function addIngredient(itemId) {
    $('#item-id').val(itemId);
    addIngredientModal.show();
}

$('#ingredient-select').on('change', function() {
    const unit = $(this).find(':selected').data('unit');
    $('#unit-display').text(unit);
});

$('#add-ingredient-form').on('submit', function(e) {
    e.preventDefault();
    
    $. post('ajax_add_recipe.php', $(this).serialize(), function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert(response.message);
        }
    }, 'json');
});

function deleteRecipe(recipeId) {
    if (confirm('Xóa nguyên liệu này khỏi công thức?')) {
        $.post('ajax_delete_recipe.php', { recipe_id: recipeId }, function(response) {
            if (response.success) {
                location.reload();
            }
        }, 'json');
    }
}
</script>

<?php include '../../includes/footer.php'; ?>