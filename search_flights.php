<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Function to send JSON response
function sendResponse($data) {
    echo json_encode($data);
    exit;
}

try {
    // Include required files with proper path handling
    $config_path = __DIR__ . '/../config/database.php';
    $flight_path = __DIR__ . '/flight.php';
    
    if (!file_exists($config_path)) {
        throw new Exception("Database config file not found at: " . $config_path);
    }
    
    if (!file_exists($flight_path)) {
        throw new Exception("Flight class file not found at: " . $flight_path);
    }
    
    require_once $config_path;
    require_once $flight_path;
    
    // Check if this is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse([
            'success' => false,
            'message' => 'Only POST requests are allowed',
            'outbound' => [],
            'return' => []
        ]);
    }
    
    // Get and validate POST data
    $departure_city = trim($_POST['departure_city'] ?? '');
    $arrival_city = trim($_POST['arrival_city'] ?? '');
    $departure_date = $_POST['departure_date'] ?? '';
    $return_date = $_POST['return_date'] ?? '';
    $passengers = intval($_POST['passengers'] ?? 1);
    $class = $_POST['class'] ?? 'economy';
    $trip_type = $_POST['trip_type'] ?? 'one-way';
    
    // Log search parameters for debugging
    error_log("Search parameters: " . json_encode($_POST));
    
    // Validate required fields
    if (empty($departure_city)) {
        sendResponse([
            'success' => false,
            'message' => 'Departure city is required',
            'outbound' => [],
            'return' => []
        ]);
    }
    
    if (empty($arrival_city)) {
        sendResponse([
            'success' => false,
            'message' => 'Arrival city is required',
            'outbound' => [],
            'return' => []
        ]);
    }
    
    if (empty($departure_date)) {
        sendResponse([
            'success' => false,
            'message' => 'Departure date is required',
            'outbound' => [],
            'return' => []
        ]);
    }
    
    // Validate date format
    $date_check = DateTime::createFromFormat('Y-m-d', $departure_date);
    if (!$date_check || $date_check->format('Y-m-d') !== $departure_date) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid departure date format',
            'outbound' => [],
            'return' => []
        ]);
    }
    
    // Create Flight instance
    $flight = new Flight();
    
    // Search outbound flights
    $outbound_flights = $flight->searchFlights($departure_city, $arrival_city, $departure_date, $passengers, $class);
    
    error_log("Found " . count($outbound_flights) . " outbound flights");
    
    // Prepare response
    $response = [
        'success' => true,
        'outbound' => $outbound_flights,
        'return' => [],
        'trip_type' => $trip_type,
        'passengers' => $passengers,
        'class' => $class
    ];
    
    // Search return flights if round trip
    if ($trip_type === 'round-trip' && !empty($return_date)) {
        $return_flights = $flight->searchFlights($arrival_city, $departure_city, $return_date, $passengers, $class);
        $response['return'] = $return_flights;
        error_log("Found " . count($return_flights) . " return flights");
    }
    
    // Add helpful message if no flights found
    if (empty($response['outbound'])) {
        $response['message'] = 'No flights found for the selected criteria. Try different cities or dates.';
    }
    
    sendResponse($response);
    
} catch (Exception $e) {
    error_log("Search flights error: " . $e->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'outbound' => [],
        'return' => []
    ]);
}
?>
