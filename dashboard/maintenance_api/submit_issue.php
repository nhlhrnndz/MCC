<?php
// dashboard/maintenance_api/submit_issue.php

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

// Determine user type (regular user or admin)
$username = $_SESSION['username'];
$userRole = $_SESSION['role'] ?? null;

// Check if user is admin/manager
$isAdmin = false;
$adminId = null;
$userId = null;

if ($userRole && in_array($userRole, ['admin', 'manager'])) {
    // User is admin/manager
    $adminQuery = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
    $adminQuery->bind_param("s", $username);
    $adminQuery->execute();
    $adminResult = $adminQuery->get_result();
    $adminUser = $adminResult->fetch_assoc();
    
    if ($adminUser) {
        $isAdmin = true;
        $adminId = $adminUser['id'];
    }
}

if (!$isAdmin) {
    // Check regular user
    $userQuery = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $userQuery->bind_param("s", $username);
    $userQuery->execute();
    $userResult = $userQuery->get_result();
    $currentUser = $userResult->fetch_assoc();
    
    if (!$currentUser) {
        sendError('User not found');
    }
    $userId = $currentUser['id'];
}

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Invalid request method');
}

// Get POST data (support both form data and JSON)
$input = json_decode(file_get_contents('php://input'), true);
if ($input) {
    $facility_id = isset($input['facility_id']) ? intval($input['facility_id']) : 0;
    $title = isset($input['title']) ? trim($input['title']) : '';
    $description = isset($input['description']) ? trim($input['description']) : '';
    $severity = isset($input['severity']) ? $input['severity'] : 'medium';
} else {
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

// Insert the issue - use the appropriate user ID
$reported_by = $isAdmin ? $adminId : $userId;
$reported_by_type = $isAdmin ? 'admin' : 'user';

$insertStmt = $conn->prepare("
    INSERT INTO maintenance_issues (reported_by, reported_by_type, facility_id, facility_name, title, description, severity, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'open', NOW()
)");

if (!$insertStmt) {
    sendError('Database prepare error: ' . $conn->error);
}

$insertStmt->bind_param("isissis", $reported_by, $reported_by_type, $facility_id, $facility_name, $title, $description, $severity);

if (!$insertStmt->execute()) {
    sendError('Database error: ' . $insertStmt->error);
}

$issue_id = $conn->insert_id;

// Add initial note (optional - can be the issue description as first note)
$noteStmt = $conn->prepare("
    INSERT INTO maintenance_notes (issue_id, author_id, author_type, note, created_at)
    VALUES (?, ?, ?, ?, NOW())
");
$author_type = $isAdmin ? 'admin' : 'user';
$author_id = $isAdmin ? $adminId : $userId;
$initialNote = "Issue reported: " . $description;

$noteStmt->bind_param("iiss", $issue_id, $author_id, $author_type, $initialNote);
$noteStmt->execute();

// Clear buffer before final output
ob_clean();

// Return success response
echo json_encode([
    'success' => true,
    'message' => 'Issue reported successfully. Our maintenance team will review it shortly.',
    'issue_id' => $issue_id
]);
exit;
?>