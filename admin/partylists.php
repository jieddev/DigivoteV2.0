<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/partylist_functions.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Run database update script if needed
if (!$conn->query("SHOW TABLES LIKE 'partylists'")->num_rows) {
    require_once '../config/update_db_partylists.php';
}

// Initialize variables
$message = '';
$editPartylist = null;
$selectedPartylist = null;
$partylistCandidates = [];
$availableCandidates = [];

// Get all partylists
$partylists = getAllPartylists();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new partylist
    if (isset($_POST['action']) && $_POST['action'] === 'add_partylist') {
        $name = trim($_POST['name']);
        $acronym = trim($_POST['acronym']);
        $description = trim($_POST['description']);
        
        // Handle logo upload
        $logo = '';
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/images/partylists/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = time() . '_' . basename($_FILES['logo']['name']);
            $uploadFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadFile)) {
                $logo = 'assets/images/partylists/' . $fileName;
            }
        }
        
        // Add partylist
        $data = [
            'name' => $name,
            'acronym' => $acronym,
            'description' => $description,
            'logo' => $logo
        ];
        
        if (addPartylist($data)) {
            $message = '<div class="alert alert-success">Partylist added successfully!</div>';
            // Refresh partylists
            $partylists = getAllPartylists();
        } else {
            $message = '<div class="alert alert-danger">Failed to add partylist: ' . $conn->error . '</div>';
        }
    }
    
    // Update existing partylist
    elseif (isset($_POST['action']) && $_POST['action'] === 'update_partylist') {
        $partylistId = (int)$_POST['partylist_id'];
        $name = trim($_POST['name']);
        $acronym = trim($_POST['acronym']);
        $description = trim($_POST['description']);
        
        // Handle logo upload
        $data = [
            'name' => $name,
            'acronym' => $acronym,
            'description' => $description
        ];
        
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/images/partylists/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = time() . '_' . basename($_FILES['logo']['name']);
            $uploadFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadFile)) {
                $data['logo'] = 'assets/images/partylists/' . $fileName;
            }
        }
        
        if (updatePartylist($partylistId, $data)) {
            $message = '<div class="alert alert-success">Partylist updated successfully!</div>';
            // Refresh partylists
            $partylists = getAllPartylists();
        } else {
            $message = '<div class="alert alert-danger">Failed to update partylist: ' . $conn->error . '</div>';
        }
    }
    
    // Delete partylist
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete_partylist') {
        $partylistId = (int)$_POST['partylist_id'];
        
        if (deletePartylist($partylistId)) {
            $message = '<div class="alert alert-success">Partylist deleted successfully!</div>';
            // Refresh partylists
            $partylists = getAllPartylists();
        } else {
            $message = '<div class="alert alert-danger">Failed to delete partylist: ' . $conn->error . '</div>';
        }
    }
    
    // Add candidate to partylist
    elseif (isset($_POST['action']) && $_POST['action'] === 'add_candidate_to_partylist') {
        $candidateId = (int)$_POST['candidate_id'];
        $partylistId = (int)$_POST['partylist_id'];
        $positionInPartylist = trim($_POST['position_in_partylist']);
        
        if (addCandidateToPartylist($candidateId, $partylistId, $positionInPartylist)) {
            $message = '<div class="alert alert-success">Candidate added to partylist successfully!</div>';
            // Set selected partylist for UI
            $selectedPartylist = $partylistId;
        } else {
            $message = '<div class="alert alert-danger">Failed to add candidate to partylist: ' . $conn->error . '</div>';
        }
    }
    
    // Remove candidate from partylist
    elseif (isset($_POST['action']) && $_POST['action'] === 'remove_candidate_from_partylist') {
        $candidateId = (int)$_POST['candidate_id'];
        $partylistId = (int)$_POST['partylist_id'];
        
        if (removeCandidateFromPartylist($candidateId, $partylistId)) {
            $message = '<div class="alert alert-success">Candidate removed from partylist successfully!</div>';
            // Set selected partylist for UI
            $selectedPartylist = $partylistId;
        } else {
            $message = '<div class="alert alert-danger">Failed to remove candidate from partylist: ' . $conn->error . '</div>';
        }
    }
    
    // Update candidate position in partylist
    elseif (isset($_POST['action']) && $_POST['action'] === 'update_candidate_position') {
        $candidateId = (int)$_POST['candidate_id'];
        $partylistId = (int)$_POST['partylist_id'];
        $positionInPartylist = trim($_POST['position_in_partylist']);
        
        if (updateCandidatePositionInPartylist($candidateId, $partylistId, $positionInPartylist)) {
            $message = '<div class="alert alert-success">Candidate position updated successfully!</div>';
            // Set selected partylist for UI
            $selectedPartylist = $partylistId;
        } else {
            $message = '<div class="alert alert-danger">Failed to update candidate position: ' . $conn->error . '</div>';
        }
    }
}

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Edit partylist
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        $partylistId = (int)$_GET['id'];
        $editPartylist = getPartylistById($partylistId);
    }
    
    // View partylist details
    if (isset($_GET['view']) && is_numeric($_GET['view'])) {
        $selectedPartylist = (int)$_GET['view'];
    }
}

