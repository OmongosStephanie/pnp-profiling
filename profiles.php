<?php
// profiles.php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle delete request
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    // Check if user is admin (optional - remove if you want all users to delete)
    if ($_SESSION['role'] == 'admin') {
        try {
            // Start transaction
            $db->beginTransaction();
            
            // Delete siblings first (foreign key constraint)
            $query = "DELETE FROM siblings WHERE profile_id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $delete_id);
            $stmt->execute();
            
            // Delete profile
            $query = "DELETE FROM biographical_profiles WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $delete_id);
            $stmt->execute();
            
            $db->commit();
            
            // Set success message
            $success_message = "Profile deleted successfully!";
        } catch (Exception $e) {
            $db->rollBack();
            $error_message = "Error deleting profile: " . $e->getMessage();
        }
    } else {
        $error_message = "You don't have permission to delete profiles.";
    }
    
    // Redirect to remove delete parameter from URL
    header("Location: profiles.php");
    exit();
}

// Get all profiles
$query = "SELECT * FROM biographical_profiles ORDER BY created_at DESC";
$stmt = $db->query($query);
$profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Profiles - PNP Biographical Profiling System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #f0f2f5;
        }
        
        .header {
            background: #0a2f4d;
            color: white;
            padding: 15px 0;
            border-bottom: 3px solid #c9a959;
            margin-bottom: 30px;
        }
        
        .container {
            max-width: 1200px;
        }
        
        .page-title {
            color: #0a2f4d;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #c9a959;
        }
        
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background: #0a2f4d;
            color: white;
            font-weight: 600;
            font-size: 14px;
            padding: 12px;
            border: none;
        }
        
        .table tbody tr:hover {
            background: #f5f5f5;
        }
        
        .badge-active {
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .badge-delisted {
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .badge-archived {
            background: #ffc107;
            color: #333;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .btn-action {
            padding: 5px 10px;
            margin: 0 2px;
            border-radius: 3px;
        }
        
        .btn-view {
            background: #17a2b8;
            color: white;
        }
        
        .btn-view:hover {
            background: #138496;
            color: white;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #333;
        }
        
        .btn-edit:hover {
            background: #e0a800;
            color: #333;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
            color: white;
        }
        
        .btn-add {
            background: #0a2f4d;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            margin-bottom: 20px;
            display: inline-block;
        }
        
        .btn-add:hover {
            background: #c9a959;
            color: #0a2f4d;
        }
        
        .alert {
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .footer {
            background: white;
            padding: 15px 0;
            margin-top: 30px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        /* Delete Modal Styles */
        .modal-header {
            background: #0a2f4d;
            color: white;
            border-bottom: 3px solid #c9a959;
        }
        
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        
        .modal-footer .btn-danger {
            background: #dc3545;
            border: none;
        }
        
        .modal-footer .btn-danger:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-shield-alt fa-2x me-2"></i>
                    <span class="h5">PNP Biographical Profiling System</span>
                    <br><small>Manolo Fortich Police Station</small>
                </div>
                <div>
                    <span class="me-3">
                        <i class="fas fa-user"></i> <?php echo $_SESSION['rank'] . ' ' . $_SESSION['full_name']; ?>
                    </span>
                    <a href="dashboard.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="page-title">
                <i class="fas fa-list me-2"></i>All Profiles
            </h2>
            <a href="profile_form.php" class="btn-add">
                <i class="fas fa-plus-circle"></i> Add New Profile
            </a>
        </div>
        
        <!-- Profiles Table -->
        <div class="table-container">
            <?php if (count($profiles) > 0): ?>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Alias</th>
                            <th>Age</th>
                            <th>Status</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($profiles as $profile): ?>
                        <tr>
                            <td>#<?php echo str_pad($profile['id'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($profile['full_name']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($profile['alias'] ?: 'N/A'); ?></td>
                            <td><?php echo $profile['age']; ?></td>
                            <td>
                                <?php
                                $statusClass = '';
                                switch($profile['status']) {
                                    case 'active':
                                        $statusClass = 'badge-active';
                                        break;
                                    case 'delisted':
                                        $statusClass = 'badge-delisted';
                                        break;
                                    case 'archived':
                                        $statusClass = 'badge-archived';
                                        break;
                                    default:
                                        $statusClass = 'badge-secondary';
                                }
                                ?>
                                <span class="<?php echo $statusClass; ?>">
                                    <?php echo ucfirst($profile['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d', strtotime($profile['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <!-- View Button -->
                                    <a href="view_profile.php?id=<?php echo $profile['id']; ?>" 
                                       class="btn btn-action btn-view" 
                                       title="View Profile">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <!-- Edit Button -->
                                    <a href="edit_profile.php?id=<?php echo $profile['id']; ?>" 
                                       class="btn btn-action btn-edit" 
                                       title="Edit Profile">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <!-- Delete Button (Only for Admin) -->
                                    <?php if ($_SESSION['role'] == 'admin'): ?>
                                    <button type="button" 
                                            class="btn btn-action btn-delete" 
                                            title="Delete Profile"
                                            onclick="confirmDelete(<?php echo $profile['id']; ?>, '<?php echo htmlspecialchars($profile['full_name']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No Profiles Found</h4>
                    <p>Click the "Add New Profile" button to create your first profile.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Summary -->
        <div class="mt-3 text-muted">
            <small>Total Profiles: <strong><?php echo count($profiles); ?></strong></small>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this profile?</p>
                    <p class="fw-bold" id="deleteProfileName"></p>
                    <p class="text-danger small">
                        <i class="fas fa-warning"></i>
                        This action cannot be undone. All associated data including siblings will be permanently deleted.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete Permanently
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <div class="container">
            <small>Department of the Interior and Local Government | PHILIPPINE NATIONAL POLICE<br>
            BUKIDNON POLICE PROVINCIAL OFFICE | MANOLO FORTICH POLICE STATION<br>
            All data is confidential and for official use only.</small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Initialize modal
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        
        // Confirm delete function
        function confirmDelete(id, name) {
            // Set the profile name in the modal
            document.getElementById('deleteProfileName').textContent = name;
            
            // Set the delete link
            document.getElementById('confirmDeleteBtn').href = 'profiles.php?delete=' + id;
            
            // Show the modal
            deleteModal.show();
        }
        
        // Optional: Add keyboard shortcut for delete (Ctrl+Del)
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'Delete') {
                e.preventDefault();
                // You can implement this if needed
            }
        });
    </script>
</body>
</html>