// POS System JavaScript

let cart = [];
let currentOrderType = 'dine_in';
let selectedTable = null;

$(document).ready(function() {
    console.log('POS System loaded');
    
    // Load menu items on page load
    loadMenuItems('all');
    
    // Category filter
    $('.category-btn').on('click', function() {
        console.log('Category clicked:', $(this).data('category'));
        $('.category-btn').removeClass('active');
        $(this).addClass('active');
        const categoryId = $(this).data('category');
        loadMenuItems(categoryId);
    });
    
    // Search menu
    $('#search-menu').on('keyup', function() {
        const search = $(this).val();
        console.log('Searching:', search);
        const activeCategory = $('.category-btn.active').data('category') || 'all';
        loadMenuItems(activeCategory, search);
    });
    
    // Order type change
    $('#order-type').on('change', function() {
        currentOrderType = $(this).val();
        if (currentOrderType !== 'dine_in') {
            $('#table-select').val('').prop('disabled', true);
        } else {
            $('#table-select').prop('disabled', false);
        }
    });
    
    // Table selection
    $('#table-select').on('change', function() {
        selectedTable = $(this).val();
    });
    
    // Discount change
    $('#discount').on('input', function() {
        updateCartSummary();
    });
    
    // Cart actions
    $('#btn-clear').on('click', clearCart);
    $('#btn-hold').on('click', holdOrder);
    $('#btn-kitchen').on('click', sendToKitchen);
    $('#btn-payment').on('click', openPaymentModal);
    
    // Payment modal
    $('#payment-method').on('change', function() {
        if ($(this).val() === 'cash') {
            $('#cash-payment-section').show();
        } else {
            $('#cash-payment-section').hide();
        }
    });
    
    $('#customer-paid').on('input', calculateChange);
    $('#btn-confirm-payment').on('click', confirmPayment);
});

function loadMenuItems(categoryId, search = '') {
    console.log('Loading items - Category:', categoryId, 'Search:', search);
    
    $.ajax({
        url: 'ajax_get_menu_items.php',
        method: 'GET',
        data: { 
            category_id: categoryId, 
            search: search 
        },
        dataType: 'json',
        beforeSend: function() {
            $('#menu-items-grid').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
        },
        success: function(items) {
            let msg = 'Items loaded: ' + items.length + '\n';
            items.forEach((item, idx) => {
                msg += '[' + idx + '] item_id: ' + item.item_id + ', name: ' + item.item_name + '\n';
                if (!item.item_id) {
                    msg += '==> LỖI: Item thiếu item_id!\n';
                }
            });
            alert(msg);
            displayMenuItems(items);
        },
        error: function(xhr, status, error) {
            console.error('Error loading menu:', error);
            console.error('Response:', xhr.responseText);
            $('#menu-items-grid').html('<p class="text-center text-danger">Lỗi tải menu.  Vui lòng thử lại! </p>');
            showToast('Không thể tải menu', 'danger');
        }
    });
}

function displayMenuItems(items) {
    const grid = $('#menu-items-grid');
    grid.empty();
    
    if (items.length === 0) {
        grid.append('<p class="text-center text-muted mt-5">Không có món nào</p>');
        return;
    }
    
    items.forEach(item => {
        const itemCard = `
            <div class="menu-item-card ${! item.is_available ? 'unavailable' : ''}" 
                 data-item-id="${item.item_id}"
                 data-item-name="${item.item_name}"
                 data-item-price="${item.price}">
                ${item.image ? 
                    `<img src="${SITE_URL}/uploads/menu_images/${item.image}" alt="${item.item_name}">` : 
                    '<div style="width: 100%;height:120px;background:#f0f0f0;border-radius:8px;display:flex;align-items:center;justify-content:center;margin-bottom:10px;"><i class="fas fa-utensils fa-3x text-muted"></i></div>'
                }
                <div class="item-name">${item.item_name}</div>
                <div class="item-price">${formatMoney(item.price)}</div>
                ${! item.is_available ? '<div class="badge bg-danger mt-2">Hết hàng</div>' : ''}
            </div>
        `;
        grid.append(itemCard);
    });
    
    // Add click event to menu items
    $('.menu-item-card').on('click', function() {
        if ($(this).hasClass('unavailable')) {
            showToast('Món này hiện không có sẵn', 'warning');
            return;
        }
        
        const itemId = $(this).data('item-id');
        const itemName = $(this).data('item-name');
        const price = $(this).data('item-price');
        
        addToCart(itemId, itemName, price);
    });
}

function addToCart(itemId, itemName, price) {
    // Log object item khi thêm vào cart
        const itemObj = { item_id: itemId, name: itemName, price: parseFloat(price), quantity: 1 };
    console.log('Adding to cart, item object:', itemObj);
    // ...existing code...
    
    // Check if item already in cart
        const existingItem = cart.find(item => item.item_id === itemId);
    
    if (existingItem) {
        existingItem.quantity++;
    } else {
        cart.push(itemObj);
    }
    
    updateCartDisplay();
    updateCartSummary();
    showToast('Đã thêm ' + itemName, 'success');
}

