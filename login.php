<?php
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

// Check if already logged in
if (isset($_SESSION['student_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = trim($_POST['student_id']);
    $password = $_POST['password'];
    
    if (empty($studentId) || empty($password)) {
        $error = 'Please enter both student ID and password';
    } else {
        // Verify student credentials
        $student = verifyStudentCredentials($studentId, $password);
        
        if ($student) {
            // Set session variables
            $_SESSION['student_id'] = $student['id'];
            $_SESSION['student_name'] = $student['name'];
            
            // Redirect to voting page
            header("Location: index.php");
            exit;
        } else {
            $error = 'Invalid student ID or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DigiVote</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h4>DigiVote - Student Login</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="post" action="">
                            <div class="form-group">
                                <label for="student_id">Student ID</label>
                                <input type="text" class="form-control" id="student_id" name="student_id" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Login</button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <p>Don't have an account? <a href="register.php">Register</a></p>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <a href="admin/login.php">Admin Login</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
