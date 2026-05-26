<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MCC | About Us</title>
  
  <!-- CDN Imports -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Lora:wght@600&display=swap" rel="stylesheet">

  <style>
    /* ----------------------------
       GLOBAL STYLES
    ----------------------------- */
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #ffffff;
      margin: 0;
      padding: 0;
      overflow-x: hidden;
    }

    img {
      max-width: 100%;
      height: auto;
      display: block;
    }

    /* ----------------------------
       NAVBAR
    ----------------------------- */
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

    /* Log In Button */
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

    @keyframes fadeIn {
      from { opacity: 0; transform: translate(-50%, -40%); }
      to { opacity: 1; transform: translate(-50%, -50%); }
    }

    /* ----------------------------
       ABOUT SECTION
    ----------------------------- */
    .about-section {
      padding: 80px 15px;
    }

    .about-section .row {
      margin-bottom: 70px;
    }

    .about-section img {
      width: 100%;
      height: 400px;
      object-fit: cover;
      border-radius: 12px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.1);
      transition: transform 0.4s ease;
    }

    .about-section img:hover {
      transform: scale(1.02);
    }

    .about-section h2 {
      color: #007f4f;
      font-weight: 600;
      margin-bottom: 15px;
    }

    .about-section p {
      color: #444;
      font-size: 1.05rem;
      line-height: 1.65;
      text-align: justify;
    }

    /* ----------------------------
       FOOTER
    ----------------------------- */
    footer {
    background: #d4f7ef; /* Lighter aquamarine */
    padding: 40px 20px 20px;
    color: #1a3a2a;
    font-family: 'Poppins', sans-serif;
    border-top: 3px solid #ffffff;
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
    color: #0b5138;
}

.footer-section p {
    margin: 4px 0;
    font-size: 14px;
    color: #2b4f38;
}

.footer-social-inline a {
    display: inline-block;
    margin-right: 12px;
    font-size: 18px;
    color: #007f4f;
    transition: 0.3s;
}

.footer-social-inline a:hover {
    color: #1a3a2a;
    transform: translateY(-2px);
}

.footer-line {
    margin: 20px 0 10px;
    border: none;
    border-top: 1px solid rgba(0, 128, 96, 0.3);
}

.footer-copy {
    font-size: 13px;
    opacity: 0.9;
    text-align: center;
    margin-bottom: 0;
    color: #0b5138;
    font-weight: 600;
}
    /* ----------------------------
       RESPONSIVE DESIGN
    ----------------------------- */
    @media (max-width: 768px) {
      .banner {
        height: 300px;
      }

      .banner-text h1 {
        font-size: 48px;
      }

      .about-section {
        padding: 60px 10px;
      }

      .about-section img {
        height: 280px;
      }

      .footer-container {
        flex-direction: column;
        text-align: center;
      }
      
      .footer-section { 
        margin-bottom: 15px; 
      }
    }

    @media (max-width: 576px) {
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
    <a class="navbar-brand fw-bold" href="index.php">MCC</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link active" href="about_us.php">About Us</a></li>
        <li class="nav-item"><a class="nav-link" href="facilities.php">Facilities</a></li>
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
    <h1>About Us</h1>
  </div>
</div>

<!-- 🏡 About Content -->
<div class="container about-section">
  <div class="row align-items-center">
    <div class="col-lg-6 mb-4 mb-lg-0">
      <img src="assets\pools.jpg" alt="MCC Pool Area">
    </div>
    <div class="col-lg-6">
      <h2>Welcome to Malaruhatan Country Club</h2>
      <p>
        Nestled in the serene beauty of Malaruhatan, our Country Club offers a perfect blend of relaxation, recreation, and celebration. From elegant event spaces to cozy accommodations and exquisite dining, MCC brings people together for memorable moments surrounded by nature and sophistication.
      </p>
    </div>
  </div>

  <div class="row align-items-center flex-lg-row-reverse">
    <div class="col-lg-6 mb-4 mb-lg-0">
      <img src="assets/event venue/catering.jpg" alt="Event Catering Services">
    </div>
    <div class="col-lg-6">
      <h2>Our Services</h2>
      <p>
        We provide a full range of event and hospitality services, from weddings and corporate gatherings to private retreats. Our dedicated team ensures every event is seamless, supported by modern amenities, catering options, and online reservations.
      </p>
    </div>
  </div>

  <div class="row align-items-center">
    <div class="col-lg-6 mb-4 mb-lg-0">
      <img src="assets/rooms/retreats.jpg" alt="Retreat Rooms and Accommodations">
    </div>
    <div class="col-lg-6">
      <h2>What Makes Us Unique</h2>
      <p>
        MCC stands out through its mix of natural beauty, refined elegance, and warm hospitality. Every detail is carefully crafted to ensure your comfort and satisfaction, making each visit and event truly unforgettable.
      </p>
    </div>
  </div>

  <div class="row align-items-center flex-lg-row-reverse">
    <div class="col-lg-6 mb-4 mb-lg-0">
      <img src="assets/MCC gate.jpg" alt="Malaruhatan Country Club Gate">
    </div>
    <div class="col-lg-6">
      <h2>Come and Celebrate with Us!</h2>
      <p>
        Whether it is a dream wedding, corporate event, or family getaway, Malaruhatan Country Club invites you to experience timeless elegance and heartfelt hospitality. Celebrate your moments with us, where every occasion shines.
      </p>
    </div>
  </div>
</div>

<!-- 🌿 MODERN FOOTER -->
<footer>
  <div class="footer-container">
    <div class="footer-section">
      <h6><i class="fa-solid fa-location-dot"></i> Address</h6>
      <p>Malaruhatan, Lian, Batangas</p>
    </div>

    <div class="footer-section">
      <h6><i class="fa-solid fa-phone"></i> Contact</h6>
      <p>+63 958368673</p>
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