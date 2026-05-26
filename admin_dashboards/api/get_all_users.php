<?php
// get_all_users.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

include '../../db_connect.php';

try {
    // Fetch regular users
    $users_stmt = $conn->query("
        SELECT 
            id,
            full_name as name,
            username,
            email,
            contact_number as contact,
            'User' as role,
            status,
            created_at
        FROM users 
        ORDER BY created_at DESC
    ");
    $regular_users = $users_stmt->fetch_all(MYSQLI_ASSOC);

    // Fetch admin users (both admins and managers)
    $admin_stmt = $conn->query("
        SELECT 
            id,
            fullname as name,
            username,
            email,
            contact,
            CASE 
                WHEN role = 'admin' THEN 'Admin'
                WHEN role = 'manager' THEN 'Event Manager'
                ELSE role 
            END as role,
            status,
            created_at
        FROM admin_users 
        ORDER BY created_at DESC
    ");
    $admin_users = $admin_stmt->fetch_all(MYSQLI_ASSOC);

    // Combine both arrays
    $all_users = array_merge($regular_users, $admin_users);

    // Return success with users data
    echo json_encode([
        'success' => true,
        'users' => $all_users,
        'total_count' => count($all_users)
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>