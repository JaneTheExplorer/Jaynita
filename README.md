# FlyFlex - Flight Booking System

A comprehensive flight booking system built with HTML, CSS, JavaScript, PHP, and MySQL.

## Features

### User Features
- User registration and authentication
- Flight search with real-time results
- Flight booking with passenger details
- Booking management (view, cancel bookings)
- Personal booking reports and statistics
- Responsive design for all devices

### Admin Features
- Admin dashboard with comprehensive analytics
- View all bookings with detailed information
- Manage booking statuses (pending, confirmed, cancelled)
- Filter bookings by status, date, and airline
- Revenue and destination analytics
- Real-time booking statistics

## Installation

### Prerequisites
- Web server (Apache/Nginx)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Modern web browser

### Setup Instructions

1. **Clone or download the project files**
   \`\`\`bash
   git clone <repository-url>
   cd flyflex
   \`\`\`

2. **Database Setup**
   - Create a MySQL database named `flyflex`
   - Import the SQL script from `scripts/database.sql`
   - Update database credentials in `config/database.php`

3. **Configure Web Server**
   - Point your web server document root to the project folder
   - Ensure PHP is properly configured
   - Enable URL rewriting if needed

4. **Set File Permissions**
   \`\`\`bash
   chmod 755 assets/
   chmod 644 assets/css/style.css
   chmod 644 assets/js/main.js
   \`\`\`

5. **Access the Application**
   - Open your web browser
   - Navigate to `http://localhost/flyflex`

## Default Admin Credentials
- **Username:** admin
- **Password:** password

## Directory Structure
\`\`\`
flyflex/
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── main.js
├── config/
│   └── database.php
├── includes/
│   ├── auth.php
│   ├── flight.php
│   ├── search_flights.php
│   ├── process_booking.php
│   ├── cancel_booking.php
│   ├── update_booking_status.php
│   └── logout.php
├── user/
│   ├── login.php
│   ├── signup.php
│   └── dashboard.php
├── admin/
│   ├── login.php
│   └── dashboard.php
├── scripts/
│   └── database.sql
├── index.html
└── README.md
\`\`\`

## Database Schema

### Tables
- **users**: User account information
- **admins**: Administrator accounts
- **airlines**: Airline information
- **flights**: Flight schedules and details
- **bookings**: User flight bookings

## Security Features
- Password hashing using PHP's password_hash()
- SQL injection prevention with prepared statements
- Session management for authentication
- Input validation and sanitization
- XSS protection with htmlspecialchars()

## Browser Compatibility
- Chrome 60+
- Firefox 55+
- Safari 11+
- Edge 79+

## Contributing
1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License
This project is licensed under the MIT License.

## Support
For support and questions, please contact the development team.
