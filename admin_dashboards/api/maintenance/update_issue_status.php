<?php
// admin_dashboards/api/maintenance/update_issue_status.php
// Both admin and manager can update status of assigned issues

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
$new_status = isset($data['status']) ? $data['status'] : '';

$allowed_statuses = ['open', 'in_progress', 'resolved', 'closed'];
if (!in_array($new_status, $allowed_statuses)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid status value']);
    exit;
}

if (!$issue_id) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Issue ID required']);
    exit;
}

require_once dirname(__DIR__, 3) . '/db_connect.php';

$conn->begin_transaction();

try {
    // Check if issue exists and verify permissions
    $checkStmt = $conn->prepare("
        SELECT status, facility_id, assigned_to 
        FROM maintenance_issues 
        WHERE id = ?
    ");
    $checkStmt->bind_param("i", $issue_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $issue = $checkResult->fetch_assoc();
    
    if (!$issue) {
        throw new Exception('Issue not found');
    }
    
    // Managers can only update issues assigned to them
    if ($admin_role === 'manager' && $issue['assigned_to'] != $admin_id) {
        throw new Exception('You can only update issues assigned to you');
    }
    
    $old_status = $issue['status'];
    
    $updateSql = "UPDATE maintenance_issues SET status = ?";
    $params = [$new_status];
    $types = "s";
    
    if ($new_status === 'resolved' || $new_status === 'closed') {
        $updateSql .= ", resolved_at = NOW()";
    } elseif ($old_status === 'resolved' && $new_status !== 'resolved') {
        $updateSql .= ", resolved_at = NULL";
    }
    
    $updateSql .= " WHERE id = ?";
    $params[] = $issue_id;
    $types .= "i";
    
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param($types, ...$params);
    $updateStmt->execute();
    
    if ($new_status === 'resolved' || $new_status === 'closed') {
        $unflagStmt = $conn->prepare("
            UPDATE maintenance_facility_flags 
            SET unflagged_at = NOW() 
            WHERE facility_id = ? AND issue_id = ? AND unflagged_at IS NULL
        ");
        $unflagStmt->bind_param("ii", $issue['facility_id'], $issue_id);
        $unflagStmt->execute();
    }
    
    $author_type = ($admin_role === 'admin') ? 'admin' : 'manager';
    $note = "Status changed from '{$old_status}' to '{$new_status}' by " . ucfirst($author_type);
    $noteStmt = $conn->prepare("
        INSERT INTO maintenance_notes (issue_id, author_id, author_type, note, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $noteStmt->bind_param("iiss", $issue_id, $admin_id, $author_type, $note);
    $noteStmt->execute();
    
    $conn->commit();
    
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully'
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>