<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'digivote');

// API configuration for student data
define('STUDENT_API_URL', 'http://192.168.1.4/Enrollees/backend/api_get_students.php');
define('STUDENT_API_KEY', 'your_api_key_here'); // Replace with actual API key if needed

// Application settings
define('SITE_NAME', 'DigiVote');
define('ADMIN_EMAIL', 'admin@example.com');
define('VOTES_PER_STUDENT', 1);
define('ELECTION_START_DATE', '2025-05-23 00:00:00');
define('ELECTION_END_DATE', '2025-06-01 23:59:59');

// Time zone
date_default_timezone_set('Asia/Manila');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    // If database doesn't exist, create it
    if (strpos($e->getMessage(), "Unknown database") !== false) {
        $tempConn = new mysqli(DB_HOST, DB_USER, DB_PASS);
        $tempConn->query("CREATE DATABASE " . DB_NAME);
        $tempConn->close();
        
        // Try to connect again
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Initialize database tables
        require_once dirname(__FILE__) . '/init_db.php';
    } else {
        die("Database connection error: " . $e->getMessage());
    }
}
