<?php
/**
 * DigiVote - Student Voting System
 * Partylist functions file
 */

// Check if database connection exists
if (!isset($conn)) {
    require_once dirname(__FILE__) . '/../config/config.php';
}

/**
 * Get all partylists
 * 
 * @return array Array of partylists
 */
function getAllPartylists() {
    global $conn;
    
    $query = "SELECT p.*, 
              (SELECT COUNT(*) FROM candidate_partylist WHERE partylist_id = p.id) as candidate_count 
              FROM partylists p 
              ORDER BY p.name";
    $result = $conn->query($query);
    
    $partylists = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $partylists[] = $row;
        }
    }
    
    return $partylists;
}

/**
 * Get a single partylist by ID
 * 
 * @param int $partylistId The partylist ID
 * @return array|false Partylist data or false if not found
 */
function getPartylistById($partylistId) {
    global $conn;
    
    $partylistId = (int)$partylistId;
    $query = "SELECT * FROM partylists WHERE id = $partylistId";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return false;
}

/**
 * Add a new partylist
 * 
 * @param array $data Partylist data (name, acronym, description, logo)
 * @return int|false The new partylist ID or false on failure
 */
function addPartylist($data) {
    global $conn;
    
    $name = $conn->real_escape_string($data['name']);
    $acronym = $conn->real_escape_string($data['acronym']);
    $description = $conn->real_escape_string($data['description']);
    $logo = $conn->real_escape_string($data['logo'] ?? '');
    
    $query = "INSERT INTO partylists (name, acronym, description, logo) 
              VALUES ('$name', '$acronym', '$description', '$logo')";
    
    if ($conn->query($query)) {
        return $conn->insert_id;
    }
    
    return false;
}

/**
 * Update an existing partylist
 * 
 * @param int $partylistId The partylist ID
 * @param array $data The data to update (name, acronym, description, logo)
 * @return bool True if update successful, false otherwise
 */
function updatePartylist($partylistId, $data) {
    global $conn;
    
    $partylistId = (int)$partylistId;
    $name = $conn->real_escape_string($data['name']);
    $acronym = $conn->real_escape_string($data['acronym']);
    $description = $conn->real_escape_string($data['description']);
    
    $logoSql = '';
    if (isset($data['logo']) && !empty($data['logo'])) {
        $logo = $conn->real_escape_string($data['logo']);
        $logoSql = ", logo = '$logo'";
    }
    
    $query = "UPDATE partylists 
              SET name = '$name', acronym = '$acronym', description = '$description'$logoSql 
              WHERE id = $partylistId";
    
    return $conn->query($query);
}

/**
 * Delete a partylist
 * 
 * @param int $partylistId The partylist ID
 * @return bool True if deletion successful, false otherwise
 */
function deletePartylist($partylistId) {
    global $conn;
    
    $partylistId = (int)$partylistId;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First remove all candidate associations
        $query = "DELETE FROM candidate_partylist WHERE partylist_id = $partylistId";
        if (!$conn->query($query)) {
            throw new Exception("Failed to delete candidate associations: " . $conn->error);
        }
        
        // Update candidates that had this partylist
        $query = "UPDATE candidates SET partylist_id = NULL WHERE partylist_id = $partylistId";
        if (!$conn->query($query)) {
            throw new Exception("Failed to update candidates: " . $conn->error);
        }
        
        // Then delete the partylist
        $query = "DELETE FROM partylists WHERE id = $partylistId";
        if (!$conn->query($query)) {
            throw new Exception("Failed to delete partylist: " . $conn->error);
        }
        
        // Commit transaction
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Partylist deletion error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all candidates in a partylist
 * 
 * @param int $partylistId The partylist ID
 * @return array Array of candidates in the partylist
 */
function getCandidatesByPartylist($partylistId) {
    global $conn;
    
    $partylistId = (int)$partylistId;
    $query = "SELECT c.*, cp.position_in_partylist 
              FROM candidates c 
              JOIN candidate_partylist cp ON c.id = cp.candidate_id 
              WHERE cp.partylist_id = $partylistId 
              ORDER BY cp.position_in_partylist, c.name";
    
    $result = $conn->query($query);
    $candidates = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $candidates[] = $row;
        }
    }
    
    return $candidates;
}

