<?php
// This script initializes the database tables for DigiVote

// Check if connection is established
if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once 'config.php';
}

// Create students table
$conn->query("CREATE TABLE IF NOT EXISTS students (
    id VARCHAR(20) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Create candidates table
$conn->query("CREATE TABLE IF NOT EXISTS candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    position VARCHAR(100) NOT NULL,
    description TEXT,
    photo VARCHAR(255),
    votes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Create votes table
$conn->query("CREATE TABLE IF NOT EXISTS votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    candidate_id INT NOT NULL,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (candidate_id) REFERENCES candidates(id),
    UNIQUE KEY unique_vote (student_id, candidate_id)
)");

// Create admin table
$conn->query("CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Insert default admin account
$adminUsername = 'admin';
$adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
$adminName = 'System Administrator';
$adminEmail = 'admin@example.com';

// Check if admin exists before inserting
$adminCheck = $conn->query("SELECT id FROM admins WHERE username = '$adminUsername'");
if ($adminCheck->num_rows == 0) {
    $conn->query("INSERT INTO admins (username, password, name, email) 
                 VALUES ('$adminUsername', '$adminPassword', '$adminName', '$adminEmail')");
}

// Insert sample candidates
$sampleCandidates = [
    ['John Doe', 'President', 'Experienced leader with a vision for the future.', 'assets/images/candidate1.jpg'],
    ['Jane Smith', 'President', 'Dedicated to improving student welfare and campus facilities.', 'assets/images/candidate2.jpg'],
    ['Michael Johnson', 'Vice President', 'Committed to transparency and student representation.', 'assets/images/candidate3.jpg'],
    ['Sarah Williams', 'Vice President', 'Advocate for inclusive education and student rights.', 'assets/images/candidate4.jpg']
];

// Check if candidates exist before inserting
$candidateCheck = $conn->query("SELECT id FROM candidates LIMIT 1");
if ($candidateCheck->num_rows == 0) {
    foreach ($sampleCandidates as $candidate) {
        $name = $conn->real_escape_string($candidate[0]);
        $position = $conn->real_escape_string($candidate[1]);
        $description = $conn->real_escape_string($candidate[2]);
        $photo = $conn->real_escape_string($candidate[3]);
        
        $conn->query("INSERT INTO candidates (name, position, description, photo) 
                     VALUES ('$name', '$position', '$description', '$photo')");
    }
}

echo "Database initialized successfully!";
