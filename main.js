// Main JavaScript functionality for FlyFlex

document.addEventListener("DOMContentLoaded", () => {
  initializeApp()
})

function initializeApp() {
  // Initialize date inputs with today's date as minimum
  const dateInputs = document.querySelectorAll('input[type="date"]')
  const today = new Date().toISOString().split("T")[0]
  dateInputs.forEach((input) => {
    input.min = today
  })

  // Set up departure and return date relationship
  setupDateInputs()

  // Initialize form validations
  initializeFormValidations()

  // Initialize search functionality
  initializeFlightSearch()

  // Initialize booking functionality
  initializeBookingFunctions()

  // Initialize admin functions
  initializeAdminFunctions()
}

function setupDateInputs() {
  const departureInput = document.getElementById("departure_date")
  const returnInput = document.getElementById("return_date")

  if (departureInput && returnInput) {
    departureInput.addEventListener("change", function () {
      const tripType = document.querySelector('input[name="trip_type"]:checked')?.value
      if (tripType === "round-trip" && !returnInput.disabled) {
        returnInput.min = this.value
        // Clear return date if it's before departure date
        if (returnInput.value && returnInput.value < this.value) {
          returnInput.value = ""
        }
      }
    })
  }
}

function initializeFormValidations() {
  const forms = document.querySelectorAll("form")
  forms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      if (!validateForm(this)) {
        e.preventDefault()
      }
    })
  })
}

function validateForm(form) {
  const requiredFields = form.querySelectorAll("[required]")
  let isValid = true

  requiredFields.forEach((field) => {
    if (!field.value.trim()) {
      showFieldError(field, "This field is required")
      isValid = false
    } else {
      clearFieldError(field)
    }
  })

  // Email validation
  const emailFields = form.querySelectorAll('input[type="email"]')
  emailFields.forEach((field) => {
    if (field.value && !isValidEmail(field.value)) {
      showFieldError(field, "Please enter a valid email address")
      isValid = false
    }
  })

  // Password validation
  const passwordFields = form.querySelectorAll('input[type="password"]')
  passwordFields.forEach((field) => {
    if (field.value && field.value.length < 6) {
      showFieldError(field, "Password must be at least 6 characters")
      isValid = false
    }
  })

  return isValid
}

function showFieldError(field, message) {
  clearFieldError(field)
  const errorDiv = document.createElement("div")
  errorDiv.className = "field-error"
  errorDiv.style.color = "#dc3545"
  errorDiv.style.fontSize = "0.875rem"
  errorDiv.style.marginTop = "0.25rem"
  errorDiv.textContent = message
  field.parentNode.appendChild(errorDiv)
  field.style.borderColor = "#dc3545"
}

function clearFieldError(field) {
  const existingError = field.parentNode.querySelector(".field-error")
  if (existingError) {
    existingError.remove()
  }
  field.style.borderColor = "#e1e5e9"
}

function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

function initializeFlightSearch() {
  const searchForm = document.getElementById("flightSearchForm")
  if (searchForm) {
    searchForm.addEventListener("submit", (e) => {
      e.preventDefault()
      searchFlights()
    })
  }
}

function searchFlights() {
  console.log("Search flights function called")

  const form = document.getElementById("flightSearchForm")
  if (!form) {
    console.error("Search form not found")
    showAlert("Search form not found", "error")
    return
  }

  const formData = new FormData(form)

  // Log form data for debugging
  console.log("Form data:")
  for (const [key, value] of formData.entries()) {
    console.log(key, value)
  }

  // Determine the correct path based on current location
  const currentPath = window.location.pathname
  let searchUrl = "includes/search_flights.php"

  if (currentPath.includes("/user/")) {
    searchUrl = "../includes/search_flights.php"
  } else if (currentPath.includes("/admin/")) {
    searchUrl = "../includes/search_flights.php"
  }

  console.log("Using search URL:", searchUrl)

  showSpinner("flightResults")

  fetch(searchUrl, {
    method: "POST",
    body: formData,
  })
    .then((response) => {
      console.log("Response status:", response.status)
      console.log("Response headers:", response.headers)

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      return response.text() // Get as text first to see what we're receiving
    })
    .then((text) => {
      console.log("Raw response:", text)

      try {
        const data = JSON.parse(text)
        console.log("Parsed JSON:", data)
        hideSpinner("flightResults")
        displayFlightResults(data)
      } catch (e) {
        console.error("JSON parse error:", e)
        console.error("Response text:", text)
        hideSpinner("flightResults")
        showAlert("Invalid response from server", "error")
        document.getElementById("flightResults").innerHTML = `
          <div class="no-results">
            <h3>Server Error</h3>
            <p>The server returned an invalid response. Please check the console for details.</p>
            <pre style="background: #f5f5f5; padding: 10px; font-size: 12px; overflow: auto;">${text}</pre>
          </div>
        `
      }
    })
    .catch((error) => {
      hideSpinner("flightResults")
      console.error("Search error:", error)
      showAlert("Error searching flights: " + error.message, "error")
      document.getElementById("flightResults").innerHTML = `
        <div class="no-results">
          <h3>Connection Error</h3>
          <p>Unable to connect to the server. Please check your connection and try again.</p>
          <p><strong>Error:</strong> ${error.message}</p>
        </div>
      `
    })
}

