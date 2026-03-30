<?php
// barangays.php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get search query from URL
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get all barangays and their profile counts
$barangayQuery = "SELECT 
                   TRIM(SUBSTRING_INDEX(present_address, ',', 1)) as barangay,
                   COUNT(*) as profile_count
                   FROM biographical_profiles 
                   WHERE present_address IS NOT NULL AND present_address != ''
                   GROUP BY barangay";
$barangayStmt = $db->query($barangayQuery);
$barangayStats = $barangayStmt->fetchAll(PDO::FETCH_ASSOC);

// Create an associative array for easy lookup
$barangayCounts = [];
foreach ($barangayStats as $stat) {
    $barangayCounts[$stat['barangay']] = $stat['profile_count'];
}

// List of all 22 Manolo Fortich barangays
$all_barangays = [
    'Agusan Canyon', 'Alae', 'Dahilayan', 'Dalirig', 'Damilag',
    'Dicklum', 'Guilang-guilang', 'Kalugmanan', 'Lindaban', 'Lingion',
    'Lunocan', 'Maluko', 'Mambatangan', 'Mampayag', 'Minsuro',
    'Mantibugao', 'Tankulan (Poblacion)', 'San Miguel', 'Sankanan',
    'Santiago', 'Santo Niño', 'Ticala'
];

// Filter barangays based on search
$filtered_barangays = $all_barangays;
if (!empty($search)) {
    $filtered_barangays = array_filter($all_barangays, function($barangay) use ($search) {
        return stripos($barangay, $search) !== false;
    });
    // Reset array keys
    $filtered_barangays = array_values($filtered_barangays);
}

