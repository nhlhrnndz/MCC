<?php
// sport_and_leisure.php

// DATABASE CONNECTION
$db_connect_path = __DIR__ . '/../db_connect.php'; 
if (file_exists($db_connect_path)) {
    require_once $db_connect_path; 
} else {
    die("Error: Database connection file (db_connect.php) not found at expected path: " . $db_connect_path);
}

// Check login
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: ../login.php");
    exit;
}

// Fetch facilities
$facilities = [];
$sql = "SELECT name, rate, unit, type, availability_text, image_url, extras_text, addons_json 
        FROM sport_leisure_facilities 
        ORDER BY type DESC, name ASC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $facility_data = [
            'name' => $row['name'],
            'available' => $row['availability_text'],
            'rate' => (float)$row['rate'],
            'unit' => $row['unit'],
            'img' => $row['image_url'],
            'type' => $row['type'],
            'extra' => $row['extras_text'] ?? ($row['type'] === 'pool' ? 'Add-ons available' : 'N/A'), 
            'addons_json' => $row['addons_json'] ?? '[]'
        ];
        
        if ($row['type'] === 'pool' && !empty($row['addons_json'])) {
            $addons = json_decode($row['addons_json'], true);
            $extra_parts = array_map(fn($a) => "{$a['label']} ₱{$a['price']}", $addons);
            $facility_data['extra'] = implode(' | ', $extra_parts);
        }
        
        $facilities[] = $facility_data;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Sports & Leisure — MCC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #00b8a9;
            --primary-dark: #00998c;
            --primary-light: #e3f8f6;
        }

        .sports-container { padding: 20px; }
        
        /* Welcome Card Styles */
        .welcome-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 184, 169, 0.3);
        }

        .welcome-icon {
            font-size: 4rem;
            opacity: 0.2;
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
        }

        .dashboard-header {
            margin-bottom: 30px;
        }

        .facility-card { 
            background: white; 
            border-radius: 12px; 
            border: 1px solid #e9ecef; 
            transition: all 0.3s ease; 
            margin-bottom: 16px; 
            overflow: hidden; 
        }
        .facility-card:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 25px rgba(0,0,0,0.1); 
        }
        .facility-img { 
            height: 200px; 
            object-fit: cover; 
            width: 100%; 
        }
        .facility-body { 
            padding: 20px; 
        }
        .facility-title { 
            font-weight: 600; 
            font-size: 1.1rem; 
            margin-bottom: 12px; 
            color: var(--primary); 
        }
        .btn-proposal { 
            background: var(--primary); 
            color: white; 
            border: none; 
            padding: 10px 20px; 
            border-radius: 8px; 
            font-weight: 600; 
            width: 100%; 
            transition: all 0.3s ease;
        }
        .btn-proposal:hover { 
            background: var(--primary-dark); 
            transform: translateY(-2px); 
            box-shadow: 0 4px 12px rgba(0, 184, 169, 0.4); 
        }
        .total-display { 
            background: var(--primary-light); 
            padding: 12px 15px; 
            border-radius: 8px; 
            border-left: 4px solid var(--primary); 
            font-size: 1.1rem; 
            margin: 15px 0; 
        }
        .payment-option { 
            background: #f8fff9; 
            border: 2px solid #d4edda; 
            border-radius: 10px; 
            transition: all 0.2s; 
        }
        .payment-option:checked + label { 
            background: var(--primary-light); 
            border-color: var(--primary);
        }
        .detail-label { 
            font-weight: 600; 
            color: #495057; 
            min-width: 80px; 
            display: inline-block; 
        }
        .detail-value { 
            color: #6c757d; 
        }
        .facility-details div { 
            margin-bottom: 8px; 
        }

        /* Modal Header Styles */
        .modal-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-bottom: none;
        }

        .modal-title {
            font-weight: 600;
        }

        /* Form Check Styles */
        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .text-success {
            color: var(--primary) !important;
        }

        /* Payment Option Cards */
        .payment-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .payment-card:hover {
            border-color: var(--primary-light);
        }

        .payment-card.selected {
            border-color: var(--primary);
            background-color: var(--primary-light);
        }
    </style>