function updateCartDisplay() {
    const cartContainer = $('#cart-items');
    cartContainer.empty();
    
    if (cart.length === 0) {
        cartContainer.html(`
            <div class="empty-cart">
                <i class="fas fa-shopping-basket"></i>
                <p>Chưa có món nào</p>
            </div>
        `);
        return;
    }
    
    cart.forEach((item, index) => {
        const cartItem = `
            <div class="cart-item">
                <div class="cart-item-info">
                    <div class="cart-item-name">${item.name}</div>
                    <div class="cart-item-price">${formatMoney(item.price)}</div>
                </div>
                <div class="cart-item-controls">
                    <button class="btn btn-sm btn-outline-secondary" onclick="decreaseQuantity(${index})">
                        <i class="fas fa-minus"></i>
                    </button>
                    <span class="cart-item-quantity">${item.quantity}</span>
                    <button class="btn btn-sm btn-outline-secondary" onclick="increaseQuantity(${index})">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        cartContainer.append(cartItem);
    });
}

function increaseQuantity(index) {
    cart[index].quantity++;
    updateCartDisplay();
    updateCartSummary();
}

function decreaseQuantity(index) {
    if (cart[index].quantity > 1) {
        cart[index].quantity--;
        updateCartDisplay();
        updateCartSummary();
    }
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCartDisplay();
    updateCartSummary();
}

function updateCartSummary() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const tax = subtotal * 0.1;
    const discount = parseFloat($('#discount').val()) || 0;
    const total = subtotal + tax - discount;
    
    $('#subtotal').text(formatMoney(subtotal));
    $('#tax').text(formatMoney(tax));
    $('#total').text(formatMoney(total));
    $('#payment-total').text(formatMoney(total));
}

function clearCart() {
    if (cart.length === 0) return;
    
    if (confirm('Bạn có chắc muốn xóa toàn bộ đơn hàng?')) {
        cart = [];
        updateCartDisplay();
        updateCartSummary();
        showToast('Đã xóa đơn hàng', 'info');
    }
}

function holdOrder() {
    if (cart.length === 0) {
        showToast('Giỏ hàng trống', 'warning');
        return;
    }
    
    // Save to localStorage
    const heldOrders = JSON.parse(localStorage. getItem('heldOrders') || '[]');
    heldOrders.push({
        cart: cart,
        orderType: currentOrderType,
        table: selectedTable,
        timestamp: new Date().toISOString()
    });
    localStorage.setItem('heldOrders', JSON.stringify(heldOrders));
    
    showToast('Đã giữ đơn hàng', 'success');
    clearCart();
}

function sendToKitchen() {
    if (cart.length === 0) {
        showToast('Giỏ hàng trống', 'warning');
        return;
    }
    
    showToast('Đã gửi đơn hàng vào bếp', 'success');
}

function openPaymentModal() {
    if (cart.length === 0) {
        showToast('Giỏ hàng trống', 'warning');
        return;
    }
    
    const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
    modal.show();
}

function calculateChange() {
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const tax = total * 0.1;
    const discount = parseFloat($('#discount').val()) || 0;
    const finalTotal = total + tax - discount;
    
    const customerPaid = parseFloat($('#customer-paid').val()) || 0;
    const change = customerPaid - finalTotal;
    
    $('#change-amount').text(formatMoney(Math.max(0, change)));
}

function confirmPayment() {
    if (cart.length === 0) return;
    
    const paymentData = {
        items: cart,
        order_type: currentOrderType,
        table_id: selectedTable,
        payment_method: $('#payment-method').val(),
        customer_name: $('#customer-name').val(),
        customer_phone: $('#customer-phone').val(),
        notes: $('#order-notes').val(),
        discount: parseFloat($('#discount').val()) || 0
    };
    
    $.ajax({
        url: 'ajax_create_order.php',
        method: 'POST',
        data: JSON.stringify(paymentData),
        contentType: 'application/json',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showToast('Thanh toán thành công! ', 'success');
                $('#paymentModal').modal('hide');
                
                // Clear cart
                cart = [];
                updateCartDisplay();
                updateCartSummary();
                
                // Reset form
                $('#customer-name').val('');
                $('#customer-phone').val('');
                $('#order-notes').val('');
                $('#discount').val(0);
                $('#customer-paid').val('');
                
                // Optionally print receipt
                if (confirm('In hóa đơn? ')) {
                    window.open('print_receipt.php? order_id=' + response.order_id, '_blank');
                }
            } else {
                showToast('Lỗi:  ' + response.message, 'danger');
            }
        },
        error: function() {
            showToast('Không thể xử lý thanh toán', 'danger');
        }
    });
}

// Helper function for money formatting (if not already defined)
if (typeof formatMoney !== 'function') {
    function formatMoney(amount) {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(amount);
    }
}