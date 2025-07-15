<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/flight.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to book flights']);
    exit;
}

if ($_POST) {
    $flight = new Flight();
    
    $flight_id = $_POST['flight_id'] ?? '';
    $passenger_name = $_POST['passenger_name'] ?? '';
    $passenger_email = $_POST['passenger_email'] ?? '';
    $passenger_phone = $_POST['passenger_phone'] ?? '';
    $passengers = $_POST['passengers'] ?? 1;
    $class = $_POST['class'] ?? 'economy';
    $total_price = $_POST['total_price'] ?? 0;
    
    $result = $flight->bookFlight(
        $_SESSION['user_id'], 
        $flight_id, 
        $passenger_name, 
        $passenger_email, 
        $passenger_phone,
        $passengers,
        $class,
        $total_price
    );
    
    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
