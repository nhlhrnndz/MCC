<?php
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
    // Get issue details
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
    
    // Get notes - FIXED: removed author_type since it doesn't exist
    $notesStmt = $conn->prepare("
        SELECT mn.*, u.full_name as author_name
        FROM maintenance_notes mn
        LEFT JOIN users u ON mn.author_id = u.id
        WHERE mn.issue_id = ?
        ORDER BY mn.created_at ASC
    ");
    $notesStmt->bind_param("i", $issue_id);
    $notesStmt->execute();
    $notes = $notesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Add author_type for frontend compatibility (default to 'user')
    foreach ($notes as &$note) {
        $note['author_type'] = 'user';
        if (!$note['author_name']) {
            $note['author_name'] = 'System';
        }
    }
    
    // Check if facility is currently flagged (unavailable)
    $flagStmt = $conn->prepare("
        SELECT * FROM maintenance_facility_flags 
        WHERE facility_id = ? AND issue_id = ? AND unflagged_at IS NULL
    ");
    $flagStmt->bind_param("ii", $issue['facility_id'], $issue_id);
    $flagStmt->execute();
    $isFlagged = $flagStmt->get_result()->num_rows > 0;
    
    $issue['is_facility_unavailable'] = $isFlagged;
    $issue['notes'] = $notes;
    echo json_encode($issue);
} else {
    $issuesStmt = $conn->prepare("
        SELECT 
            mi.id, 
            mi.facility_name, 
            mi.title, 
            mi.severity, 
            mi.status, 
            mi.created_at, 
            mi.resolved_at,
            (SELECT COUNT(*) FROM maintenance_facility_flags mff 
             WHERE mff.facility_id = mi.facility_id 
             AND mff.issue_id = mi.id 
             AND mff.unflagged_at IS NULL) as is_facility_unavailable
        FROM maintenance_issues mi
        WHERE mi.reported_by = ?
        ORDER BY mi.created_at DESC
    ");
    $issuesStmt->bind_param("i", $user_id);
    $issuesStmt->execute();
    echo json_encode($issuesStmt->get_result()->fetch_all(MYSQLI_ASSOC));
}
?>