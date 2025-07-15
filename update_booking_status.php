<?php
session_start();
require_once '../includes/auth.php';
require_once '../config/database.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if ($input && isset($input['booking_id']) && isset($input['status'])) {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "UPDATE bookings SET booking_status = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        
        if ($stmt->execute([$input['status'], $input['booking_id']])) {
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