</head>
<body>
<div class="container-fluid sports-container">
    <div class="px-2">
        <!-- ========================================= -->
        <!-- PAGE HEADER                               -->
        <!-- ========================================= -->
        <div class="dashboard-header">
            <div class="welcome-card p-4 position-relative mb-4">
                <h1 class="fw-bold mb-2">Sports & Leisure</h1>
                <p class="mb-0 opacity-75">Book sports & pool facilities at Malaruhatan Country Club</p>
                <i class="fas fa-swimming-pool welcome-icon"></i>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($facilities as $f): 
                $targetModal = $f["type"] === "pool" ? "#poolModal" : "#bookingModal";
                $addons_json_safe = htmlspecialchars($f["addons_json"], ENT_QUOTES, 'UTF-8');
            ?>
                <div class="col-md-6 col-lg-3">
                    <div class="facility-card">
                        <img src="<?= htmlspecialchars($f["img"]) ?>" class="facility-img" alt="<?= htmlspecialchars($f["name"]) ?>">
                        <div class="facility-body">
                            <div class="facility-title"><?= htmlspecialchars($f["name"]) ?></div>
                            <div class="facility-details">
                                <div><span class="detail-label">Rate</span><span class="detail-value">₱<?= $f["rate"] ?><?= $f["unit"] == "head" ? '/head' : '/hour' ?></span></div>
                                <div><span class="detail-label">Available</span><span class="detail-value"><?= $f["available"] ?></span></div>
                                <div><span class="detail-label">Extras</span><span class="detail-value"><?= $f["extra"] ?></span></div>
                            </div>
                            <button class="btn-proposal"
                                data-bs-toggle="modal"
                                data-bs-target="<?= $targetModal ?>"
                                data-name="<?= htmlspecialchars($f["name"]) ?>"
                                data-rate="<?= $f["rate"] ?>"
                                data-extra="<?= htmlspecialchars($f["extra"]) ?>"
                                data-addons-json="<?= $addons_json_safe ?>"
                                data-available="<?= htmlspecialchars($f["available"]) ?>">
                                <i class="fas fa-calendar-plus me-1"></i> Book Now
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ==================== SPORTS MODAL ==================== -->
<div class="modal fade" id="bookingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="bookingModalLabel"></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p id="modalDetails" class="text-muted mb-4"></p>
                <form id="bookingForm" method="POST" action="api_reservation/save_booking.php">
                    <input type="hidden" name="facility_name" id="sports_name">
                    <input type="hidden" name="facility_type" value="sports">
                    <input type="hidden" name="total_amount" id="sports_total">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Date</label>
                        <input type="date" name="booking_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Start Time</label>
                        <input type="time" name="booking_time" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Duration (hours)</label>
                        <input type="number" id="hours" name="hours" class="form-control" min="1" value="1" required>
                    </div>

                    <div class="total-display">
                        <p id="total">Total: <b>₱0</b></p>
                    </div>

                    <!-- PAYMENT OPTION (SPORTS) -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-success mb-3">Payment Option</label>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="payment-card text-center" onclick="document.getElementById('sports_full').click()">
                                    <input class="form-check-input" type="radio" name="payment_type" value="full" id="sports_full" checked style="display: none;">
                                    <label class="form-check-label fw-bold cursor-pointer">
                                        Full Payment<br>
                                        <span class="text-success">₱<span id="sports_fullAmount">0.00</span></span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="payment-card text-center" onclick="document.getElementById('sports_deposit').click()">
                                    <input class="form-check-input" type="radio" name="payment_type" value="deposit" id="sports_deposit" style="display: none;">
                                    <label class="form-check-label fw-bold cursor-pointer">
                                        20% Deposit<br>
                                        <span class="text-warning">₱<span id="sports_depositAmount">0.00</span></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-proposal w-100">
                        <i class="fas fa-credit-card me-2"></i>Confirm Booking
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ==================== POOL MODAL ==================== -->
<div class="modal fade" id="poolModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="poolModalLabel"></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p id="poolDetails" class="text-muted mb-4"></p>
                <form id="poolForm" method="POST" action="api_reservation/save_booking.php">
                    <input type="hidden" name="facility_name" id="pool_name">
                    <input type="hidden" name="facility_type" value="pool">
                    <input type="hidden" name="total_amount" id="pool_total">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Date</label>
                        <input type="date" name="booking_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Time (8:00 AM - 8:00 PM)</label>
                        <input type="time" name="booking_time" class="form-control" id="pool_time" min="08:00" max="20:00" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Number of Guests</label>
                        <input type="number" id="guestCount" name="guest_count" class="form-control" min="1" value="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Add-ons</label>
                        <div id="addons" class="ps-2"></div>
                    </div>

                    <div class="total-display">
                        <p id="poolTotal">Total: <b>₱0</b></p>
                    </div>

                    <!-- PAYMENT OPTION (POOL) -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-success mb-3">Payment Option</label>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="payment-card text-center" onclick="document.getElementById('pool_full').click()">
                                    <input class="form-check-input" type="radio" name="payment_type" value="full" id="pool_full" checked style="display: none;">
                                    <label class="form-check-label fw-bold cursor-pointer">
                                        Full Payment<br>
                                        <span class="text-success">₱<span id="pool_fullAmount">0.00</span></span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="payment-card text-center" onclick="document.getElementById('pool_deposit').click()">
                                    <input class="form-check-input" type="radio" name="payment_type" value="deposit" id="pool_deposit" style="display: none;">
                                    <label class="form-check-label fw-bold cursor-pointer">
                                        20% Deposit<br>
                                        <span class="text-warning">₱<span id="pool_depositAmount">0.00</span></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-proposal w-100">
                        <i class="fas fa-credit-card me-2"></i>Confirm Booking
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ==================== SPORTS LOGIC ====================
const bookingModal = document.getElementById('bookingModal');
bookingModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const name = button.getAttribute('data-name');
    const rate = parseFloat(button.getAttribute('data-rate')) || 0;

    document.getElementById('bookingModalLabel').textContent = `${name} Booking`;
    document.getElementById('modalDetails').textContent = `₱${rate}/hour`;

    const hoursInput = document.getElementById('hours');
    hoursInput.value = 1;
    hoursInput.dataset.rate = rate;

    const total = rate * 1;
    document.getElementById('total').innerHTML = `Total: <b>₱${total}</b>`;
    document.getElementById('sports_total').value = total;
    document.getElementById('sports_name').value = name;

    // Update payment options
    updateSportsPaymentOptions();
});

