<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MCC | Facilities</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Lora:wght@600&display=swap" rel="stylesheet">

  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #ffffff;
      margin: 0;
      padding: 0;
      overflow-x: hidden;
    }

    /* 🌿 Navbar (same as main.php) */
    .navbar {
      background-color: #d4f7ef;
      backdrop-filter: blur(8px);
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
      padding: 0.8rem 1.5rem;
      transition: background-color 0.3s ease;
    }

    .navbar.scrolled {
      background-color: #d4f7ef;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .navbar-brand {
      color: #00925e !important;
      font-size: 1.4rem;
      font-weight: 700;
      letter-spacing: 0.5px;
    }

    .navbar-brand:hover {
      color: #0aa24a;
    }

    .nav-link {
      color: #333 !important;
      font-weight: 500;
      margin: 0 8px;
      transition: color 0.3s ease;
    }

    .nav-link:hover,
    .nav-link.active {
      color: #0aa24a !important;
    }

    .navbar-nav {
      gap: 25px;
    }

    .btn-login {
      padding: 10px 20px;
      border-radius: 50px;
      background-color: #00d084; /* brighter green */
      color: #fff;
      font-weight: 600;
      text-decoration: none;
      box-shadow: 0 4px 15px rgba(0, 208, 132, 0.4);
      transition: all 0.3s ease;
    }

    .btn-login:hover {
      background-color: #00b671;
      box-shadow: 0 6px 20px rgba(0, 182, 113, 0.5);
    }

    /* 🌿 PROFESSIONAL BANNER (from contact.php) */
    .banner {
      position: relative;
      width: 100%;
      height: 380px;
      overflow: hidden;
      margin-top: 75px; /* Added to account for fixed navbar */
    }

    .banner-image {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transform: scale(1.05);
      filter: brightness(0.55);
      transition: transform 1.3s ease;
    }

    .banner:hover .banner-image {
      transform: scale(1.1);
    }

    .banner-text {
      position: absolute;
      top: 72%;
      left: 50%;
      transform: translate(-50%, -50%);
      text-align: center;
      color: #fff;
      animation: fadeIn 1.2s ease forwards;
    }

    .banner-text h1 {
      font-size: 64px;
      font-family: 'Lora', serif;
      font-weight: 700;
      letter-spacing: 2px;
      text-shadow: 3px 3px 18px rgba(0,0,0,0.85);
    }

    .banner-text i {
      font-size: 32px;
      color: #00dba3;
    }

    .divider {
      width: 100px;
      height: 4px;
      background: linear-gradient(90deg, #00dba3, #01bc89, #00a877);
      border-radius: 20px;
      margin-left: 0;
      box-shadow: 0 0 10px rgba(1, 188, 137, 0.6);
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translate(-50%, -40%); }
      to { opacity: 1; transform: translate(-50%, -50%); }
    }

    /* 🏡 Facilities Section */
    .facilities-wrapper {
      background-color: #ffffff;
      padding: 80px 0;
    }

    .facilities-wrapper .container {
      max-width: 1400px;
    }

    .facility-section {
      margin-bottom: 100px;
      transition: all 0.3s ease;
    }

    .facility-section img {
      width: 100%;
      height: 400px;
      object-fit: cover;
      border-radius: 14px;
      box-shadow: 0 3px 15px rgba(0,0,0,0.1);
      transition: transform 0.4s ease, box-shadow 0.4s ease;
    }

    .facility-section img:hover {
      transform: scale(1.03);
      box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }

    .facility-info {
      padding: 30px;
    }

    .facility-info h2 {
      color: #007f4f;
      font-weight: 600;
      margin-bottom: 15px;
    }

    .facility-info p {
      color: #444;
      font-size: 1.05rem;
      line-height: 1.7;
    }

    /* 🪴 Section Divider Line */
    .facility-section:not(:last-child)::after {
      content: "";
      display: block;
      width: 60%;
      height: 1px;
      background: #e0f4e8;
      margin: 60px auto 0;
    }

    /* 🌿 Footer */
    footer {
      text-align: center;
      padding: 40px;
      color: #0b5138;
      background-color: #f3fcf8;
      margin-top: 40px;
    }

    /* 📱 Responsive */
    @media (max-width: 992px) {
      .banner {
        height: 300px;
      }

      .banner-text h1 {
        font-size: 48px;
      }

      .facility-section {
        margin-bottom: 60px;
      }

      .facility-section img {
        height: 320px;
      }

      .facility-info {
        padding: 20px 10px;
        text-align: center;
      }
    }

    @media (max-width: 768px) {
      .banner-text h1 {
        font-size: 36px;
      }
    }
  </style>
</head>
<body>

<!-- 🌿 Navbar -->
<nav class="navbar navbar-expand-lg navbar-light fixed-top">
  <div class="container-fluid px-4">
    <a class="navbar-brand fw-bold" href="main.php">MCC</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="main.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="about_us.php">About Us</a></li>
        <li class="nav-item"><a class="nav-link active" href="facilities.php">Facilities</a></li>
        <li class="nav-item"><a class="nav-link" href="contact.php">Contact Us</a></li>
      </ul>
    </div>

    <div class="ms-auto">
      <a href="user_login.php" class="btn-login">Log In</a>
    </div>
  </div>
