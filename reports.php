<?php
// reports.php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get selected year from URL (default to current year)
$selectedYear = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Get all available years from database (from 2016 onwards)
// Parse date from date_time_place_of_arrest field
$yearsQuery = "SELECT DISTINCT 
               YEAR(STR_TO_DATE(SUBSTRING_INDEX(date_time_place_of_arrest, 'T', 1), '%Y-%m-%d')) as year 
               FROM biographical_profiles 
               WHERE date_time_place_of_arrest IS NOT NULL 
               AND date_time_place_of_arrest != ''
               AND TRIM(date_time_place_of_arrest) != ''
               AND STR_TO_DATE(SUBSTRING_INDEX(date_time_place_of_arrest, 'T', 1), '%Y-%m-%d') IS NOT NULL
               AND YEAR(STR_TO_DATE(SUBSTRING_INDEX(date_time_place_of_arrest, 'T', 1), '%Y-%m-%d')) >= 2016
               ORDER BY year DESC";
$yearsStmt = $db->query($yearsQuery);
$availableYears = $yearsStmt->fetchAll(PDO::FETCH_ASSOC);

// If no years in database, show current year as option
if (empty($availableYears)) {
    $availableYears = [['year' => date('Y')]];
}

// Get monthly arrest statistics for SELECTED year
$monthlyQuery = "SELECT 
                  MONTH(STR_TO_DATE(SUBSTRING_INDEX(date_time_place_of_arrest, 'T', 1), '%Y-%m-%d')) as month,
                  MONTHNAME(STR_TO_DATE(SUBSTRING_INDEX(date_time_place_of_arrest, 'T', 1), '%Y-%m-%d')) as month_name,
                  COUNT(*) as arrest_count
                  FROM biographical_profiles 
                  WHERE date_time_place_of_arrest IS NOT NULL 
                  AND date_time_place_of_arrest != ''
                  AND TRIM(date_time_place_of_arrest) != ''
                  AND STR_TO_DATE(SUBSTRING_INDEX(date_time_place_of_arrest, 'T', 1), '%Y-%m-%d') IS NOT NULL
                  AND YEAR(STR_TO_DATE(SUBSTRING_INDEX(date_time_place_of_arrest, 'T', 1), '%Y-%m-%d')) = :year
                  GROUP BY MONTH(STR_TO_DATE(SUBSTRING_INDEX(date_time_place_of_arrest, 'T', 1), '%Y-%m-%d'))
                  ORDER BY month";
$monthlyStmt = $db->prepare($monthlyQuery);
$monthlyStmt->bindParam(':year', $selectedYear);
$monthlyStmt->execute();
$monthlyArrests = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);

// Create array of month counts for easy lookup
$monthCounts = [];
$monthNames = [];
$monthLabels = [];
foreach ($monthlyArrests as $m) {
    $monthCounts[$m['month']] = $m['arrest_count'];
    $monthNames[$m['month']] = $m['month_name'];
    $monthLabels[] = $m['month_name'];
}
$monthValues = array_values($monthCounts);

// Get barangay statistics - FIXED QUERY
$barangayQuery = "SELECT 
                   TRIM(SUBSTRING_INDEX(present_address, ',', 1)) as barangay,
                   COUNT(*) as total,
                   SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                   SUM(CASE WHEN status = 'delisted' THEN 1 ELSE 0 END) as delisted
                   FROM biographical_profiles 
                   WHERE present_address IS NOT NULL AND present_address != ''
                   GROUP BY TRIM(SUBSTRING_INDEX(present_address, ',', 1))
                   ORDER BY total DESC";
$barangayStmt = $db->query($barangayQuery);
$barangayReports = $barangayStmt->fetchAll(PDO::FETCH_ASSOC);

// Get drug types statistics
$drugQuery = "SELECT drugs_involved, other_drugs_pushed, drugs_pushed FROM biographical_profiles";
$drugStmt = $db->query($drugQuery);
$allDrugs = $drugStmt->fetchAll(PDO::FETCH_ASSOC);

// Process drug statistics
$drugCategories = [
    'Shabu' => 0,
    'Marijuana' => 0,
    'Cocaine' => 0,
    'Ecstasy' => 0,
    'Other' => 0
];

