<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../db_connect.php';

// Check if user is admin/manager
$username = $_SESSION['username'];
$role = $_SESSION['role'] ?? null;

if (!in_array($role, ['admin', 'manager'])) {
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

// Get admin user ID
$adminQuery = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
$adminQuery->bind_param("s", $username);
$adminQuery->execute();
$adminResult = $adminQuery->get_result();
$adminUser = $adminResult->fetch_assoc();

if (!$adminUser) {
    echo json_encode(['success' => false, 'error' => 'Admin user not found']);
    exit;
}

$admin_id = $adminUser['id'];

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $issue_id = isset($_POST['issue_id']) ? intval($_POST['issue_id']) : 0;
    $note = isset($_POST['note']) ? trim($_POST['note']) : '';
} else {
    $issue_id = isset($input['issue_id']) ? intval($input['issue_id']) : 0;
    $note = isset($input['note']) ? trim($input['note']) : '';
}

if (!$issue_id || !$note) {
    echo json_encode(['success' => false, 'error' => 'Issue ID and note are required']);
    exit;
}

// Verify issue exists
$issueCheck = $conn->prepare("SELECT id FROM maintenance_issues WHERE id = ?");
$issueCheck->bind_param("i", $issue_id);
$issueCheck->execute();
$issueResult = $issueCheck->get_result();

if ($issueResult->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Issue not found']);
    exit;
}

// Insert note
$insertStmt = $conn->prepare("
    INSERT INTO maintenance_notes (issue_id, author_id, author_type, note, created_at)
    VALUES (?, ?, 'admin', ?, NOW())
");
$insertStmt->bind_param("iis", $issue_id, $admin_id, $note);

if ($insertStmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Note added successfully',
        'note_id' => $conn->insert_id
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to add note: ' . $insertStmt->error]);
}
?>