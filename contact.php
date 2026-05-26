<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contact Us - Malaruhatan Country Club</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Lora:wght@600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
<style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background-color: #ffffff;
      color: #1a1a1a;
    }

    /* ----------------------------
       NAVBAR STYLES
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
      background-color: #00d084;
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

    /* 🌿 PROFESSIONAL BANNER */
    .banner {
      position: relative;
      width: 100%;
      height: 380px;
      overflow: hidden;
      margin-top: 75px;
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

    .container {
      display: flex;
      justify-content: space-between;
      gap: 50px;
      padding: 70px 100px;
      max-width: 1200px;
      margin: auto;
      flex-wrap: wrap;
    }

    .glass-card {
      background: rgba(255,255,255,0.9);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      padding: 35px;
      width: 45%;
      box-shadow: 0 10px 25px rgba(0,0,0,0.15);
      border: 1px solid rgba(1,188,137,0.2);
    }

    .contact-info h2 {
      font-size: 30px;
      color: #01bc89;
      margin-bottom: 20px;
      text-transform: uppercase;
      font-weight: 700;
    }

    .info-item {
      margin-bottom: 20px;
    }

    .info-item h4 {
      font-size: 18px;
      color: #000;
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .info-item i {
      color: #01bc89;
      font-size: 20px;
    }

    .info-item a {
      color: #000;
      text-decoration: none;
    }

    .info-item a:hover {
      color: #01bc89;
      text-decoration: underline;
    }

    .contact-form h3 {
      text-align: center;
      color: #01bc89;
      font-size: 26px;
      margin-bottom: 25px;
      text-transform: uppercase;
    }

    .contact-form input,
    .contact-form textarea {
      width: 100%;
      padding: 14px;
      margin-bottom: 20px;
      border: none;
      border-bottom: 2px solid #ccc;
      background: transparent;
      font-size: 15px;
      outline: none;
    }

    .contact-form input:focus,
    .contact-form textarea:focus {
      border-color: #01bc89;
    }

    .send-btn {
      width: 100%;
      background: #01bc89;
      color: white;
      padding: 14px;
      border-radius: 8px;
      font-size: 18px;
      border: none;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    .send-btn:hover {
      background: #009f73;
    }

    .send-btn:disabled {
      background: #cccccc;
      cursor: not-allowed;
    }

    /* Alert Messages */
    .alert {
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 8px;
      font-weight: 500;
    }

    .alert-success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .alert-error {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    footer {
      background: #f3fcf8;
      color: #0b5138;
      text-align: center;
      padding: 40px 15px;
      font-size: 0.95rem;
      letter-spacing: 0.2px;
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
      .container {
        padding: 40px 20px;
        flex-direction: column;
      }
      
      .glass-card {
        width: 100%;
      }
      
      .footer-container {
        flex-direction: column;
        text-align: center;
      }
      
      .footer-section { margin-bottom: 15px; }
      
      .banner-text h1 {
        font-size: 48px;
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
          <li class="nav-item"><a class="nav-link" href="about_us.php">About Us</a></li>
          <li class="nav-item"><a class="nav-link" href="facilities.php">Facilities</a></li>
          <li class="nav-item"><a class="nav-link active" href="contact.php">Contact Us</a></li>
        </ul>
      </div>

      <div class="ms-auto">
        <a href="user_login.php" class="btn-login">Log In</a>
      </div>
    </div>
  </nav>

  <!-- 🌿 PROFESSIONAL HEADER BANNER -->
  <div class="banner">
    <img src="assets\mainbanner\water Banner Landscape (1).png" alt="Banner Image" class="banner-image">
    <div class="banner-overlay"></div>

    <div class="banner-text">
      <h1> Contact Us</h1>
    </div>
  </div>

  <!-- 🌿 MAIN CONTENT -->
  <div class="container">

    <!-- LEFT -->
    <div class="contact-info glass-card">
      <h2>Get In Touch</h2>

      <div class="info-item">
        <h4><i class="fa-solid fa-location-dot"></i> Address</h4>
        <p>
          <a href="https://www.google.com/maps/search/?api=1&query=Martirez+St,+Brgy.+Malaruhatan,+Lian,+Philippines" target="_blank">
            Martirez St. Brgy. Malaruhatan, Lian, Philippines
          </a>
        </p>
      </div>

      <div class="info-item">
        <h4><i class="fa-solid fa-phone"></i> Phone</h4>
        <p><a href="tel:09958368673">0995 836 8673</a></p>
      </div>

      <div class="info-item">
        <h4><i class="fa-solid fa-envelope"></i> Email</h4>
        <p><a href="mailto:mccresort@yahoo.com">mccresort@yahoo.com</a></p>
      </div>
    </div>

    <!-- RIGHT -->
    <div class="contact-form glass-card">
      <h3>Send a Message</h3>
      
      <!-- Display Success/Error Messages -->
      <?php
      if (isset($_GET['status'])) {
          if ($_GET['status'] == 'success') {
              echo '<div class="alert alert-success">Thank you! Your message has been sent successfully.</div>';
          } elseif ($_GET['status'] == 'error') {
              echo '<div class="alert alert-error">Sorry, there was an error sending your message. Please try again.</div>';
          } elseif ($_GET['status'] == 'validation_error') {
              echo '<div class="alert alert-error">Please fill in all required fields correctly.</div>';
          }
      }
      ?>
      
      <form action="send_email.php" method="POST" id="contactForm">
        <input type="text" name="full_name" placeholder="Full Name" required value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
        <input type="email" name="email" placeholder="Email Address" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        <input type="text" name="subject" placeholder="Subject" required value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>">
        <textarea name="message" placeholder="Your Message..." rows="5" required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
        <button type="submit" class="send-btn" id="submitBtn">Send Message</button>
      </form>
    </div>

  </div>

  <!-- FOOTER -->
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
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Smooth navbar background transition when scrolling
    window.addEventListener('scroll', function() {
      const navbar = document.querySelector('.navbar');
      navbar.classList.toggle('scrolled', window.scrollY > 50);
    });

    // Form submission feedback
    document.getElementById('contactForm').addEventListener('submit', function() {
      document.getElementById('submitBtn').disabled = true;
      document.getElementById('submitBtn').textContent = 'Sending...';
    });
  </script>
</body>
</html>