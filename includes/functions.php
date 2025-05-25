<?php
/**
 * DigiVote - Student Voting System
 * Core functions file
 */

// Check if database connection exists
if (!isset($conn)) {
    require_once dirname(__FILE__) . '/../config/config.php';
}

/**
 * Fetch student data from the API
 * 
 * @param string $studentId The student ID to fetch
 * @return array|false Student data or false if not found
 */
function getStudentFromAPI($studentId) {
    $url = STUDENT_API_URL . '/' . urlencode($studentId);
    
    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n" .
                        "X-API-Key: " . STUDENT_API_KEY . "\r\n",
            'method' => 'GET'
        ]
    ];
    
    $context = stream_context_create($options);
    
    // For testing purposes, we'll simulate API response
    // In production, uncomment the file_get_contents line and remove the mock data
    // $response = file_get_contents($url, false, $context);
    
    // Mock API response for testing
    if ($studentId) {
        $mockData = [
            'id' => $studentId,
            'name' => 'Student ' . $studentId,
            'email' => 'student' . $studentId . '@example.com',
            'department' => 'Computer Science',
            'year_level' => rand(1, 4),
            'status' => 'Active'
        ];
        
        return $mockData;
    }
    
    return false;
    
    // Uncomment for production use with real API
    /*
    if ($response === false) {
        return false;
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['error']) || !isset($data['id'])) {
        return false;
    }
    
    return $data;
    */
}

/**
 * Check if a student exists in the API
 * 
 * @param string $studentId The student ID to check
 * @return bool True if student exists, false otherwise
 */
function checkStudentExistsInAPI($studentId) {
    $student = getStudentFromAPI($studentId);
    return ($student !== false);
}

/**
 * Check if a student is already registered in the system
 * 
 * @param string $studentId The student ID to check
 * @return bool True if student is registered, false otherwise
 */
function isStudentRegistered($studentId) {
    global $conn;
    
    $studentId = $conn->real_escape_string($studentId);
    $query = "SELECT id FROM students WHERE id = '$studentId'";
    $result = $conn->query($query);
    
    return ($result->num_rows > 0);
}

/**
 * Register a new student
 * 
 * @param string $studentId The student ID
 * @param string $name The student's name
 * @param string $email The student's email
 * @param string $password The student's password (will be hashed)
 * @return bool True if registration successful, false otherwise
 */
function registerStudent($studentId, $name, $email, $password) {
    global $conn;
    
    $studentId = $conn->real_escape_string($studentId);
    $name = $conn->real_escape_string($name);
    $email = $conn->real_escape_string($email);
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $query = "INSERT INTO students (id, name, email, password) 
              VALUES ('$studentId', '$name', '$email', '$hashedPassword')";
    
    return $conn->query($query);
}

/**
 * Verify student credentials for login
 * 
 * @param string $studentId The student ID
 * @param string $password The password to verify
 * @return array|false Student data if credentials are valid, false otherwise
 */
function verifyStudentCredentials($studentId, $password) {
    global $conn;
    
    $studentId = $conn->real_escape_string($studentId);
    $query = "SELECT * FROM students WHERE id = '$studentId'";
    $result = $conn->query($query);
    
    if ($result->num_rows === 1) {
        $student = $result->fetch_assoc();
        
        if (password_verify($password, $student['password'])) {
            return $student;
        }
    }
    
    return false;
}

/**
 * Get student information
 * 
 * @param string $studentId The student ID
 * @return array Student information
 */
function getStudentInfo($studentId) {
    global $conn;
    
    $studentId = $conn->real_escape_string($studentId);
    $query = "SELECT id, name, email, created_at FROM students WHERE id = '$studentId'";
    $result = $conn->query($query);
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return [
        'id' => $studentId,
        'name' => 'Unknown Student',
        'email' => '',
        'created_at' => ''
    ];
}

/**
 * Get all candidates
 * 
 * @return array Array of candidates
 */
function getAllCandidates() {
    global $conn;
    
    $query = "SELECT * FROM candidates ORDER BY position, name";
    $result = $conn->query($query);
    
    $candidates = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $candidates[] = $row;
        }
    }
    
    return $candidates;
}

/**
 * Check if a student has already voted
 * 
 * @param string $studentId The student ID
 * @return bool True if student has voted, false otherwise
 */
function hasStudentVoted($studentId) {
    global $conn;
    
    $studentId = $conn->real_escape_string($studentId);
    $query = "SELECT id FROM votes WHERE student_id = '$studentId'";
    $result = $conn->query($query);
    
    return ($result->num_rows >= VOTES_PER_STUDENT);
}

/**
 * Submit a vote
 * 
 * @param string $studentId The student ID
 * @param int $candidateId The candidate ID
 * @return bool True if vote was recorded, false otherwise
 */
function submitVote($studentId, $candidateId) {
    global $conn;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        $studentId = $conn->real_escape_string($studentId);
        $candidateId = (int)$candidateId;
        
        // Check if student has already voted
        if (hasStudentVoted($studentId)) {
            throw new Exception("Student has already voted");
        }
        
        // Insert vote
        $query = "INSERT INTO votes (student_id, candidate_id) VALUES ('$studentId', $candidateId)";
        if (!$conn->query($query)) {
            throw new Exception("Failed to insert vote: " . $conn->error);
        }
        
        // Update candidate vote count
        $query = "UPDATE candidates SET votes = votes + 1 WHERE id = $candidateId";
        if (!$conn->query($query)) {
            throw new Exception("Failed to update candidate votes: " . $conn->error);
        }
        
        // Commit transaction
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Vote submission error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get election results
 * 
 * @return array Array of candidates with vote counts
 */
function getElectionResults() {
    global $conn;
    
    $query = "SELECT id, name, position, votes FROM candidates ORDER BY position, votes DESC, name";
    $result = $conn->query($query);
    
    $results = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $results[] = $row;
        }
    }
    
    return $results;
}

/**
 * Get total number of votes cast
 * 
 * @return int Total number of votes
 */
function getTotalVotes() {
    global $conn;
    
    try {
        $query = "SELECT COUNT(*) as total FROM votes";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return (int)$row['total'];
        }
        
        return 0;
    } catch (Exception $e) {
        error_log("Error getting total votes: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get total number of students who have voted
 * 
 * @return int Total number of students who voted
 */
function getTotalVoters() {
    global $conn;
    
    try {
        $query = "SELECT COUNT(DISTINCT student_id) as total FROM votes";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return (int)$row['total'];
        }
        
        return 0;
    } catch (Exception $e) {
        error_log("Error getting total voters: " . $e->getMessage());
        return 0;
    }
}

/**
 * Check if election is currently active
 * 
 * @return bool True if election is active, false otherwise
 */
function isElectionActive() {
    $now = time();
    $startDate = strtotime(ELECTION_START_DATE);
    $endDate = strtotime(ELECTION_END_DATE);
    
    return ($now >= $startDate && $now <= $endDate);
}