foreach ($allDrugs as $profile) {
    // Check drugs_involved
    if (!empty($profile['drugs_involved'])) {
        $drugs = explode(',', $profile['drugs_involved']);
        foreach ($drugs as $drug) {
            $drug = trim(strtolower($drug));
            if (strpos($drug, 'shabu') !== false) $drugCategories['Shabu']++;
            elseif (strpos($drug, 'marijuana') !== false) $drugCategories['Marijuana']++;
            elseif (strpos($drug, 'cocaine') !== false) $drugCategories['Cocaine']++;
            elseif (strpos($drug, 'ecstasy') !== false) $drugCategories['Ecstasy']++;
            else $drugCategories['Other']++;
        }
    }
    
    // Check drugs_pushed
    if (!empty($profile['drugs_pushed'])) {
        $drugs = explode(',', $profile['drugs_pushed']);
        foreach ($drugs as $drug) {
            $drug = trim(strtolower($drug));
            if (strpos($drug, 'shabu') !== false) $drugCategories['Shabu']++;
            elseif (strpos($drug, 'marijuana') !== false) $drugCategories['Marijuana']++;
            elseif (strpos($drug, 'cocaine') !== false) $drugCategories['Cocaine']++;
            elseif (strpos($drug, 'ecstasy') !== false) $drugCategories['Ecstasy']++;
            else $drugCategories['Other']++;
        }
    }
    
    // Check other_drugs_pushed
    if (!empty($profile['other_drugs_pushed'])) {
        $drugCategories['Other']++;
    }
}

// Remove drug categories with zero count
$drugCategories = array_filter($drugCategories, function($count) {
    return $count > 0;
});

// Sort by count (highest first)
arsort($drugCategories);

// Get age demographics
$ageQuery = "SELECT 
              CASE 
                WHEN age < 18 THEN 'Minor (Below 18)'
                WHEN age BETWEEN 18 AND 30 THEN 'Young Adult (18-30)'
                WHEN age BETWEEN 31 AND 50 THEN 'Adult (31-50)'
                WHEN age > 50 THEN 'Senior (51+)'
                ELSE 'Unknown'
              END as age_group,
              COUNT(*) as count
              FROM biographical_profiles 
              WHERE age IS NOT NULL
              GROUP BY age_group";
$ageStmt = $db->query($ageQuery);
$ageGroups = $ageStmt->fetchAll(PDO::FETCH_ASSOC);

// Get sex distribution
$sexQuery = "SELECT 
              sex,
              COUNT(*) as count
              FROM biographical_profiles 
              WHERE sex IS NOT NULL
              GROUP BY sex";
$sexStmt = $db->query($sexQuery);
$sexDistribution = $sexStmt->fetchAll(PDO::FETCH_ASSOC);

