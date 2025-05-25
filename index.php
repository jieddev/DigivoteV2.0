<?php
session_start();
require_once 'config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

// Get student information
$student = getStudentInfo($_SESSION['student_id']);

// Check if student has already voted
$hasVoted = hasStudentVoted($_SESSION['student_id']);

// Get all candidates
$candidates = getAllCandidates();

// Group candidates by position
$positionGroups = [];
foreach ($candidates as $candidate) {
    $positionGroups[$candidate['position']][] = $candidate;
}

// Handle vote submission
$voteMessage = '';
$votingEnabled = isElectionActive();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$hasVoted && $votingEnabled) {
    // Validate that all positions have a selection
    $selectedCandidates = $_POST['candidate_id'] ?? [];
    $allPositionsSelected = true;
    $missingPositions = [];
    
    // Check if each position has a selection
    foreach (array_keys($positionGroups) as $position) {
        if (!isset($selectedCandidates[$position])) {
            $allPositionsSelected = false;
            $missingPositions[] = $position;
        }
    }
    
    if (!$allPositionsSelected) {
        $voteMessage = '<div class="alert alert-danger">Please select one candidate for each position: ' . implode(', ', $missingPositions) . '</div>';
    } else {
        // Submit votes for each position
        $success = true;
        foreach ($selectedCandidates as $position => $candidateId) {
            $result = submitVote($_SESSION['student_id'], $candidateId);
            if (!$result) {
                $success = false;
                break;
            }
        }
        
        if ($success) {
            $voteMessage = '<div class="alert alert-success">Your votes have been recorded successfully!</div>';
            $hasVoted = true;
        } else {
            $voteMessage = '<div class="alert alert-danger">There was an error recording your votes. Please try again.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DigiVote - Student Voting System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
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
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4>Welcome, <?php echo htmlspecialchars($student['name']); ?></h4>
                    </div>
                    <div class="card-body">
                        <?php echo $voteMessage; ?>
                        
                        <?php if (!$votingEnabled): ?>
                            <div class="alert alert-warning">
                                Voting is currently not active. The election period is from <?php echo date('F j, Y', strtotime(ELECTION_START_DATE)); ?> to <?php echo date('F j, Y', strtotime(ELECTION_END_DATE)); ?>.
                            </div>
                        <?php elseif ($hasVoted): ?>
                            <div class="alert alert-info">
                                You have already cast your vote. Thank you for participating!
                                <p class="mt-2"><a href="results.php" class="btn btn-primary">View Results</a></p>
                            </div>
                        <?php elseif (empty($positionGroups)): ?>
                            <div class="alert alert-warning">
                                There are no candidates available for voting at this time.
                            </div>
                        <?php else: ?>
                            <h5>Please select one candidate for each position:</h5>
                            <form method="post" action="">
                                <?php foreach ($positionGroups as $position => $positionCandidates): ?>
                                <div class="card mb-4">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0"><?php echo htmlspecialchars($position); ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <?php foreach ($positionCandidates as $candidate): ?>
                                            <div class="col-md-4 mb-3">
                                                <div class="card h-100">
                                                    <?php if (!empty($candidate['photo'])): ?>
                                                    <img src="<?php echo htmlspecialchars($candidate['photo']); ?>" class="card-img-top candidate-img" alt="<?php echo htmlspecialchars($candidate['name']); ?>">
                                                    <?php else: ?>
                                                    <div class="card-img-top candidate-img-placeholder">No Image</div>
                                                    <?php endif; ?>
                                                    <div class="card-body">
                                                        <h5 class="card-title"><?php echo htmlspecialchars($candidate['name']); ?></h5>
                                                        <?php if (!empty($candidate['partylist_id'])): ?>
                                                        <?php 
                                                            $partylistQuery = "SELECT name, acronym FROM partylists WHERE id = {$candidate['partylist_id']} LIMIT 1";
                                                            $partylistResult = $conn->query($partylistQuery);
                                                            if ($partylistResult && $partylistResult->num_rows > 0) {
                                                                $partylist = $partylistResult->fetch_assoc();
                                                                echo '<p class="card-text badge badge-primary">' . htmlspecialchars($partylist['acronym'] ?? $partylist['name']) . '</p>';
                                                            }
                                                        ?>
                                                        <?php endif; ?>
                                                        <p class="card-text small"><?php echo htmlspecialchars($candidate['description']); ?></p>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="candidate_id[<?php echo htmlspecialchars($position); ?>]" value="<?php echo $candidate['id']; ?>" id="candidate<?php echo $candidate['id']; ?>" <?php echo (isset($_POST['candidate_id'][$position]) && $_POST['candidate_id'][$position] == $candidate['id']) ? 'checked' : ''; ?> required>
                                                            <label class="form-check-label" for="candidate<?php echo $candidate['id']; ?>">
                                                                Select this candidate
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <button type="submit" class="btn btn-primary btn-lg btn-block mt-3">Submit All Votes</button>
                            </form>
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
