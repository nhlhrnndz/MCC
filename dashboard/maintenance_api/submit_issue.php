<?php
// MCC/dashboard/maintenance_api/submit_issue.php

// Remove any whitespace before this line!

// Start output buffering to catch any accidental output
ob_start();

// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear any previous output
ob_clean();

// Set JSON header
header('Content-Type: application/json');

// Disable error display (log instead)
ini_set('display_errors', 0);
ini_set('log_errors', 1);

function sendError($message) {
    // Clear any output buffer before sending JSON
    ob_clean();
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    sendError('Please login to submit a report');
}

require_once __DIR__ . '/../../db_connect.php';

if (!$conn) {
    sendError('Database connection failed');
}

$username = $_SESSION['username'];
$userQuery = $conn->prepare("SELECT id FROM users WHERE username = ?");
$userQuery->bind_param("s", $username);
$userQuery->execute();
$userResult = $userQuery->get_result();
$currentUser = $userResult->fetch_assoc();

if (!$currentUser) {
    sendError('User not found');
}

$user_id = $currentUser['id'];

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Invalid request method');
}

// Get POST data (support both form data and JSON)
$input = json_decode(file_get_contents('php://input'), true);
if ($input) {
    // Handle JSON input
    $facility_id = isset($input['facility_id']) ? intval($input['facility_id']) : 0;
    $title = isset($input['title']) ? trim($input['title']) : '';
    $description = isset($input['description']) ? trim($input['description']) : '';
    $severity = isset($input['severity']) ? $input['severity'] : 'medium';
} else {
    // Handle form data
    $facility_id = isset($_POST['facility_id']) ? intval($_POST['facility_id']) : 0;
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $severity = isset($_POST['severity']) ? $_POST['severity'] : 'medium';
}

if (!$facility_id) sendError('Please select a facility');
if (!$title) sendError('Please enter a title');
if (!$description) sendError('Please enter a description');

$allowedSeverities = ['low', 'medium', 'high', 'critical'];
if (!in_array($severity, $allowedSeverities)) {
    $severity = 'medium';
}

// Verify facility exists
$facilityQuery = $conn->prepare("SELECT name FROM sport_leisure_facilities WHERE id = ?");
$facilityQuery->bind_param("i", $facility_id);
$facilityQuery->execute();
$facilityResult = $facilityQuery->get_result();
$facility = $facilityResult->fetch_assoc();

if (!$facility) {
    sendError('Invalid facility selected');
}

$facility_name = $facility['name'];

// Insert the issue
$insertStmt = $conn->prepare("
    INSERT INTO maintenance_issues (reported_by, facility_id, facility_name, title, description, severity, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, 'open', NOW())
");
$insertStmt->bind_param("iissss", $user_id, $facility_id, $facility_name, $title, $description, $severity);

if (!$insertStmt->execute()) {
    sendError('Database error: ' . $insertStmt->error);
}

// Clear buffer before final output
ob_clean();

// Return success response
echo json_encode([
    'success' => true,
    'message' => 'Issue reported successfully. Our maintenance team will review it shortly.',
    'issue_id' => $conn->insert_id
]);
exit;
?>