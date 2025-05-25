<?php
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

// Initialize variables for filtering
$yearLevel = isset($_GET['year_level']) ? $_GET['year_level'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$orderBy = isset($_GET['order_by']) ? $_GET['order_by'] : 'lname';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Build API URL with parameters
$apiUrl = STUDENT_API_URL;
$queryParams = [];

if (!empty($yearLevel)) {
    $queryParams[] = "year_level=" . urlencode($yearLevel);
}
if (!empty($search)) {
    $queryParams[] = "search=" . urlencode($search);
}
if (!empty($orderBy)) {
    $queryParams[] = "order_by=" . urlencode($orderBy);
}
if (!empty($order)) {
    $queryParams[] = "order=" . urlencode($order);
}

if (!empty($queryParams)) {
    $apiUrl .= '?' . implode('&', $queryParams);
}

// Fetch data from API
$response = file_get_contents($apiUrl);
$studentsData = json_decode($response, true);

// Handle potential errors
$error = '';
if ($studentsData === null || $studentsData['status'] === 'error') {
    $error = 'Error fetching student data: ' . ($studentsData['message'] ?? 'Unknown error');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student List - DigiVote</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php"><?php echo SITE_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item active">
                        <a class="nav-link" href="students.php">Students</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="results.php">Results</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Student List</h4>
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
                                       placeholder="Search by name, gender, or status">
                            </div>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-block">Apply Filters</button>
                        </div>
                    </div>
                </form>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php else: ?>
                    <!-- Students Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Student ID</th>
                                    <th>
                                        <a href="?year_level=<?php echo $yearLevel; ?>&search=<?php echo urlencode($search); ?>&order_by=lname&order=<?php echo $orderBy == 'lname' && $order == 'ASC' ? 'DESC' : 'ASC'; ?>">
                                            Name
                                            <?php if ($orderBy == 'lname'): ?>
                                                <i class="fas fa-sort-<?php echo $order == 'ASC' ? 'up' : 'down'; ?>"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th>Gender</th>
                                    <th>
                                        <a href="?year_level=<?php echo $yearLevel; ?>&search=<?php echo urlencode($search); ?>&order_by=year_level&order=<?php echo $orderBy == 'year_level' && $order == 'ASC' ? 'DESC' : 'ASC'; ?>">
                                            Year Level
                                            <?php if ($orderBy == 'year_level'): ?>
                                                <i class="fas fa-sort-<?php echo $order == 'ASC' ? 'up' : 'down'; ?>"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th>Email</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($studentsData['data']) && !empty($studentsData['data'])): ?>
                                    <?php foreach ($studentsData['data'] as $student): ?>
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
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No students found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination or Count Information -->
                    <div class="mt-3">
                        <p>Total Students: <?php echo $studentsData['count'] ?? 0; ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
