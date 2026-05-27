<?php
session_start();
require_once '../../db_connect.php';

// Check if user is logged in and has fnb_manager role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'fnb_manager') {
    header('Location: ../../index.php');
    exit();
}

$page_title = "F&B Management - MCC";
$admin_name = $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'F&B Manager';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

    <style>
        :root {
            --primary: #00b8a9;
            --primary-dark: #00998c;
            --light-bg: #e3f8f6;
        }

        body {
            background: linear-gradient(to bottom, #f5f7fa, var(--light-bg));
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Sidebar Styles - Matching Admin Dashboard */
        .sidebar {
            width: 260px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: linear-gradient(180deg, #00b8a9 0%, #00998c 100%);
            color: white;
            padding: 30px 20px;
            box-shadow: 4px 0 20px rgba(0,0,0,0.15);
            z-index: 1000;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }

        .sidebar-header h5 {
            color: white;
            font-weight: 700;
            font-size: 1.6rem;
            margin: 0;
        }

        .sidebar-header p {
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
            margin: 8px 0 0;
        }

        .nav-link {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            border-radius: 12px;
            margin: 6px 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            cursor: pointer;
        }

        .nav-link i {
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.2);
            transform: translateX(8px);
            color: white;
        }

        .nav-link.active {
            background: rgba(255,255,255,0.25);
            color: white;
            font-weight: 600;
            transform: translateX(8px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 5px;
            height: 60%;
            background: white;
            border-radius: 0 3px 3px 0;
        }

        .logout-btn {
            margin-top: auto;
            padding: 20px 10px 10px;
            border-top: 1px solid rgba(255,255,255,0.2);
        }

        .logout-btn a {
            color: #ff6b6b !important;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 20px;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
        }

        .logout-btn a:hover {
            background: rgba(255,107,107,0.2);
            color: white !important;
            transform: translateX(8px);
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 20px;
            min-height: 100vh;
        }

        /* Beautiful Header Banner */
        .dashboard-banner {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 18px;
            padding: 2.5rem 2.8rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 184, 169, 0.3);
        }

        .dashboard-banner h1 {
            font-size: 2.4rem;
            font-weight: 700;
            margin: 0;
            line-height: 1.2;
        }

        .dashboard-banner p {
            opacity: 0.92;
            font-size: 1.15rem;
            margin: 0.6rem 0 0;
        }

        .banner-icon {
            font-size: 7rem;
            opacity: 0.12;
            position: absolute;
            right: 20px;
            bottom: -25px;
            pointer-events: none;
        }

        /* Card Styles */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-3px);
        }

        .btn-primary, .btn-success {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover, .btn-success:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .text-primary, .text-success {
            color: var(--primary) !important;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending { background-color: #ffc107; color: #000; }
        .status-received { background-color: #17a2b8; color: #fff; }
        .status-preparing { background-color: #fd7e14; color: #fff; }
        .status-ready { background-color: #28a745; color: #fff; }
        .status-delivered { background-color: #6c757d; color: #fff; }
        .status-cancelled { background-color: #dc3545; color: #fff; }

        @media (max-width: 768px) {
            .sidebar {
                width: 80px;
                padding: 30px 10px;
            }
            .sidebar-header h5, .sidebar-header p, .nav-link span {
                display: none;
            }
            .nav-link {
                justify-content: center;
                padding: 16px;
                margin: 8px 5px;
            }
            .nav-link i {
                font-size: 1.4rem;
                margin: 0;
            }
            .logout-btn a span { display: none; }
            .logout-btn a { justify-content: center; }
            .main-content {
                margin-left: 80px;
            }
            .dashboard-banner {
                padding: 2rem;
                text-align: center;
            }
            .dashboard-banner h1 { font-size: 2rem; }
            .banner-icon { display: none; }
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h5>F&B Manager</h5>
                <p>Food & Beverage Control</p>
            </div>

            <nav class="flex-column">
                <a class="nav-link active" onclick="showSection('menu')" style="cursor: pointer;">
                    <i class="fas fa-utensils"></i>
                    <span>Menu Management</span>
                </a>

                <a class="nav-link" onclick="showSection('orders')" style="cursor: pointer;">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Order Management</span>
                </a>


            </nav>

            <div class="logout-btn">
                <a href="../../logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content flex-grow-1">
            <div class="container-fluid">

                <!-- Beautiful Banner -->
                <div class="dashboard-banner position-relative">
                    <div>
                        <h1>F&B Management Dashboard</h1>
                        <p>Welcome back, <?php echo htmlspecialchars($admin_name); ?> • <?php echo date('l, F j, Y'); ?></p>
                    </div>
                    <i class="fas fa-utensils banner-icon"></i>
                </div>

                <!-- Menu Management Section -->
                <div id="menuSection">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="text-primary">Menu Items</h3>
                        <button class="btn btn-primary" onclick="showAddMenuModal()">
                            <i class="fas fa-plus me-2"></i>Add New Item
                        </button>
                    </div>
                    <div id="menuItemsList" class="row">
                        <div class="col-12 text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted">Loading menu items...</p>
                        </div>
                    </div>
                </div>

                <!-- Orders Management Section -->
                <div id="ordersSection" style="display: none;">
                    <h3 class="text-primary mb-4">All Orders</h3>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table" style="background-color: var(--primary); color: white;">
                                <tr>
                                    <th>Order ID</th>
                                    <th>User ID</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="ordersList">
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-2 text-muted">Loading orders...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Menu Modal -->
    <div class="modal fade" id="menuModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white;">
                    <h5 class="modal-title" id="menuModalTitle">
                        <i class="fas fa-utensils me-2"></i>Add Menu Item
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="menuForm">
                        <input type="hidden" id="menuId" name="id">
                        <div class="mb-3">
                            <label for="name" class="form-label fw-bold">Item Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label fw-bold">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="price" class="form-label fw-bold">Price (RM)</label>
                            <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label fw-bold">Category</label>
                            <select class="form-control" id="category" name="category" required>
                                <option value="">Select Category</option>
                                <option value="Appetizer">Appetizer</option>
                                <option value="Main Course">Main Course</option>
                                <option value="Dessert">Dessert</option>
                                <option value="Beverage">Beverage</option>
                                <option value="Snack">Snack</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="image" class="form-label fw-bold">Image URL</label>
                            <input type="text" class="form-control" id="image" name="image" placeholder="https://example.com/image.jpg">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_available" name="is_available" value="1" checked>
                            <label class="form-check-label" for="is_available">Available</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveMenuItem()">Save Item</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white;">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let currentSection = 'menu';

        // Update active state on sidebar links
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', function() {
                document.querySelectorAll('.sidebar .nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });

        function showSection(section) {
            currentSection = section;
            if (section === 'menu') {
                document.getElementById('menuSection').style.display = 'block';
                document.getElementById('ordersSection').style.display = 'none';
                loadMenuItems();
            } else {
                document.getElementById('menuSection').style.display = 'none';
                document.getElementById('ordersSection').style.display = 'block';
                loadOrders();
            }
        }

        function loadMenuItems() {
            $('#menuItemsList').html('<div class="col-12 text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Loading menu items...</p></div>');
            
            $.ajax({
                url: '../api/fnb/get_menu_items.php',
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        displayMenuItems(response.data);
                    } else {
                        $('#menuItemsList').html('<div class="col-12 text-center text-danger py-5">Failed to load menu: ' + (response.message || 'Unknown error') + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    $('#menuItemsList').html('<div class="col-12 text-center text-danger py-5">Error loading menu. Please refresh and try again.</div>');
                }
            });
        }

        function displayMenuItems(items) {
            const container = document.getElementById('menuItemsList');
            if (!items || items.length === 0) {
                container.innerHTML = '<div class="col-12 text-center py-5"><i class="fas fa-utensils fa-3x text-muted mb-3"></i><h5>No menu items found</h5><p class="text-muted">Click "Add New Item" to create your first menu item.</p></div>';
                return;
            }

            container.innerHTML = items.map(item => `
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        ${item.image ? `<img src="${item.image}" class="card-img-top" style="height: 200px; object-fit: cover;" alt="${item.name}">` : '<div class="card-img-top bg-light text-center py-5"><i class="fas fa-utensils fa-3x text-muted"></i></div>'}
                        <div class="card-body">
                            <h5 class="card-title text-primary fw-bold">${escapeHtml(item.name)}</h5>
                            <p class="card-text text-muted small">${escapeHtml(item.description.substring(0, 100))}${item.description.length > 100 ? '...' : ''}</p>
                            <p class="card-text"><strong>Price:</strong> <span class="text-primary fw-bold">RM ${parseFloat(item.price).toFixed(2)}</span></p>
                            <p class="card-text"><strong>Category:</strong> ${escapeHtml(item.category)}</p>
                            <p class="card-text">
                                <span class="badge ${item.is_available == 1 ? 'bg-success' : 'bg-danger'}">
                                    ${item.is_available == 1 ? 'Available' : 'Unavailable'}
                                </span>
                            </p>
                            <div class="d-flex gap-2">
                                <button class="btn btn-warning btn-sm flex-grow-1" onclick="editMenuItem(${item.id})">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-danger btn-sm flex-grow-1" onclick="deleteMenuItem(${item.id})">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
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

        function showAddMenuModal() {
            document.getElementById('menuModalTitle').innerHTML = '<i class="fas fa-plus me-2"></i>Add Menu Item';
            document.getElementById('menuForm').reset();
            document.getElementById('menuId').value = '';
            document.getElementById('is_available').checked = true;
            new bootstrap.Modal(document.getElementById('menuModal')).show();
        }

        function editMenuItem(id) {
            $.ajax({
                url: '../api/fnb/get_menu_items.php?id=' + id,
                type: 'GET',
                success: function(response) {
                    if (response.success && response.data) {
                        const item = response.data;
                        document.getElementById('menuModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Menu Item';
                        document.getElementById('menuId').value = item.id;
                        document.getElementById('name').value = item.name;
                        document.getElementById('description').value = item.description;
                        document.getElementById('price').value = item.price;
                        document.getElementById('category').value = item.category;
                        document.getElementById('image').value = item.image || '';
                        document.getElementById('is_available').checked = item.is_available == 1;
                        new bootstrap.Modal(document.getElementById('menuModal')).show();
                    }
                }
            });
        }

        function saveMenuItem() {
            const formData = new FormData(document.getElementById('menuForm'));
            const id = document.getElementById('menuId').value;
            const url = id ? '../api/fnb/update_menu_item.php' : '../api/fnb/add_menu_item.php';
            
            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        bootstrap.Modal.getInstance(document.getElementById('menuModal')).hide();
                        loadMenuItems();
                        alert(response.message);
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error saving menu item');
                }
            });
        }

        function deleteMenuItem(id) {
            if (confirm('Are you sure you want to delete this item?')) {
                $.ajax({
                    url: '../api/fnb/delete_menu_item.php',
                    type: 'POST',
                    data: { id: id },
                    success: function(response) {
                        if (response.success) {
                            loadMenuItems();
                            alert(response.message);
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }
                });
            }
        }

        function loadOrders() {
            $('#ordersList').html('<tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Loading orders...</p></td></tr>');
            
            $.ajax({
                url: '../api/fnb/get_all_orders.php',
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        displayOrders(response.data);
                    } else {
                        $('#ordersList').html('<tr><td colspan="6" class="text-center text-danger py-5">Failed to load orders</td></tr>');
                    }
                }
            });
        }

        function displayOrders(orders) {
            const tbody = document.getElementById('ordersList');
            if (!orders || orders.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5"><i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i><h5>No orders found</h5><p class="text-muted">Orders will appear here when customers place them.</p></td></tr>';
                return;
            }

            tbody.innerHTML = orders.map(order => `
                <tr>
                    <td><span class="fw-bold">#${order.id}</span></td>
                    <td>${order.user_id}</td>
                    <td class="fw-bold text-primary">RM ${parseFloat(order.total_amount).toFixed(2)}</td>
                    <td>
                        <select class="form-select form-select-sm status-select" data-order-id="${order.id}" onchange="updateOrderStatus(${order.id}, this.value)" style="width: auto;">
                            <option value="pending" ${order.status === 'pending' ? 'selected' : ''}>Pending</option>
                            <option value="received" ${order.status === 'received' ? 'selected' : ''}>Received</option>
                            <option value="preparing" ${order.status === 'preparing' ? 'selected' : ''}>Preparing</option>
                            <option value="ready" ${order.status === 'ready' ? 'selected' : ''}>Ready</option>
                            <option value="delivered" ${order.status === 'delivered' ? 'selected' : ''}>Delivered</option>
                            <option value="cancelled" ${order.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                        </select>
                    </td>
                    <td>${new Date(order.created_at).toLocaleString()}</td>
                    <td>
                        <button class="btn btn-primary btn-sm" onclick="viewOrderDetails(${order.id})">
                            <i class="fas fa-eye"></i> View
                        </button>
                     </td>
                 </tr>
            `).join('');
        }

        function updateOrderStatus(orderId, status) {
            $.ajax({
                url: '../api/fnb/update_order_status.php',
                type: 'POST',
                data: { order_id: orderId, status: status },
                success: function(response) {
                    if (response.success) {
                        alert('Order status updated successfully');
                    } else {
                        alert('Error: ' + response.message);
                        loadOrders();
                    }
                }
            });
        }

        function viewOrderDetails(orderId) {
            $.ajax({
                url: '../api/fnb/get_all_orders.php?order_id=' + orderId,
                type: 'GET',
                success: function(response) {
                    if (response.success && response.data) {
                        const order = response.data;
                        let itemsHtml = '';
                        
                        if (order.items && order.items.length > 0) {
                            itemsHtml = `
                                <table class="table table-bordered">
                                    <thead class="table" style="background-color: var(--primary); color: white;">
                                        <tr>
                                            <th>Item Name</th>
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
                                                <td>RM ${parseFloat(item.unit_price).toFixed(2)}</td>
                                                <td>RM ${(item.quantity * item.unit_price).toFixed(2)}</td>
                                            </tr>
                                        `).join('')}
                                        <tr class="table-light fw-bold">
                                            <td colspan="3" class="text-end">Total:</td>
                                            <td class="text-primary">RM ${parseFloat(order.total_amount).toFixed(2)}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            `;
                        }
                        
                        const html = `
                            <div class="mb-4">
                                <h5 class="text-primary">Order #${order.id}</h5>
                                <hr>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p><strong>User ID:</strong> ${order.user_id}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Status:</strong> <span class="badge status-${order.status}">${order.status.toUpperCase()}</span></p>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p><strong>Created At:</strong> ${new Date(order.created_at).toLocaleString()}</p>
                                    </div>
                                </div>
                                <h6 class="text-primary mt-3">Order Items</h6>
                                ${itemsHtml}
                            </div>
                        `;
                        
                        document.getElementById('orderDetailsContent').innerHTML = html;
                        new bootstrap.Modal(document.getElementById('orderDetailsModal')).show();
                    }
                }
            });
        }

        // Load initial menu items
        loadMenuItems();
    </script>
</body>
</html>