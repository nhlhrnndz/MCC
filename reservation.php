<?php
// reservation.php - redirect to dashboard version
session_start();
header('Location: user_dashboard.php?page=reservation');
exit;
?>