// If a partylist is selected, get its candidates and available candidates
if ($selectedPartylist) {
    $partylistCandidates = getCandidatesByPartylist($selectedPartylist);
    $availableCandidates = getCandidatesNotInPartylist();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Partylists - DigiVote Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .partylist-logo {
            max-height: 60px;
            max-width: 100px;
        }
        .candidate-photo {
            max-height: 40px;
            max-width: 40px;
            border-radius: 50%;
        }
    </style>
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
                    <li class="nav-item">
                        <a class="nav-link" href="students.php">Student List</a>
                    </li>
                    <li class="nav-item active">
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
                <h2>Manage Partylists</h2>
                
                <?php echo $message; ?>
                
                <!-- Partylist Form -->
                <div class="card mt-4">
                    <div class="card-header bg-primary text-white">
                        <h4><?php echo $editPartylist ? 'Edit Partylist' : 'Add New Partylist'; ?></h4>
                    </div>
                    <div class="card-body">
                        <form method="post" action="" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="<?php echo $editPartylist ? 'update_partylist' : 'add_partylist'; ?>">
                            <?php if ($editPartylist): ?>
                                <input type="hidden" name="partylist_id" value="<?php echo $editPartylist['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label for="name">Partylist Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo $editPartylist ? htmlspecialchars($editPartylist['name']) : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="acronym">Acronym</label>
                                <input type="text" class="form-control" id="acronym" name="acronym" value="<?php echo $editPartylist ? htmlspecialchars($editPartylist['acronym']) : ''; ?>">
                                <small class="form-text text-muted">Short name or acronym for the partylist (e.g., USA, PSC)</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo $editPartylist ? htmlspecialchars($editPartylist['description']) : ''; ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="logo">Logo</label>
                                <input type="file" class="form-control-file" id="logo" name="logo">
                                <?php if ($editPartylist && !empty($editPartylist['logo'])): ?>
                                    <div class="mt-2">
                                        <img src="../<?php echo htmlspecialchars($editPartylist['logo']); ?>" alt="Current logo" class="img-thumbnail partylist-logo">
                                        <p class="text-muted">Current logo. Upload a new one to replace it.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary"><?php echo $editPartylist ? 'Update Partylist' : 'Add Partylist'; ?></button>
                                <?php if ($editPartylist): ?>
                                    <a href="partylists.php" class="btn btn-secondary">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <!-- Partylists List -->
                    <div class="col-md-5">
                        <div class="card">
                            <div class="card-header bg-dark text-white">
                                <h4>Partylists</h4>
                            </div>
                            <div class="card-body">
                                <?php if (empty($partylists)): ?>
                                    <p class="text-center">No partylists found.</p>
                                <?php else: ?>
                                    <div class="list-group">
                                        <?php foreach ($partylists as $partylist): ?>
                                            <a href="partylists.php?view=<?php echo $partylist['id']; ?>" class="list-group-item list-group-item-action <?php echo ($selectedPartylist == $partylist['id']) ? 'active' : ''; ?>">
                                                <div class="d-flex w-100 justify-content-between align-items-center">
                                                    <div>
                                                        <?php if (!empty($partylist['logo'])): ?>
                                                            <img src="../<?php echo htmlspecialchars($partylist['logo']); ?>" alt="<?php echo htmlspecialchars($partylist['name']); ?>" class="partylist-logo mr-2">
                                                        <?php endif; ?>
                                                        <h5 class="mb-1 d-inline-block"><?php echo htmlspecialchars($partylist['name']); ?></h5>
                                                        <?php if (!empty($partylist['acronym'])): ?>
                                                            <span class="badge badge-primary"><?php echo htmlspecialchars($partylist['acronym']); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <span class="badge badge-info"><?php echo $partylist['candidate_count']; ?> candidates</span>
                                                    </div>
                                                </div>
                                                <p class="mb-1"><?php echo htmlspecialchars(substr($partylist['description'], 0, 100) . (strlen($partylist['description']) > 100 ? '...' : '')); ?></p>
                                                <div class="mt-2">
                                                    <a href="partylists.php?action=edit&id=<?php echo $partylist['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal<?php echo $partylist['id']; ?>">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </div>
                                                
                                                <!-- Delete Confirmation Modal -->
                                                <div class="modal fade" id="deleteModal<?php echo $partylist['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel<?php echo $partylist['id']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="deleteModalLabel<?php echo $partylist['id']; ?>">Confirm Delete</h5>
                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                Are you sure you want to delete partylist <strong><?php echo htmlspecialchars($partylist['name']); ?></strong>?
                                                                <p class="text-danger mt-2">This action cannot be undone. All candidate associations with this partylist will be removed.</p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                <form method="post" action="">
                                                                    <input type="hidden" name="action" value="delete_partylist">
                                                                    <input type="hidden" name="partylist_id" value="<?php echo $partylist['id']; ?>">
                                                                    <button type="submit" class="btn btn-danger">Delete</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Partylist Details and Candidates -->
                    <div class="col-md-7">
                        <?php if ($selectedPartylist): ?>
                            <?php $partylist = getPartylistById($selectedPartylist); ?>
                            <?php if ($partylist): ?>
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <h4><?php echo htmlspecialchars($partylist['name']); ?> Candidates</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-4">
                                            <h5>Partylist Details</h5>
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($partylist['name']); ?></p>
                                                    <p><strong>Acronym:</strong> <?php echo htmlspecialchars($partylist['acronym'] ?? 'N/A'); ?></p>
                                                    <p><strong>Description:</strong> <?php echo htmlspecialchars($partylist['description'] ?? 'No description'); ?></p>
                                                </div>
                                                <div class="col-md-4 text-center">
                                                    <?php if (!empty($partylist['logo'])): ?>
                                                        <img src="../<?php echo htmlspecialchars($partylist['logo']); ?>" alt="<?php echo htmlspecialchars($partylist['name']); ?>" class="img-thumbnail partylist-logo">
                                                    <?php else: ?>
                                                        <div class="text-muted">No logo</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <h5>Current Candidates</h5>
                                        <?php if (empty($partylistCandidates)): ?>
                                            <p class="text-center">No candidates in this partylist.</p>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Photo</th>
                                                            <th>Name</th>
                                                            <th>Election Position</th>
                                                            <th>Partylist Position</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($partylistCandidates as $candidate): ?>
                                                            <tr>
                                                                <td>
                                                                    <?php if (!empty($candidate['photo'])): ?>
                                                                        <img src="../<?php echo htmlspecialchars($candidate['photo']); ?>" alt="<?php echo htmlspecialchars($candidate['name']); ?>" class="candidate-photo">
                                                                    <?php else: ?>
                                                                        <i class="fas fa-user-circle fa-2x text-secondary"></i>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td><?php echo htmlspecialchars($candidate['name']); ?></td>
                                                                <td><?php echo htmlspecialchars($candidate['position']); ?></td>
                                                                <td>
                                                                    <form method="post" action="" class="form-inline">
                                                                        <input type="hidden" name="action" value="update_candidate_position">
                                                                        <input type="hidden" name="candidate_id" value="<?php echo $candidate['id']; ?>">
                                                                        <input type="hidden" name="partylist_id" value="<?php echo $selectedPartylist; ?>">
                                                                        <input type="text" class="form-control form-control-sm mr-2" name="position_in_partylist" value="<?php echo htmlspecialchars($candidate['position_in_partylist'] ?? ''); ?>" placeholder="Position">
                                                                        <button type="submit" class="btn btn-sm btn-outline-primary">Update</button>
                                                                    </form>
                                                                </td>
                                                                <td>
                                                                    <form method="post" action="">
                                                                        <input type="hidden" name="action" value="remove_candidate_from_partylist">
                                                                        <input type="hidden" name="candidate_id" value="<?php echo $candidate['id']; ?>">
                                                                        <input type="hidden" name="partylist_id" value="<?php echo $selectedPartylist; ?>">
                                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                                            <i class="fas fa-user-minus"></i> Remove
                                                                        </button>
                                                                    </form>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <h5 class="mt-4">Add Candidates to Partylist</h5>
                                        <?php if (empty($availableCandidates)): ?>
                                            <p class="text-center">No available candidates to add.</p>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Photo</th>
                                                            <th>Name</th>
                                                            <th>Election Position</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($availableCandidates as $candidate): ?>
                                                            <tr>
                                                                <td>
                                                                    <?php if (!empty($candidate['photo'])): ?>
                                                                        <img src="../<?php echo htmlspecialchars($candidate['photo']); ?>" alt="<?php echo htmlspecialchars($candidate['name']); ?>" class="candidate-photo">
                                                                    <?php else: ?>
                                                                        <i class="fas fa-user-circle fa-2x text-secondary"></i>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td><?php echo htmlspecialchars($candidate['name']); ?></td>
                                                                <td><?php echo htmlspecialchars($candidate['position']); ?></td>
                                                                <td>
                                                                    <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#addCandidateModal<?php echo $candidate['id']; ?>">
                                                                        <i class="fas fa-user-plus"></i> Add
                                                                    </button>
                                                                    
                                                                    <!-- Add Candidate Modal -->
                                                                    <div class="modal fade" id="addCandidateModal<?php echo $candidate['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="addCandidateModalLabel<?php echo $candidate['id']; ?>" aria-hidden="true">
                                                                        <div class="modal-dialog" role="document">
                                                                            <div class="modal-content">
                                                                                <div class="modal-header">
                                                                                    <h5 class="modal-title" id="addCandidateModalLabel<?php echo $candidate['id']; ?>">Add Candidate to Partylist</h5>
                                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                                        <span aria-hidden="true">&times;</span>
                                                                                    </button>
                                                                                </div>
                                                                                <form method="post" action="">
                                                                                    <div class="modal-body">
                                                                                        <p>Add <strong><?php echo htmlspecialchars($candidate['name']); ?></strong> to <?php echo htmlspecialchars($partylist['name']); ?></p>
                                                                                        <input type="hidden" name="action" value="add_candidate_to_partylist">
                                                                                        <input type="hidden" name="candidate_id" value="<?php echo $candidate['id']; ?>">
                                                                                        <input type="hidden" name="partylist_id" value="<?php echo $selectedPartylist; ?>">
                                                                                        <div class="form-group">
                                                                                            <label for="position_in_partylist<?php echo $candidate['id']; ?>">Position in Partylist</label>
                                                                                            <input type="text" class="form-control" id="position_in_partylist<?php echo $candidate['id']; ?>" name="position_in_partylist" placeholder="e.g., President, Secretary, Member">
                                                                                            <small class="form-text text-muted">This is the candidate's position within the partylist, not the election position.</small>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="modal-footer">
                                                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                                        <button type="submit" class="btn btn-success">Add to Partylist</button>
                                                                                    </div>
                                                                                </form>
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
                            <?php else: ?>
                                <div class="alert alert-warning">Partylist not found.</div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="card">
                                <div class="card-body text-center">
                                    <p class="lead">Select a partylist to view and manage its candidates</p>
                                    <i class="fas fa-hand-point-left fa-3x text-primary"></i>
                                </div>
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
