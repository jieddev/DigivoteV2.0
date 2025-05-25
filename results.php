<?php
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

// Get election results
$results = getElectionResults();

// Get voting statistics
$totalVotes = getTotalVotes();
$totalVoters = getTotalVoters();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results - DigiVote</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">DigiVote</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="students.php">Students</a>
                    </li>
                    <li class="nav-item active">
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
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4>Election Results</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!isElectionActive()): ?>
                            <div class="alert alert-info">
                                <h5>Election Statistics</h5>
                                <p>Total Votes Cast: <?php echo $totalVotes; ?></p>
                                <p>Total Students Voted: <?php echo $totalVoters; ?></p>
                            </div>
                            
                            <?php
                            // Group candidates by position
                            $positionResults = [];
                            foreach ($results as $candidate) {
                                $positionResults[$candidate['position']][] = $candidate;
                            }
                            
                            // Display results for each position
                            foreach ($positionResults as $position => $candidates):
                            ?>
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
                                        <canvas id="chart-<?php echo str_replace(' ', '-', strtolower($position)); ?>"></canvas>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <script>
                            <?php foreach ($positionResults as $position => $candidates): ?>
                                new Chart(document.getElementById('chart-<?php echo str_replace(' ', '-', strtolower($position)); ?>'), {
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
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <h5>Election is still in progress</h5>
                                <p>Results will be available after the election ends on <?php echo date('F j, Y, g:i a', strtotime(ELECTION_END_DATE)); ?>.</p>
                                <p><a href="index.php" class="btn btn-primary">Return to Voting</a></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <p class="mb-0">DigiVote &copy; <?php echo date('Y'); ?> - Student Voting System</p>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