// Get user activity (for admin only)
$userActivity = [];
if ($_SESSION['role'] == 'admin') {
    $userQuery = "SELECT 
                   u.full_name,
                   u.rank,
                   COUNT(bp.id) as profiles_created
                   FROM users u
                   LEFT JOIN biographical_profiles bp ON u.id = bp.created_by
                   GROUP BY u.id
                   ORDER BY profiles_created DESC";
    $userStmt = $db->query($userQuery);
    $userActivity = $userStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Calculate total drug mentions
$totalDrugMentions = array_sum($drugCategories);

// Calculate total profiles
$totalProfilesQuery = "SELECT COUNT(*) as total FROM biographical_profiles";
$totalProfilesStmt = $db->query($totalProfilesQuery);
$totalProfiles = $totalProfilesStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Calculate active profiles
$activeProfilesQuery = "SELECT COUNT(*) as active FROM biographical_profiles WHERE status = 'active'";
$activeProfilesStmt = $db->query($activeProfilesQuery);
$activeProfiles = $activeProfilesStmt->fetch(PDO::FETCH_ASSOC)['active'];

// Get current date for report
$currentDate = date('F d, Y');
$generatedBy = $_SESSION['full_name'] . ' - ' . $_SESSION['rank'];

// Check if there are arrests for selected year
$hasArrests = count($monthlyArrests) > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Reports - PNP Biographical Profiling System</title>
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

        /* Side Menu Styles */
        .app-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #0a2f4d 0%, #123b5e 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar-header {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-logo {
            width: 70px;
            height: 70px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            border: 2px solid #c9a959;
        }

        .sidebar-logo i {
            font-size: 32px;
            color: #c9a959;
        }

        .sidebar-header h3 {
            font-size: 18px;
            margin: 0 0 5px;
            font-weight: 600;
        }

        .sidebar-header p {
            font-size: 12px;
            color: #b0c4de;
            margin: 0;
        }

        .user-info-sidebar {
            background: rgba(255,255,255,0.1);
            margin: 20px;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
        }

        .user-avatar-sidebar {
            width: 60px;
            height: 60px;
            background: #c9a959;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            color: #0a2f4d;
            font-weight: 600;
            font-size: 24px;
        }

        .user-name-sidebar {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .user-rank-sidebar {
            font-size: 12px;
            color: #b0c4de;
        }

        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav li {
            margin: 5px 15px;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: #e0e0e0;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .sidebar-nav a i {
            width: 20px;
            font-size: 16px;
        }

        .sidebar-nav a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .sidebar-nav a.active {
            background: #c9a959;
            color: #0a2f4d;
        }

        .sidebar-nav a.active i {
            color: #0a2f4d;
        }

        /* Main Content */
        .main-content-wrapper {
            flex: 1;
            margin-left: 280px;
            transition: margin-left 0.3s ease;
        }

        /* Top Navbar */
        .top-navbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: #0a2f4d;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .menu-toggle:hover {
            background: #f1f5f9;
        }

        .page-title {
            font-size: 20px;
            font-weight: 600;
            color: #0a2f4d;
            margin: 0;
        }

        .top-user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .top-user-avatar {
            width: 40px;
            height: 40px;
            background: #c9a959;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0a2f4d;
            font-weight: 600;
            font-size: 16px;
        }

        .top-user-name {
            font-weight: 500;
            font-size: 14px;
            color: #1e293b;
        }

        .top-user-rank {
            font-size: 12px;
            color: #64748b;
        }

        .main-content {
            padding: 30px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-header h2 {
            font-size: 24px;
            font-weight: 600;
            color: #0a2f4d;
            margin: 0;
        }

        .page-header h2 i {
            color: #c9a959;
            margin-right: 10px;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
            border-top: 4px solid #c9a959;
        }

        .stat-card i {
            font-size: 40px;
            color: #c9a959;
            margin-bottom: 10px;
        }

        .stat-card h3 {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .stat-card .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #0a2f4d;
        }

        .year-nav {
            background: white;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            border-left: 4px solid #c9a959;
        }

        .year-nav-title {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #0a2f4d;
            font-weight: 600;
            font-size: 15px;
        }

        .year-nav-title i {
            color: #c9a959;
            font-size: 18px;
        }

        .year-dropdown {
            flex: 1;
        }

        .year-select {
            width: 200px;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            outline: none;
            transition: all 0.2s;
        }

        .year-select:hover {
            background: #e2e8f0;
            border-color: #c9a959;
        }

        .year-select:focus {
            border-color: #0a2f4d;
            box-shadow: 0 0 0 2px rgba(10,47,77,0.1);
        }

        .btn-print {
            background: #0a2f4d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-print:hover {
            background: #c9a959;
            color: #0a2f4d;
        }

        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .report-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #0a2f4d;
            break-inside: avoid;
        }

        .report-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        .report-header i {
            font-size: 20px;
            color: #c9a959;
        }

        .report-header h3 {
            font-size: 16px;
            font-weight: 600;
            color: #0a2f4d;
            margin: 0;
        }

        .chart-container {
            height: 300px;
            margin-bottom: 15px;
            position: relative;
        }

        .no-data-message {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #94a3b8;
            text-align: center;
            padding: 20px;
        }

        .no-data-message i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #cbd5e1;
        }

        .stats-table {
            width: 100%;
            border-collapse: collapse;
        }

        .stats-table th {
            text-align: left;
            padding: 10px;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
        }

        .stats-table td {
            padding: 10px;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
        }

        .stats-table tr:hover td {
            background: #f8fafc;
        }

        .badge-count {
            background: #0a2f4d;
            color: white;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .drug-rank {
            display: inline-block;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #c9a959;
            color: #0a2f4d;
            text-align: center;
            line-height: 24px;
            font-weight: 700;
            font-size: 12px;
            margin-right: 8px;
        }

        .total-mentions {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 10px;
            padding: 8px;
            background: #f8fafc;
            border-radius: 8px;
        }

        .report-footer {
            margin-top: 30px;
            padding: 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 3px solid #c9a959;
        }

        .report-footer .signature {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px dashed #e2e8f0;
        }

        .footer {
            background: white;
            padding: 20px 0;
            margin-top: 50px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }

        @media print {
            .sidebar,
            .top-navbar,
            .year-nav,
            .btn-print,
            .footer,
            .no-print {
                display: none !important;
            }

            .main-content-wrapper {
                margin-left: 0 !important;
            }

            @page {
                size: A4;
                margin: 1.5cm;
            }

            body {
                background: white;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .report-header-print {
                display: block !important;
            }

            .stats-cards {
                display: grid;
            }

            .report-card {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content-wrapper {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .top-user-info {
                display: none;
            }
            
            .reports-grid {
                grid-template-columns: 1fr;
            }
            
            .year-nav {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .stats-cards {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
            
            .main-content {
                padding: 20px;
            }
            
            .page-header h2 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
<div class="app-wrapper">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h3>PNP Profiling System</h3>
            <p>Manolo Fortich Police Station</p>
        </div>
        
        <div class="user-info-sidebar">
            <div class="user-avatar-sidebar">
                <?php echo substr($_SESSION['full_name'], 0, 1); ?>
            </div>
            <div class="user-name-sidebar"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
            <div class="user-rank-sidebar"><?php echo htmlspecialchars($_SESSION['rank']); ?> • <?php echo htmlspecialchars($_SESSION['unit']); ?></div>
        </div>
        
        <ul class="sidebar-nav">
            <li>
                <a href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="barangays.php">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Barangays</span>
                </a>
            </li>
            <li>
                <a href="reports.php" class="active">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </li>
            <?php if ($_SESSION['role'] == 'admin'): ?>
            <li>
                <a href="users.php">
                    <i class="fas fa-users-cog"></i>
                    <span>Accounts</span>
                </a>
            </li>
            <?php endif; ?>
            <li>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content Wrapper -->
    <div class="main-content-wrapper">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h2 class="page-title">Statistical Reports</h2>
            <div class="top-user-info">
                <div>
                    <div class="top-user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                    <div class="top-user-rank"><?php echo htmlspecialchars($_SESSION['rank']); ?></div>
                </div>
                <div class="top-user-avatar">
                    <?php echo substr($_SESSION['full_name'], 0, 1); ?>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="page-header">
                <h2><i class="fas fa-chart-bar"></i> Statistical Reports</h2>
                <button onclick="window.print()" class="btn-print no-print">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <h3>Total Profiles</h3>
                    <div class="stat-number"><?php echo number_format($totalProfiles); ?></div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-calendar-alt"></i>
                    <h3>Total Arrests (<?php echo $selectedYear; ?>)</h3>
                    <div class="stat-number"><?php echo number_format(array_sum($monthValues)); ?></div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-capsules"></i>
                    <h3>Total Drug Mentions</h3>
                    <div class="stat-number"><?php echo number_format($totalDrugMentions); ?></div>
                </div>
            </div>

            <!-- Year Navigation - Dropdown Select -->
            <div class="year-nav no-print">
                <div class="year-nav-title">
                    <i class="fas fa-calendar"></i>
                    <span>Select Year:</span>
                </div>
                <div class="year-dropdown">
                    <select onchange="window.location.href=this.value;" class="year-select">
                        <?php 
                        $years = range(2016, 2026);
                        foreach ($years as $year): 
                            $selected = ($selectedYear == $year) ? 'selected' : '';
                        ?>
                            <option value="?year=<?php echo $year; ?>" <?php echo $selected; ?>>
                                <?php echo $year; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="reports-grid">
                <!-- Barangay Distribution Report -->
                <div class="report-card">
                    <div class="report-header">
                        <i class="fas fa-map-marker-alt"></i>
                        <h3>Top Barangays by Population</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="barangayChart"></canvas>
                    </div>
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>Barangay</th>
                                <th>Total</th>
                            </thead>
                        <tbody>
                            <?php foreach (array_slice($barangayReports, 0, 5) as $report): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($report['barangay']); ?></td>
                                <td><strong><?php echo $report['total']; ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Monthly Arrests Summary for Selected Year -->
                <div class="report-card">
                    <div class="report-header">
                        <i class="fas fa-calendar-alt"></i>
                        <h3>Monthly Arrests (<?php echo $selectedYear; ?>)</h3>
                    </div>
                    <div class="chart-container">
                        <?php if ($hasArrests): ?>
                            <canvas id="monthlyChart"></canvas>
                        <?php else: ?>
                            <div class="no-data-message">
                                <i class="fas fa-chart-pie"></i>
                                <h4>No Arrest Records</h4>
                                <p>No arrests recorded for the year <?php echo $selectedYear; ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Arrests</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalArrestsForYear = array_sum($monthValues);
                            for ($m = 1; $m <= 12; $m++): 
                                $monthName = date('F', mktime(0, 0, 0, $m, 1));
                                $count = isset($monthCounts[$m]) ? $monthCounts[$m] : 0;
                                $percentage = $totalArrestsForYear > 0 ? round(($count / $totalArrestsForYear) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><?php echo $monthName; ?></td>
                                <td><span class="badge-count"><?php echo $count; ?></span></td>
                                <td><?php echo $percentage; ?>%</td>
                            </tr>
                            <?php endfor; ?>
                            <?php if ($totalArrestsForYear > 0): ?>
                            <tr style="background: #f8fafc; font-weight: 600;">
                                <td><strong>TOTAL</strong></td>
                                <td><span class="badge-count" style="background: #c9a959;"><?php echo $totalArrestsForYear; ?></span></td>
                                <td>100%</td>
                            </tr>
                            <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: #94a3b8; padding: 20px;">
                                    No arrest records for this year
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Drug Types Summary -->
                <div class="report-card">
                    <div class="report-header">
                        <i class="fas fa-capsules"></i>
                        <h3>Drug Types Distribution</h3>
                    </div>
                    <div class="total-mentions">
                        <i class="fas fa-chart-simple"></i> Total Drug Mentions: <strong><?php echo $totalDrugMentions; ?></strong>
                    </div>
                    <div class="chart-container">
                        <canvas id="drugChart"></canvas>
                    </div>
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Drug Type</th>
                                <th>Mentions</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1;
                            foreach ($drugCategories as $drug => $count): 
                                $percentage = $totalDrugMentions > 0 ? round(($count / $totalDrugMentions) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td>
                                    <?php if ($rank == 1): ?>
                                        <span class="drug-rank">🥇</span>
                                    <?php elseif ($rank == 2): ?>
                                        <span class="drug-rank">🥈</span>
                                    <?php elseif ($rank == 3): ?>
                                        <span class="drug-rank">🥉</span>
                                    <?php else: ?>
                                        <span class="drug-rank"><?php echo $rank; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo $drug; ?></strong></td>
                                <td><span class="badge-count"><?php echo $count; ?></span></td>
                                <td><?php echo $percentage; ?>%</td>
                            </tr>
                            <?php 
                            $rank++;
                            endforeach; 
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- Age Demographics -->
                <div class="report-card">
                    <div class="report-header">
                        <i class="fas fa-chart-line"></i>
                        <h3>Age Demographics</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="ageChart"></canvas>
                    </div>
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>Age Group</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </thead>
                        <tbody>
                            <?php 
                            $totalAgeCount = array_sum(array_column($ageGroups, 'count'));
                            foreach ($ageGroups as $group): 
                                $percentage = $totalAgeCount > 0 ? round(($group['count'] / $totalAgeCount) * 100, 1) : 0;
                            ?>
                             <tr>
                                <td><?php echo htmlspecialchars($group['age_group']); ?></td>
                                <td><span class="badge-count"><?php echo $group['count']; ?></span></td>
                                <td><?php echo $percentage; ?>%</td>
                             </tr>
                            <?php endforeach; ?>
                        </tbody>
                     </table>
                </div>

                <!-- Sex Distribution -->
                <div class="report-card">
                    <div class="report-header">
                        <i class="fas fa-venus-mars"></i>
                        <h3>Sex Distribution</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="sexChart"></canvas>
                    </div>
                    <table class="stats-table">
                        <thead>
                             <tr>
                                <th>Sex</th>
                                <th>Count</th>
                                <th>Percentage</th>
                             </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalSexCount = array_sum(array_column($sexDistribution, 'count'));
                            foreach ($sexDistribution as $sex): 
                                $percentage = $totalSexCount > 0 ? round(($sex['count'] / $totalSexCount) * 100, 1) : 0;
                            ?>
                             <tr>
                                <td><?php echo htmlspecialchars($sex['sex']); ?></td>
                                <td><span class="badge-count"><?php echo $sex['count']; ?></span></td>
                                <td><?php echo $percentage; ?>%</td>
                             </tr>
                            <?php endforeach; ?>
                        </tbody>
                     </table>
                </div>
            </div>

            <?php if ($_SESSION['role'] == 'admin' && !empty($userActivity)): ?>
            <div class="report-card" style="margin-top: 20px;">
                <div class="report-header">
                    <i class="fas fa-users"></i>
                    <h3>User Activity Report</h3>
                </div>
                <table class="stats-table">
                    <thead>
                         <tr>
                            <th>Officer Name</th>
                            <th>Rank</th>
                            <th>Profiles Created</th>
                         </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($userActivity as $user): ?>
                         <tr>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['rank']); ?></td>
                            <td><span class="badge-count"><?php echo $user['profiles_created']; ?></span></td>
                         </tr>
                        <?php endforeach; ?>
                    </tbody>
                 </table>
            </div>
            <?php endif; ?>

            <!-- Report Footer -->
            <div class="report-footer">
                <p>This report is generated by the PNP Biographical Profiling System</p>
                <p>Generated by: <?php echo htmlspecialchars($generatedBy); ?> | Date: <?php echo $currentDate; ?></p>
                <div class="signature">
                    <div>
                        <p>_________________________</p>
                        <p><strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong></p>
                        <p><?php echo htmlspecialchars($_SESSION['rank']); ?></p>
                    </div>
                    <div>
                        <p>_________________________</p>
                        <p><strong>Chief of Police</strong></p>
                        <p>Manolo Fortich Police Station</p>
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
    </div>
</div>

<script>
    // Sidebar Toggle for Mobile
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');

    menuToggle.addEventListener('click', function() {
        sidebar.classList.toggle('open');
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const isClickInsideSidebar = sidebar.contains(event.target);
        const isClickOnToggle = menuToggle.contains(event.target);
        
        if (!isClickInsideSidebar && !isClickOnToggle && window.innerWidth <= 768 && sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
        }
    });

    // Close sidebar on escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
        }
    });

    // Barangay Chart
    const barangayCtx = document.getElementById('barangayChart').getContext('2d');
    new Chart(barangayCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column(array_slice($barangayReports, 0, 5), 'barangay')); ?>,
            datasets: [{
                label: 'Total Profiles',
                data: <?php echo json_encode(array_column(array_slice($barangayReports, 0, 5), 'total')); ?>,
                backgroundColor: '#0a2f4d',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                tooltip: { callbacks: { label: function(context) { return context.raw + ' profiles'; } } }
            },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    <?php if ($hasArrests): ?>
    // Monthly Chart - PIE CHART
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(monthlyCtx, {
        type: 'pie',
        data: {
            labels: <?php 
                $monthsWithArrests = [];
                $arrestValues = [];
                foreach ($monthlyArrests as $m) {
                    $monthsWithArrests[] = $m['month_name'];
                    $arrestValues[] = $m['arrest_count'];
                }
                echo json_encode($monthsWithArrests); 
            ?>,
            datasets: [{
                data: <?php echo json_encode($arrestValues); ?>,
                backgroundColor: [
                    '#0a2f4d', '#c9a959', '#28a745', '#dc3545', '#ffc107',
                    '#17a2b8', '#6c757d', '#007bff', '#6610f2', '#e83e8c',
                    '#fd7e14', '#20c997'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: { font: { size: 11 } }
                },
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
            }
        }
    });
    <?php endif; ?>

    // Drug Chart
    const drugCtx = document.getElementById('drugChart').getContext('2d');
    new Chart(drugCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($drugCategories)); ?>,
            datasets: [{
                label: 'Number of Mentions',
                data: <?php echo json_encode(array_values($drugCategories)); ?>,
                backgroundColor: ['#dc3545', '#28a745', '#ffc107', '#17a2b8', '#6c757d'],
                borderWidth: 0,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
            plugins: { 
                legend: { display: false },
                tooltip: { callbacks: { label: function(context) { return context.raw + ' mentions'; } } }
            }
        }
    });

    // Age Chart
    const ageCtx = document.getElementById('ageChart').getContext('2d');
    new Chart(ageCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($ageGroups, 'age_group')); ?>,
            datasets: [{
                label: 'Number of Persons',
                data: <?php echo json_encode(array_column($ageGroups, 'count')); ?>,
                backgroundColor: '#c9a959',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    // Sex Chart
    const sexCtx = document.getElementById('sexChart').getContext('2d');
    new Chart(sexCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($sexDistribution, 'sex')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($sexDistribution, 'count')); ?>,
                backgroundColor: ['#0a2f4d', '#c9a959', '#6c757d'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
</script>
</body>
</html>