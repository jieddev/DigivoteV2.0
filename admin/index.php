<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Get voting statistics
$totalVotes = getTotalVotes();
$totalVoters = getTotalVoters();

// Get all candidates with vote counts
$candidates = getElectionResults();

// Group candidates by position
$positionResults = [];
foreach ($candidates as $candidate) {
    $positionResults[$candidate['position']][] = $candidate;
}

// Handle form submission for adding a new candidate
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_candidate') {
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
        } else {
            $message = '<div class="alert alert-danger">Failed to add candidate: ' . $conn->error . '</div>';
        }
        
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - DigiVote</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <li class="nav-item active">
                        <a class="nav-link" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
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
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></h2>
                <p>Election period: <?php echo date('F j, Y', strtotime(ELECTION_START_DATE)); ?> to <?php echo date('F j, Y', strtotime(ELECTION_END_DATE)); ?></p>
                
                <?php echo $message; ?>
                
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Votes</h5>
                                <h2 class="card-text"><?php echo $totalVotes; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Voters</h5>
                                <h2 class="card-text"><?php echo $totalVoters; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Candidates</h5>
                                <h2 class="card-text"><?php echo count($candidates); ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header bg-dark text-white">
                        <h4>Election Results</h4>
                    </div>
                    <div class="card-body">
                        <?php foreach ($positionResults as $position => $candidates): ?>
                            <h5 class="mt-4"><?php echo htmlspecialchars($position); ?> Results</h5>
                            <div class="row">
                                <div class="col-md-8">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Candidate</th>
                                                <th>Votes</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $totalPositionVotes = array_sum(array_column($candidates, 'votes'));
                                            foreach ($candidates as $candidate):
                                                $percentage = ($totalPositionVotes > 0) ? round(($candidate['votes'] / $totalPositionVotes) * 100, 2) : 0;
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($candidate['name']); ?></td>
                                                <td><?php echo $candidate['votes']; ?></td>
                                                <td><?php echo $percentage; ?>%</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-4">
                                    <canvas id="admin-chart-<?php echo str_replace(' ', '-', strtolower($position)); ?>"></canvas>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header bg-dark text-white">
                        <h4>Add New Candidate</h4>
                    </div>
                    <div class="card-body">
                        <form method="post" action="" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add_candidate">
                            <div class="form-group">
                                <label for="name">Candidate Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="position">Position</label>
                                <input type="text" class="form-control" id="position" name="position" required>
                            </div>
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="photo">Photo</label>
                                <input type="file" class="form-control-file" id="photo" name="photo">
                            </div>
                            <button type="submit" class="btn btn-primary">Add Candidate</button>
                        </form>
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

    <script>
    <?php foreach ($positionResults as $position => $candidates): ?>
        new Chart(document.getElementById('admin-chart-<?php echo str_replace(' ', '-', strtolower($position)); ?>'), {
            type: 'pie',
            data: {
                labels: [<?php echo "'" . implode("', '", array_column($candidates, 'name')) . "'"; ?>],
                datasets: [{
                    data: [<?php echo implode(', ', array_column($candidates, 'votes')); ?>],
                    backgroundColor: [
                        '#007bff',
                        '#28a745',
                        '#dc3545',
                        '#ffc107',
                        '#17a2b8',
                        '#6610f2'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    <?php endforeach; ?>
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