$total_profiles = array_sum($barangayCounts);
$total_barangays_filtered = count($filtered_barangays);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Barangays - PNP Biographical Profiling System</title>
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
            gap: 20px;
        }

        .page-title-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-title-section i {
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

        /* Search Section */
        .search-section {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #c9a959;
        }

        .search-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .search-input-group {
            flex: 1;
            display: flex;
            gap: 10px;
        }

        .search-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            color: #1e293b;
            transition: all 0.2s;
        }

        .search-input:focus {
            outline: none;
            border-color: #c9a959;
            box-shadow: 0 0 0 2px rgba(201, 169, 89, 0.1);
        }

        .btn-search {
            background: #0a2f4d;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-search:hover {
            background: #c9a959;
            color: #0a2f4d;
        }

        .btn-clear {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-clear:hover {
            background: #e2e8f0;
            color: #0a2f4d;
        }

        .search-results-info {
            margin-top: 12px;
            padding: 8px 12px;
            background: #f0f9ff;
            border-radius: 8px;
            font-size: 13px;
            color: #0a2f4d;
        }

        .summary-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #c9a959;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .total-barangays {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .total-number {
            font-size: 36px;
            font-weight: 700;
            color: #0a2f4d;
        }

        .total-label {
            color: #64748b;
            font-size: 14px;
        }

        .total-label strong {
            color: #0a2f4d;
            font-size: 18px;
        }

        .barangay-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .barangay-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.2s;
            border-left: 4px solid #0a2f4d;
            position: relative;
        }

        .barangay-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .barangay-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }

        .barangay-icon {
            width: 50px;
            height: 50px;
            background: #e8f2ff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0a2f4d;
            font-size: 24px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .barangay-icon:hover {
            background: #0a2f4d;
            color: #c9a959;
            transform: scale(1.05);
        }

        .barangay-name {
            font-size: 18px;
            font-weight: 600;
            color: #0a2f4d;
            margin: 0;
            flex: 1;
        }

        /* Profile Count Badge */
        .profile-count {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #0a2f4d;
            color: white;
            padding: 8px 12px;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .profile-count i {
            color: #c9a959;
            font-size: 14px;
        }

        .profile-count.small {
            font-size: 14px;
            padding: 4px 10px;
        }

        .profile-count.zero {
            background: #e2e8f0;
            color: #64748b;
        }

        .btn-view-barangay {
            width: 100%;
            background: #0a2f4d;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s;
            margin-top: 15px;
            cursor: pointer;
        }

        .btn-view-barangay:hover {
            background: #c9a959;
            color: #0a2f4d;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .no-results i {
            font-size: 48px;
            color: #cbd5e1;
            margin-bottom: 15px;
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

        /* Mobile Responsive */
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
            
            .title-text h2 {
                font-size: 20px;
            }
            
            .title-text p {
                font-size: 12px;
            }
            
            .page-title-section i {
                font-size: 20px;
                padding: 8px;
            }
            
            .main-content {
                padding: 20px;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .search-input-group {
                flex-direction: column;
            }
            
            .btn-search, .btn-clear {
                justify-content: center;
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
                <a href="barangays.php" class="active">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Barangays</span>
                </a>
            </li>
            <li>
                <a href="reports.php">
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
            <h2 class="page-title">Barangays</h2>
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
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-title-section">
                    <i class="fas fa-map-marker-alt"></i>
                    <div class="title-text">
                        <h2>Barangays of Manolo Fortich</h2>
                        <p>Click the location icon to view barangay map or click View Profiles to see profiles</p>
                    </div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="search-section">
                <form method="GET" action="" class="search-form">
                    <div class="search-input-group">
                        <input type="text" 
                               name="search" 
                               class="search-input" 
                               placeholder="Search barangay by name..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn-search">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <?php if (!empty($search)): ?>
                        <a href="barangays.php" class="btn-clear">
                            <i class="fas fa-times"></i> Clear
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
                
                <?php if (!empty($search)): ?>
                <div class="search-results-info">
                    <i class="fas fa-info-circle"></i>
                    Found <strong><?php echo $total_barangays_filtered; ?></strong> barangay(s) matching "<strong><?php echo htmlspecialchars($search); ?></strong>"
                </div>
                <?php endif; ?>
            </div>

            <!-- Summary Card -->
            <div class="summary-card">
                <div class="total-barangays">
                    <span class="total-number"><?php echo $total_barangays_filtered; ?></span>
                    <span class="total-label"><?php echo !empty($search) ? 'Barangays Found' : 'Total Barangays'; ?></span>
                </div>
                <div class="total-label">
                    <strong><?php echo $total_profiles; ?></strong> total profiles across all barangays
                </div>
            </div>

            <!-- Barangay Grid -->
            <?php if (count($filtered_barangays) > 0): ?>
            <div class="barangay-grid">
                <?php 
                foreach ($filtered_barangays as $barangay): 
                    // Get profile count for this barangay
                    $count = 0;
                    foreach ($barangayCounts as $dbBarangay => $profileCount) {
                        if (stripos($dbBarangay, $barangay) !== false) {
                            $count = $profileCount;
                            break;
                        }
                    }
                    
                    // Calculate percentage
                    $percentage = $total_profiles > 0 ? round(($count / $total_profiles) * 100) : 0;
                    
                    // Highlight search term in barangay name
                    $display_name = $barangay;
                    if (!empty($search)) {
                        $display_name = preg_replace('/(' . preg_quote($search, '/') . ')/i', '<mark style="background: #fef3c7; padding: 0 2px; border-radius: 3px;">$1</mark>', $barangay);
                    }
                ?>
                <div class="barangay-card">
                    <!-- Profile Count Badge -->
                    <div class="profile-count <?php echo $count == 0 ? 'zero' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <?php echo $count; ?>
                    </div>
                    
                    <div class="barangay-header">
                        <!-- LOCATION ICON - Goes to barangay map page -->
                        <a href="barangay_map.php?barangay=<?php echo urlencode($barangay); ?>" class="barangay-icon" title="View Barangay Location">
                            <i class="fas fa-map-marker-alt"></i>
                        </a>
                        <h3 class="barangay-name"><?php echo $display_name; ?></h3>
                    </div>
                    
                    <!-- Simple progress bar (optional) -->
                    <?php if ($count > 0): ?>
                    <div style="height: 4px; background: #e2e8f0; border-radius: 2px; margin: 10px 0;">
                        <div style="width: <?php echo $percentage; ?>%; height: 100%; background: #0a2f4d; border-radius: 2px;"></div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- VIEW PROFILES BUTTON - Goes to barangay profiles page -->
                    <a href="barangay_profiles.php?barangay=<?php echo urlencode($barangay); ?>" class="btn-view-barangay">
                        <i class="fas fa-eye"></i> View Profiles
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h4 style="color: #475569; margin-bottom: 10px;">No Barangays Found</h4>
                <p style="color: #64748b;">No barangays matching "<strong><?php echo htmlspecialchars($search); ?></strong>" were found.</p>
                <a href="barangays.php" style="display: inline-block; margin-top: 15px; background: #0a2f4d; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none;">
                    <i class="fas fa-redo"></i> Show All Barangays
                </a>
            </div>
            <?php endif; ?>
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
</script>
</body>
</html>