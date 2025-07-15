<?php

require_once '../includes/auth.php';
require_once '../includes/flight.php';

$auth = new Auth();
$flight = new Flight();

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_bookings = $flight->getUserBookings($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FlyFlex</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <div class="dashboard-header">
            <div class="container">
                <div class="dashboard-nav">
                    <a href="../index.html" class="logo">FlyFlex</a>
                    <div>
                        <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
                        <a href="../includes/logout.php" class="btn" style="margin-left: 1rem;">Logout</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            <div class="dashboard-content">
                <aside class="sidebar">
                    <ul class="sidebar-menu">
                        <li><a href="#search" class="active" onclick="showSection('search')">Search Flights</a></li>
                        <li><a href="#bookings" onclick="showSection('bookings')">My Bookings</a></li>
                        <li><a href="#reports" onclick="showSection('reports')">Reports</a></li>
                    </ul>
                </aside>

                <main class="main-content">
                    <!-- Search Section -->
                    <div id="search-section" class="content-section">
                        <h2>Search Flights</h2>
                        <div class="search-form">
                            <form id="flightSearchForm">
                                <!-- Trip Type Selection -->
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Trip Type</label>
                                        <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                                            <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal;">
                                                <input type="radio" name="trip_type" value="one-way" checked onchange="toggleReturnDate()">
                                                One Way
                                            </label>
                                            <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal;">
                                                <input type="radio" name="trip_type" value="round-trip" onchange="toggleReturnDate()">
                                                Round Trip
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="departure_city">From</label>
                                        <input type="text" id="departure_city" name="departure_city" placeholder="Departure City" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="arrival_city">To</label>
                                        <input type="text" id="arrival_city" name="arrival_city" placeholder="Destination City" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="departure_date">Departure Date</label>
                                        <input type="date" id="departure_date" name="departure_date" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="return_date">Return Date</label>
                                        <input type="date" id="return_date" name="return_date" disabled style="background-color: white;">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="passengers">Passengers</label>
                                        <select id="passengers" name="passengers" required>
                                            <option value="1">1 Passenger</option>
                                            <option value="2">2 Passengers</option>
                                            <option value="3">3 Passengers</option>
                                            <option value="4">4 Passengers</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="class">Class</label>
                                        <select id="class" name="class" required>
                                            <option value="economy">Economy Class</option>
                                            <option value="business">Business Class</option>
                                            <option value="first">First Class</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Search button in its own row -->
                                <div class="form-row">
                                    <div class="form-group search-button-group">
                                        <button type="submit" class="btn search-btn" onclick="searchFlights(); return false;">Search Flights</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div id="flightResults" class="flight-results"></div>
                    </div>

                    <!-- Bookings Section -->
                    <div id="bookings-section" class="content-section" style="display: none;">
                        <h2>My Bookings</h2>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Booking Reference</th>
                                        <th>Flight</th>
                                        <th>Route</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Amount</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($user_bookings as $booking): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($booking['booking_reference']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['airline_name'] . ' ' . $booking['flight_number']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['departure_city'] . ' → ' . $booking['arrival_city']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($booking['departure_date'])); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $booking['booking_status']; ?>">
                                                <?php echo ucfirst($booking['booking_status']); ?>
                                            </span>
                                        </td>
                                        <td>$<?php echo number_format($booking['total_amount'], 2); ?></td>
                                        <td>
                                            <?php if ($booking['booking_status'] !== 'cancelled'): ?>
                                                <button class="btn btn-danger btn-sm" onclick="cancelBooking(<?php echo $booking['id']; ?>)">Cancel</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Reports Section -->
                    <div id="reports-section" class="content-section" style="display: none;">
                        <h2>Booking Reports</h2>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo count($user_bookings); ?></div>
                                <div class="stat-label">Total Bookings</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number">
                                    <?php echo count(array_filter($user_bookings, function($b) { return $b['booking_status'] === 'confirmed'; })); ?>
                                </div>
                                <div class="stat-label">Confirmed Flights</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number">
                                    $<?php echo number_format(array_sum(array_column($user_bookings, 'total_amount')), 2); ?>
                                </div>
                                <div class="stat-label">Total Spent</div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <!-- Booking Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Book Flight</h2>
            <div id="selectedFlightInfo" class="flight-info-display"></div>
            <form id="bookingForm">
                <input type="hidden" id="selectedFlightId" name="flight_id">
                <input type="hidden" id="selectedPassengers" name="passengers">
                <input type="hidden" id="selectedClass" name="class">
                <input type="hidden" id="selectedTotalPrice" name="total_price">
                
                <div class="form-group">
                    <label for="passenger_name">Passenger Name</label>
                    <input type="text" id="passenger_name" name="passenger_name" required>
                </div>
                <div class="form-group">
                    <label for="passenger_email">Passenger Email</label>
                    <input type="email" id="passenger_email" name="passenger_email" required>
                </div>
                <div class="form-group">
                    <label for="passenger_phone">Phone Number</label>
                    <input type="tel" id="passenger_phone" name="passenger_phone" required>
                </div>
                <button type="button" class="btn" onclick="confirmBooking()">Confirm Booking</button>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
function toggleReturnDate() {
    const tripType = document.querySelector('input[name="trip_type"]:checked').value;
    const returnDateInput = document.getElementById('return_date');
    
    if (tripType === 'round-trip') {
        returnDateInput.disabled = false;
        returnDateInput.required = true;
        returnDateInput.style.backgroundColor = 'white';
        returnDateInput.style.cursor = 'pointer';
        
        // Set minimum date to departure date if selected
        const departureDate = document.getElementById('departure_date').value;
        if (departureDate) {
            returnDateInput.min = departureDate;
        }
    } else {
        returnDateInput.disabled = true;
        returnDateInput.required = false;
        returnDateInput.value = '';
        returnDateInput.style.backgroundColor = '#f8f9fa';
        returnDateInput.style.cursor = 'not-allowed';
    }
}

// Add event listener for departure date to update return date minimum
document.addEventListener('DOMContentLoaded', function() {
    const departureInput = document.getElementById('departure_date');
    const returnInput = document.getElementById('return_date');
    
    if (departureInput && returnInput) {
        departureInput.addEventListener('change', function() {
            if (!returnInput.disabled) {
                returnInput.min = this.value;
                // Clear return date if it's before departure date
                if (returnInput.value && returnInput.value < this.value) {
                    returnInput.value = '';
                }
            }
        });
    }
});

function showSection(section) {
    // Hide all sections
    document.querySelectorAll('.content-section').forEach(s => s.style.display = 'none');
    
    // Show selected section
    document.getElementById(section + '-section').style.display = 'block';
    
    // Update active menu item
    document.querySelectorAll('.sidebar-menu a').forEach(a => a.classList.remove('active'));
    event.target.classList.add('active');
}

function searchFlights() {
  const formData = new FormData(document.getElementById("flightSearchForm"))
  const searchParams = new URLSearchParams(formData)

  showSpinner("flightResults")

  fetch("../includes/search_flights.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      hideSpinner("flightResults")
      displayFlightResults(data)
    })
    .catch((error) => {
      hideSpinner("flightResults")
      console.error('Search error:', error)
      showAlert("Error searching flights. Please try again.", "error")
    })
}
</script>
</body>
</html>
