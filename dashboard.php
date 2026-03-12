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

// Check for success message from profile form
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

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

// Get distinct years from arrest dates
$yearQuery = "SELECT DISTINCT YEAR(arrest_datetime) as year 
              FROM biographical_profiles 
              WHERE arrest_datetime IS NOT NULL 
              ORDER BY year DESC";
$yearStmt = $db->query($yearQuery);
$years = $yearStmt->fetchAll(PDO::FETCH_ASSOC);

// Get distinct months from arrest dates
$monthQuery = "SELECT DISTINCT MONTH(arrest_datetime) as month, 
               MONTHNAME(arrest_datetime) as month_name 
               FROM biographical_profiles 
               WHERE arrest_datetime IS NOT NULL 
               ORDER BY month";
$monthStmt = $db->query($monthQuery);
$months = $monthStmt->fetchAll(PDO::FETCH_ASSOC);

// Get filter values from URL
$selectedYear = isset($_GET['year']) ? $_GET['year'] : '';
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : '';

// Recent profiles with filter (based on arrest date)
$recentQuery = "SELECT * FROM biographical_profiles WHERE 1=1";
if (!empty($selectedYear)) {
    $recentQuery .= " AND YEAR(arrest_datetime) = :year";
}
if (!empty($selectedMonth)) {
    $recentQuery .= " AND MONTH(arrest_datetime) = :month";
}
$recentQuery .= " ORDER BY arrest_datetime DESC LIMIT 10";

$recentStmt = $db->prepare($recentQuery);
if (!empty($selectedYear)) {
    $recentStmt->bindParam(':year', $selectedYear);
}
if (!empty($selectedMonth)) {
    $recentStmt->bindParam(':month', $selectedMonth);
}
$recentStmt->execute();
$recent_profiles = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
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
        
        /* Filter Section Styles */
        .filter-card {
            background: white;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .filter-title {
            color: #0a2f4d;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #c9a959;
        }
        
        .filter-title i {
            margin-right: 10px;
            color: #c9a959;
        }
        
        .form-select, .form-control {
            border: 1px solid #ced4da;
            border-radius: 3px;
            padding: 8px 12px;
        }
        
        .form-select:focus, .form-control:focus {
            border-color: #0a2f4d;
            box-shadow: 0 0 0 0.2rem rgba(10,47,77,0.25);
        }
        
        .btn-filter {
            background: #0a2f4d;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 3px;
            transition: all 0.3s;
        }
        
        .btn-filter:hover {
            background: #c9a959;
            color: #0a2f4d;
        }
        
        .btn-reset {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 3px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-reset:hover {
            background: #5a6268;
            color: white;
        }
        
        .active-filter {
            background: #e8f4fd;
            border-left: 4px solid #0a2f4d;
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 3px;
        }
        
        .active-filter i {
            color: #0a2f4d;
            margin-right: 5px;
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
        
        .badge-archived {
            background: #ffc107;
            color: #333;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .action-buttons .btn {
            padding: 5px 10px;
            margin: 0 2px;
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
        
        .info-text {
            font-size: 13px;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .no-arrest {
            color: #999;
            font-style: italic;
        }
        
        .arrest-badge {
            background: #dc3545;
            color: white;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 10px;
            margin-left: 5px;
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
        <!-- Success Message -->
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
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
                
                <!-- Filter Section (based on arrest date) -->
                <div class="filter-card">
                    <div class="filter-title">
                        <i class="fas fa-filter"></i> Filter by Arrest Date
                    </div>
                    
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label"><i class="fas fa-calendar"></i> Arrest Year</label>
                            <select name="year" class="form-select">
                                <option value="">All Years</option>
                                <?php foreach ($years as $year): ?>
                                    <option value="<?php echo $year['year']; ?>" 
                                        <?php echo $selectedYear == $year['year'] ? 'selected' : ''; ?>>
                                        <?php echo $year['year']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><i class="fas fa-calendar-alt"></i> Arrest Month</label>
                            <select name="month" class="form-select">
                                <option value="">All Months</option>
                                <?php foreach ($months as $month): ?>
                                    <option value="<?php echo $month['month']; ?>" 
                                        <?php echo $selectedMonth == $month['month'] ? 'selected' : ''; ?>>
                                        <?php echo $month['month_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-filter me-2">
                                <i class="fas fa-search"></i> Apply Filter
                            </button>
                            <a href="dashboard.php" class="btn btn-reset">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </form>
                    
                    <!-- Active Filter Display -->
                    <?php if (!empty($selectedYear) || !empty($selectedMonth)): ?>
                    <div class="active-filter mt-3">
                        <i class="fas fa-info-circle"></i>
                        <strong>Active Filter:</strong> 
                        Showing arrests from 
                        <?php 
                        if (!empty($selectedMonth) && !empty($selectedYear)) {
                            $monthName = date('F', mktime(0, 0, 0, $selectedMonth, 1));
                            echo "<span class='badge bg-primary'>$monthName $selectedYear</span>";
                        } elseif (!empty($selectedYear)) {
                            echo "<span class='badge bg-primary'>Year $selectedYear</span>";
                        } elseif (!empty($selectedMonth)) {
                            $monthName = date('F', mktime(0, 0, 0, $selectedMonth, 1));
                            echo "<span class='badge bg-primary'>Month of $monthName</span>";
                        }
                        ?>
                        <span class="info-text float-end">
                            Total: <?php echo count($recent_profiles); ?> arrests
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>
                            <i class="fas fa-clock me-2"></i>
                            <?php 
                            if (!empty($selectedYear) || !empty($selectedMonth)) {
                                echo "Arrest Records";
                            } else {
                                echo "Recent Arrests";
                            }
                            ?>
                        </h5>
                        <a href="profile_form.php" class="btn btn-pnp btn-sm">
                            <i class="fas fa-plus"></i> New Profile
                        </a>
                    </div>
                    
                    <?php if (count($recent_profiles) > 0): ?>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Alias</th>
                                <th>Age</th>
                                <th>Status</th>
                                <th>Arrest Date</th>
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
                                <td>
                                    <?php 
                                    if (!empty($profile['arrest_datetime'])) {
                                        echo date('Y-m-d', strtotime($profile['arrest_datetime']));
                                        echo ' <span class="arrest-badge"><i class="fas fa-clock"></i> ' . date('H:i', strtotime($profile['arrest_datetime'])) . '</span>';
                                    } else {
                                        echo '<span class="no-arrest">No arrest record</span>';
                                    }
                                    ?>
                                </td>
                                <td class="action-buttons">
                                    <a href="view_profile.php?id=<?php echo $profile['id']; ?>" class="btn btn-sm btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_profile.php?id=<?php echo $profile['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No arrest records found for the selected period.</p>
                        <a href="dashboard.php" class="btn btn-sm btn-filter">View All Arrests</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-submit form when dropdown changes
        document.querySelector('select[name="year"]').addEventListener('change', function() {
            this.form.submit();
        });
        
        document.querySelector('select[name="month"]').addEventListener('change', function() {
            this.form.submit();
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>