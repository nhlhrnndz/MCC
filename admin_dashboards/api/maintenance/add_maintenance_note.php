<?php
// admin_dashboards/api/maintenance/add_maintenance_note.php

ob_start();
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$admin_id = $_SESSION['admin_id'];
$admin_role = $_SESSION['admin_role'] ?? '';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

$issue_id = isset($data['issue_id']) ? intval($data['issue_id']) : 0;
$note = isset($data['note']) ? trim($data['note']) : '';

if (!$issue_id) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Issue ID required']);
    exit;
}

if (empty($note)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Note cannot be empty']);
    exit;
}

require_once dirname(__DIR__, 3) . '/db_connect.php';

// Check if issue exists and verify permissions for manager
$checkStmt = $conn->prepare("
    SELECT assigned_to FROM maintenance_issues WHERE id = ?
");
$checkStmt->bind_param("i", $issue_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$issue = $checkResult->fetch_assoc();

if (!$issue) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Issue not found']);
    exit;
}

// Managers can only add notes to issues assigned to them
if ($admin_role === 'manager' && $issue['assigned_to'] != $admin_id) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'You can only add notes to issues assigned to you']);
    exit;
}

$author_type = ($admin_role === 'admin') ? 'admin' : 'manager';

$insertStmt = $conn->prepare("
    INSERT INTO maintenance_notes (issue_id, author_id, author_type, note, created_at)
    VALUES (?, ?, ?, ?, NOW())
");
$insertStmt->bind_param("iiss", $issue_id, $admin_id, $author_type, $note);

if ($insertStmt->execute()) {
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Note added successfully',
        'note_id' => $conn->insert_id
    ]);
} else {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Failed to add note: ' . $conn->error
    ]);
}
?>