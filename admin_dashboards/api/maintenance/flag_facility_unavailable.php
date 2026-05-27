<?php
// admin_dashboards/api/maintenance/flag_facility_unavailable.php
// Only full admins can flag/unflag facilities

ob_start();
session_start();
header('Content-Type: application/json');

// Only admin can flag facilities (not manager)
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
$facility_id = isset($data['facility_id']) ? intval($data['facility_id']) : 0;
$action = isset($data['action']) ? $data['action'] : 'flag';

if (!$issue_id || !$facility_id) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

if (!in_array($action, ['flag', 'unflag'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

require_once dirname(__DIR__, 3) . '/db_connect.php';

// Get facility type
$facilityStmt = $conn->prepare("SELECT type, name FROM sport_leisure_facilities WHERE id = ?");
$facilityStmt->bind_param("i", $facility_id);
$facilityStmt->execute();
$facilityResult = $facilityStmt->get_result();
$facility = $facilityResult->fetch_assoc();

if (!$facility) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Facility not found']);
    exit;
}

$facility_type = $facility['type'];
$facility_name = $facility['name'];

$conn->begin_transaction();

try {
    if ($action === 'flag') {
        // Check if already flagged
        $checkStmt = $conn->prepare("
            SELECT id FROM maintenance_facility_flags 
            WHERE facility_id = ? AND issue_id = ? AND unflagged_at IS NULL
        ");
        $checkStmt->bind_param("ii", $facility_id, $issue_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            // Insert flag
            $insertStmt = $conn->prepare("
                INSERT INTO maintenance_facility_flags (facility_id, issue_id, flagged_at)
                VALUES (?, ?, NOW())
            ");
            $insertStmt->bind_param("ii", $facility_id, $issue_id);
            $insertStmt->execute();
            
            // Add note
            $note = "Facility '{$facility_name}' flagged as unavailable due to maintenance issue";
            $author_type = 'admin';
            $noteStmt = $conn->prepare("
                INSERT INTO maintenance_notes (issue_id, author_id, author_type, note, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $noteStmt->bind_param("iiss", $issue_id, $admin_id, $author_type, $note);
            $noteStmt->execute();
        }
        
        $message = "Facility '{$facility_name}' has been flagged as unavailable";
        
    } else {
        // Unflag - update existing flag
        $updateStmt = $conn->prepare("
            UPDATE maintenance_facility_flags 
            SET unflagged_at = NOW() 
            WHERE facility_id = ? AND issue_id = ? AND unflagged_at IS NULL
        ");
        $updateStmt->bind_param("ii", $facility_id, $issue_id);
        $updateStmt->execute();
        
        // Add note
        $note = "Facility '{$facility_name}' is now available for bookings";
        $author_type = 'admin';
        $noteStmt = $conn->prepare("
            INSERT INTO maintenance_notes (issue_id, author_id, author_type, note, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $noteStmt->bind_param("iiss", $issue_id, $admin_id, $author_type, $note);
        $noteStmt->execute();
        
        $message = "Facility '{$facility_name}' is now available";
    }
    
    $conn->commit();
    
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => $message
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