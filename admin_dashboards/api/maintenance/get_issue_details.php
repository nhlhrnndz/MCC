<?php
// admin_dashboards/api/maintenance/get_issue_details.php

ob_start();
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    ob_end_clean();
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$admin_id = $_SESSION['admin_id'];
$admin_role = $_SESSION['admin_role'] ?? '';
$issue_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$issue_id) {
    ob_end_clean();
    echo json_encode(['error' => 'Issue ID required']);
    exit;
}

require_once dirname(__DIR__, 3) . '/db_connect.php';

// Get issue details
$sql = "
    SELECT 
        mi.*,
        au.username as assigned_name,
        au.fullname as assigned_fullname,
        u.full_name as reporter_name,
        u.username as reporter_username
    FROM maintenance_issues mi
    LEFT JOIN admin_users au ON mi.assigned_to = au.id
    LEFT JOIN users u ON mi.reported_by = u.id
    WHERE mi.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $issue_id);
$stmt->execute();
$result = $stmt->get_result();
$issue = $result->fetch_assoc();

if (!$issue) {
    ob_end_clean();
    echo json_encode(['error' => 'Issue not found']);
    exit;
}

// Check manager permissions
if ($admin_role === 'manager' && $issue['assigned_to'] != $admin_id && $issue['assigned_to'] !== null) {
    ob_end_clean();
    echo json_encode(['error' => 'You can only view issues assigned to you']);
    exit;
}

// Get notes
$notesSql = "
    SELECT 
        mn.*,
        CASE 
            WHEN mn.author_type = 'admin' THEN CONCAT('Admin: ', au.username)
            WHEN mn.author_type = 'manager' THEN CONCAT('Manager: ', au.username)
            ELSE CONCAT('User: ', u.full_name)
        END as author_name
    FROM maintenance_notes mn
    LEFT JOIN admin_users au ON mn.author_id = au.id AND mn.author_type IN ('admin', 'manager')
    LEFT JOIN users u ON mn.author_id = u.id AND mn.author_type = 'user'
    WHERE mn.issue_id = ?
    ORDER BY mn.created_at ASC
";

$notesStmt = $conn->prepare($notesSql);
$notesStmt->bind_param("i", $issue_id);
$notesStmt->execute();
$notesResult = $notesStmt->get_result();
$notes = [];
while ($note = $notesResult->fetch_assoc()) {
    $notes[] = $note;
}

$issue['notes'] = $notes;

ob_end_clean();
echo json_encode($issue);
?>