</nav>
<!-- 🌿 PROFESSIONAL BANNER (from contact.php) -->
<div class="banner">
  <img src="assets\mainbanner\water Banner Landscape (1).png" alt="Banner Image" class="banner-image">
  <div class="banner-text">
    <h1>Our Facilities</h1>
  </div>
</div>

<!-- 🏢 Facilities Section -->
<div class="facilities-wrapper">
  <div class="container">

    <!-- Event Venue -->
    <div class="facility-section">
      <div class="row align-items-center">
        <div class="col-lg-6 mb-4 mb-lg-0">
          <img src="assets/event venue/table3.jpg" alt="Event Venue">
        </div>
        <div class="col-lg-6 facility-info">
          <h2>Event Venue</h2>
          <p>Our event venue offers a spacious and elegant setup ideal for weddings, birthdays, corporate events, and other celebrations. With its open design and scenic atmosphere, it's perfect for any occasion.</p>
        </div>
      </div>
    </div>

    <!-- Dining Hall -->
    <div class="facility-section">
      <div class="row align-items-center flex-lg-row-reverse">
        <div class="col-lg-6 mb-4 mb-lg-0">
          <img src="assets/event venue/diningexp3.jpg" alt="Dining Hall">
        </div>
        <div class="col-lg-6 facility-info">
          <h2>Dining Hall</h2>
          <p>Enjoy delicious meals in our dining hall, designed with comfort and elegance in mind. Perfect for family gatherings, casual dining, or private celebrations.</p>
        </div>
      </div>
    </div>

    <!-- Rooms -->
    <div class="facility-section">
      <div class="row align-items-center">
        <div class="col-lg-6 mb-4 mb-lg-0">
          <img src="assets/rooms/rooms.jpg" alt="Rooms">
        </div>
        <div class="col-lg-6 facility-info">
          <h2>Rooms</h2>
          <p>Relax and recharge in our well-furnished rooms that cater to families, couples, and solo travelers. Each room offers comfort, tranquility, and modern amenities.</p>
        </div>
      </div>
    </div>

    <!-- Tennis Court -->
    <div class="facility-section">
      <div class="row align-items-center flex-lg-row-reverse">
        <div class="col-lg-6 mb-4 mb-lg-0">
          <img src="assets/sports/tennis2.jpg" alt="Tennis Court">
        </div>
        <div class="col-lg-6 facility-info">
          <h2>Tennis Court</h2>
          <p>Challenge your friends or practice your serve in our modern tennis courts, equipped with night lighting for matches any time of the day.</p>
        </div>
      </div>
    </div>

    <!-- Swimming Pool -->
    <div class="facility-section">
      <div class="row align-items-center">
        <div class="col-lg-6 mb-4 mb-lg-0">
          <img src="assets\pools.jpg" alt="Swimming Pool">
        </div>
        <div class="col-lg-6 facility-info">
          <h2>Swimming Pool</h2>
          <p>Our pools offer both fun and relaxation. Dive into our refreshing public pool or enjoy privacy in our exclusive private pool areas.</p>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- 🌿 Footer -->
<!-- 🌿 MODERN FOOTER -->
<footer>
  <div class="footer-container">
    <div class="footer-section">
      <h6><i class="fa-solid fa-location-dot"></i> Address</h6>
      <p>Malaruhatan, Lian, Batangas</p>
    </div>

    <div class="footer-section">
      <h6><i class="fa-solid fa-phone"></i> Contact</h6>
      <p>+63 995-836-8673</p>
      <p><i class="fa-regular fa-envelope"></i>mccresort@yahoo.com</p>
    </div>

    <div class="footer-section">
      <h6>Follow Us</h6>
      <div class="footer-social-inline">
        <a href="https://www.facebook.com/malaruhatan/?locale=tl_PH"><i class="fa-brands fa-facebook-f"></i></a>
      </div>
    </div>
  </div>

  <hr class="footer-line">
  <p class="footer-copy">Copyright © 2025 Malaruhatan Country Club. All Rights Reserved.</p>

  <style>
    footer {
      background: #d4f7ef;
      padding: 40px 20px 20px;
      color: #2b4f38;
      font-family: 'Poppins', sans-serif;
    }

    .footer-container {
      max-width: 1100px;
      margin: auto;
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 20px;
      text-align: left;
    }

    .footer-section h6 {
      font-weight: 700;
      margin-bottom: 8px;
      font-size: 16px;
    }

    .footer-section p {
      margin: 4px 0;
      font-size: 14px;
    }

    .footer-social-inline a {
      display: inline-block;
      margin-right: 12px;
      font-size: 18px;
      color: #2b4f38;
      transition: 0.3s;
    }

    .footer-social-inline a:hover {
      color: #01bc89;
    }

    .footer-line {
      margin: 20px 0 10px;
      border: none;
      border-top: 1px solid #c8f7d8;
    }

    .footer-copy {
      font-size: 13px;
      opacity: 0.7;
      text-align: center;
      margin-bottom: 0;
    }

    @media screen and (max-width: 768px) {
      .footer-container {
        flex-direction: column;
        text-align: center;
      }
      .footer-section { margin-bottom: 15px; }
    }
  </style>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Smooth navbar background transition when scrolling
  window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    navbar.classList.toggle('scrolled', window.scrollY > 50);
  });
</script>
</body>
</html>