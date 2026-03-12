<?php
// dashboard.php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get statistics
$stats = [];

// Total profiles
$query = "SELECT COUNT(*) as total FROM biographical_profiles";
$stmt = $db->query($query);
$stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Active profiles
$query = "SELECT COUNT(*) as total FROM biographical_profiles WHERE status = 'active'";
$stmt = $db->query($query);
$stats['active'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Delisted profiles
$query = "SELECT COUNT(*) as total FROM biographical_profiles WHERE status = 'delisted'";
$stmt = $db->query($query);
$stats['delisted'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Recent profiles
$query = "SELECT * FROM biographical_profiles ORDER BY created_at DESC LIMIT 5";
$stmt = $db->query($query);
$recent_profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PNP Biographical Profiling System - Dashboard</title>
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
        
        .header h2 {
            margin: 0;
            font-size: 20px;
        }
        
        .header small {
            color: #c9a959;
        }
        
        .sidebar {
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .sidebar .nav-link {
            color: #333;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 5px;
        }
        
        .sidebar .nav-link:hover {
            background: #0a2f4d;
            color: white;
        }
        
        .sidebar .nav-link.active {
            background: #0a2f4d;
            color: white;
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
        }
        
        .stats-card {
            background: white;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border-left: 4px solid #0a2f4d;
        }
        
        .stats-number {
            font-size: 32px;
            font-weight: bold;
            color: #0a2f4d;
        }
        
        .stats-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
        }
        
        .table-container {
            background: white;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .btn-pnp {
            background: #0a2f4d;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 3px;
        }
        
        .btn-pnp:hover {
            background: #c9a959;
            color: #0a2f4d;
        }
        
        .badge-active {
            background: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .badge-delisted {
            background: #dc3545;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
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
                <div class="text-end">
                    <div><strong><?php echo $_SESSION['rank'] . ' ' . $_SESSION['full_name']; ?></strong></div>
                    <small><?php echo $_SESSION['unit']; ?></small>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <div class="sidebar">
                    <div class="text-center mb-3">
                        <i class="fas fa-user-shield fa-3x" style="color: #0a2f4d;"></i>
                        <h5 class="mt-2"><?php echo $_SESSION['rank']; ?></h5>
                        <p class="text-muted"><?php echo $_SESSION['full_name']; ?></p>
                    </div>
                    <hr>
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a class="nav-link" href="profile_form.php">
                            <i class="fas fa-plus-circle"></i> New Profile
                        </a>
                        <a class="nav-link" href="profiles.php">
                            <i class="fas fa-list"></i> View Profiles
                        </a>
                        <a class="nav-link" href="search.php">
                            <i class="fas fa-search"></i> Search
                        </a>
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users-cog"></i> User Management
                        </a>
                        <?php endif; ?>
                        <hr>
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </nav>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="row">
                    <div class="col-md-4">
                        <div class="stats-card">
                            <div class="stats-number"><?php echo $stats['total']; ?></div>
                            <div class="stats-label">Total Profiles</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <div class="stats-number"><?php echo $stats['active']; ?></div>
                            <div class="stats-label">Active Profiles</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <div class="stats-number"><?php echo $stats['delisted']; ?></div>
                            <div class="stats-label">Delisted</div>
                        </div>
                    </div>
                </div>
                
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Recent Profiles</h5>
                        <a href="profile_form.php" class="btn btn-pnp btn-sm">
                            <i class="fas fa-plus"></i> New Profile
                        </a>
                    </div>
                    
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Alias</th>
                                <th>Age</th>
                                <th>Status</th>
                                <th>Date Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_profiles as $profile): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($profile['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($profile['alias'] ?: 'N/A'); ?></td>
                                <td><?php echo $profile['age']; ?></td>
                                <td>
                                    <span class="badge-<?php echo $profile['status']; ?>">
                                        <?php echo ucfirst($profile['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($profile['created_at'])); ?></td>
                                <td>
                                    <a href="view_profile.php?id=<?php echo $profile['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_profile.php?id=<?php echo $profile['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
</body>
</html>