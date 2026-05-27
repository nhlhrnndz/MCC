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
        <!-- Menu will be loaded here -->
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
let currentItem = null;
const userId = <?php echo $user_id; ?>;

// ============================================
// HARDCODED MENU DATA - Using relative paths from project root
// ============================================
const menuData = [
    {
        id: 1,
        name: "Grilled Chicken",
        description: "Grilled chicken breast with vegetables, served with rice and gravy.",
        price: 250.00,
        category: "Main Course",
        image: "../upload/grilled_chicken.jpg",
        is_available: true
    },
    {
        id: 2,
        name: "Caesar Salad",
        description: "Fresh romaine lettuce with Caesar dressing, croutons, and parmesan cheese.",
        price: 150.00,
        category: "Appetizer",
        image: "../upload/caesar_salad.jpg",
        is_available: true
    },
    {
        id: 3,
        name: "Chocolate Cake",
        description: "Rich chocolate cake with creamy chocolate frosting.",
        price: 120.00,
        category: "Dessert",
        image: "../upload/chocolate_cake.jpg",
        is_available: true
    },
    {
        id: 4,
        name: "Iced Tea",
        description: "Freshly brewed iced tea with lemon.",
        price: 40.00,
        category: "Beverage",
        image: "../upload/iced_tea.jpg",
        is_available: true
    },
    {
        id: 5,
        name: "Club Sandwich",
        description: "Triple-decker sandwich with ham, turkey, bacon, lettuce, and tomato.",
        price: 180.00,
        category: "Snack",
        image: "../upload/club_sandwich.jpg",
        is_available: true
    },
    {
        id: 6,
        name: "Bulalo Sa MCC",
        description: "Hot beef bulalo soup with vegetables, corn, and bone marrow.",
        price: 380.00,
        category: "Specialty",
        image: "../upload/bulalo.jpg",
        is_available: true
    },
    {
        id: 7,
        name: "Sinigang na Hipon",
        description: "Shrimp sour soup with vegetables, cooked in tamarind broth.",
        price: 300.00,
        category: "Main Course",
        image: "../upload/sinigang_hipon.jpg",
        is_available: true
    },
    {
        id: 8,
        name: "Crispy Pata",
        description: "Deep fried crispy pork leg served with special sauce.",
        price: 400.00,
        category: "Main Course",
        image: "../upload/crispy_pata.jpg",
        is_available: true
    },
    {
        id: 9,
        name: "Lechon Kawali",
        description: "Crispy pork belly slices served with lechon sauce.",
        price: 150.00,
        category: "Main Course",
        image: "../upload/lechon_kawali.jpg",
        is_available: true
    },
    {
        id: 10,
        name: "Chopsuey",
        description: "Mixed vegetables stir fry with chicken and shrimp.",
        price: 170.00,
        category: "Main Course",
        image: "../upload/chopsuey.jpg",
        is_available: true
    },
    {
        id: 11,
        name: "Pork Barbeque",
        description: "Grilled pork barbeque skewers marinated in special sauce.",
        price: 35.00,
        category: "Main Course",
        image: "../upload/pork_barbeque.jpg",
        is_available: true
    }
];

function loadMenu() {
    displayMenu(menuData);
}

function displayMenu(items) {
    const container = $('#menuContainer');
    container.empty();

    if (!items || items.length === 0) {
        container.html(`
            <div class="col-12 text-center">
                <h5>No menu items available</h5>
            </div>
        `);
        return;
    }

    const availableItems = items.filter(item => item.is_available == true);

    availableItems.forEach(item => {
        // Build the correct image URL
        let imageUrl = item.image;
        
        // Log to console for debugging
        console.log("Item:", item.name, "Image URL:", imageUrl);

        const card = `
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 shadow-sm" style="border-radius: 12px; overflow: hidden;">
                    <img
                        src="${imageUrl}"
                        class="card-img-top"
                        style="height: 220px; width: 100%; object-fit: cover;"
                        alt="${escapeHtml(item.name)}"
                        onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22300%22 height=%22200%22%3E%3Crect width=%22300%22 height=%22200%22 fill=%22%23e9ecef%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%236c757d%22 font-size=%2216%22%3E🍽️ ${escapeHtml(item.name)}%3C/text%3E%3C/svg%3E'; this.style.objectFit='contain';"
                    >
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title text-success fw-bold">${escapeHtml(item.name)}</h5>
                        <p class="card-text text-muted small">${escapeHtml(item.description || '')}</p>
                        <p><span class="badge bg-info">${escapeHtml(item.category)}</span></p>
                        <div class="d-flex justify-content-between align-items-center mt-auto">
                            <span class="h5 text-success fw-bold mb-0">₱${item.price.toFixed(2)}</span>
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

    let imageUrl = item.image;

    $('#orderItemDetails').html(`
        <div class="text-center mb-3">
            <img
                src="${imageUrl}"
                class="img-fluid rounded mb-2"
                style="max-height: 150px; object-fit: cover;"
                onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22150%22 height=%22150%22%3E%3Crect width=%22150%22 height=%22150%22 fill=%22%23e9ecef%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%236c757d%22 font-size=%2214%22%3E🍽️%3C/text%3E%3C/svg%3E';"
            >
            <h5>${escapeHtml(item.name)}</h5>
            <p class="text-muted small">${escapeHtml(item.description)}</p>
            <p class="fw-bold">Price: ₱${item.price.toFixed(2)}</p>
        </div>
    `);

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
            alert('Error placing order');
        }
    });
}

$(document).ready(function() {
    loadMenu();
});
</script>