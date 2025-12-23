// Inventory Management JavaScript

$(document).ready(function() {
    // Low stock alert
    checkLowStock();
    
    // Ingredient autocomplete
    initializeAutocomplete();
    
    // Recipe calculator
    initializeRecipeCalculator();
});

function checkLowStock() {
    $.ajax({
        url: 'ajax_check_low_stock.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data && response.data.length > 0) {
                showLowStockAlert(response.data);
            }
        }
    });
}

function showLowStockAlert(items) {
    let message = 'Cảnh báo: ' + items.length + ' nguyên liệu sắp hết:\n';
    items.forEach(item => {
        message += `\n- ${item.ingredient_name}:  còn ${item.current_stock} ${item.unit}`;
    });
    
    if (confirm(message + '\n\nBạn có muốn nhập kho ngay? ')) {
        window.location. href = 'stock_in.php';
    }
}

function initializeAutocomplete() {
    // Ingredient search with autocomplete (only if plugin is available and input exists)
    if (!$.fn.autocomplete) return;
    const $input = $('#ingredient-search');
    if (!$input.length) return;

    $input.autocomplete({
        source: function(request, responseCb) {
            $.ajax({
                url: 'ajax_search_ingredients.php',
                data: { q: request.term },
                dataType: 'json',
                success: function(res) {
                    if (res.success && res.data) {
                        responseCb(res.data);
                    } else {
                        responseCb([]);
                    }
                }
            });
        },
        minLength: 2,
        select: function(event, ui) {
            $('#ingredient-id').val(ui.item.id);
        }
    });
}

function initializeRecipeCalculator() {
    // Calculate total cost when recipe quantities change
    $('.recipe-quantity').on('input', function() {
        calculateRecipeCost();
    });
}

function calculateRecipeCost() {
    let totalCost = 0;
    
    $('.recipe-item').each(function() {
        const quantity = parseFloat($(this).find('.recipe-quantity').val()) || 0;
        const unitPrice = parseFloat($(this).data('unit-price')) || 0;
        const itemCost = quantity * unitPrice;
        
        $(this).find('.item-cost').text(formatMoney(itemCost));
        totalCost += itemCost;
    });
    
    $('#total-recipe-cost').text(formatMoney(totalCost));
    
    // Calculate profit margin
    const sellingPrice = parseFloat($('#selling-price').val()) || 0;
    const profit = sellingPrice - totalCost;
    const margin = sellingPrice > 0 ? (profit / sellingPrice * 100).toFixed(2) : 0;
    
    $('#profit-amount').text(formatMoney(profit));
    $('#profit-margin').text(margin + '%');
}

function formatMoney(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

// Stock movement chart
function renderStockChart(ingredientId) {
    $.ajax({
        url: 'ajax_get_stock_history.php',
        method: 'GET',
        data: { ingredient_id: ingredientId },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                // Giả sử response.data là mảng các bản ghi lịch sử tồn kho
                const dates = response.data.map(item => item.created_at);
                const quantities = response.data.map(item => item.quantity);
                const ctx = document.getElementById('stockChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: dates,
                        datasets: [{
                            label: 'Tồn kho',
                            data: quantities,
                            borderColor: 'rgb(75, 192, 192)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero:  true
                            }
                        }
                    }
                });
            }
        }
    });
}

// Dữ liệu nguyên liệu phải được render ra JS từ PHP, ví dụ:
// const INGREDIENTS_DATA = ...;
function editIngredient(ingredientId) {
    if (typeof INGREDIENTS_DATA === 'undefined') {
        alert('Không tìm thấy dữ liệu nguyên liệu!');
        return;
    }
    const ing = INGREDIENTS_DATA.find(i => i.ingredient_id == ingredientId);
    if (!ing) {
        alert('Không tìm thấy nguyên liệu!');
        return;
    }
    $('#edit-ingredient-id').val(ing.ingredient_id);
    $('#edit-ingredient-name').val(ing.ingredient_name);
    $('#edit-ingredient-unit').val(ing.unit);
    $('#edit-ingredient-stock').val(ing.current_stock);
    $('#edit-ingredient-reorder').val(ing.reorder_level);
    $('#edit-ingredient-price').val(ing.cost_price);
    $('#edit-ingredient-supplier').val(ing.supplier_name);
    $('#edit-ingredient-phone').val(ing.supplier_phone);
    const modal = new bootstrap.Modal(document.getElementById('editIngredientModal'));
    modal.show();
}