<?php
// Add debug logging
error_log("fnb_menu.php loaded");
error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));
?>
<?php
// This file is loaded by user_dashboard.php?page=fnb_menu
require_once __DIR__ . '/../db_connect.php';

// Session is already started by user_dashboard.php
$user_id = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'] ?? 'Guest';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-success">Food & Beverage Menu</h2>
<a href="?page=fnb_my_orders" class="btn btn-outline-success">
    <i class="fas fa-history me-2"></i>View My Orders
</a>
            </div>
        </div>
    </div>

    <div class="row" id="menuContainer">
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-success" role="status">
                <span class="visually-hidden">Loading menu...</span>
            </div>
            <p class="mt-2 text-muted">Loading delicious options...</p>
        </div>
    </div>
</div>

<!-- Order Modal -->
<div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #00b8a9 0%, #00998c 100%); color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-shopping-cart me-2"></i>Place Order
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="orderItemDetails"></div>
                <form id="orderForm">
                    <input type="hidden" id="menuItemId" name="menu_item_id">
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" value="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Total Price</label>
                        <p class="h4 text-success" id="totalPrice">₱0.00</p>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="placeOrder()">Place Order</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
let currentItem = null;
const userId = <?php echo $user_id; ?>;

function loadMenu() {
    $.ajax({
        url: 'fnb_api/get_menu_items.php',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                displayMenu(response.data);
            } else {
                $('#menuContainer').html('<div class="col-12 text-center text-danger">Failed to load menu: ' + (response.message || 'Unknown error') + '</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            $('#menuContainer').html('<div class="col-12 text-center text-danger">Error loading menu. Please refresh and try again.</div>');
        }
    });
}

function displayMenu(items) {
    const container = $('#menuContainer');
    container.empty();
    
    if (!items || items.length === 0) {
        container.html('<div class="col-12 text-center"><i class="fas fa-utensils fa-3x text-muted mb-3"></i><h5>No menu items available</h5><p class="text-muted">Check back later for delicious options!</p></div>');
        return;
    }

    const availableItems = items.filter(item => item.is_available == 1);
    
    if (availableItems.length === 0) {
        container.html('<div class="col-12 text-center"><i class="fas fa-ban fa-3x text-muted mb-3"></i><h5>No items available</h5><p class="text-muted">Menu is currently unavailable</p></div>');
        return;
    }

    availableItems.forEach(item => {
        const card = `
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 shadow-sm" style="border-radius: 12px; transition: transform 0.2s;">
                    ${item.image ? `<img src="${item.image}" class="card-img-top" style="height: 200px; object-fit: cover; border-radius: 12px 12px 0 0;" alt="${item.name}">` : 
                                   `<div class="bg-light text-center py-5" style="height: 200px;">
                                        <i class="fas fa-utensils fa-3x text-muted mt-5"></i>
                                    </div>`}
                    <div class="card-body">
                        <h5 class="card-title text-success fw-bold">${escapeHtml(item.name)}</h5>
                        <p class="card-text text-muted small">${escapeHtml(item.description.substring(0, 100))}${item.description.length > 100 ? '...' : ''}</p>
                        <p class="card-text">
                            <span class="badge bg-info">${escapeHtml(item.category)}</span>
                        </p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="h5 text-success fw-bold mb-0">₱${parseFloat(item.price).toFixed(2)}</span>
                            <button class="btn btn-success btn-sm" onclick='showOrderModal(${JSON.stringify(item)})'>
                                <i class="fas fa-shopping-cart me-1"></i>Order
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.append(card);
    });
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text).replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

function showOrderModal(item) {
    currentItem = item;
    $('#menuItemId').val(item.id);
    $('#orderItemDetails').html(`
        <div class="text-center mb-3">
            ${item.image ? `<img src="${item.image}" class="img-fluid rounded mb-2" style="max-height: 150px;">` : ''}
            <h5>${escapeHtml(item.name)}</h5>
            <p class="text-muted small">${escapeHtml(item.description)}</p>
            <p class="fw-bold">Price: ₱${parseFloat(item.price).toFixed(2)}</p>
        </div>
    `);
    updateTotalPrice();
    
    $('#quantity').val(1);
    updateTotalPrice();
    
    new bootstrap.Modal(document.getElementById('orderModal')).show();
}

$('#quantity').on('input', function() {
    updateTotalPrice();
});

function updateTotalPrice() {
    const quantity = parseInt($('#quantity').val()) || 0;
    const total = quantity * (currentItem ? currentItem.price : 0);
    $('#totalPrice').text(`₱${total.toFixed(2)}`);
}

function placeOrder() {
    const quantity = parseInt($('#quantity').val());
    if (quantity < 1) {
        alert('Please enter a valid quantity');
        return;
    }

    $.ajax({
        url: 'fnb_api/place_order.php',
        type: 'POST',
        data: {
            menu_item_id: currentItem.id,
            quantity: quantity,
            unit_price: currentItem.price
        },
        success: function(response) {
            if (response.success) {
                bootstrap.Modal.getInstance(document.getElementById('orderModal')).hide();
                
                if (typeof Toast !== 'undefined') {
                    Toast.show('Order placed successfully! Your food is being prepared.', 'success');
                } else {
                    alert('Order placed successfully!');
                }
                
                $('#quantity').val(1);
            } else {
                if (typeof Toast !== 'undefined') {
                    Toast.show(response.message || 'Error placing order', 'error');
                } else {
                    alert('Error: ' + response.message);
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Place order error:', error);
            if (typeof Toast !== 'undefined') {
                Toast.show('Error placing order. Please try again.', 'error');
            } else {
                alert('Error placing order');
            }
        }
    });
}

$(document).ready(function() {
    loadMenu();
});
</script>