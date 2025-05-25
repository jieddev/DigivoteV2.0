<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Initialize variables
$message = '';
$editCandidate = null;

// Get all candidates
$candidates = getAllCandidates();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new candidate
    if (isset($_POST['action']) && $_POST['action'] === 'add_candidate') {
        $name = trim($_POST['name']);
        $position = trim($_POST['position']);
        $description = trim($_POST['description']);
        
        // Handle photo upload
        $photo = '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/images/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = time() . '_' . basename($_FILES['photo']['name']);
            $uploadFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadFile)) {
                $photo = 'assets/images/' . $fileName;
            }
        }
        
        // Insert candidate into database
        $query = "INSERT INTO candidates (name, position, description, photo) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssss", $name, $position, $description, $photo);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Candidate added successfully!</div>';
            // Refresh candidates list
            $candidates = getAllCandidates();
        } else {
            $message = '<div class="alert alert-danger">Failed to add candidate: ' . $conn->error . '</div>';
        }
        
        $stmt->close();
    }
    
    // Update existing candidate
    elseif (isset($_POST['action']) && $_POST['action'] === 'update_candidate') {
        $id = (int)$_POST['candidate_id'];
        $name = trim($_POST['name']);
        $position = trim($_POST['position']);
        $description = trim($_POST['description']);
        
        // Check if a new photo was uploaded
        $photoSql = '';
        $photo = '';
        
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/images/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = time() . '_' . basename($_FILES['photo']['name']);
            $uploadFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadFile)) {
                $photo = 'assets/images/' . $fileName;
                $photoSql = ', photo = ?';
            }
        }
        
        // Update candidate in database
        if (!empty($photoSql)) {
            $query = "UPDATE candidates SET name = ?, position = ?, description = ? $photoSql WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssi", $name, $position, $description, $id);
            
            if (!empty($photo)) {
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssssi", $name, $position, $description, $photo, $id);
            }
        } else {
            $query = "UPDATE candidates SET name = ?, position = ?, description = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssi", $name, $position, $description, $id);
        }
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Candidate updated successfully!</div>';
            // Refresh candidates list
            $candidates = getAllCandidates();
        } else {
            $message = '<div class="alert alert-danger">Failed to update candidate: ' . $conn->error . '</div>';
        }
        
        $stmt->close();
    }
    
    // Delete candidate
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete_candidate') {
        $id = (int)$_POST['candidate_id'];
        
        // Delete candidate from database
        $query = "DELETE FROM candidates WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Candidate deleted successfully!</div>';
            // Refresh candidates list
            $candidates = getAllCandidates();
        } else {
            $message = '<div class="alert alert-danger">Failed to delete candidate: ' . $conn->error . '</div>';
        }
        
        $stmt->close();
    }
}

// Handle GET requests for editing
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Get candidate details
    $query = "SELECT * FROM candidates WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $editCandidate = $result->fetch_assoc();
    }
    
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Candidates - DigiVote Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">DigiVote Admin</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item active">
                        <a class="nav-link" href="candidates.php">Candidates</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="students.php">Student List</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="partylists.php">Partylist</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2>Manage Candidates</h2>
                
                <?php echo $message; ?>
                
                <!-- Candidate Form -->
                <div class="card mt-4">
                    <div class="card-header bg-primary text-white">
                        <h4><?php echo $editCandidate ? 'Edit Candidate' : 'Add New Candidate'; ?></h4>
                    </div>
                    <div class="card-body">
                        <form method="post" action="" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="<?php echo $editCandidate ? 'update_candidate' : 'add_candidate'; ?>">
                            <?php if ($editCandidate): ?>
                                <input type="hidden" name="candidate_id" value="<?php echo $editCandidate['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label for="name">Candidate Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo $editCandidate ? htmlspecialchars($editCandidate['name']) : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="position">Position</label>
                                <input type="text" class="form-control" id="position" name="position" value="<?php echo $editCandidate ? htmlspecialchars($editCandidate['position']) : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo $editCandidate ? htmlspecialchars($editCandidate['description']) : ''; ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="photo">Photo</label>
                                <input type="file" class="form-control-file" id="photo" name="photo">
                                <?php if ($editCandidate && !empty($editCandidate['photo'])): ?>
                                    <div class="mt-2">
                                        <img src="../<?php echo htmlspecialchars($editCandidate['photo']); ?>" alt="Current photo" class="img-thumbnail" style="max-height: 100px;">
                                        <p class="text-muted">Current photo. Upload a new one to replace it.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary"><?php echo $editCandidate ? 'Update Candidate' : 'Add Candidate'; ?></button>
                                <?php if ($editCandidate): ?>
                                    <a href="candidates.php" class="btn btn-secondary">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Candidates List -->
                <div class="card mt-4">
                    <div class="card-header bg-dark text-white">
                        <h4>Current Candidates</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($candidates)): ?>
                            <p class="text-center">No candidates found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Photo</th>
                                            <th>Name</th>
                                            <th>Position</th>
                                            <th>Description</th>
                                            <th>Votes</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($candidates as $candidate): ?>
                                            <tr>
                                                <td><?php echo $candidate['id']; ?></td>
                                                <td>
                                                    <?php if (!empty($candidate['photo'])): ?>
                                                        <img src="../<?php echo htmlspecialchars($candidate['photo']); ?>" alt="<?php echo htmlspecialchars($candidate['name']); ?>" class="img-thumbnail" style="max-height: 50px;">
                                                    <?php else: ?>
                                                        <span class="text-muted">No photo</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($candidate['name']); ?></td>
                                                <td><?php echo htmlspecialchars($candidate['position']); ?></td>
                                                <td>
                                                    <?php 
                                                    $desc = htmlspecialchars($candidate['description']);
                                                    echo strlen($desc) > 50 ? substr($desc, 0, 50) . '...' : $desc; 
                                                    ?>
                                                </td>
                                                <td><?php echo $candidate['votes']; ?></td>
                                                <td>
                                                    <a href="candidates.php?action=edit&id=<?php echo $candidate['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal<?php echo $candidate['id']; ?>">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                    
                                                    <!-- Delete Confirmation Modal -->
                                                    <div class="modal fade" id="deleteModal<?php echo $candidate['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel<?php echo $candidate['id']; ?>" aria-hidden="true">
                                                        <div class="modal-dialog" role="document">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="deleteModalLabel<?php echo $candidate['id']; ?>">Confirm Delete</h5>
                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                        <span aria-hidden="true">&times;</span>
                                                                    </button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    Are you sure you want to delete candidate <strong><?php echo htmlspecialchars($candidate['name']); ?></strong>?
                                                                    <p class="text-danger mt-2">This action cannot be undone.</p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                    <form method="post" action="">
                                                                        <input type="hidden" name="action" value="delete_candidate">
                                                                        <input type="hidden" name="candidate_id" value="<?php echo $candidate['id']; ?>">
                                                                        <button type="submit" class="btn btn-danger">Delete</button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <p class="mb-0">DigiVote Admin &copy; <?php echo date('Y'); ?> - Student Voting System</p>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
