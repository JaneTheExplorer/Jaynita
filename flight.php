<?php
class Flight {
    private $conn;
    
    public function __construct() {
        try {
            // Include database config
            $config_path = __DIR__ . '/../config/database.php';
            if (!file_exists($config_path)) {
                throw new Exception("Database config not found");
            }
            
            require_once $config_path;
            
            $database = new Database();
            $this->conn = $database->getConnection();
            
            if (!$this->conn) {
                throw new Exception("Failed to establish database connection");
            }
            
            error_log("Flight class: Database connection established");
            
        } catch (Exception $e) {
            error_log("Flight class constructor error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function searchFlights($departure_city, $arrival_city, $departure_date, $passengers = 1, $class = 'economy') {
        try {
            if (!$this->conn) {
                throw new Exception("No database connection available");
            }
            
            // Log search parameters
            error_log("Searching flights: '$departure_city' to '$arrival_city' on '$departure_date' for $passengers passengers in $class class");
            
            // Build query with flexible city matching
            $query = "SELECT f.*, a.name as airline_name, a.code as airline_code 
                     FROM flights f 
                     JOIN airlines a ON f.airline_id = a.id 
                     WHERE (f.departure_city LIKE ? OR f.departure_city LIKE ?)
                     AND (f.arrival_city LIKE ? OR f.arrival_city LIKE ?)
                     AND f.departure_date = ? 
                     AND f.status = 'active' 
                     AND f.available_seats >= ?
                     ORDER BY f.departure_time";
            
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare search query");
            }
            
            // Create search patterns for flexible matching
            $dep_pattern1 = "%$departure_city%";
            $dep_pattern2 = $departure_city . "%";
            $arr_pattern1 = "%$arrival_city%";
            $arr_pattern2 = $arrival_city . "%";
            
            $success = $stmt->execute([
                $dep_pattern1, $dep_pattern2, 
                $arr_pattern1, $arr_pattern2, 
                $departure_date, $passengers
            ]);
            
            if (!$success) {
                throw new Exception("Failed to execute search query");
            }
            
            $flights = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Raw query returned " . count($flights) . " flights");
            
            // If no exact matches, try broader search
            if (empty($flights)) {
                error_log("No flights found with strict criteria, trying broader search");
                
                $broad_query = "SELECT f.*, a.name as airline_name, a.code as airline_code 
                               FROM flights f 
                               JOIN airlines a ON f.airline_id = a.id 
                               WHERE f.status = 'active' 
                               AND f.available_seats >= ?
                               ORDER BY f.departure_date, f.departure_time
                               LIMIT 10";
                
                $stmt = $this->conn->prepare($broad_query);
                $stmt->execute([$passengers]);
                $flights = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                error_log("Broad search returned " . count($flights) . " flights");
            }
            
            // Apply class-based pricing and formatting
            foreach ($flights as &$flight) {
                $base_price = floatval($flight['price']);
                
                switch ($class) {
                    case 'business':
                        $flight['price'] = $base_price * 2.5;
                        $flight['class'] = 'Business Class';
                        break;
                    case 'first':
                        $flight['price'] = $base_price * 4;
                        $flight['class'] = 'First Class';
                        break;
                    default:
                        $flight['class'] = 'Economy Class';
                        break;
                }
                
                $flight['passengers'] = $passengers;
                $flight['total_price'] = $flight['price'] * $passengers;
                
                // Ensure numeric values are properly formatted
                $flight['price'] = number_format($flight['price'], 2, '.', '');
                $flight['total_price'] = number_format($flight['total_price'], 2, '.', '');
            }
            
            error_log("Returning " . count($flights) . " processed flights");
            return $flights;
            
        } catch (PDOException $e) {
            error_log("Database error in searchFlights: " . $e->getMessage());
            return [];
        } catch (Exception $e) {
            error_log("General error in searchFlights: " . $e->getMessage());
            return [];
        }
    }
    
    public function bookFlight($user_id, $flight_id, $passenger_name, $passenger_email, $passenger_phone, $passengers = 1, $class = 'economy', $total_price = 0) {
        try {
            $this->conn->beginTransaction();
            
            // Check flight availability
            $query = "SELECT * FROM flights WHERE id = ? AND available_seats >= ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$flight_id, $passengers]);
            $flight = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(!$flight) {
                throw new Exception("Flight not available or insufficient seats");
            }
            
            // Generate booking reference
            $booking_ref = 'FF' . strtoupper(uniqid());
            
            // Create booking with additional details
            $query = "INSERT INTO bookings (user_id, flight_id, booking_reference, passenger_name, passenger_email, passenger_phone, total_amount, passengers_count, class_type, booking_status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id, $flight_id, $booking_ref, $passenger_name, $passenger_email, $passenger_phone, $total_price, $passengers, $class]);
            
            // Update available seats
            $query = "UPDATE flights SET available_seats = available_seats - ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$passengers, $flight_id]);
            
            $this->conn->commit();
            return ['success' => true, 'booking_reference' => $booking_ref, 'message' => 'Booking successful'];
            
        } catch(Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getUserBookings($user_id) {
        try {
            $query = "SELECT b.*, f.flight_number, f.departure_city, f.arrival_city, 
                            f.departure_date, f.departure_time, f.arrival_date, f.arrival_time,
                            a.name as airline_name 
                     FROM bookings b
                     JOIN flights f ON b.flight_id = f.id
                     JOIN airlines a ON f.airline_id = a.id
                     WHERE b.user_id = ?
                     ORDER BY b.booking_date DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }
    
    public function cancelBooking($booking_id, $user_id) {
        try {
            $this->conn->beginTransaction();
            
            // Get booking details
            $query = "SELECT b.*, b.passengers_count FROM bookings b WHERE b.id = ? AND b.user_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$booking_id, $user_id]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(!$booking) {
                throw new Exception("Booking not found");
            }
            
            // Update booking status
            $query = "UPDATE bookings SET booking_status = 'cancelled' WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$booking_id]);
            
            // Update available seats
            $passengers_count = $booking['passengers_count'] ?? 1;
            $query = "UPDATE flights SET available_seats = available_seats + ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$passengers_count, $booking['flight_id']]);
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'Booking cancelled successfully'];
            
        } catch(Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getAllBookings($status_filter = '', $date_filter = '', $airline_filter = '') {
        try {
            $query = "SELECT b.*, f.flight_number, f.departure_city, f.arrival_city, 
                            f.departure_date, f.departure_time, a.name as airline_name,
                            u.first_name, u.last_name, u.email as user_email
                     FROM bookings b
                     JOIN flights f ON b.flight_id = f.id
                     JOIN airlines a ON f.airline_id = a.id
                     JOIN users u ON b.user_id = u.id";
            
            $conditions = [];
            $params = [];
            
            if ($status_filter) {
                $conditions[] = "b.booking_status = ?";
                $params[] = $status_filter;
            }
            
            if ($date_filter) {
                $conditions[] = "DATE(b.booking_date) = ?";
                $params[] = $date_filter;
            }
            
            if ($airline_filter) {
                $conditions[] = "a.name = ?";
                $params[] = $airline_filter;
            }
            
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(" AND ", $conditions);
            }
            
            $query .= " ORDER BY b.booking_date DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }
    
    public function getBookingStats() {
        try {
            $stats = [];
            
            // Total bookings
            $query = "SELECT COUNT(*) as total_bookings FROM bookings";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['total_bookings'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_bookings'];
            
            // Confirmed bookings
            $query = "SELECT COUNT(*) as confirmed_bookings FROM bookings WHERE booking_status = 'confirmed'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['confirmed_bookings'] = $stmt->fetch(PDO::FETCH_ASSOC)['confirmed_bookings'];
            
            // Pending bookings
            $query = "SELECT COUNT(*) as pending_bookings FROM bookings WHERE booking_status = 'pending'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['pending_bookings'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending_bookings'];
            
            // Cancelled bookings
            $query = "SELECT COUNT(*) as cancelled_bookings FROM bookings WHERE booking_status = 'cancelled'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['cancelled_bookings'] = $stmt->fetch(PDO::FETCH_ASSOC)['cancelled_bookings'];
            
            return $stats;
        } catch(PDOException $e) {
            return [];
        }
    }
}
?>
