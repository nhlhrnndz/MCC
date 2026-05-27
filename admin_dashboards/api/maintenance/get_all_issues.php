<?php
// admin_dashboards/api/maintenance/get_all_issues.php
// Fetch all maintenance issues with optional filters

ob_start();
session_start();
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    ob_end_clean();
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once dirname(__DIR__, 3) . '/db_connect.php';

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$severity = isset($_GET['severity']) ? $_GET['severity'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$sql = "
    SELECT 
        mi.*,
        au.username as assigned_name,
        u.full_name as reporter_name
    FROM maintenance_issues mi
    LEFT JOIN admin_users au ON mi.assigned_to = au.id
    LEFT JOIN users u ON mi.reported_by = u.id
    WHERE 1=1
";

$params = [];
$types = "";

if ($status !== '') {
    $sql .= " AND mi.status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($severity !== '') {
    $sql .= " AND mi.severity = ?";
    $params[] = $severity;
    $types .= "s";
}

if ($search !== '') {
    $sql .= " AND (mi.title LIKE ? OR mi.facility_name LIKE ? OR mi.description LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}

$sql .= " ORDER BY 
    CASE mi.severity 
        WHEN 'critical' THEN 1 
        WHEN 'high' THEN 2 
        WHEN 'medium' THEN 3 
        WHEN 'low' THEN 4 
    END,
    mi.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$issues = [];
while ($row = $result->fetch_assoc()) {
    $issues[] = $row;
}

// Get statistics
$statsSql = "
    SELECT 
        SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_count,
        SUM(CASE WHEN severity = 'critical' AND status IN ('open', 'in_progress') THEN 1 ELSE 0 END) as critical_active_count
    FROM maintenance_issues
";
$statsResult = $conn->query($statsSql);
$statsRow = $statsResult->fetch_assoc();

ob_end_clean();
echo json_encode([
    'issues' => $issues,
    'stats' => [
        'open' => intval($statsRow['open_count'] ?? 0),
        'in_progress' => intval($statsRow['in_progress_count'] ?? 0),
        'resolved' => intval($statsRow['resolved_count'] ?? 0),
        'closed' => intval($statsRow['closed_count'] ?? 0),
        'critical' => intval($statsRow['critical_active_count'] ?? 0)
    ]
]);
?>