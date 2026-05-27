<?php
// dashboard/maintenance_api/get_user_issues.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../db_connect.php';

$username = $_SESSION['username'];
$userQuery = $conn->prepare("SELECT id FROM users WHERE username = ?");
$userQuery->bind_param("s", $username);
$userQuery->execute();
$userResult = $userQuery->get_result();
$currentUser = $userResult->fetch_assoc();

if (!$currentUser) {
    echo json_encode(['error' => 'User not found']);
    exit;
}

$user_id = $currentUser['id'];
$issue_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$requested_user = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($requested_user && $requested_user != $user_id) {
    echo json_encode(['error' => 'Access denied']);
    exit;
}

if ($issue_id) {
    $issueStmt = $conn->prepare("
        SELECT mi.*, au.username as assigned_name
        FROM maintenance_issues mi
        LEFT JOIN admin_users au ON mi.assigned_to = au.id
        WHERE mi.id = ? AND mi.reported_by = ?
    ");
    $issueStmt->bind_param("ii", $issue_id, $user_id);
    $issueStmt->execute();
    $issue = $issueStmt->get_result()->fetch_assoc();
    
    if (!$issue) {
        echo json_encode(['error' => 'Issue not found']);
        exit;
    }
    
    $notesStmt = $conn->prepare("
        SELECT mn.*,
            CASE 
                WHEN mn.author_type = 'user' THEN u.full_name
                WHEN mn.author_type = 'admin' THEN au.username
                ELSE 'System'
            END as author_name
        FROM maintenance_notes mn
        LEFT JOIN users u ON mn.author_id = u.id AND mn.author_type = 'user'
        LEFT JOIN admin_users au ON mn.author_id = au.id AND mn.author_type IN ('admin', 'manager')
        WHERE mn.issue_id = ?
        ORDER BY mn.created_at ASC
    ");
    $notesStmt->bind_param("i", $issue_id);
    $notesStmt->execute();
    $issue['notes'] = $notesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode($issue);
} else {
    $issuesStmt = $conn->prepare("
        SELECT id, facility_name, title, severity, status, created_at, resolved_at
        FROM maintenance_issues
        WHERE reported_by = ?
        ORDER BY created_at DESC
    ");
    $issuesStmt->bind_param("i", $user_id);
    $issuesStmt->execute();
    echo json_encode($issuesStmt->get_result()->fetch_all(MYSQLI_ASSOC));
}
?>