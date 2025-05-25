<?php
/**
 * DigiVote - Student Voting System
 * Admin functions file
 */

// Check if database connection exists
if (!isset($conn)) {
    require_once dirname(__FILE__) . '/../config/config.php';
}

/**
 * Get all registered students from the database
 * 
 * @param string $search Optional search term
 * @param string $orderBy Optional column to order by
 * @param string $order Optional order direction (ASC or DESC)
 * @return array Array of students
 */
function getAllRegisteredStudents($search = '', $orderBy = 'id', $order = 'ASC') {
    global $conn;
    
    $query = "SELECT s.*, 
              (SELECT COUNT(*) FROM votes WHERE student_id = s.id) as has_voted 
              FROM students s";
    
    // Add search condition if provided
    if (!empty($search)) {
        $search = $conn->real_escape_string($search);
        $query .= " WHERE s.id LIKE '%$search%' OR s.name LIKE '%$search%' OR s.email LIKE '%$search%'";
    }
    
    // Add order by clause
    $validColumns = ['id', 'name', 'email', 'created_at'];
    $orderBy = in_array($orderBy, $validColumns) ? $orderBy : 'id';
    $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
    
    $query .= " ORDER BY s.$orderBy $order";
    
    $result = $conn->query($query);
    $students = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
    
    return $students;
}

/**
 * Get a single student by ID
 * 
 * @param string $studentId The student ID
 * @return array|false Student data or false if not found
 */
function getStudentById($studentId) {
    global $conn;
    
    $studentId = $conn->real_escape_string($studentId);
    $query = "SELECT * FROM students WHERE id = '$studentId'";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return false;
}

/**
 * Update student information
 * 
 * @param string $studentId The student ID
 * @param array $data The data to update (name, email)
 * @return bool True if update successful, false otherwise
 */
function updateStudent($studentId, $data) {
    global $conn;
    
    $studentId = $conn->real_escape_string($studentId);
    $name = $conn->real_escape_string($data['name']);
    $email = $conn->real_escape_string($data['email']);
    
    $query = "UPDATE students SET name = '$name', email = '$email' WHERE id = '$studentId'";
    return $conn->query($query);
}

/**
 * Reset student password
 * 
 * @param string $studentId The student ID
 * @param string $newPassword The new password (will be hashed)
 * @return bool True if reset successful, false otherwise
 */
function resetStudentPassword($studentId, $newPassword) {
    global $conn;
    
    $studentId = $conn->real_escape_string($studentId);
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $query = "UPDATE students SET password = '$hashedPassword' WHERE id = '$studentId'";
    return $conn->query($query);
}

/**
 * Delete a student
 * 
 * @param string $studentId The student ID
 * @return bool True if deletion successful, false otherwise
 */
function deleteStudent($studentId) {
    global $conn;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        $studentId = $conn->real_escape_string($studentId);
        
        // First delete votes by this student
        $query = "DELETE FROM votes WHERE student_id = '$studentId'";
        if (!$conn->query($query)) {
            throw new Exception("Failed to delete student votes: " . $conn->error);
        }
        
        // Then delete the student
        $query = "DELETE FROM students WHERE id = '$studentId'";
        if (!$conn->query($query)) {
            throw new Exception("Failed to delete student: " . $conn->error);
        }
        
        // Commit transaction
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Student deletion error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all students from the API with optional filtering
 * 
 * @param array $filters Optional filters (year_level, search, etc.)
 * @return array Array of students from API
 */
function getAllStudentsFromAPI($filters = []) {
    $apiUrl = STUDENT_API_URL;
    $queryParams = [];
    
    // Add filters to query parameters
    foreach ($filters as $key => $value) {
        if (!empty($value)) {
            $queryParams[] = $key . "=" . urlencode($value);
        }
    }
    
    if (!empty($queryParams)) {
        $apiUrl .= '?' . implode('&', $queryParams);
    }
    
    // For testing purposes, we'll simulate API response
    // In production, uncomment the file_get_contents line and remove the mock data
    // $response = file_get_contents($apiUrl);
    // $studentsData = json_decode($response, true);
    
    // Mock API response for testing
    $mockStudents = [];
    $totalStudents = 50;
    
    for ($i = 1; $i <= $totalStudents; $i++) {
        $yearLevel = rand(1, 4);
        
        // Apply year level filter if set
        if (isset($filters['year_level']) && !empty($filters['year_level']) && $yearLevel != $filters['year_level']) {
            continue;
        }
        
        $gender = rand(0, 1) ? 'Male' : 'Female';
        $firstName = $gender === 'Male' ? 
            ['John', 'Michael', 'David', 'Robert', 'James'][rand(0, 4)] : 
            ['Mary', 'Jennifer', 'Lisa', 'Sarah', 'Emily'][rand(0, 4)];
        
        $lastName = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis'][rand(0, 7)];
        $middleName = chr(65 + rand(0, 25));
        
        $student = [
            'student_id' => '2025' . str_pad($i, 4, '0', STR_PAD_LEFT),
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'gender' => $gender,
            'year_level' => $yearLevel,
            'email' => strtolower($firstName) . '.' . strtolower($lastName) . '@example.com',
            'status' => rand(0, 10) > 1 ? 'Active' : 'Inactive'
        ];
        
        // Apply search filter if set
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $studentName = strtolower($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']);
            $studentId = strtolower($student['student_id']);
            $studentEmail = strtolower($student['email']);
            $studentGender = strtolower($student['gender']);
            $studentStatus = strtolower($student['status']);
            
            if (strpos($studentName, $search) === false && 
                strpos($studentId, $search) === false && 
                strpos($studentEmail, $search) === false && 
                strpos($studentGender, $search) === false && 
                strpos($studentStatus, $search) === false) {
                continue;
            }
        }
        
        $mockStudents[] = $student;
    }
    
    // Apply sorting if set
    if (isset($filters['order_by']) && !empty($filters['order_by'])) {
        $orderBy = $filters['order_by'];
        $order = isset($filters['order']) && strtoupper($filters['order']) === 'DESC' ? SORT_DESC : SORT_ASC;
        
        $sortColumn = array_column($mockStudents, $orderBy);
        array_multisort($sortColumn, $order, $mockStudents);
    }
    
    return [
        'status' => 'success',
        'count' => count($mockStudents),
        'data' => $mockStudents
    ];
}
