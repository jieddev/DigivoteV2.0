# DigiVote - Student Voting System

DigiVote is a web-based voting system that uses an API for getting student data. It allows students to register, login, and vote for candidates in an election.

## Features

- Student registration and login
- API integration for student data verification
- Secure voting process
- Real-time election results with charts
- Admin dashboard for managing candidates and monitoring results

## Requirements

- PHP 7.2 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- XAMPP (recommended for local development)

## Installation

1. Clone or download this repository to your web server's document root (e.g., `htdocs` folder for XAMPP)
2. Create a MySQL database named `digivote` (or update the database name in `config/config.php`)
3. Configure your database connection settings in `config/config.php`
4. Update the API configuration in `config/config.php` with your actual API endpoint and key
5. Access the application through your web browser (e.g., `http://localhost/DigivoteV2.0`)

## First-time Setup

The system will automatically create the necessary database tables on first run. A default admin account will be created with the following credentials:

- Username: admin
- Password: admin123

It's recommended to change the admin password after the first login.

## API Integration

The system is designed to integrate with an external API for student data verification. You need to:

1. Update the `STUDENT_API_URL` and `STUDENT_API_KEY` constants in `config/config.php`
2. Modify the `getStudentFromAPI()` function in `includes/functions.php` to match your API's response format

For testing purposes, the system includes a mock API response.

## Directory Structure

- `/admin` - Admin dashboard and management pages
- `/assets` - CSS, JavaScript, and image files
- `/config` - Configuration files
- `/includes` - Core functions and utilities

## Security Considerations

- Update the default admin credentials
- Use HTTPS for production environments
- Regularly backup your database
- Keep PHP and all dependencies updated

## License

This project is open-source and available for educational and non-commercial use.
