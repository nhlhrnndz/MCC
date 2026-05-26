<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Reservation | MCC Dashboard</title>
  <link href="../bootstrap5/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #2c3e50;
      --secondary: #3498db;
      --accent: #2ecc71;
      --light: #ecf0f1;
      --dark: #2c3e50;
    }
    
    body {
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      min-height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    
    .reservation-container {
      max-width: 800px;
      width: 100%;
    }
    
    .reservation-hero {
      background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
      color: white;
      border-radius: 20px;
      padding: 50px 30px;
      margin-bottom: 30px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      position: relative;
      overflow: hidden;
      text-align: center;
    }
    
    .reservation-hero::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
      background-size: cover;
    }
    
    .hero-content {
      position: relative;
      z-index: 2;
    }
    
    .reservation-card {
      border: none;
      border-radius: 20px;
      box-shadow: 0 15px 35px rgba(0,0,0,0.1);
      overflow: hidden;
      transition: transform 0.3s ease;
      background: white;
      text-align: center;
      padding: 40px 30px;
    }
    
    .reservation-card:hover {
      transform: translateY(-10px);
    }
    
    .card-icon {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 25px;
      font-size: 40px;
      background: linear-gradient(135deg, #3498db, #2980b9);
      color: white;
      box-shadow: 0 8px 20px rgba(52, 152, 219, 0.3);
    }
    
    .btn-primary-custom {
      background: linear-gradient(135deg, var(--secondary), #2980b9);
      border: none;
      padding: 15px 40px;
      border-radius: 50px;
      font-weight: 600;
      font-size: 18px;
      transition: all 0.3s ease;
      box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
      margin-top: 20px;
    }
    
    .btn-primary-custom:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(52, 152, 219, 0.6);
    }
    
    .feature-list {
      list-style: none;
      padding: 0;
      margin: 25px 0;
    }
    
    .feature-list li {
      padding: 10px 0;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: left;
      max-width: 300px;
      margin: 0 auto;
    }
    
    .feature-list i {
      color: var(--accent);
      margin-right: 12px;
      font-size: 18px;
      min-width: 20px;
    }
    
    .step-indicator {
      display: flex;
      justify-content: center;
      margin: 30px 0;
    }
    
    .step {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin: 0 20px;
    }
    
    .step-circle {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background-color: #e0e0e0;
      color: #9e9e9e;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      margin-bottom: 10px;
      font-size: 20px;
    }
    
    .step.active .step-circle {
      background-color: var(--secondary);
      color: white;
    }
    
    .step-label {
      font-size: 16px;
      color: #757575;
      font-weight: 500;
    }
    
    .step.active .step-label {
      color: var(--secondary);
      font-weight: 600;
    }
    
    .room-types {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 15px;
      margin: 25px 0;
    }
    
    .room-type-badge {
      background: #e3f2fd;
      color: #1976d2;
      padding: 8px 16px;
      border-radius: 20px;
      font-size: 14px;
      font-weight: 500;
    }
    
    .help-section {
      text-align: center;
      margin-top: 30px;
      padding-top: 25px;
      border-top: 1px solid #e0e0e0;
    }
  </style>
</head>
<body>
  <div class="reservation-container">
    <!-- Hero Section -->
    <div class="reservation-hero">
      <div class="hero-content">
        <h1 class="display-5 fw-bold mb-3">Book Your Perfect Room</h1>
        <p class="lead mb-4">Choose from our variety of comfortable accommodations for your stay</p>
        
        <!-- Step Indicator -->
        <div class="step-indicator">
          <div class="step active">
          </div>
        </div>
      </div>
    </div>
    
    <!-- Room Accommodation Card -->
    <div class="reservation-card">
      <div class="card-icon">
        <i class="fas fa-bed"></i>
      </div>
      <h2 class="mb-3">Room Accommodation</h2>
      <p class="text-muted mb-4 fs-5">
        Discover our comfortable rooms perfect for individuals, couples, families, and groups. 
        Enjoy premium amenities and exceptional service during your stay.
      </p>
      
      <!-- Room Types -->
      <div class="room-types">
        <span class="room-type-badge">Deluxe Rooms</span>
        <span class="room-type-badge">Family Rooms</span>
        <span class="room-type-badge">Premier Suites</span>
        <span class="room-type-badge">Dormitory Style</span>
        <span class="room-type-badge">Grand Premier</span>
      </div>
      
      <!-- Features -->
      <ul class="feature-list">
        <li><i class="fas fa-check"></i> Various room sizes for every need</li>
        <li><i class="fas fa-check"></i> Premium amenities included</li>
        <li><i class="fas fa-check"></i> Flexible booking options</li>
        <li><i class="fas fa-check"></i> 24/7 customer support</li>
        <li><i class="fas fa-check"></i> Best price guarantee</li>
      </ul>
      
      <!-- Action Button -->
<a href="reservation_steps/room_selection.php" class="btn btn-primary-custom">
    Start Room Reservation <i class="fas fa-arrow-right ms-2"></i>
</a>
      <!-- Help Section -->
      <div class="help-section">
        <p class="text-muted mb-3">Need assistance with your reservation?</p>
        <div class="d-flex justify-content-center gap-3">
          <a href="../contact.php" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-phone me-2"></i> Contact Us
          </a>
          <a href="../facilities.php" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-info-circle me-2"></i> View Facilities
          </a>
        </div>
      </div>
    </div>
  </div>

  <script src="../bootstrap5/js/bootstrap.bundle.min.js"></script>
</body>
</html>