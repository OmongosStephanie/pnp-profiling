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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
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
                    <li><a href="barangays.php" class="active"><i class="fas fa-map-marker-alt"></i> Barangays</a></li>
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
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
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
                    <!-- LOCATION ICON - Goes to different page (e.g., barangay map or location info) -->
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
</body>
</html>