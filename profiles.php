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
    
    // Check if user is admin
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

// Get statistics
$total_profiles = count($profiles);

// Initialize counters
$active_count = 0;
$delisted_count = 0;
$archived_count = 0;

// Count by status
foreach ($profiles as $profile) {
    switch($profile['status']) {
        case 'active':
            $active_count++;
            break;
        case 'delisted':
            $delisted_count++;
            break;
        case 'archived':
            $archived_count++;
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Profiles - PNP Biographical Profiling System</title>
    
    <!-- External CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f4f7fb;
            color: #1e293b;
            line-height: 1.5;
        }

        /* Modern Navbar */
        .navbar-modern {
            background: linear-gradient(135deg, #0a2f4d 0%, #123b5e 100%);
            padding: 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .navbar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .pnp-logo {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #c9a959;
        }

        .pnp-logo i {
            font-size: 28px;
            color: #c9a959;
        }

        .title-area h1 {
            font-size: 22px;
            font-weight: 600;
            color: white;
            margin: 0;
            line-height: 1.2;
        }

        .title-area .subtitle {
            font-size: 13px;
            color: #b0c4de;
            margin: 0;
        }

        .title-area .station {
            font-size: 14px;
            color: #c9a959;
            font-weight: 500;
            margin: 2px 0 0;
        }

        .user-area {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255,255,255,0.1);
            padding: 8px 16px;
            border-radius: 40px;
            border: 1px solid rgba(201, 169, 89, 0.3);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: #c9a959;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0a2f4d;
            font-weight: 600;
            font-size: 18px;
        }

        .user-info {
            line-height: 1.3;
        }

        .user-name {
            font-weight: 600;
            color: white;
            font-size: 14px;
        }

        .user-rank {
            font-size: 12px;
            color: #b0c4de;
        }

        /* Navigation Menu */
        .nav-menu {
            background: rgba(0,0,0,0.2);
            padding: 0;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .nav-menu ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 5px;
        }

        .nav-menu li {
            margin: 0;
        }

        .nav-menu a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            color: #e0e0e0;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }

        .nav-menu a i {
            font-size: 16px;
            width: 20px;
        }

        .nav-menu a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            border-bottom-color: #c9a959;
        }

        .nav-menu a.active {
            background: rgba(255,255,255,0.15);
            color: white;
            border-bottom-color: #c9a959;
        }

        /* Main Content */
        .main-content {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-title i {
            font-size: 28px;
            color: #c9a959;
            background: #0a2f4d;
            padding: 12px;
            border-radius: 12px;
        }

        .title-text h2 {
            font-size: 24px;
            font-weight: 600;
            color: #0a2f4d;
            margin: 0 0 5px;
        }

        .title-text p {
            color: #64748b;
            margin: 0;
            font-size: 14px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
            border-left: 4px solid #0a2f4d;
            transition: all 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }

        .stat-icon.total { 
            background: #e8f2ff; 
            color: #0a2f4d; 
        }
        
        .stat-icon.active { 
            background: #e3f9e5; 
            color: #28a745; 
        }
        
        .stat-icon.delisted { 
            background: #ffe5e5; 
            color: #dc3545; 
        }
        
        .stat-icon.archived { 
            background: #fff3cd; 
            color: #856404; 
        }

        .stat-details h3 {
            font-size: 32px;
            font-weight: 700;
            color: #0a2f4d;
            margin: 0;
            line-height: 1.2;
        }

        .stat-details p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Add Button */
        .btn-add {
            background: #0a2f4d;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 4px 10px rgba(10,47,77,0.2);
        }

        .btn-add:hover {
            background: #c9a959;
            color: #0a2f4d;
            transform: translateY(-2px);
        }

        /* Table Container */
        .table-container {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        /* Modern Table */
        .modern-table {
            width: 100%;
            border-collapse: collapse;
        }

        .modern-table th {
            background: #f8fafc;
            padding: 15px 12px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border-bottom: 2px solid #e2e8f0;
        }

        .modern-table td {
            padding: 15px 12px;
            font-size: 14px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .modern-table tbody tr:hover {
            background: #f8fafc;
        }

        /* ID Badge */
        .id-badge {
            background: #e8f2ff;
            color: #0a2f4d;
            font-weight: 600;
            font-size: 13px;
            padding: 4px 8px;
            border-radius: 6px;
            display: inline-block;
            font-family: monospace;
        }

        /* Name with Alias */
        .name-with-alias {
            display: flex;
            flex-direction: column;
        }

        .full-name {
            font-weight: 600;
            color: #1e293b;
        }

        .alias {
            font-size: 12px;
            color: #94a3b8;
        }

        /* Status Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            text-align: center;
            min-width: 80px;
        }

        .status-active {
            background: #e3f9e5;
            color: #28a745;
        }

        .status-delisted {
            background: #ffe5e5;
            color: #dc3545;
        }

        .status-archived {
            background: #fff3cd;
            color: #856404;
        }

        /* Date Badge */
        .date-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #f1f5f9;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            color: #475569;
        }

        .date-badge i {
            color: #c9a959;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 6px;
        }

        .btn-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-icon.view {
            background: #0a2f4d;
        }

        .btn-icon.view:hover {
            background: #123b5e;
            transform: translateY(-2px);
        }

        .btn-icon.edit {
            background: #c9a959;
        }

        .btn-icon.edit:hover {
            background: #d4b36a;
            transform: translateY(-2px);
        }

        .btn-icon.delete {
            background: #dc3545;
        }

        .btn-icon.delete:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 60px;
            color: #cbd5e1;
            margin-bottom: 20px;
        }

        .empty-state h4 {
            color: #475569;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #94a3b8;
            margin-bottom: 20px;
        }

        /* Footer */
        .footer {
            background: white;
            padding: 20px 0;
            margin-top: 50px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }

        /* Alert */
        .alert-modern {
            background: white;
            border-left: 4px solid;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .alert-success {
            border-left-color: #28a745;
        }

        .alert-success i {
            color: #28a745;
        }

        .alert-danger {
            border-left-color: #dc3545;
        }

        .alert-danger i {
            color: #dc3545;
        }

        /* Modal */
        .modal-content {
            border: none;
            border-radius: 16px;
        }

        .modal-header {
            background: #0a2f4d;
            color: white;
            border-bottom: 3px solid #c9a959;
            border-radius: 16px 16px 0 0;
            padding: 20px;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .modal-body {
            padding: 25px;
        }

        .modal-footer {
            padding: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .warning-text {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 12px;
            border-radius: 8px;
            margin-top: 15px;
            font-size: 13px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .navbar-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .page-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .page-title {
                flex-direction: column;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Modern Navbar -->
    <nav class="navbar-modern">
        <div class="navbar-container">
            <div class="navbar-header">
                <div class="logo-area">
                    <div class="pnp-logo">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="title-area">
                        <h1>PNP Biographical Profiling System</h1>
                        <div class="station">MANOLO FORTICH POLICE STATION</div>
                        <div class="subtitle">Bukidnon Police Provincial Office</div>
                    </div>
                </div>
                
                <div class="user-area">
                    <div class="user-profile">
                        <div class="user-avatar">
                            <?php echo substr($_SESSION['full_name'], 0, 1); ?>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?php echo $_SESSION['full_name']; ?></div>
                            <div class="user-rank"><?php echo $_SESSION['rank']; ?> • <?php echo $_SESSION['unit']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="nav-menu">
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="profile_form.php"><i class="fas fa-plus-circle"></i> New Profile</a></li>
                    <li><a href="profiles.php" class="active"><i class="fas fa-list"></i> View Profiles</a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                    <li><a href="users.php"><i class="fas fa-users-cog"></i> User Management</a></li>
                    <?php endif; ?>
                    <li style="margin-left: auto;"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert-modern alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success_message; ?></span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" style="background: none; border: none; cursor: pointer;">×</button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert-modern alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error_message; ?></span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" style="background: none; border: none; cursor: pointer;">×</button>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <i class="fas fa-list"></i>
                <div class="title-text">
                    <h2>All Biographical Profiles</h2>
                    <p>View and manage all profiles in the system</p>
                </div>
            </div>
            <a href="profile_form.php" class="btn-add">
                <i class="fas fa-plus-circle"></i> Add New Profile
            </a>
        </div>
        <!-- Profiles Table -->
        <div class="table-container">
            <?php if (count($profiles) > 0): ?>
                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Full Name / Alias</th>
                                <th>Age</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($profiles as $profile): ?>
                            <tr>
                                <td>
                                    <div class="name-with-alias">
                                        <span class="full-name"><?php echo htmlspecialchars($profile['full_name']); ?></span>
                                        <?php if (!empty($profile['alias'])): ?>
                                            <span class="alias">"<?php echo htmlspecialchars($profile['alias']); ?>"</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo $profile['age']; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <!-- View Button -->
                                        <a href="view_profile.php?id=<?php echo $profile['id']; ?>" 
                                           class="btn-icon view" 
                                           title="View Profile">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <!-- Edit Button -->
                                        <a href="edit_profile.php?id=<?php echo $profile['id']; ?>" 
                                           class="btn-icon edit" 
                                           title="Edit Profile">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <!-- Delete Button (Only for Admin) -->
                                        <?php if ($_SESSION['role'] == 'admin'): ?>
                                        <button type="button" 
                                                class="btn-icon delete" 
                                                title="Delete Profile"
                                                onclick="confirmDelete(<?php echo $profile['id']; ?>, '<?php echo htmlspecialchars(addslashes($profile['full_name'])); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Summary -->
                <div class="mt-4 text-muted" style="border-top: 1px solid #e2e8f0; padding-top: 15px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <small>
                            <i class="fas fa-database"></i> 
                            Showing <strong><?php echo count($profiles); ?></strong> records
                        </small>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h4>No Profiles Found</h4>
                    <p>Click the "Add New Profile" button to create your first profile.</p>
                    <a href="profile_form.php" class="btn-add" style="display: inline-flex;">
                        <i class="fas fa-plus-circle"></i> Create First Profile
                    </a>
                </div>
            <?php endif; ?>
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
                    <p class="fw-bold text-center fs-5" id="deleteProfileName"></p>
                    <div class="warning-text">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Warning:</strong> This action cannot be undone. All associated data including siblings will be permanently deleted from the system.
                    </div>
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
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>© <?php echo date('Y'); ?> Philippine National Police - Manolo Fortich Police Station. All rights reserved.</p>
            <p>Department of the Interior and Local Government | Bukidnon Police Provincial Office</p>
        </div>
    </footer>

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

        // Close alert
        document.querySelectorAll('.btn-close').forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.alert-modern').remove();
            });
        });
    </script>
</body>
</html>