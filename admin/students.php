<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/admin_functions.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Initialize variables
$message = '';
$editStudent = null;

// Initialize filter variables
$search = isset($_GET['search']) ? $_GET['search'] : '';
$yearLevel = isset($_GET['year_level']) ? $_GET['year_level'] : '';
$orderBy = isset($_GET['order_by']) ? $_GET['order_by'] : 'student_id';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Get registered students from database
$registeredStudents = getAllRegisteredStudents($search, $orderBy, $order);

// Get students from API
$apiFilters = [
    'year_level' => $yearLevel,
    'search' => $search,
    'order_by' => $orderBy,
    'order' => $order
];
$apiStudents = getAllStudentsFromAPI($apiFilters);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new student manually
    if (isset($_POST['action']) && $_POST['action'] === 'add_student') {
        $studentId = trim($_POST['student_id']);
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        
        // Validate input
        if (empty($studentId) || empty($name) || empty($email) || empty($password)) {
            $message = '<div class="alert alert-danger">All fields are required</div>';
        } elseif (isStudentRegistered($studentId)) {
            $message = '<div class="alert alert-danger">Student ID already registered</div>';
        } else {
            // Register the student
            if (registerStudent($studentId, $name, $email, $password)) {
                $message = '<div class="alert alert-success">Student added successfully!</div>';
                // Refresh student list
                $registeredStudents = getAllRegisteredStudents($search, $orderBy, $order);
            } else {
                $message = '<div class="alert alert-danger">Failed to add student: ' . $conn->error . '</div>';
            }
        }
    }
    
    // Update existing student
    elseif (isset($_POST['action']) && $_POST['action'] === 'update_student') {
        $studentId = trim($_POST['student_id']);
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        
        // Validate input
        if (empty($studentId) || empty($name) || empty($email)) {
            $message = '<div class="alert alert-danger">All fields are required</div>';
        } else {
            // Update the student
            $data = [
                'name' => $name,
                'email' => $email
            ];
            
            if (updateStudent($studentId, $data)) {
                $message = '<div class="alert alert-success">Student updated successfully!</div>';
                // Refresh student list
                $registeredStudents = getAllRegisteredStudents($search, $orderBy, $order);
            } else {
                $message = '<div class="alert alert-danger">Failed to update student: ' . $conn->error . '</div>';
            }
        }
    }
    
    // Reset student password
    elseif (isset($_POST['action']) && $_POST['action'] === 'reset_password') {
        $studentId = trim($_POST['student_id']);
        $newPassword = trim($_POST['new_password']);
        
        // Validate input
        if (empty($studentId) || empty($newPassword)) {
            $message = '<div class="alert alert-danger">Student ID and new password are required</div>';
        } else {
            // Reset the password
            if (resetStudentPassword($studentId, $newPassword)) {
                $message = '<div class="alert alert-success">Password reset successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">Failed to reset password: ' . $conn->error . '</div>';
            }
        }
    }
    
    // Delete student
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete_student') {
        $studentId = trim($_POST['student_id']);
        
        // Validate input
        if (empty($studentId)) {
            $message = '<div class="alert alert-danger">Student ID is required</div>';
        } else {
            // Delete the student
            if (deleteStudent($studentId)) {
                $message = '<div class="alert alert-success">Student deleted successfully!</div>';
                // Refresh student list
                $registeredStudents = getAllRegisteredStudents($search, $orderBy, $order);
            } else {
                $message = '<div class="alert alert-danger">Failed to delete student: ' . $conn->error . '</div>';
            }
        }
    }
    
    // Register student from API
    elseif (isset($_POST['action']) && $_POST['action'] === 'register_from_api') {
        $studentId = trim($_POST['student_id']);
        $password = trim($_POST['password']);
        
        // Validate input
        if (empty($studentId) || empty($password)) {
            $message = '<div class="alert alert-danger">Student ID and password are required</div>';
        } elseif (isStudentRegistered($studentId)) {
            $message = '<div class="alert alert-danger">Student ID already registered</div>';
        } else {
            // Get student data from API
            $studentData = getStudentFromAPI($studentId);
            
            if ($studentData) {
                $name = $studentData['name'] ?? ($studentData['first_name'] . ' ' . $studentData['last_name']);
                $email = $studentData['email'] ?? '';
                
                // Register the student
                if (registerStudent($studentId, $name, $email, $password)) {
                    $message = '<div class="alert alert-success">Student registered successfully!</div>';
                    // Refresh student list
                    $registeredStudents = getAllRegisteredStudents($search, $orderBy, $order);
                } else {
                    $message = '<div class="alert alert-danger">Failed to register student: ' . $conn->error . '</div>';
                }
            } else {
                $message = '<div class="alert alert-danger">Student not found in API</div>';
            }
        }
    }
}