function displayFlightResults(data) {
  console.log("Displaying flight results:", data)

  const resultsContainer = document.getElementById("flightResults")

  if (!data.success) {
    resultsContainer.innerHTML = `
      <div class="no-results">
        <h3>Search Error</h3>
        <p>${data.message || "An error occurred while searching for flights."}</p>
      </div>
    `
    return
  }

  if (!data.outbound || data.outbound.length === 0) {
    resultsContainer.innerHTML = `
      <div class="no-results">
        <h3>No flights found</h3>
        <p>${data.message || "Try adjusting your search criteria."}</p>
        <p><small>Search was for ${data.passengers} passenger(s) in ${data.class} class</small></p>
      </div>
    `
    return
  }

  let resultsHTML = `
    <div class="search-summary">
      <h3>Search Results</h3>
      <p>${data.passengers} passenger(s) • ${data.class.charAt(0).toUpperCase() + data.class.slice(1)} Class</p>
    </div>
  `

  // Display outbound flights
  resultsHTML += `<div class="flight-section">
    <h4>Outbound Flights (${data.outbound.length} found)</h4>
    <div class="flights-list">`

  resultsHTML += data.outbound
    .map(
      (flight) => `
        <div class="flight-card">
          <div class="flight-header">
            <div class="airline-info">
              <strong>${flight.airline_name}</strong>
              <span>${flight.flight_number}</span>
              <span class="class-badge">${flight.class}</span>
            </div>
            <div class="flight-price">
              <div class="price-per-person">$${Number.parseFloat(flight.price).toFixed(2)} per person</div>
              <div class="total-price"><strong>Total: $${Number.parseFloat(flight.total_price).toFixed(2)}</strong></div>
              <div class="passengers-info">${flight.passengers} passenger(s)</div>
            </div>
          </div>
          <div class="flight-details">
            <div class="departure">
              <div class="city">${flight.departure_city}</div>
              <div class="time">${formatTime(flight.departure_time)}</div>
              <div class="date">${formatDate(flight.departure_date)}</div>
            </div>
            <div class="flight-route">
              <div class="route-line"></div>
            </div>
            <div class="arrival">
              <div class="city">${flight.arrival_city}</div>
              <div class="time">${formatTime(flight.arrival_time)}</div>
              <div class="date">${formatDate(flight.arrival_date)}</div>
            </div>
          </div>
          <div class="flight-footer">
            <div class="seats-available">${flight.available_seats} seats available</div>
            <button class="btn btn-primary" onclick="selectFlight(${flight.id}, 'outbound', ${flight.total_price}, '${flight.airline_name}', '${flight.flight_number}', '${flight.departure_city}', '${flight.arrival_city}', '${flight.departure_date}', '${flight.departure_time}', '${flight.class}', ${flight.passengers})">
              Book This Flight
            </button>
          </div>
        </div>
      `,
    )
    .join("")

  resultsHTML += `</div></div>`

  // Display return flights if round trip
  if (data.trip_type === "round-trip" && data.return && data.return.length > 0) {
    resultsHTML += `<div class="flight-section">
      <h4>Return Flights (${data.return.length} found)</h4>
      <div class="flights-list">`

    resultsHTML += data.return
      .map(
        (flight) => `
          <div class="flight-card">
            <div class="flight-header">
              <div class="airline-info">
                <strong>${flight.airline_name}</strong>
                <span>${flight.flight_number}</span>
                <span class="class-badge">${flight.class}</span>
              </div>
              <div class="flight-price">
                <div class="price-per-person">$${Number.parseFloat(flight.price).toFixed(2)} per person</div>
                <div class="total-price"><strong>Total: $${Number.parseFloat(flight.total_price).toFixed(2)}</strong></div>
                <div class="passengers-info">${flight.passengers} passenger(s)</div>
              </div>
            </div>
            <div class="flight-details">
              <div class="departure">
                <div class="city">${flight.departure_city}</div>
                <div class="time">${formatTime(flight.departure_time)}</div>
                <div class="date">${formatDate(flight.departure_date)}</div>
              </div>
              <div class="flight-route">
                <div class="route-line"></div>
              </div>
              <div class="arrival">
                <div class="city">${flight.arrival_city}</div>
                <div class="time">${formatTime(flight.arrival_time)}</div>
                <div class="date">${formatDate(flight.arrival_date)}</div>
              </div>
            </div>
            <div class="flight-footer">
              <div class="seats-available">${flight.available_seats} seats available</div>
              <button class="btn btn-primary" onclick="selectFlight(${flight.id}, 'return', ${flight.total_price}, '${flight.airline_name}', '${flight.flight_number}', '${flight.departure_city}', '${flight.arrival_city}', '${flight.departure_date}', '${flight.departure_time}', '${flight.class}', ${flight.passengers})">
                Book This Flight
              </button>
            </div>
          </div>
        `,
      )
      .join("")

    resultsHTML += `</div></div>`
  }

  resultsContainer.innerHTML = resultsHTML
}

