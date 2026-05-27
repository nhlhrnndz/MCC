<?php
// This file is loaded by user_dashboard.php?page=fnb_my_orders
require_once __DIR__ . '/../db_connect.php';

$user_id = $_SESSION['user_id'] ?? 0;
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-success">My Food Orders</h2>
                <a href="?page=fnb_menu" class="btn btn-success">
                    <i class="fas fa-utensils me-2"></i>Order More Food
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-success">
                        <tr>
                            <th>Order ID</th>
                            <th>Items</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Order Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ordersList">
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="spinner-border text-success" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2 text-muted">Loading your orders...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #00b8a9 0%, #00998c 100%); color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-receipt me-2"></i>Order Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const userId = <?php echo $user_id; ?>;

function loadOrders() {
    $.ajax({
        url: 'fnb_api/get_user_orders.php',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                displayOrders(response.data);
            } else {
                $('#ordersList').html('<tr><td colspan="6" class="text-center text-danger">Failed to load orders</td></tr>');
            }
        },
        error: function() {
            $('#ordersList').html('<tr><td colspan="6" class="text-center text-danger">Error loading orders</td></tr>');
        }
    });
}

function displayOrders(orders) {
    const tbody = $('#ordersList');
    tbody.empty();
    
    if (!orders || orders.length === 0) {
        tbody.html('<tr><td colspan="6" class="text-center py-5"><i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i><h5>No orders found</h5><p class="text-muted">You haven\'t placed any food orders yet.</p><a href="?page=fnb_menu" class="btn btn-success mt-2">Browse Menu</a></td></tr>');
        return;
    }

    orders.forEach(order => {
        const statusClass = getStatusClass(order.status);
        const statusText = getStatusText(order.status);
        const canCancel = order.status === 'pending' || order.status === 'received';
        
        const row = `
            <tr>
                <td><span class="fw-bold">#${order.id}</span></td>
                <td>${order.item_count || 0} item(s)</td>
                <td class="fw-bold text-success">₱${parseFloat(order.total_amount).toFixed(2)}</td>
                <td><span class="badge ${statusClass} fs-6">${statusText}</span></td>
                <td>${new Date(order.created_at).toLocaleString()}</td>
                <td>
                    <button class="btn btn-info btn-sm me-1" onclick="viewOrderDetails(${order.id})">
                        <i class="fas fa-eye"></i> View
                    </button>
                    ${canCancel ? `
                        <button class="btn btn-danger btn-sm" onclick="cancelOrder(${order.id})">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    ` : ''}
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function getStatusClass(status) {
    const classes = {
        'pending': 'bg-warning text-dark',
        'received': 'bg-info',
        'preparing': 'bg-primary',
        'ready': 'bg-success',
        'delivered': 'bg-secondary',
        'cancelled': 'bg-danger'
    };
    return classes[status] || 'bg-secondary';
}

function getStatusText(status) {
    const texts = {
        'pending': 'Pending',
        'received': 'Received',
        'preparing': 'Preparing',
        'ready': 'Ready for Pickup',
        'delivered': 'Delivered',
        'cancelled': 'Cancelled'
    };
    return texts[status] || status;
}

function viewOrderDetails(orderId) {
    $.ajax({
        url: 'fnb_api/get_user_orders.php',
        type: 'GET',
        data: { order_id: orderId },
        success: function(response) {
            if (response.success && response.data) {
                const order = Array.isArray(response.data) ? response.data[0] : response.data;
                let itemsHtml = '';
                
                if (order.items && order.items.length > 0) {
                    itemsHtml = `
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${order.items.map(item => `
                                    <tr>
                                        <td>${escapeHtml(item.name)}</td>
                                        <td>${item.quantity}</td>
                                        <td>₱${parseFloat(item.unit_price).toFixed(2)}</td>
                                        <td>₱${(item.quantity * item.unit_price).toFixed(2)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                            <tfoot>
                                <tr class="table-success">
                                    <td colspan="3" class="text-end fw-bold">Total:</td>
                                    <td class="fw-bold">₱${parseFloat(order.total_amount).toFixed(2)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    `;
                }
                
                const html = `
                    <div class="mb-4">
                        <h5 class="text-success">Order #${order.id}</h5>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Status:</strong> <span class="badge ${getStatusClass(order.status)}">${getStatusText(order.status)}</span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Order Date:</strong> ${new Date(order.created_at).toLocaleString()}</p>
                            </div>
                        </div>
                        ${itemsHtml}
                    </div>
                `;
                
                $('#orderDetailsContent').html(html);
                new bootstrap.Modal(document.getElementById('orderDetailsModal')).show();
            }
        }
    });
}

function cancelOrder(orderId) {
    if (confirm('Are you sure you want to cancel this order?')) {
        $.ajax({
            url: 'fnb_api/cancel_order.php',
            type: 'POST',
            data: { order_id: orderId },
            success: function(response) {
                if (response.success) {
                    if (typeof Toast !== 'undefined') {
                        Toast.show('Order cancelled successfully', 'success');
                    } else {
                        alert('Order cancelled successfully');
                    }
                    loadOrders();
                } else {
                    if (typeof Toast !== 'undefined') {
                        Toast.show(response.message, 'error');
                    } else {
                        alert('Error: ' + response.message);
                    }
                }
            }
        });
    }
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

$(document).ready(function() {
    loadOrders();
});
</script>