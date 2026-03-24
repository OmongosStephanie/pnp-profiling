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

// Arrested profiles - Use date_time_place_of_arrest
$query = "SELECT COUNT(*) as total FROM biographical_profiles WHERE date_time_place_of_arrest IS NOT NULL AND date_time_place_of_arrest != ''";
$stmt = $db->query($query);
$stats['arrested'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get filter values from URL
$selectedYear = isset($_GET['year']) ? $_GET['year'] : '';
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : '';
$selectedBarangay = isset($_GET['barangay']) ? $_GET['barangay'] : '';

// ============================================
// DYNAMIC BARANGAY PIE CHART (Filters by Year and Month)
// This shows top barangays based on arrests in selected period
// ============================================
$barangayQuery = "SELECT 
                    TRIM(SUBSTRING_INDEX(present_address, ',', 1)) as barangay,
                    COUNT(*) as count
                    FROM biographical_profiles 
                    WHERE present_address IS NOT NULL 
                    AND present_address != ''
                    AND date_time_place_of_arrest IS NOT NULL 
                    AND date_time_place_of_arrest != ''
                    AND date_time_place_of_arrest != '0000-00-00 00:00:00'";

$barangayParams = [];

if (!empty($selectedYear)) {
    $barangayQuery .= " AND YEAR(date_time_place_of_arrest) = :year";
    $barangayParams[':year'] = $selectedYear;
}
if (!empty($selectedMonth)) {
    $barangayQuery .= " AND MONTH(date_time_place_of_arrest) = :month";
    $barangayParams[':month'] = $selectedMonth;
}

$barangayQuery .= " GROUP BY barangay ORDER BY count DESC LIMIT 5";

$barangayStmt = $db->prepare($barangayQuery);
foreach ($barangayParams as $key => $value) {
    $barangayStmt->bindValue($key, $value);
}
$barangayStmt->execute();
$barangayStats = $barangayStmt->fetchAll(PDO::FETCH_ASSOC);

$barangayLabels = [];
$barangayCounts = [];
$barangayData = [];
foreach ($barangayStats as $stat) {
    $barangayLabels[] = $stat['barangay'];
    $barangayCounts[] = $stat['count'];
    $barangayData[] = [
        'barangay' => $stat['barangay'],
        'count' => $stat['count']
    ];
}

// Get ALL barangay statistics for dropdown (without filters)
$allBarangayStatsQuery = "SELECT 
                            TRIM(SUBSTRING_INDEX(present_address, ',', 1)) as barangay,
                            COUNT(*) as count
                            FROM biographical_profiles 
                            WHERE present_address IS NOT NULL AND present_address != ''
                            GROUP BY barangay
                            ORDER BY barangay ASC";
$allBarangayStatsStmt = $db->query($allBarangayStatsQuery);
$allBarangayStats = $allBarangayStatsStmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// DYNAMIC ARREST BY YEAR/MONTH PIE CHART (Filters by Barangay)
// This shows arrests by month/year based on selected barangay
// ============================================
$arrestQuery = "SELECT 
                    YEAR(date_time_place_of_arrest) as year,
                    MONTH(date_time_place_of_arrest) as month,
                    MONTHNAME(date_time_place_of_arrest) as month_name,
                    DATE_FORMAT(date_time_place_of_arrest, '%M %Y') as month_year,
                    COUNT(*) as count
                    FROM biographical_profiles 
                    WHERE date_time_place_of_arrest IS NOT NULL 
                    AND date_time_place_of_arrest != ''
                    AND date_time_place_of_arrest != '0000-00-00 00:00:00'";

$arrestParams = [];

if (!empty($selectedBarangay)) {
    $arrestQuery .= " AND present_address LIKE :barangay";
    $arrestParams[':barangay'] = '%' . $selectedBarangay . '%';
}

if (!empty($selectedYear)) {
    $arrestQuery .= " AND YEAR(date_time_place_of_arrest) = :year";
    $arrestParams[':year'] = $selectedYear;
}

if (!empty($selectedMonth)) {
    $arrestQuery .= " AND MONTH(date_time_place_of_arrest) = :month";
    $arrestParams[':month'] = $selectedMonth;
}

$arrestQuery .= " GROUP BY YEAR(date_time_place_of_arrest), MONTH(date_time_place_of_arrest), MONTHNAME(date_time_place_of_arrest)
                    ORDER BY year DESC, month DESC
                    LIMIT 12";

$arrestStmt = $db->prepare($arrestQuery);
foreach ($arrestParams as $key => $value) {
    $arrestStmt->bindValue($key, $value);
}
$arrestStmt->execute();
$arrestByYearMonth = $arrestStmt->fetchAll(PDO::FETCH_ASSOC);

$arrestYearMonthLabels = [];
$arrestYearMonthCounts = [];
$arrestYearMonthData = [];
foreach ($arrestByYearMonth as $stat) {
    $label = $stat['month_name'] . ' ' . $stat['year'];
    $arrestYearMonthLabels[] = $label;
    $arrestYearMonthCounts[] = $stat['count'];
    $arrestYearMonthData[] = [
        'year' => $stat['year'],
        'month' => $stat['month'],
        'month_name' => $stat['month_name'],
        'label' => $label,
        'month_year' => $stat['month_year'],
        'count' => $stat['count']
    ];
}

// ============================================
// GET DISTINCT YEARS AND MONTHS FOR FILTERS
// ============================================
$yearQuery = "SELECT DISTINCT 
              YEAR(date_time_place_of_arrest) as year 
              FROM biographical_profiles 
              WHERE date_time_place_of_arrest IS NOT NULL 
              AND date_time_place_of_arrest != ''
              AND date_time_place_of_arrest != '0000-00-00 00:00:00'
              ORDER BY year DESC";
$yearStmt = $db->query($yearQuery);
$years = $yearStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all months from database (for months that have data)
$monthQuery = "SELECT DISTINCT 
                MONTH(date_time_place_of_arrest) as month, 
                MONTHNAME(date_time_place_of_arrest) as month_name 
                FROM biographical_profiles 
                WHERE date_time_place_of_arrest IS NOT NULL 
                AND date_time_place_of_arrest != ''
                AND date_time_place_of_arrest != '0000-00-00 00:00:00'
                ORDER BY month";
$monthStmt = $db->query($monthQuery);
$monthsWithData = $monthStmt->fetchAll(PDO::FETCH_ASSOC);

// Create array of all months (January to December)
$allMonths = [
    1 => 'January',
    2 => 'February',
    3 => 'March',
    4 => 'April',
    5 => 'May',
    6 => 'June',
    7 => 'July',
    8 => 'August',
    9 => 'September',
    10 => 'October',
    11 => 'November',
    12 => 'December'
];

// ============================================
// GET FILTERED PROFILES FOR TABLE
// ============================================
function getFilteredProfiles($db, $selectedBarangay, $selectedYear, $selectedMonth) {
    $query = "SELECT * FROM biographical_profiles WHERE 1=1";
    $params = [];
    
    if (!empty($selectedBarangay)) {
        $query .= " AND present_address LIKE :barangay";
        $params[':barangay'] = '%' . $selectedBarangay . '%';
    }
    
    if (!empty($selectedYear)) {
        $query .= " AND YEAR(date_time_place_of_arrest) = :year";
        $params[':year'] = $selectedYear;
    }
    
    if (!empty($selectedMonth)) {
        $query .= " AND MONTH(date_time_place_of_arrest) = :month";
        $params[':month'] = $selectedMonth;
    }
    
    $query .= " ORDER BY date_time_place_of_arrest DESC LIMIT 50";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$filtered_profiles = getFilteredProfiles($db, $selectedBarangay, $selectedYear, $selectedMonth);
$filtered_count = count($filtered_profiles);
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
        /* All CSS styles remain the same as previous */
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

        .main-content {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

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

        .filter-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
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
            display: grid;
            grid-template-columns: repeat(3, 1fr) auto;
            gap: 12px;
            align-items: flex-end;
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
            white-space: nowrap;
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

        .charts-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
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
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        .chart-header-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-header-left i {
            font-size: 18px;
            color: #c9a959;
        }

        .chart-header-left h4 {
            font-size: 16px;
            font-weight: 600;
            color: #0a2f4d;
            margin: 0;
        }

        .filter-badge {
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            color: #0a2f4d;
        }

        .chart-container {
            height: 250px;
            position: relative;
            cursor: pointer;
        }

        .chart-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            justify-content: center;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 4px 8px;
            border-radius: 20px;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 11px;
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
            width: 10px;
            height: 10px;
            border-radius: 2px;
        }

        .legend-name {
            font-weight: 500;
        }

        .legend-count {
            background: #e2e8f0;
            padding: 2px 5px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 600;
        }

        .no-data-message {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
        }

        .profiles-table-container {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-top: 20px;
        }

        .profiles-table {
            width: 100%;
            border-collapse: collapse;
        }

        .profiles-table th {
            text-align: left;
            padding: 12px 10px;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
        }

        .profiles-table td {
            padding: 12px 10px;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
        }

        .arrest-date-badge {
            background: #fef3c7;
            color: #92400e;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
            display: inline-block;
            white-space: nowrap;
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

        @media (max-width: 1024px) {
            .charts-row {
                grid-template-columns: 1fr;
            }
            .filter-form {
                grid-template-columns: 1fr;
            }
            .profiles-table-container {
                overflow-x: auto;
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

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $stats['total']; ?></h3>
                    <p>Total Profiles</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $stats['active']; ?></h3>
                    <p>Active</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon delisted">
                    <i class="fas fa-user-times"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $stats['delisted']; ?></h3>
                    <p>Delisted</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon arrested">
                    <i class="fas fa-gavel"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $stats['arrested']; ?></h3>
                    <p>Arrest Records</p>
                </div>
            </div>
        </div>

        <!-- Quick Filters -->
        <div class="filter-card">
            <div class="filter-header">
                <i class="fas fa-filter"></i>
                <h4>Quick Filters</h4>
            </div>
            <div>
                <form method="GET" action="" id="filterForm" class="filter-form">
                    <div class="filter-group">
                        <label><i class="fas fa-map-marker-alt"></i> Barangay</label>
                        <select name="barangay" class="filter-select" onchange="this.form.submit()">
                            <option value="">All Barangays</option>
                            <?php
                            $allBarangays = [];
                            foreach ($allBarangayStats as $stat) {
                                $allBarangays[] = $stat['barangay'];
                            }
                            sort($allBarangays);
                            foreach ($allBarangays as $barangay): 
                            ?>
                                <option value="<?php echo htmlspecialchars($barangay); ?>" 
                                    <?php echo $selectedBarangay == $barangay ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($barangay); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-calendar"></i> Arrest Year</label>
                        <select name="year" class="filter-select" onchange="this.form.submit()">
                            <option value="">All Years</option>
                            <?php 
                            $currentYear = date('Y');
                            for ($y = 2016; $y <= $currentYear; $y++): 
                            ?>
                                <option value="<?php echo $y; ?>" <?php echo $selectedYear == $y ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-calendar-alt"></i> Arrest Month</label>
                        <select name="month" class="filter-select" onchange="this.form.submit()">
                            <option value="">All Months</option>
                            <?php 
                            // Display all months from January to December
                            for ($m = 1; $m <= 12; $m++): 
                                $monthName = date('F', mktime(0, 0, 0, $m, 1));
                                $monthNumber = $m;
                            ?>
                                <option value="<?php echo $monthNumber; ?>" <?php echo $selectedMonth == $monthNumber ? 'selected' : ''; ?>>
                                    <?php echo $monthName; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group" style="justify-content: flex-end;">
                        <a href="dashboard.php" class="btn-reset">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Active Filter Display -->
            <?php if (!empty($selectedBarangay) || !empty($selectedYear) || !empty($selectedMonth)): ?>
            <div class="active-filter-badge">
                <i class="fas fa-info-circle"></i>
                <span>
                    <strong>Active Filters:</strong>
                    <?php if (!empty($selectedBarangay)): ?>
                        <span class="filter-tag"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($selectedBarangay); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($selectedYear)): ?>
                        <span class="filter-tag"><i class="fas fa-calendar"></i> Year: <?php echo $selectedYear; ?></span>
                    <?php endif; ?>
                    <?php if (!empty($selectedMonth)): ?>
                        <span class="filter-tag"><i class="fas fa-calendar-alt"></i> Month: <?php echo date('F', mktime(0, 0, 0, $selectedMonth, 1)); ?></span>
                    <?php endif; ?>
                    <span class="badge-count" style="background: #0a2f4d; color: white;"><?php echo $filtered_count; ?> records found</span>
                </span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Charts Row -->
        <div class="charts-row">
            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-header-left">
                        <i class="fas fa-chart-pie"></i>
                        <h4>Top 5 Barangays by Arrest Count</h4>
                    </div>
                    <?php if (!empty($selectedYear) || !empty($selectedMonth)): ?>
                        <span class="filter-badge">
                            <i class="fas fa-filter"></i> 
                            <?php 
                            if (!empty($selectedYear)) echo "Year: $selectedYear";
                            if (!empty($selectedMonth)) echo " Month: " . date('F', mktime(0, 0, 0, $selectedMonth, 1));
                            ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="chart-container">
                    <canvas id="barangayPieChart"></canvas>
                </div>
                <div class="chart-legend" id="pieChartLegend"></div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-header-left">
                        <i class="fas fa-chart-line"></i>
                        <h4>Arrests by Month/Year</h4>
                    </div>
                    <?php if (!empty($selectedBarangay)): ?>
                        <span class="filter-badge">
                            <i class="fas fa-filter"></i> Barangay: <?php echo htmlspecialchars($selectedBarangay); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="chart-container">
                    <canvas id="arrestYearMonthPieChart"></canvas>
                </div>
                <div class="chart-legend" id="arrestYearMonthLegend"></div>
            </div>
        </div>

        <!-- Filtered Profiles Table -->
        <?php if (!empty($selectedBarangay) || !empty($selectedYear) || !empty($selectedMonth)): ?>
        <div class="profiles-table-container">
            <div class="card-header" style="margin-bottom: 15px;">
                <h5><i class="fas fa-list"></i> Filtered Profiles</h5>
                <span class="badge-count"><?php echo $filtered_count; ?> entries</span>
            </div>
            
            <?php if ($filtered_count > 0): ?>
            <div style="overflow-x: auto;">
                <table class="profiles-table">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Alias</th>
                            <th>Age</th>
                            <th>Barangay</th>
                            <th>Date of Arrest</th>
                            <th>Actions</th>
                         </thead>
                    <tbody>
                        <?php foreach ($filtered_profiles as $profile): 
                            $barangay = '';
                            if (!empty($profile['present_address'])) {
                                $addressParts = explode(',', $profile['present_address']);
                                $barangay = trim($addressParts[0]);
                            }
                            
                            $arrest_date = '';
                            if (!empty($profile['date_time_place_of_arrest'])) {
                                $arrest_datetime = strtotime($profile['date_time_place_of_arrest']);
                                if ($arrest_datetime) {
                                    $arrest_date = date('M d, Y', $arrest_datetime);
                                } else {
                                    $arrest_date = $profile['date_time_place_of_arrest'];
                                }
                            }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($profile['full_name']); ?>\\
                            了一般<?php echo htmlspecialchars($profile['alias'] ?: '—'); ?>\\
                            了一般<?php echo $profile['age']; ?>\\
                            了一般
                                <?php if (!empty($barangay)): ?>
                                    <span class="filter-tag" style="background: #f1f5f9; color: #0a2f4d;"><?php echo htmlspecialchars($barangay); ?></span>
                                <?php else: ?>
                                    <span class="no-arrest">Not specified</span>
                                <?php endif; ?>
                              一般
                            了一般
                                <?php if (!empty($arrest_date)): ?>
                                    <span class="arrest-date-badge">
                                        <i class="fas fa-calendar-check"></i> <?php echo htmlspecialchars($arrest_date); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="no-arrest">No arrest record</span>
                                <?php endif; ?>
                              一般
                            <td class="action-btns">
                                <a href="view_profile.php?id=<?php echo $profile['id']; ?>" class="btn-icon view" title="View Profile"><i class="fas fa-eye"></i></a>
                                <a href="edit_profile.php?id=<?php echo $profile['id']; ?>" class="btn-icon edit" title="Edit Profile"><i class="fas fa-edit"></i></a>
                            一般
                         ?>
                        <?php endforeach; ?>
                    </tbody>
                66
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-folder-open fa-3x" style="color: #cbd5e1; margin-bottom: 15px;"></i>
                <p style="color: #64748b;">No profiles found matching your filters.</p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <div class="container">
            <p>© <?php echo date('Y'); ?> Philippine National Police - Manolo Fortich Police Station. All rights reserved.</p>
            <p>Department of the Interior and Local Government | Bukidnon Police Provincial Office</p>
        </div>
    </footer>

    <script>
        // Barangay data from PHP
        const barangayData = <?php echo json_encode($barangayData); ?>;
        const barangayColors = ['#0a2f4d', '#c9a959', '#28a745', '#dc3545', '#ffc107'];
        
        // Arrest by Year and Month data
        const arrestYearMonthData = <?php echo json_encode($arrestYearMonthData); ?>;
        
        // Generate colors for year-month combinations
        const yearMonthColors = [];
        for (let i = 0; i < arrestYearMonthData.length; i++) {
            const hue = (i * 45) % 360;
            yearMonthColors.push(`hsl(${hue}, 70%, 50%)`);
        }
        
        // Barangay Pie Chart - shows top barangays based on arrests from date_time_place_of_arrest
        if (barangayData.length > 0) {
            const pieCtx = document.getElementById('barangayPieChart').getContext('2d');
            new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode($barangayLabels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($barangayCounts); ?>,
                        backgroundColor: barangayColors,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false }, 
                        tooltip: { 
                            callbacks: { 
                                label: function(context) { 
                                    const label = context.label || ''; 
                                    const value = context.raw || 0; 
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0); 
                                    const percentage = ((value / total) * 100).toFixed(1); 
                                    return `${label}: ${value} arrests (${percentage}%)`; 
                                } 
                            } 
                        } 
                    },
                    onClick: function(event, item) { 
                        if (item.length > 0) { 
                            const index = item[0].dataIndex; 
                            let url = '?barangay=' + encodeURIComponent(barangayData[index].barangay);
                            <?php if (!empty($selectedYear)): ?>
                                url += '&year=<?php echo $selectedYear; ?>';
                            <?php endif; ?>
                            <?php if (!empty($selectedMonth)): ?>
                                url += '&month=<?php echo $selectedMonth; ?>';
                            <?php endif; ?>
                            window.location.href = url; 
                        } 
                    }
                }
            });
        } else {
            document.getElementById('barangayPieChart').style.display = 'none';
            document.getElementById('pieChartLegend').innerHTML = '<div class="no-data-message">No arrest data available for the selected period</div>';
        }
        
        // Arrest by Year and Month Pie Chart - shows arrests grouped by month/year from date_time_place_of_arrest
        if (arrestYearMonthData.length > 0) {
            const arrestYearMonthCtx = document.getElementById('arrestYearMonthPieChart').getContext('2d');
            new Chart(arrestYearMonthCtx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode($arrestYearMonthLabels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($arrestYearMonthCounts); ?>,
                        backgroundColor: yearMonthColors,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false }, 
                        tooltip: { 
                            callbacks: { 
                                label: function(context) { 
                                    const label = context.label || ''; 
                                    const value = context.raw || 0; 
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0); 
                                    const percentage = ((value / total) * 100).toFixed(1); 
                                    return `${label}: ${value} arrests (${percentage}%)`; 
                                } 
                            } 
                        } 
                    },
                    onClick: function(event, item) { 
                        if (item.length > 0) { 
                            const index = item[0].dataIndex; 
                            const data = arrestYearMonthData[index];
                            let url = '?year=' + data.year + '&month=' + data.month;
                            <?php if (!empty($selectedBarangay)): ?>
                                url += '&barangay=<?php echo urlencode($selectedBarangay); ?>';
                            <?php endif; ?>
                            window.location.href = url; 
                        } 
                    }
                }
            });
        } else {
            document.getElementById('arrestYearMonthPieChart').style.display = 'none';
            document.getElementById('arrestYearMonthLegend').innerHTML = '<div class="no-data-message">No arrest data available for the selected barangay</div>';
        }

        // Create clickable legend for Barangay
        const legendContainer = document.getElementById('pieChartLegend');
        if (barangayData.length > 0) {
            barangayData.forEach((item, index) => {
                const legendItem = document.createElement('div');
                legendItem.className = 'legend-item';
                legendItem.onclick = function() { 
                    let url = '?barangay=' + encodeURIComponent(item.barangay);
                    <?php if (!empty($selectedYear)): ?>
                        url += '&year=<?php echo $selectedYear; ?>';
                    <?php endif; ?>
                    <?php if (!empty($selectedMonth)): ?>
                        url += '&month=<?php echo $selectedMonth; ?>';
                    <?php endif; ?>
                    window.location.href = url; 
                };
                legendItem.innerHTML = `<div class="legend-color" style="background-color: ${barangayColors[index]}"></div><span class="legend-name">${item.barangay}</span><span class="legend-count">${item.count}</span>`;
                legendContainer.appendChild(legendItem);
            });

            // Add "All Barangays" option
            const allItem = document.createElement('div');
            allItem.className = 'legend-item';
            allItem.onclick = function() { 
                let url = '?';
                <?php if (!empty($selectedYear)): ?>
                    url += 'year=<?php echo $selectedYear; ?>&';
                <?php endif; ?>
                <?php if (!empty($selectedMonth)): ?>
                    url += 'month=<?php echo $selectedMonth; ?>&';
                <?php endif; ?>
                window.location.href = url; 
            };
            allItem.innerHTML = `<div class="legend-color" style="background-color: #6c757d"></div><span class="legend-name">All Barangays</span><span class="legend-count"><?php echo array_sum($barangayCounts); ?></span>`;
            legendContainer.appendChild(allItem);
        }

        // Create clickable legend for Arrest by Year and Month
        const arrestYearMonthLegend = document.getElementById('arrestYearMonthLegend');
        if (arrestYearMonthData.length > 0) {
            arrestYearMonthData.forEach((item, index) => {
                const legendItem = document.createElement('div');
                legendItem.className = 'legend-item';
                legendItem.onclick = function() { 
                    let url = '?year=' + item.year + '&month=' + item.month;
                    <?php if (!empty($selectedBarangay)): ?>
                        url += '&barangay=<?php echo urlencode($selectedBarangay); ?>';
                    <?php endif; ?>
                    window.location.href = url; 
                };
                legendItem.innerHTML = `<div class="legend-color" style="background-color: ${yearMonthColors[index]}"></div><span class="legend-name">${item.label}</span><span class="legend-count">${item.count}</span>`;
                arrestYearMonthLegend.appendChild(legendItem);
            });

            // Add "All Records" option
            const allRecordsItem = document.createElement('div');
            allRecordsItem.className = 'legend-item';
            allRecordsItem.onclick = function() { 
                let url = '?';
                <?php if (!empty($selectedBarangay)): ?>
                    url += 'barangay=<?php echo urlencode($selectedBarangay); ?>&';
                <?php endif; ?>
                window.location.href = url; 
            };
            allRecordsItem.innerHTML = `<div class="legend-color" style="background-color: #6c757d"></div><span class="legend-name">All Records</span><span class="legend-count"><?php echo array_sum($arrestYearMonthCounts); ?></span>`;
            arrestYearMonthLegend.appendChild(allRecordsItem);
        }

        // Auto-submit form when dropdown changes
        document.querySelectorAll('select[name="barangay"], select[name="year"], select[name="month"]').forEach(select => {
            select.addEventListener('change', function() { this.form.submit(); });
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>