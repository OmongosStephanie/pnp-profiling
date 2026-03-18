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

// Arrested profiles
$query = "SELECT COUNT(*) as total FROM biographical_profiles WHERE arrest_datetime IS NOT NULL";
$stmt = $db->query($query);
$stats['arrested'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get barangay statistics for pie chart
$barangayStatsQuery = "SELECT 
                        TRIM(SUBSTRING_INDEX(present_address, ',', 1)) as barangay,
                        COUNT(*) as count
                        FROM biographical_profiles 
                        WHERE present_address IS NOT NULL AND present_address != ''
                        GROUP BY barangay
                        ORDER BY count DESC
                        LIMIT 5";
$barangayStatsStmt = $db->query($barangayStatsQuery);
$barangayStats = $barangayStatsStmt->fetchAll(PDO::FETCH_ASSOC);

$barangayLabels = [];
$barangayCounts = [];
$barangayData = []; // For clickable links
foreach ($barangayStats as $stat) {
    $barangayLabels[] = $stat['barangay'];
    $barangayCounts[] = $stat['count'];
    $barangayData[] = [
        'barangay' => $stat['barangay'],
        'count' => $stat['count']
    ];
}

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
$selectedBarangay = isset($_GET['barangay']) ? $_GET['barangay'] : '';

// Recent profiles with filters
$recentQuery = "SELECT * FROM biographical_profiles WHERE 1=1";

if (!empty($selectedYear)) {
    $recentQuery .= " AND YEAR(arrest_datetime) = :year";
}
if (!empty($selectedMonth)) {
    $recentQuery .= " AND MONTH(arrest_datetime) = :month";
}
if (!empty($selectedBarangay)) {
    $recentQuery .= " AND present_address LIKE :barangay";
}

$recentQuery .= " ORDER BY created_at DESC LIMIT 10";

$recentStmt = $db->prepare($recentQuery);

if (!empty($selectedYear)) {
    $recentStmt->bindParam(':year', $selectedYear);
}
if (!empty($selectedMonth)) {
    $recentStmt->bindParam(':month', $selectedMonth);
}
if (!empty($selectedBarangay)) {
    $barangayParam = $selectedBarangay . '%';
    $recentStmt->bindParam(':barangay', $barangayParam);
}

$recentStmt->execute();
$recent_profiles = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent activities
$activityQuery = "SELECT bp.*, u.full_name as creator_name 
                 FROM biographical_profiles bp 
                 LEFT JOIN users u ON bp.created_by = u.id 
                 ORDER BY bp.created_at DESC LIMIT 5";
$activityStmt = $db->query($activityQuery);
$recent_activities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PNP Biographical Profiling System - Dashboard</title>
    
    <!-- External CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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

        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 16px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 5px solid #c9a959;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-text h2 {
            font-size: 24px;
            font-weight: 600;
            color: #0a2f4d;
            margin: 0 0 5px;
        }

        .welcome-text p {
            color: #64748b;
            margin: 0;
            font-size: 14px;
        }

        .date-badge {
            background: #0a2f4d;
            color: white;
            padding: 10px 20px;
            border-radius: 40px;
            font-size: 14px;
        }

        .date-badge i {
            margin-right: 8px;
            color: #c9a959;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.2s;
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

        .stat-icon.total { background: #e8f2ff; color: #0a2f4d; }
        .stat-icon.active { background: #e3f9e5; color: #28a745; }
        .stat-icon.delisted { background: #ffe5e5; color: #dc3545; }
        .stat-icon.arrested { background: #fff3cd; color: #ffc107; }

        .stat-details h3 {
            font-size: 28px;
            font-weight: 700;
            color: #0a2f4d;
            margin: 0;
            line-height: 1.2;
        }

        .stat-details p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 13px;
            font-weight: 500;
            text-transform: uppercase;
        }

        /* Charts Row - Updated for bigger pie chart */
        .charts-row {
            display: grid;
            grid-template-columns: 2fr 1fr; /* Pie chart takes 2/3, filter takes 1/3 */
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #c9a959;
            transition: all 0.2s;
        }

        .chart-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .chart-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        .chart-header i {
            font-size: 18px;
            color: #c9a959;
        }

        .chart-header h4 {
            font-size: 16px;
            font-weight: 600;
            color: #0a2f4d;
            margin: 0;
        }

        .chart-container {
            height: 300px; /* Bigger height for pie chart */
            position: relative;
            cursor: pointer;
        }

        /* Chart Legend with Clickable Items */
        .chart-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 20px;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 12px;
        }

        .legend-item:hover {
            background: #0a2f4d;
            color: white;
            transform: translateY(-2px);
        }

        .legend-item:hover .legend-count {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 3px;
        }

        .legend-name {
            font-weight: 500;
        }

        .legend-count {
            background: #e2e8f0;
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
        }

        /* Filter Section - Smaller design */
        .filter-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #c9a959;
        }

        .filter-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        .filter-header i {
            font-size: 16px;
            color: #c9a959;
        }

        .filter-header h4 {
            margin: 0;
            font-size: 15px;
            font-weight: 600;
            color: #0a2f4d;
        }

        .filter-form {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 3px;
            text-transform: uppercase;
        }

        .filter-select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 13px;
            color: #1e293b;
            background: white;
            cursor: pointer;
        }

        .filter-select:focus {
            outline: none;
            border-color: #c9a959;
        }

        .btn-reset {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s;
            width: 100%;
            margin-top: 5px;
        }

        .btn-reset:hover {
            background: #e2e8f0;
            color: #0a2f4d;
        }

        .active-filter-badge {
            margin-top: 15px;
            padding: 10px 12px;
            background: #f0f9ff;
            border-radius: 8px;
            border-left: 4px solid #0a2f4d;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            flex-wrap: wrap;
        }

        .filter-tag {
            background: #0a2f4d;
            color: white;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 11px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-left: 8px;
        }

        .filter-tag i {
            color: #c9a959;
            font-size: 10px;
        }

        /* Tables Container */
        .tables-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .table-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .card-header h5 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #0a2f4d;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-header h5 i {
            color: #c9a959;
        }

        .badge-count {
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: #0a2f4d;
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
        }

        .modern-table th {
            text-align: left;
            padding: 12px 10px;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
        }

        .modern-table td {
            padding: 12px 10px;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
        }

        .modern-table tr:hover td {
            background: #f8fafc;
        }

        .action-btns {
            display: flex;
            gap: 5px;
        }

        .btn-icon {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            font-size: 12px;
            transition: all 0.2s;
        }

        .btn-icon.view { background: #0a2f4d; }
        .btn-icon.edit { background: #c9a959; }
        .btn-icon.view:hover { background: #123b5e; }
        .btn-icon.edit:hover { background: #d4b36a; }

        .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .activity-item {
            display: flex;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 36px;
            height: 36px;
            background: #f1f5f9;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0a2f4d;
        }

        .activity-details {
            flex: 1;
        }

        .activity-title {
            font-weight: 500;
            color: #1e293b;
            margin-bottom: 3px;
            font-size: 13px;
        }

        .activity-meta {
            font-size: 11px;
            color: #94a3b8;
            display: flex;
            gap: 10px;
        }

        .footer {
            background: white;
            padding: 20px 0;
            margin-top: 40px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }

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

        @media (max-width: 1024px) {
            .charts-row {
                grid-template-columns: 1fr; /* Stack on mobile */
            }
            
            .tables-grid {
                grid-template-columns: 1fr;
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
                    <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="profile_form.php"><i class="fas fa-plus-circle"></i> New Profile</a></li>
                    <li><a href="profiles.php"><i class="fas fa-list"></i> View Profiles</a></li>
                    <li><a href="barangays.php"><i class="fas fa-map-marker-alt"></i> Barangays</a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                    <li><a href="users.php"><i class="fas fa-users-cog"></i> Account</a></li>
                    <?php endif; ?>
                    <li style="margin-left: auto;"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <!-- Success Message -->
        <?php if ($success_message): ?>
            <div class="alert-modern alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success_message; ?></span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" style="background: none; border: none; cursor: pointer;">×</button>
            </div>
        <?php endif; ?>

        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="welcome-text">
                <h2>Welcome back, <?php echo $_SESSION['full_name']; ?>!</h2>
                <p>Here's what's happening with your biographical profiles today.</p>
            </div>
            <div class="date-badge">
                <i class="fas fa-calendar-alt"></i> <?php echo date('F d, Y'); ?>
            </div>
        </div>

        <!-- Charts Row - Pie Chart (Big) and Filter (Small) -->
        <div class="charts-row">
            <!-- Pie Chart Card (Big) -->
            <div class="chart-card">
                <div class="chart-header">
                    <i class="fas fa-chart-pie"></i>
                    <h4>Top 5 Barangays by Profile Count</h4>
                </div>
                <div class="chart-container">
                    <canvas id="barangayPieChart"></canvas>
                </div>
                <!-- Clickable Legend -->
                <div class="chart-legend" id="pieChartLegend">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>

            <!-- Filter Section (Small) -->
            <div class="filter-card">
                <div class="filter-header">
                    <i class="fas fa-filter"></i>
                    <h4>Quick Filters</h4>
                </div>
                <div>
                    <form method="GET" action="" id="filterForm">
                        <!-- Barangay Filter -->
                        <div class="filter-group">
                            <label><i class="fas fa-map-marker-alt"></i> Barangay</label>
                            <select name="barangay" class="filter-select" id="barangaySelect" onchange="this.form.submit()">
                                <option value="">All Barangays</option>
                                <?php
                                $manolo_barangays = [
                                    'Agusan Canyon', 'Alae', 'Dahilayan', 'Dalirig', 'Damilag',
                                    'Dicklum', 'Guilang-guilang', 'Kalugmanan', 'Lindaban', 'Lingion',
                                    'Lunocan', 'Maluko', 'Mambatangan', 'Mampayag', 'Minsuro',
                                    'Mantibugao', 'Tankulan (Poblacion)', 'San Miguel', 'Sankanan',
                                    'Santiago', 'Santo Niño', 'Ticala'
                                ];
                                sort($manolo_barangays);
                                foreach ($manolo_barangays as $barangay): 
                                ?>
                                    <option value="<?php echo htmlspecialchars($barangay); ?>" 
                                        <?php echo $selectedBarangay == $barangay ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($barangay); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Year Filter -->
                        <div class="filter-group">
                            <label><i class="fas fa-calendar"></i> Arrest Year</label>
                            <select name="year" class="filter-select" onchange="this.form.submit()">
                                <option value="">All Years</option>
                                <?php 
                                for ($y = 2016; $y <= 2026; $y++): 
                                ?>
                                    <option value="<?php echo $y; ?>" 
                                        <?php echo $selectedYear == $y ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <!-- Month Filter -->
                        <div class="filter-group">
                            <label><i class="fas fa-calendar-alt"></i> Arrest Month</label>
                            <select name="month" class="filter-select" onchange="this.form.submit()">
                                <option value="">All Months</option>
                                <?php foreach ($months as $month): ?>
                                    <option value="<?php echo $month['month']; ?>" 
                                        <?php echo $selectedMonth == $month['month'] ? 'selected' : ''; ?>>
                                        <?php echo $month['month_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Reset Button -->
                        <a href="dashboard.php" class="btn-reset">
                            <i class="fas fa-redo"></i> Reset Filters
                        </a>
                    </form>
                </div>
            </div>
        </div>

        <!-- Active Filter Display -->
        <?php if (!empty($selectedBarangay) || !empty($selectedYear) || !empty($selectedMonth)): ?>
        <div class="active-filter-badge" style="margin-bottom: 20px;">
            <i class="fas fa-info-circle"></i>
            <span>
                <strong>Active Filters:</strong>
                
                <?php if (!empty($selectedBarangay)): ?>
                    <span class="filter-tag">
                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($selectedBarangay); ?>
                    </span>
                <?php endif; ?>
                
                <?php if (!empty($selectedYear)): ?>
                    <span class="filter-tag">
                        <i class="fas fa-calendar"></i> Year: <?php echo $selectedYear; ?>
                    </span>
                <?php endif; ?>
                
                <?php if (!empty($selectedMonth)): ?>
                    <span class="filter-tag">
                        <i class="fas fa-calendar-alt"></i> 
                        Month: <?php echo date('F', mktime(0, 0, 0, $selectedMonth, 1)); ?>
                    </span>
                <?php endif; ?>
                
                <span class="badge-count" style="margin-left: 10px;">
                    <?php echo count($recent_profiles); ?> records found
                </span>
            </span>
        </div>
        <?php endif; ?>

        <!-- Tables Grid -->
        <div class="tables-grid">
            <!-- Recent Profiles Table -->
            <div class="table-card">
                <div class="card-header">
                    <h5>
                        <i class="fas fa-clock"></i>
                        <?php 
                        if (!empty($selectedBarangay) || !empty($selectedYear) || !empty($selectedMonth)) {
                            echo "Filtered Profiles";
                        } else {
                            echo "Recent Profiles";
                        }
                        ?>
                    </h5>
                    <span class="badge-count"><?php echo count($recent_profiles); ?> entries</span>
                </div>
                
                <?php if (count($recent_profiles) > 0): ?>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Alias</th>
                            <th>Age</th>
                            <th>Barangay</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_profiles as $profile): 
                            $barangay = '';
                            if (!empty($profile['present_address'])) {
                                $addressParts = explode(',', $profile['present_address']);
                                $barangay = trim($addressParts[0]);
                            }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($profile['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($profile['alias'] ?: '—'); ?></td>
                            <td><?php echo $profile['age']; ?></td>
                            <td>
                                <?php if (!empty($barangay)): ?>
                                    <span class="filter-tag" style="background: #f1f5f9; color: #0a2f4d;">
                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($barangay); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="no-arrest">Not specified</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <a href="view_profile.php?id=<?php echo $profile['id']; ?>" class="btn-icon view" title="View Profile">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_profile.php?id=<?php echo $profile['id']; ?>" class="btn-icon edit" title="Edit Profile">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-folder-open fa-3x" style="color: #cbd5e1; margin-bottom: 15px;"></i>
                    <p style="color: #64748b;">No profiles found matching your filters.</p>
                    <a href="dashboard.php" class="btn-filter" style="display: inline-block; text-decoration: none; margin-top: 10px; background: #0a2f4d; color: white; padding: 10px 20px; border-radius: 8px;">
                        <i class="fas fa-redo"></i> Clear Filters
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Recent Activities -->
            <div class="table-card">
                <div class="card-header">
                    <h5>
                        <i class="fas fa-history"></i>
                        Recent Activities
                    </h5>
                    <span class="badge-count">Latest 5</span>
                </div>
                
                <?php if (count($recent_activities) > 0): ?>
                <ul class="activity-list">
                    <?php foreach ($recent_activities as $activity): ?>
                    <li class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="activity-details">
                            <div class="activity-title">
                                New profile created: <?php echo htmlspecialchars($activity['full_name']); ?>
                            </div>
                            <div class="activity-meta">
                                <span><i class="fas fa-user"></i> <?php echo $activity['creator_name'] ?? 'System'; ?></span>
                                <span><i class="fas fa-clock"></i> <?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?></span>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-history fa-2x" style="color: #cbd5e1; margin-bottom: 10px;"></i>
                    <p style="color: #64748b;">No recent activities</p>
                </div>
                <?php endif; ?>
                
                <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e2e8f0;">
                    <a href="profiles.php" style="color: #0a2f4d; text-decoration: none; font-size: 13px; font-weight: 500;">
                        <i class="fas fa-arrow-right"></i> View all profiles
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Barangay data from PHP
        const barangayData = <?php echo json_encode($barangayData); ?>;
        const colors = ['#0a2f4d', '#c9a959', '#28a745', '#dc3545', '#ffc107'];
        
        // Pie Chart
        const pieCtx = document.getElementById('barangayPieChart').getContext('2d');
        const pieChart = new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($barangayLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($barangayCounts); ?>,
                    backgroundColor: colors,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false // Hide default legend
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${value} profiles (${percentage}%)`;
                            }
                        }
                    }
                },
                onClick: function(event, item) {
                    if (item.length > 0) {
                        const index = item[0].dataIndex;
                        const barangay = barangayData[index].barangay;
                        window.location.href = '?barangay=' + encodeURIComponent(barangay);
                    }
                }
            }
        });

        // Create clickable legend
        const legendContainer = document.getElementById('pieChartLegend');
        barangayData.forEach((item, index) => {
            const legendItem = document.createElement('div');
            legendItem.className = 'legend-item';
            legendItem.onclick = function() {
                window.location.href = '?barangay=' + encodeURIComponent(item.barangay);
            };
            
            legendItem.innerHTML = `
                <div class="legend-color" style="background-color: ${colors[index]}"></div>
                <span class="legend-name">${item.barangay}</span>
                <span class="legend-count">${item.count}</span>
            `;
            
            legendContainer.appendChild(legendItem);
        });

        // Add "All Barangays" option to legend
        const allItem = document.createElement('div');
        allItem.className = 'legend-item';
        allItem.onclick = function() {
            window.location.href = '?barangay=';
        };
        allItem.innerHTML = `
            <div class="legend-color" style="background-color: #6c757d"></div>
            <span class="legend-name">All Barangays</span>
            <span class="legend-count"><?php echo array_sum($barangayCounts); ?></span>
        `;
        legendContainer.appendChild(allItem);

        // Auto-submit form when dropdown changes
        document.querySelector('select[name="barangay"]')?.addEventListener('change', function() {
            this.form.submit();
        });
        
        document.querySelector('select[name="year"]')?.addEventListener('change', function() {
            this.form.submit();
        });
        
        document.querySelector('select[name="month"]')?.addEventListener('change', function() {
            this.form.submit();
        });

        // Close alert
        document.querySelector('.btn-close')?.addEventListener('click', function() {
            this.closest('.alert-modern').remove();
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>