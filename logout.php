<?php
// MCC/logout.php - CENTRALIZED LOGOUT HANDLER
session_start();

// Clear all session variables
$_SESSION = array();

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to appropriate login page based on referrer or use default
if (isset($_SERVER['HTTP_REFERER'])) {
    if (strpos($_SERVER['HTTP_REFERER'], 'admin_dashboards') !== false) {
        header("Location: ../admin_login.php");
        exit();
    } elseif (strpos($_SERVER['HTTP_REFERER'], 'event_manager') !== false) {
        header("Location: ../index.php"); // or your manager login
        exit();
    }
}

// Default redirect
header("Location: user_login.php");
exit();
?>