// Enhanced flight selection function
function selectFlight(
  flightId,
  type,
  totalPrice,
  airlineName,
  flightNumber,
  departureCity,
  arrivalCity,
  departureDate,
  departureTime,
  flightClass,
  passengers,
) {
  // Store selected flight information
  if (type === "outbound") {
    sessionStorage.setItem("selectedOutboundFlight", flightId)
    sessionStorage.setItem("outboundPrice", totalPrice)
  } else {
    sessionStorage.setItem("selectedReturnFlight", flightId)
    sessionStorage.setItem("returnPrice", totalPrice)
  }

  // Show booking modal with flight details
  showBookingModal(
    flightId,
    totalPrice,
    airlineName,
    flightNumber,
    departureCity,
    arrivalCity,
    departureDate,
    departureTime,
    flightClass,
    passengers,
  )
}

function showBookingModal(
  flightId,
  totalPrice,
  airlineName,
  flightNumber,
  departureCity,
  arrivalCity,
  departureDate,
  departureTime,
  flightClass,
  passengers,
) {
  const modal = document.getElementById("bookingModal")
  if (modal) {
    // Set form values
    document.getElementById("selectedFlightId").value = flightId
    document.getElementById("selectedPassengers").value = passengers
    document.getElementById("selectedClass").value = flightClass
    document.getElementById("selectedTotalPrice").value = totalPrice

    // Display flight information
    const flightInfo = document.getElementById("selectedFlightInfo")
    if (flightInfo) {
      flightInfo.innerHTML = `
        <h4>Flight Details</h4>
        <p><strong>Flight:</strong> ${airlineName} ${flightNumber}</p>
        <p><strong>Route:</strong> ${departureCity} → ${arrivalCity}</p>
        <p><strong>Date:</strong> ${formatDate(departureDate)} at ${formatTime(departureTime)}</p>
        <p><strong>Class:</strong> ${flightClass}</p>
        <p><strong>Passengers:</strong> ${passengers}</p>
        <p><strong>Total Price:</strong> $${Number.parseFloat(totalPrice).toFixed(2)}</p>
      `
    }

    modal.style.display = "block"
  }
}

function initializeBookingFunctions() {
  // Initialize booking modal if exists
  const bookingModal = document.getElementById("bookingModal")
  if (bookingModal) {
    const closeBtn = bookingModal.querySelector(".close")
    if (closeBtn) {
      closeBtn.addEventListener("click", () => {
        bookingModal.style.display = "none"
      })
    }

    window.addEventListener("click", (e) => {
      if (e.target === bookingModal) {
        bookingModal.style.display = "none"
      }
    })
  }
}

function confirmBooking() {
  const form = document.getElementById("bookingForm")
  const formData = new FormData(form)

  fetch("includes/process_booking.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showAlert(`Booking successful! Your booking reference is: ${data.booking_reference}`, "success")
        document.getElementById("bookingModal").style.display = "none"
        // Refresh the page or update bookings list
        setTimeout(() => {
          location.reload()
        }, 2000)
      } else {
        showAlert(data.message, "error")
      }
    })
    .catch((error) => {
      showAlert("Booking failed. Please try again.", "error")
    })
}

