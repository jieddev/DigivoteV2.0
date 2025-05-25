<?php
// This script updates the database to add partylist functionality

// Check if connection is established
if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once 'config.php';
}

// Create partylists table
$conn->query("CREATE TABLE IF NOT EXISTS partylists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    acronym VARCHAR(20),
    description TEXT,
    logo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Create candidate_partylist table for the many-to-many relationship
$conn->query("CREATE TABLE IF NOT EXISTS candidate_partylist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidate_id INT NOT NULL,
    partylist_id INT NOT NULL,
    position_in_partylist VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
    FOREIGN KEY (partylist_id) REFERENCES partylists(id) ON DELETE CASCADE,
    UNIQUE KEY unique_candidate_partylist (candidate_id, partylist_id)
)");

// Add partylist_id column to candidates table if it doesn't exist
$result = $conn->query("SHOW COLUMNS FROM candidates LIKE 'partylist_id'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE candidates ADD COLUMN partylist_id INT NULL");
}

// Insert sample partylists
$samplePartylists = [
    ['United Student Alliance', 'USA', 'Advocating for student rights and welfare.', 'assets/images/party1.jpg'],
    ['Progressive Student Coalition', 'PSC', 'Promoting academic excellence and inclusive education.', 'assets/images/party2.jpg']
];

// Check if partylists exist before inserting
$partylistCheck = $conn->query("SELECT id FROM partylists LIMIT 1");
if ($partylistCheck->num_rows == 0) {
    foreach ($samplePartylists as $partylist) {
        $name = $conn->real_escape_string($partylist[0]);
        $acronym = $conn->real_escape_string($partylist[1]);
        $description = $conn->real_escape_string($partylist[2]);
        $logo = $conn->real_escape_string($partylist[3]);
        
        $conn->query("INSERT INTO partylists (name, acronym, description, logo) 
                     VALUES ('$name', '$acronym', '$description', '$logo')");
    }
}

echo "Database updated with partylist functionality successfully!";