/**
 * Get candidates not in any partylist
 * 
 * @return array Array of candidates not in any partylist
 */
function getCandidatesNotInPartylist() {
    global $conn;
    
    $query = "SELECT c.* FROM candidates c 
              LEFT JOIN candidate_partylist cp ON c.id = cp.candidate_id 
              WHERE cp.id IS NULL 
              ORDER BY c.position, c.name";
    
    $result = $conn->query($query);
    $candidates = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $candidates[] = $row;
        }
    }
    
    return $candidates;
}

/**
 * Add a candidate to a partylist
 * 
 * @param int $candidateId The candidate ID
 * @param int $partylistId The partylist ID
 * @param string $positionInPartylist The candidate's position in the partylist
 * @return bool True if addition successful, false otherwise
 */
function addCandidateToPartylist($candidateId, $partylistId, $positionInPartylist = '') {
    global $conn;
    
    $candidateId = (int)$candidateId;
    $partylistId = (int)$partylistId;
    $positionInPartylist = $conn->real_escape_string($positionInPartylist);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First remove candidate from any existing partylist
        $query = "DELETE FROM candidate_partylist WHERE candidate_id = $candidateId";
        if (!$conn->query($query)) {
            throw new Exception("Failed to remove candidate from existing partylist: " . $conn->error);
        }
        
        // Add to new partylist
        $query = "INSERT INTO candidate_partylist (candidate_id, partylist_id, position_in_partylist) 
                  VALUES ($candidateId, $partylistId, '$positionInPartylist')";
        if (!$conn->query($query)) {
            throw new Exception("Failed to add candidate to partylist: " . $conn->error);
        }
        
        // Update candidate's partylist_id
        $query = "UPDATE candidates SET partylist_id = $partylistId WHERE id = $candidateId";
        if (!$conn->query($query)) {
            throw new Exception("Failed to update candidate's partylist: " . $conn->error);
        }
        
        // Commit transaction
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Add candidate to partylist error: " . $e->getMessage());
        return false;
    }
}

/**
 * Remove a candidate from a partylist
 * 
 * @param int $candidateId The candidate ID
 * @param int $partylistId The partylist ID
 * @return bool True if removal successful, false otherwise
 */
function removeCandidateFromPartylist($candidateId, $partylistId) {
    global $conn;
    
    $candidateId = (int)$candidateId;
    $partylistId = (int)$partylistId;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Remove from partylist
        $query = "DELETE FROM candidate_partylist 
                  WHERE candidate_id = $candidateId AND partylist_id = $partylistId";
        if (!$conn->query($query)) {
            throw new Exception("Failed to remove candidate from partylist: " . $conn->error);
        }
        
        // Update candidate's partylist_id
        $query = "UPDATE candidates SET partylist_id = NULL WHERE id = $candidateId";
        if (!$conn->query($query)) {
            throw new Exception("Failed to update candidate's partylist: " . $conn->error);
        }
        
        // Commit transaction
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Remove candidate from partylist error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update a candidate's position in a partylist
 * 
 * @param int $candidateId The candidate ID
 * @param int $partylistId The partylist ID
 * @param string $positionInPartylist The candidate's position in the partylist
 * @return bool True if update successful, false otherwise
 */
function updateCandidatePositionInPartylist($candidateId, $partylistId, $positionInPartylist) {
    global $conn;
    
    $candidateId = (int)$candidateId;
    $partylistId = (int)$partylistId;
    $positionInPartylist = $conn->real_escape_string($positionInPartylist);
    
    $query = "UPDATE candidate_partylist 
              SET position_in_partylist = '$positionInPartylist' 
              WHERE candidate_id = $candidateId AND partylist_id = $partylistId";
    
    return $conn->query($query);
}