function cancelBooking(bookingId) {
  if (!confirm("Are you sure you want to cancel this booking?")) {
    return
  }

  fetch("includes/cancel_booking.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ booking_id: bookingId }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showAlert("Booking cancelled successfully", "success")
        setTimeout(() => {
          location.reload()
        }, 1000)
      } else {
        showAlert(data.message, "error")
      }
    })
    .catch((error) => {
      showAlert("Error cancelling booking. Please try again.", "error")
    })
}

function initializeAdminFunctions() {
  // Initialize admin filters
  const statusFilter = document.getElementById("statusFilter")
  if (statusFilter) {
    statusFilter.addEventListener("change", filterBookings)
  }

  const dateFilter = document.getElementById("dateFilter")
  if (dateFilter) {
    dateFilter.addEventListener("change", filterBookings)
  }
}

function filterBookings() {
  const status = document.getElementById("statusFilter")?.value || ""
  const date = document.getElementById("dateFilter")?.value || ""

  const formData = new FormData()
  formData.append("status", status)
  formData.append("date", date)

  fetch("includes/filter_bookings.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      updateBookingsTable(data)
    })
    .catch((error) => {
      showAlert("Error filtering bookings", "error")
    })
}

function updateBookingStatus(bookingId, status) {
  fetch("includes/update_booking_status.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      booking_id: bookingId,
      status: status,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showAlert("Booking status updated successfully", "success")
        setTimeout(() => {
          location.reload()
        }, 1000)
      } else {
        showAlert(data.message, "error")
      }
    })
    .catch((error) => {
      showAlert("Error updating booking status", "error")
    })
}

function updateBookingsTable(bookings) {
  const bookingsTable = document.getElementById("bookingsTable")
  if (bookingsTable) {
    const bookingsHTML = bookings
      .map(
        (booking) => `
          <tr>
            <td>${booking.id}</td>
            <td>${booking.flight_number}</td>
            <td>${booking.customer_name}</td>
            <td>${formatDate(booking.booking_date)}</td>
            <td>${booking.status}</td>
            <td>
              <button class="btn btn-primary" onclick="updateBookingStatus(${booking.id}, 'confirmed')">Confirm</button>
              <button class="btn btn-danger" onclick="cancelBooking(${booking.id})">Cancel</button>
            </td>
          </tr>
        `,
      )
      .join("")

    bookingsTable.innerHTML = bookingsHTML
  }
}

// Utility functions
function formatTime(time) {
  return new Date(`2000-01-01 ${time}`).toLocaleTimeString("en-US", {
    hour: "2-digit",
    minute: "2-digit",
    hour12: true,
  })
}

function formatDate(date) {
  return new Date(date).toLocaleDateString("en-US", {
    weekday: "short",
    month: "short",
    day: "numeric",
  })
}

function showSpinner(containerId) {
  const container = document.getElementById(containerId)
  if (container) {
    container.innerHTML = '<div class="spinner"></div>'
  }
}

function hideSpinner(containerId) {
  const container = document.getElementById(containerId)
  if (container) {
    const spinner = container.querySelector(".spinner")
    if (spinner) {
      spinner.remove()
    }
  }
}

function showAlert(message, type = "info") {
  const alertClass = type === "error" ? "alert-error" : "alert-success"
  const alertHTML = `
    <div class="alert ${alertClass}" id="alert">
      ${message}
      <button type="button" onclick="closeAlert()" style="float: right; background: none; border: none; font-size: 1.2rem; cursor: pointer;">&times;</button>
    </div>
  `

  // Insert alert at the top of the page
  const body = document.body
  const tempDiv = document.createElement("div")
  tempDiv.innerHTML = alertHTML
  body.insertBefore(tempDiv.firstElementChild, body.firstChild)

  // Auto-hide after 5 seconds
  setTimeout(() => {
    closeAlert()
  }, 5000)
}

function closeAlert() {
  const alert = document.getElementById("alert")
  if (alert) {
    alert.remove()
  }
}

// Export functions for global use
window.bookFlight = selectFlight
window.confirmBooking = confirmBooking
window.cancelBooking = cancelBooking
window.updateBookingStatus = updateBookingStatus
window.closeAlert = closeAlert
window.updateBookingsTable = updateBookingsTable