document.getElementById('hours').addEventListener('input', function () {
    const rate = parseFloat(this.dataset.rate) || 0;
    const hours = Math.max(1, parseInt(this.value, 10) || 1);
    const total = rate * hours;
    document.getElementById('total').innerHTML = `Total: <b>₱${total}</b>`;
    document.getElementById('sports_total').value = total;
    updateSportsPaymentOptions();
});

function updateSportsPaymentOptions() {
    const total = parseFloat(document.getElementById('sports_total').value) || 0;
    document.getElementById('sports_fullAmount').textContent = total.toFixed(2);
    document.getElementById('sports_depositAmount').textContent = (total * 0.2).toFixed(2);
}

// ==================== POOL LOGIC ====================
const poolModal = document.getElementById('poolModal');
let poolRate = 0;
let currentPoolName = '';

poolModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    currentPoolName = button.getAttribute('data-name');
    poolRate = parseFloat(button.getAttribute('data-rate')) || 0;
    const addonsJsonString = button.getAttribute('data-addons-json') || '[]';

    let addons = [];
    try { addons = JSON.parse(addonsJsonString); } catch (e) {}

    document.getElementById('poolModalLabel').textContent = `${currentPoolName} Booking`;
    document.getElementById('poolDetails').textContent = `₱${poolRate} per head`;
    document.getElementById('pool_name').value = currentPoolName;

    const addonsContainer = document.getElementById('addons');
    addonsContainer.innerHTML = '';

    addons.forEach((a, idx) => {
        const id = `addon_${idx}`;
        const wrapper = document.createElement('div');
        wrapper.className = 'form-check mb-2';

        const input = document.createElement('input');
        input.className = 'form-check-input addon-check';
        input.type = 'checkbox';
        input.name = 'addons[]';
        input.id = id;
        input.value = `${a.label} - ₱${a.price}`;
        input.dataset.price = a.price;

        const label = document.createElement('label');
        label.className = 'form-check-label';
        label.htmlFor = id;
        label.innerText = `${a.label} (₱${a.price})`;

        wrapper.appendChild(input);
        wrapper.appendChild(label);
        addonsContainer.appendChild(wrapper);
    });

    document.getElementById('guestCount').value = 1;
    calculatePoolTotal();
    updatePoolPaymentOptions();
});

function calculatePoolTotal() {
    const guests = Math.max(1, parseInt(document.getElementById('guestCount').value, 10) || 1);
    let total = guests * poolRate;

    document.querySelectorAll('.addon-check:checked').forEach(cb => {
        total += parseFloat(cb.dataset.price) || 0;
    });

    document.getElementById('poolTotal').innerHTML = `Total: <b>₱${total}</b>`;
    document.getElementById('pool_total').value = total;
    updatePoolPaymentOptions();
}

document.getElementById('guestCount').addEventListener('input', calculatePoolTotal);
document.addEventListener('change', e => {
    if (e.target.classList.contains('addon-check')) calculatePoolTotal();
});

function updatePoolPaymentOptions() {
    const total = parseFloat(document.getElementById('pool_total').value) || 0;
    document.getElementById('pool_fullAmount').textContent = total.toFixed(2);
    document.getElementById('pool_depositAmount').textContent = (total * 0.2).toFixed(2);
}

// Payment card selection
document.addEventListener('DOMContentLoaded', function() {
    // Add click handlers for payment cards
    document.querySelectorAll('.payment-card').forEach(card => {
        card.addEventListener('click', function() {
            // Remove selected class from all cards in the same modal
            const modal = this.closest('.modal-body');
            modal.querySelectorAll('.payment-card').forEach(c => c.classList.remove('selected'));
            // Add selected class to clicked card
            this.classList.add('selected');
        });
    });
});

// Extra validation for Private Pool minimum guests
document.getElementById('poolForm').addEventListener('submit', function (e) {
    const timeVal = document.getElementById('pool_time').value;
    if (timeVal && (timeVal < '08:00' || timeVal > '20:00')) {
        e.preventDefault();
        alert('Pool bookings are only allowed from 08:00 to 20:00.');
    }
    if (currentPoolName.includes('Private Pool') && (parseInt(document.getElementById('guestCount').value) < 10)) {
        e.preventDefault();
        alert(currentPoolName + ' requires a minimum of 10 guests.');
    }
});
</script>
</body>
</html>