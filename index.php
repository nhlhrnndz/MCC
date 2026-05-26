<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Malaruhatan Country Club</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="main.css">
  
  <style>
    /* ----------------------------
       NAVBAR STYLES (matching about_us.php, contact.php, facilities.php)
    ----------------------------- */
    .navbar {
      background-color: #d4f7ef;
      backdrop-filter: blur(8px);
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
      padding: 0.8rem 1.5rem;
      transition: background-color 0.3s ease;
      font-family: 'Poppins', sans-serif;
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
      color: #0aa24a !important;
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
        <li class="nav-item"><a class="nav-link active" href="main.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="about_us.php">About Us</a></li>
        <li class="nav-item"><a class="nav-link" href="facilities.php">Facilities</a></li>
        <li class="nav-item"><a class="nav-link" href="contact.php">Contact Us</a></li>
      </ul>
    </div>

    <div class="ms-auto">
      <a href="user_login.php" class="btn-login">Log In</a>
    </div>
  </div>
</nav>

<!-- 🌄 Hero -->
<section class="hero fade-in">
  <video autoplay muted loop playsinline class="hero-video">
    <source src="assets/mainbanner/IMG_1783.MOV" type="video/mp4">
  </video>
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <h1>Welcome to <span>Malaruhatan Country Club</span></h1>
    <p>
      Experience elegance, relaxation, and world-class hospitality as we welcome you
      to a serene destination where comfort, nature, and exceptional service come
      together to create unforgettable moments.
    </p>
  </div>
</section>

<!-- 🎉 Event Types -->
<section class="event-types fade-in">
  <div class="event-card">
    <img src="assets/event venue/gatherings3.jpg" alt="Social Gathering">
    <div class="overlay"><h2>Social Gathering</h2></div>
  </div>
  <div class="event-card">
    <img src="assets/event venue/weddin2.jpg" alt="Weddings">
    <div class="overlay"><h2>Weddings</h2></div>
  </div>
  <div class="event-card">
    <img src="assets/event venue/gatherings.jpg" alt="Meeting & Conference">
    <div class="overlay"><h2>Meeting & Conference</h2></div>
  </div>
</section>

<!-- 🗓️ Plan Event -->
<section class="plan-event fade-in">
  <div class="container">
    <div class="icon"><i class="fa-regular fa-calendar-check"></i></div>
    <h2>Plan An <span>Event</span></h2>
    <p>Create unforgettable moments at Malaruhatan Country Club.</p>
  </div>
</section>

<!-- 📸 Gallery -->
<section class="gallery-section fade-in">
  <h2>Recent Events at our Club</h2>
  <p>Explore the memorable moments and celebrations that have taken place at Malaruhatan Country Club.</p>
  <div class="gallery-wrapper">
    <div class="gallery-container" id="galleryContainer">
      <img src="assets/recent events/event1.jpg" alt="">
      <img src="assets/recent events/event2.jpg" alt="">
      <img src="assets/recent events/event 3.jpg" alt="">
      <img src="assets/recent events/event4.jpg" alt="">
      <img src="assets/recent events/event5.jpg" alt="">
      <img src="assets/recent events/event6.jpg" alt="">
      <img src="assets/recent events/event7.jpg" alt="">
      <img src="assets/recent events/event8.jpg" alt="">
      <img src="assets/recent events/event9.jpg" alt="">
      <img src="assets/recent events/event10.jpg" alt="">
    </div>
  </div>
</section>

<!-- 🏡 Facilities -->
<section class="facilities fade-in" id="facilities">
  <h2>Our Facilities</h2>
  <div class="facilities-grid">
    <div class="facility-item">
      <a href="facilities.php#swimming-pool">
        <img src="assets/pools.jpg" alt="Swimming Pool">
        <p class="facility-title">Swimming Pool</p>
      </a>
    </div>
    <div class="facility-item">
      <a href="facilities.php#tennis-court">
        <img src="assets\sports\tennis2.jpg" alt="Tennis Court">
        <p class="facility-title">Tennis Court</p>
      </a>
    </div>
    <div class="facility-item">
      <a href="facilities.php#dining-hall">
        <img src="assets/event venue/gatherings3.jpg" alt="Dining Hall">
        <p class="facility-title">Dining Hall</p>
      </a>
    </div>
    <div class="facility-item">
      <a href="facilities.php#rooms">
        <img src="assets/rooms/rooms.jpg" alt="Rooms">
        <p class="facility-title">Rooms</p>
      </a>
    </div>
    <div class="facility-item">
      <a href="facilities.php#event-venue">
        <img src="assets/mcc4.webp" alt="Event Venue">
        <p class="facility-title">Event Venue</p>
      </a>
    </div>
  </div>
</section>

<!-- 🌿 Modern Footer -->
<footer class="footer-modern">
  <div class="container py-4">

    <div class="row text-center text-md-start">

      <!-- 📍 Address -->
      <div class="col-md-4 mb-3">
        <h6 class="fw-bold"><i class="fa-solid fa-location-dot me-2"></i>Address</h6>
        <p class="mb-0">Malaruhatan, Lian, Batangas</p>
      </div>

      <!-- ☎️ Contact -->
      <div class="col-md-4 mb-3">
        <h6 class="fw-bold">
          <i class="fa-solid fa-phone me-2"></i>+639 958368673
        </h6>
        <p class="mb-1">
          <i class="fa-regular fa-envelope me-2"></i>mccresort@yahoo.com
        </p>
      </div>

      <!-- 🌐 Socials -->
      <div class="col-md-4 mb-3">
        <h6 class="fw-bold">Follow Us</h6>
        <div class="social-links">
          <a href="https://www.facebook.com/malaruhatan/?locale=tl_PH"><i class="fa-brands fa-facebook-f"></i></a>
        </div>
      </div>

    </div>

    <hr>

    <p class="text-center small mt-3 mb-0">
      Copyright © 2025 Malaruhatan Country Club. All Rights Reserved.
    </p>

  </div>
</footer>

<!-- 🌫️ Animations & Parallax -->
<script>
const faders = document.querySelectorAll('.fade-in');
const appearOptions = { threshold: 0.2, rootMargin: "0px 0px -50px 0px" };

const appearOnScroll = new IntersectionObserver(function(entries, observer) {
  entries.forEach(entry => {
    if (!entry.isIntersecting) return;
    entry.target.classList.add('visible');
    observer.unobserve(entry.target);
  });
}, appearOptions);

faders.forEach(fader => appearOnScroll.observe(fader));

window.addEventListener('scroll', () => {
  const scroll = window.scrollY;
  const video = document.querySelector('.hero-video');
  video.style.transform = `translateY(${scroll * 0.25}px)`;
});

// Smooth navbar background transition when scrolling (matching other pages)
window.addEventListener('scroll', function() {
  const navbar = document.querySelector('.navbar');
  navbar.classList.toggle('scrolled', window.scrollY > 50);
});
</script>

<!-- 🎞️ Continuous Film-Like Gallery Loop -->
<script>
const gallery = document.getElementById("galleryContainer");
const clone = gallery.cloneNode(true);
gallery.parentElement.appendChild(clone);

// Ensure smooth infinite flow by merging two identical tracks
gallery.parentElement.style.display = "flex";
gallery.parentElement.style.flexWrap = "nowrap";
gallery.parentElement.style.overflow = "hidden";
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>