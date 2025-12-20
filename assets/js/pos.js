// POS System JavaScript

let cart = [];
let currentOrderType = 'dine_in';
let selectedTable = null;

$(document).ready(function() {
    // Load menu items
    loadMenuItems('all');
    
    // Category filter
    $('. category-btn').on('click', function() {
        $('. category-btn').removeClass('active');
        $(this).addClass('active');
        const categoryId = $(this).data('category');
        loadMenuItems(categoryId);
    });
    
    // Search menu
    $('#search-menu').on('keyup', function() {
        const search = $(this).val();
        loadMenuItems($('. category-btn.active').data('category'), search);
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
    $.ajax({
        url: 'ajax_get_menu_items.php',
        method: 'GET',
        data: { category_id: categoryId, search: search },
        dataType: 'json',
        success: function(items) {
            displayMenuItems(items);
        },
        error: function() {
            showToast('Không thể tải menu', 'danger');
        }
    });
}

function displayMenuItems(items) {
    const grid = $('#menu-items-grid');
    grid.empty();
    
    if (items.length === 0) {
        grid.append('<p class="text-center text-muted">Không có món nào</p>');
        return;
    }
    
    items. forEach(item => {
        const itemCard = `
            <div class="menu-item-card ${! item.is_available ? 'unavailable' : ''}" 
                 onclick="addToCart(${item.item_id}, '${item.item_name}', ${item.price})">
                ${item.image ? 
                    `<img src="/uploads/menu_images/${item.image}" alt="${item.item_name}">` : 
                    '<div class="no-image"><i class="fas fa-utensils"></i></div>'
                }
                <div class="item-name">${item.item_name}</div>
                <div class="item-price">${formatMoney(item.price)}</div>
                ${! item.is_available ? '<div class="badge bg-danger mt-2">Hết hàng</div>' : ''}
            </div>
        `;
        grid.append(itemCard);
    });
}

function addToCart(itemId, itemName, price) {
    // Check if item already in cart
    const existingItem = cart.find(item => item.item_id === itemId);
    
    if (existingItem) {
        existingItem.quantity++;
    } else {
        cart.push({
            item_id: itemId,
            item_name: itemName,
            price: price,
            quantity: 1,
            notes: ''
        });
    }
    
    updateCartDisplay();
    showToast(`Đã thêm ${itemName}`, 'success');
}

function updateCartDisplay() {
    const cartItems = $('#cart-items');
    cartItems.empty();
    
    if (cart.length === 0) {
        cartItems.html(`
            <div class="empty-cart">
                <i class="fas fa-shopping-basket"></i>
                <p>Chưa có món nào</p>
            </div>
        `);
        updateCartSummary();
        return;
    }
    
    cart.forEach((item, index) => {
        const cartItem = `
            <div class="cart-item">
                <div class="cart-item-info">
                    <div class="cart-item-name">${item.item_name}</div>
                    <div class="cart-item-price">${formatMoney(item.price)}</div>
                </div>
                <div class="cart-item-quantity">
                    <button class="qty-btn" onclick="updateQuantity(${index}, -1)">
                        <i class="fas fa-minus"></i>
                    </button>
                    <span class="qty-display">${item.quantity}</span>
                    <button class="qty-btn" onclick="updateQuantity(${index}, 1)">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <button class="cart-item-remove" onclick="removeFromCart(${index})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        cartItems.append(cartItem);
    });
    
    updateCartSummary();
}

function updateQuantity(index, change) {
    cart[index].quantity += change;
    
    if (cart[index].quantity <= 0) {
        cart.splice(index, 1);
    }
    
    updateCartDisplay();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCartDisplay();
}

function updateCartSummary() {
    let subtotal = 0;
    
    cart.forEach(item => {
        subtotal += item.price * item.quantity;
    });
    
    const tax = subtotal * 0.1; // 10% VAT
    const discount = parseFloat($('#discount').val()) || 0;
    const total = subtotal + tax - discount;
    
    $('#subtotal').text(formatMoney(subtotal));
    $('#tax').text(formatMoney(tax));
    $('#total').text(formatMoney(total));
}

function clearCart() {
    if (cart.length === 0) return;
    
    if (confirm('Xóa tất cả món trong giỏ hàng? ')) {
        cart = [];
        updateCartDisplay();
    }
}

function holdOrder() {
    if (cart.length === 0) {
        showToast('Giỏ hàng trống', 'warning');
        return;
    }
    
    // Save to localStorage
    const holdOrders = JSON.parse(localStorage. getItem('holdOrders') || '[]');
    holdOrders.push({
        items: cart,
        table: selectedTable,
        orderType: currentOrderType,
        timestamp: new Date().toISOString()
    });
    localStorage.setItem('holdOrders', JSON.stringify(holdOrders));
    
    cart = [];
    updateCartDisplay();
    showToast('Đã lưu đơn hàng', 'success');
}

function sendToKitchen() {
    if (cart.length === 0) {
        showToast('Giỏ hàng trống', 'warning');
        return;
    }
    
    if (currentOrderType === 'dine_in' && ! selectedTable) {
        showToast('Vui lòng chọn bàn', 'warning');
        return;
    }
    
    const orderData = {
        items: cart,
        table_id: selectedTable,
        order_type: currentOrderType,
        subtotal: calculateSubtotal(),
        tax: calculateTax(),
        discount: parseFloat($('#discount').val()) || 0,
        total: calculateTotal(),
        send_to_kitchen: true
    };
    
    loadingSpinner(true);
    
    $.ajax({
        url: 'ajax_create_order. php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(orderData),
        dataType: 'json',
        success: function(response) {
            loadingSpinner(false);
            if (response.success) {
                showToast('Đã gửi bếp:  ' + response.order_number, 'success');
                cart = [];
                updateCartDisplay();
            } else {
                showToast(response.message, 'danger');
            }
        }
    });
}

function openPaymentModal() {
    if (cart.length === 0) {
        showToast('Giỏ hàng trống', 'warning');
        return;
    }
    
    if (currentOrderType === 'dine_in' && ! selectedTable) {
        showToast('Vui lòng chọn bàn', 'warning');
        return;
    }
    
    const total = calculateTotal();
    $('#payment-total').text(formatMoney(total));
    $('#customer-paid').val(total);
    calculateChange();
    
    const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
    paymentModal.show();
}

function calculateChange() {
    const total = calculateTotal();
    const paid = parseFloat($('#customer-paid').val()) || 0;
    const change = paid - total;
    
    $('#change-amount').text(formatMoney(change >= 0 ? change : 0));
}

function confirmPayment() {
    const paymentMethod = $('#payment-method').val();
    const customerPaid = parseFloat($('#customer-paid').val()) || 0;
    const total = calculateTotal();
    
    if (paymentMethod === 'cash' && customerPaid < total) {
        showToast('Tiền khách đưa không đủ', 'warning');
        return;
    }
    
    const orderData = {
        items:  cart,
        table_id:  selectedTable,
        order_type: currentOrderType,
        subtotal: calculateSubtotal(),
        tax: calculateTax(),
        discount: parseFloat($('#discount').val()) || 0,
        total: total,
        payment_method: paymentMethod,
        customer_name: $('#customer-name').val(),
        customer_phone: $('#customer-phone').val(),
        notes: $('#order-notes').val(),
        send_to_kitchen:  true
    };
    
    loadingSpinner(true);
    
    $.ajax({
        url: 'ajax_create_order.php',
        method: 'POST',
        contentType:  'application/json',
        data: JSON.stringify(orderData),
        dataType: 'json',
        success: function(response) {
            loadingSpinner(false);
            if (response.success) {
                showToast('Thanh toán thành công! ', 'success');
                
                // Print invoice
                if (confirm('In hóa đơn? ')) {
                    printInvoice(response.order_id);
                }
                
                // Clear cart
                cart = [];
                selectedTable = null;
                $('#table-select').val('');
                $('#discount').val(0);
                updateCartDisplay();
                
                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
            } else {
                showToast(response.message, 'danger');
            }
        }
    });
}

function calculateSubtotal() {
    let subtotal = 0;
    cart.forEach(item => {
        subtotal += item.price * item.quantity;
    });
    return subtotal;
}

function calculateTax() {
    return calculateSubtotal() * 0.1;
}

function calculateTotal() {
    const subtotal = calculateSubtotal();
    const tax = calculateTax();
    const discount = parseFloat($('#discount').val()) || 0;
    return subtotal + tax - discount;
}

function printInvoice(orderId) {
    window.open('print_invoice.php?order_id=' + orderId, '_blank');
}

function formatMoney(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}