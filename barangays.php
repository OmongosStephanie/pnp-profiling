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

// Get all barangays and their profile counts
$barangayQuery = "SELECT 
                   TRIM(SUBSTRING_INDEX(present_address, ',', 1)) as barangay,
                   COUNT(*) as profile_count
                   FROM biographical_profiles 
                   WHERE present_address IS NOT NULL AND present_address != ''
                   GROUP BY barangay
                   ORDER BY barangay";
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
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
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
        }

        .btn-view-barangay:hover {
            background: #c9a959;
            color: #0a2f4d;
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
                    <li><a href="profile_form.php"><i class="fas fa-plus-circle"></i> New Profile</a></li>
                    <li><a href="profiles.php"><i class="fas fa-list"></i> View Profiles</a></li>
                    <li><a href="barangays.php" class="active"><i class="fas fa-map-marker-alt"></i> Barangays</a></li>
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
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <i class="fas fa-map-marker-alt"></i>
                <div class="title-text">
                    <h2>Barangays of Manolo Fortich</h2>
                    <p>Profile distribution across 22 barangays</p>
                </div>
            </div>
        </div>

        <!-- Summary Card -->
        <div class="summary-card">
            <div class="total-barangays">
                <span class="total-number">22</span>
                <span class="total-label">Total Barangays</span>
            </div>
            <div class="total-label">
                <strong><?php echo array_sum($barangayCounts); ?></strong> total profiles across all barangays
            </div>
        </div>

        <!-- Barangay Grid -->
        <div class="barangay-grid">
            <?php 
            $total_profiles = array_sum($barangayCounts);
            foreach ($all_barangays as $barangay): 
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
            ?>
            <div class="barangay-card">
                <!-- Profile Count Badge -->
                <div class="profile-count <?php echo $count == 0 ? 'zero' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <?php echo $count; ?>
                </div>
                
                <div class="barangay-header">
                    <div class="barangay-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3 class="barangay-name"><?php echo htmlspecialchars($barangay); ?></h3>
                </div>
                
                <!-- Simple progress bar (optional) -->
                <?php if ($count > 0): ?>
                <div style="height: 4px; background: #e2e8f0; border-radius: 2px; margin: 10px 0;">
                    <div style="width: <?php echo $percentage; ?>%; height: 100%; background: #0a2f4d; border-radius: 2px;"></div>
                </div>
                <?php endif; ?>
                
                <a href="profiles.php?barangay=<?php echo urlencode($barangay); ?>" class="btn-view-barangay">
                    <i class="fas fa-eye"></i> View Profiles
                </a>
            </div>
            <?php endforeach; ?>
        </div>
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