// Handle GET requests for editing
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $studentId = $_GET['id'];
    $editStudent = getStudentById($studentId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - DigiVote Admin</title>
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
                    <li class="nav-item">
                        <a class="nav-link" href="candidates.php">Candidates</a>
                    </li>
                    <li class="nav-item active">
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
                <h2>Manage Students</h2>
                
                <?php echo $message; ?>
                
                <!-- Student Form -->
                <div class="card mt-4">
                    <div class="card-header bg-primary text-white">
                        <h4><?php echo $editStudent ? 'Edit Student' : 'Add New Student'; ?></h4>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <input type="hidden" name="action" value="<?php echo $editStudent ? 'update_student' : 'add_student'; ?>">
                            
                            <div class="form-group">
                                <label for="student_id">Student ID</label>
                                <input type="text" class="form-control" id="student_id" name="student_id" 
                                       value="<?php echo $editStudent ? htmlspecialchars($editStudent['id']) : ''; ?>" 
                                       <?php echo $editStudent ? 'readonly' : ''; ?> required>
                            </div>
                            
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo $editStudent ? htmlspecialchars($editStudent['name']) : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo $editStudent ? htmlspecialchars($editStudent['email']) : ''; ?>" required>
                            </div>
                            
                            <?php if (!$editStudent): ?>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary"><?php echo $editStudent ? 'Update Student' : 'Add Student'; ?></button>
                                <?php if ($editStudent): ?>
                                    <a href="students.php" class="btn btn-secondary">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Registered Students List -->
                <div class="card mt-4">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h4>Registered Students</h4>
                        <button type="button" class="btn btn-sm btn-light" data-toggle="modal" data-target="#filterModal">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($registeredStudents)): ?>
                            <p class="text-center">No registered students found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>
                                                <a href="?search=<?php echo urlencode($search); ?>&order_by=id&order=<?php echo $orderBy == 'id' && $order == 'ASC' ? 'DESC' : 'ASC'; ?>">
                                                    Student ID
                                                    <?php if ($orderBy == 'id'): ?>
                                                        <i class="fas fa-sort-<?php echo $order == 'ASC' ? 'up' : 'down'; ?>"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>
                                                <a href="?search=<?php echo urlencode($search); ?>&order_by=name&order=<?php echo $orderBy == 'name' && $order == 'ASC' ? 'DESC' : 'ASC'; ?>">
                                                    Name
                                                    <?php if ($orderBy == 'name'): ?>
                                                        <i class="fas fa-sort-<?php echo $order == 'ASC' ? 'up' : 'down'; ?>"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>
                                                <a href="?search=<?php echo urlencode($search); ?>&order_by=email&order=<?php echo $orderBy == 'email' && $order == 'ASC' ? 'DESC' : 'ASC'; ?>">
                                                    Email
                                                    <?php if ($orderBy == 'email'): ?>
                                                        <i class="fas fa-sort-<?php echo $order == 'ASC' ? 'up' : 'down'; ?>"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>
                                                <a href="?search=<?php echo urlencode($search); ?>&order_by=created_at&order=<?php echo $orderBy == 'created_at' && $order == 'ASC' ? 'DESC' : 'ASC'; ?>">
                                                    Registered On
                                                    <?php if ($orderBy == 'created_at'): ?>
                                                        <i class="fas fa-sort-<?php echo $order == 'ASC' ? 'up' : 'down'; ?>"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>Voting Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($registeredStudents as $student): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($student['id']); ?></td>
                                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($student['created_at'])); ?></td>
                                                <td>
                                                    <?php if ($student['has_voted'] > 0): ?>
                                                        <span class="badge badge-success">Voted</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary">Not Voted</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="students.php?action=edit&id=<?php echo urlencode($student['id']); ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#resetPasswordModal<?php echo str_replace([' ', '.'], '_', $student['id']); ?>">
                                                        <i class="fas fa-key"></i> Reset Password
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal<?php echo str_replace([' ', '.'], '_', $student['id']); ?>">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                    
                                                    <!-- Reset Password Modal -->
                                                    <div class="modal fade" id="resetPasswordModal<?php echo str_replace([' ', '.'], '_', $student['id']); ?>" tabindex="-1" role="dialog" aria-labelledby="resetPasswordModalLabel<?php echo str_replace([' ', '.'], '_', $student['id']); ?>" aria-hidden="true">
                                                        <div class="modal-dialog" role="document">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="resetPasswordModalLabel<?php echo str_replace([' ', '.'], '_', $student['id']); ?>">Reset Password</h5>
                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                        <span aria-hidden="true">&times;</span>
                                                                    </button>
                                                                </div>
                                                                <form method="post" action="">
                                                                    <div class="modal-body">
                                                                        <p>Reset password for <strong><?php echo htmlspecialchars($student['name']); ?></strong> (<?php echo htmlspecialchars($student['id']); ?>)</p>
                                                                        <input type="hidden" name="action" value="reset_password">
                                                                        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student['id']); ?>">
                                                                        <div class="form-group">
                                                                            <label for="new_password<?php echo str_replace([' ', '.'], '_', $student['id']); ?>">New Password</label>
                                                                            <input type="password" class="form-control" id="new_password<?php echo str_replace([' ', '.'], '_', $student['id']); ?>" name="new_password" required>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                        <button type="submit" class="btn btn-warning">Reset Password</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Delete Confirmation Modal -->
                                                    <div class="modal fade" id="deleteModal<?php echo str_replace([' ', '.'], '_', $student['id']); ?>" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel<?php echo str_replace([' ', '.'], '_', $student['id']); ?>" aria-hidden="true">
                                                        <div class="modal-dialog" role="document">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="deleteModalLabel<?php echo str_replace([' ', '.'], '_', $student['id']); ?>">Confirm Delete</h5>
                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                        <span aria-hidden="true">&times;</span>
                                                                    </button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    Are you sure you want to delete student <strong><?php echo htmlspecialchars($student['name']); ?></strong> (<?php echo htmlspecialchars($student['id']); ?>)?
                                                                    <p class="text-danger mt-2">This action cannot be undone. All votes by this student will also be deleted.</p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                    <form method="post" action="">
                                                                        <input type="hidden" name="action" value="delete_student">
                                                                        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student['id']); ?>">
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
                
                <!-- API Students List -->
                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h4>Students from API</h4>
                    </div>
                    <div class="card-body">
                        <!-- Filter and Search Form -->
                        <form method="get" action="" class="mb-4">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="year_level">Year Level</label>
                                        <select class="form-control" id="year_level" name="year_level">
                                            <option value="">All</option>
                                            <option value="1" <?php echo $yearLevel == '1' ? 'selected' : ''; ?>>1st Year</option>
                                            <option value="2" <?php echo $yearLevel == '2' ? 'selected' : ''; ?>>2nd Year</option>
                                            <option value="3" <?php echo $yearLevel == '3' ? 'selected' : ''; ?>>3rd Year</option>
                                            <option value="4" <?php echo $yearLevel == '4' ? 'selected' : ''; ?>>4th Year</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="search">Search</label>
                                        <input type="text" class="form-control" id="search" name="search" 
                                               value="<?php echo htmlspecialchars($search); ?>" 
                                               placeholder="Search by ID, name, gender, or status">
                                    </div>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary btn-block">Apply Filters</button>
                                </div>
                            </div>
                        </form>
                        
                        <?php if (isset($apiStudents['data']) && !empty($apiStudents['data'])): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Name</th>
                                            <th>Gender</th>
                                            <th>Year Level</th>
                                            <th>Email</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($apiStudents['data'] as $student): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                                <td>
                                                    <?php 
                                                        echo htmlspecialchars($student['last_name'] . ', ' . 
                                                            $student['first_name'] . ' ' . 
                                                            substr($student['middle_name'], 0, 1) . '.');
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($student['gender']); ?></td>
                                                <td><?php echo htmlspecialchars($student['year_level']); ?></td>
                                                <td><?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $student['status'] === 'Active' ? 'success' : 'secondary'; ?>">
                                                        <?php echo htmlspecialchars($student['status'] ?? $student['account_status'] ?? 'Unknown'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!isStudentRegistered($student['student_id'])): ?>
                                                        <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#registerModal<?php echo $student['student_id']; ?>">
                                                            <i class="fas fa-user-plus"></i> Register
                                                        </button>
                                                        
                                                        <!-- Register Modal -->
                                                        <div class="modal fade" id="registerModal<?php echo $student['student_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="registerModalLabel<?php echo $student['student_id']; ?>" aria-hidden="true">
                                                            <div class="modal-dialog" role="document">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title" id="registerModalLabel<?php echo $student['student_id']; ?>">Register Student</h5>
                                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                            <span aria-hidden="true">&times;</span>
                                                                        </button>
                                                                    </div>
                                                                    <form method="post" action="">
                                                                        <div class="modal-body">
                                                                            <p>Register <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong> (<?php echo htmlspecialchars($student['student_id']); ?>)</p>
                                                                            <input type="hidden" name="action" value="register_from_api">
                                                                            <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student['student_id']); ?>">
                                                                            <div class="form-group">
                                                                                <label for="password<?php echo $student['student_id']; ?>">Set Password</label>
                                                                                <input type="password" class="form-control" id="password<?php echo $student['student_id']; ?>" name="password" required>
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                            <button type="submit" class="btn btn-success">Register Student</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="badge badge-info">Already Registered</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-3">
                                <p>Total Students: <?php echo $apiStudents['count'] ?? 0; ?></p>
                            </div>
                        <?php else: ?>
                            <p class="text-center">No students found in API.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1" role="dialog" aria-labelledby="filterModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterModalLabel">Filter Registered Students</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="get" action="">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="filter_search">Search</label>
                            <input type="text" class="form-control" id="filter_search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search by ID, name, or email">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                    </div>
                </form>
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
