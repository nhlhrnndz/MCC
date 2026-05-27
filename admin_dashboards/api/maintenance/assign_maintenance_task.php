<?php
// admin_dashboards/api/maintenance/assign_maintenance_task.php
// Only full admins can assign tasks

ob_start();
session_start();
header('Content-Type: application/json');

// Only admin can assign (not manager)
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'admin') {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Unauthorized - Admin access required']);
    exit;
}

$admin_id = $_SESSION['admin_id'];
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

$issue_id = isset($data['issue_id']) ? intval($data['issue_id']) : 0;
$assigned_to = isset($data['assigned_to']) ? intval($data['assigned_to']) : 0;

if (!$issue_id || !$assigned_to) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

require_once dirname(__DIR__, 3) . '/db_connect.php';

$conn->begin_transaction();

try {
    // Check if issue exists
    $checkStmt = $conn->prepare("SELECT status FROM maintenance_issues WHERE id = ?");
    $checkStmt->bind_param("i", $issue_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $issue = $checkResult->fetch_assoc();
    
    if (!$issue) {
        throw new Exception('Issue not found');
    }
    
    // Update the issue
    $updateStmt = $conn->prepare("
        UPDATE maintenance_issues 
        SET assigned_to = ?, 
            status = CASE 
                WHEN status = 'open' THEN 'in_progress' 
                ELSE status 
            END
        WHERE id = ?
    ");
    $updateStmt->bind_param("ii", $assigned_to, $issue_id);
    $updateStmt->execute();
    
    // Get assigned staff name for the note
    $staffStmt = $conn->prepare("SELECT username, fullname FROM admin_users WHERE id = ?");
    $staffStmt->bind_param("i", $assigned_to);
    $staffStmt->execute();
    $staffResult = $staffStmt->get_result();
    $staff = $staffResult->fetch_assoc();
    $staffName = $staff ? ($staff['fullname'] ?? $staff['username']) : "Staff ID: {$assigned_to}";
    
    // Add note
    $note = "Assigned to {$staffName}";
    $author_type = 'admin';
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
        'message' => 'Issue assigned successfully'
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