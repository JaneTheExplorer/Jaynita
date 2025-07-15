<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/flight.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to cancel bookings']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if ($input && isset($input['booking_id'])) {
    $flight = new Flight();
    $result = $flight->cancelBooking($input['booking_id'], $_SESSION['user_id']);
    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
