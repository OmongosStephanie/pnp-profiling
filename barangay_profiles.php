<?php
// barangay_profiles.php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: barangays.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get barangay from URL
$selectedBarangay = isset($_GET['barangay']) ? $_GET['barangay'] : '';

if (empty($selectedBarangay)) {
    header("Location: barangays.php");
    exit();
}

// Get filter values from URL
$selectedYear = isset($_GET['year']) ? $_GET['year'] : '';
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : '';

// Get distinct years from arrest_datetime for this barangay
$yearQuery = "SELECT DISTINCT YEAR(arrest_datetime) as year 
              FROM biographical_profiles 
              WHERE present_address LIKE :barangay 
              AND arrest_datetime IS NOT NULL 
              ORDER BY year DESC";
$yearStmt = $db->prepare($yearQuery);
$barangayParam = $selectedBarangay . '%';
$yearStmt->bindParam(':barangay', $barangayParam);
$yearStmt->execute();
$years = $yearStmt->fetchAll(PDO::FETCH_ASSOC);

// Get distinct months from arrest_datetime for this barangay
$monthQuery = "SELECT DISTINCT MONTH(arrest_datetime) as month, 
               MONTHNAME(arrest_datetime) as month_name 
               FROM biographical_profiles 
               WHERE present_address LIKE :barangay 
               AND arrest_datetime IS NOT NULL 
               ORDER BY month";
$monthStmt = $db->prepare($monthQuery);
$monthStmt->bindParam(':barangay', $barangayParam);
$monthStmt->execute();
$months = $monthStmt->fetchAll(PDO::FETCH_ASSOC);

// Get profiles for this specific barangay with filters
$query = "SELECT * FROM biographical_profiles 
          WHERE present_address LIKE :barangay";

if (!empty($selectedYear)) {
    $query .= " AND YEAR(arrest_datetime) = :year";
}
if (!empty($selectedMonth)) {
    $query .= " AND MONTH(arrest_datetime) = :month";
}

$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':barangay', $barangayParam);

if (!empty($selectedYear)) {
    $stmt->bindParam(':year', $selectedYear);
}
if (!empty($selectedMonth)) {
    $stmt->bindParam(':month', $selectedMonth);
}

$stmt->execute();
$profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics for this barangay
$statsQuery = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN status = 'delisted' THEN 1 ELSE 0 END) as delisted_count,
                SUM(CASE WHEN arrest_datetime IS NOT NULL THEN 1 ELSE 0 END) as arrested_count
               FROM biographical_profiles 
               WHERE present_address LIKE :barangay";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->bindParam(':barangay', $barangayParam);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Get current date for report
$currentDate = date('F d, Y');
$generatedBy = $_SESSION['full_name'] . ' - ' . $_SESSION['rank'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($selectedBarangay); ?> - PNP Biographical Profiling System</title>
    
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

        .title-text .barangay-name {
            color: #c9a959;
            font-weight: 700;
        }

        /* Print Button */
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
            text-decoration: none;
            margin-left: 10px;
        }

        .btn-print:hover {
            background: #c9a959;
            color: #0a2f4d;
        }

        /* Barangay Info Card */
        .barangay-info {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 5px solid #c9a959;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .barangay-info h3 {
            font-size: 28px;
            font-weight: 700;
            color: #0a2f4d;
            margin: 0;
        }

        .barangay-info h3 i {
            color: #c9a959;
            margin-right: 10px;
        }

        .barangay-stats {
            display: flex;
            gap: 30px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #0a2f4d;
        }

        .stat-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .filter-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .filter-header i {
            font-size: 20px;
            color: #c9a959;
        }

        .filter-header h4 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #0a2f4d;
        }

        .filter-form {
            display: flex;
            gap: 15px;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
        }

        .filter-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .filter-select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            color: #1e293b;
            background: white;
            cursor: pointer;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
        }

        .btn-filter {
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
            white-space: nowrap;
        }

        .btn-filter:hover {
            background: #123b5e;
        }

        .btn-reset {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-reset:hover {
            background: #e2e8f0;
            color: #0a2f4d;
        }

        .btn-back {
            background: #64748b;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-back:hover {
            background: #475569;
            color: white;
        }

        .active-filter-badge {
            margin-top: 15px;
            padding: 12px 15px;
            background: #f0f9ff;
            border-radius: 8px;
            border-left: 4px solid #0a2f4d;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            flex-wrap: wrap;
        }

        .filter-tag {
            background: #0a2f4d;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-left: 10px;
        }

        /* Table Container */
        .table-container {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

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

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
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

        .arrest-badge {
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            color: #64748b;
            margin-left: 5px;
        }

        .no-arrest {
            color: #94a3b8;
            font-style: italic;
            font-size: 12px;
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
        .btn-icon.print { background: #0891b2; }
        .btn-icon.view:hover { background: #123b5e; }
        .btn-icon.edit:hover { background: #d4b36a; }
        .btn-icon.print:hover { background: #0e7490; transform: translateY(-2px); }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        /* Report Footer for Print */
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

        /* PRINT STYLES */
        @media print {
            /* Hide non-printable elements */
            .navbar-modern,
            .nav-menu,
            .btn-print,
            .btn-back,
            .filter-section,
            .action-btns,
            .footer,
            .no-print {
                display: none !important;
            }

            /* Page settings */
            @page {
                size: A4;
                margin: 1.5cm;
            }

            body {
                background: white;
                padding: 0;
                margin: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .main-content {
                max-width: 100%;
                margin: 0;
                padding: 0;
            }

            .page-header {
                margin-bottom: 20px;
            }

            .barangay-info {
                break-inside: avoid;
                page-break-inside: avoid;
                border: 1px solid #ddd;
                box-shadow: none;
            }

            .table-container {
                break-inside: auto;
                box-shadow: none;
                border: 1px solid #ddd;
            }

            .modern-table th {
                background: #f0f0f0 !important;
                color: black !important;
            }

            .status-badge {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .report-footer {
                border: 1px solid #ddd;
                box-shadow: none;
                break-inside: avoid;
                page-break-inside: avoid;
                margin-top: 30px;
            }

            /* Force background colors */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }

        @media (max-width: 768px) {
            .filter-form {
                flex-direction: column;
            }
            
            .barangay-info {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .barangay-stats {
                width: 100%;
                justify-content: space-around;
            }
            
            .page-header {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Modern Navbar -->
    <nav class="navbar-modern no-print">
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
        <!-- Page Header with Print Button -->
        <div class="page-header">
            <div class="page-title">
                <i class="fas fa-map-marker-alt"></i>
                <div class="title-text">
                    <h2>Barangay: <span class="barangay-name"><?php echo htmlspecialchars($selectedBarangay); ?></span></h2>
                    <p>Viewing profiles from this barangay</p>
                </div>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="barangays.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Barangays
                </a>
                <button onclick="window.print()" class="btn-print no-print">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
        </div>

        <!-- Barangay Info Card -->
        <div class="barangay-info">
            <h3>
                <i class="fas fa-map-marker-alt"></i>
                <?php echo htmlspecialchars($selectedBarangay); ?>
            </h3>
            <div class="barangay-stats">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Profiles</div>
                </div>
            </div>
        </div>

        <!-- Filter Section with Year and Month -->
        <div class="filter-section no-print">
            <div class="filter-header">
                <i class="fas fa-filter"></i>
                <h4>Filter by Date of Arrest</h4>
            </div>
            
            <form method="GET" action="" class="filter-form">
                <input type="hidden" name="barangay" value="<?php echo htmlspecialchars($selectedBarangay); ?>">
                
                <div class="filter-group">
                    <label><i class="fas fa-calendar"></i> Arrest Year</label>
                    <select name="year" class="filter-select">
                        <option value="">All Years</option>
                        <?php foreach ($years as $year): ?>
                            <option value="<?php echo $year['year']; ?>" 
                                <?php echo $selectedYear == $year['year'] ? 'selected' : ''; ?>>
                                <?php echo $year['year']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label><i class="fas fa-calendar-alt"></i> Arrest Month</label>
                    <select name="month" class="filter-select">
                        <option value="">All Months</option>
                        <?php foreach ($months as $month): ?>
                            <option value="<?php echo $month['month']; ?>" 
                                <?php echo $selectedMonth == $month['month'] ? 'selected' : ''; ?>>
                                <?php echo $month['month_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                    <a href="barangay_profiles.php?barangay=<?php echo urlencode($selectedBarangay); ?>" class="btn-reset">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
            
            <!-- Active Filter Display -->
            <?php if (!empty($selectedYear) || !empty($selectedMonth)): ?>
            <div class="active-filter-badge">
                <i class="fas fa-info-circle"></i>
                <span>
                    <strong>Active Filters:</strong>
                    
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
                    
                    <span style="margin-left: 10px; color: #0a2f4d;">
                        <?php echo count($profiles); ?> records found
                    </span>
                </span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Profiles Table -->
        <div class="table-container">
            <?php if (count($profiles) > 0): ?>
                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Alias</th>
                                <th>Age</th>
                                <th>Date of Arrest</th>
                                <th>Place of Arrest</th>
                                <th class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($profiles as $profile): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($profile['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($profile['alias'] ?: '—'); ?></td>
                                <td><?php echo $profile['age']; ?></td>
                                <td>
                                    <?php if (!empty($profile['arrest_datetime'])): ?>
                                        <?php echo date('M d, Y', strtotime($profile['arrest_datetime'])); ?>
                                        <span class="arrest-badge">
                                            <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($profile['arrest_datetime'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="no-arrest">Not arrested</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($profile['arrest_place'])): ?>
                                        <?php echo htmlspecialchars($profile['arrest_place']); ?>
                                    <?php else: ?>
                                        <span class="no-arrest">Not specified</span>
                                    <?php endif; ?>
                                </td>
                                <td class="no-print">
                                    <!-- Action Buttons -->
                                    <div class="action-btns">
                                        <!-- View Button -->
                                        <a href="view_profile.php?id=<?php echo $profile['id']; ?>&return_to=barangay&barangay=<?php echo urlencode($selectedBarangay); ?>" 
                                           class="btn-icon view" 
                                           title="View Profile">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <!-- Edit Button -->
                                        <a href="edit_profile.php?id=<?php echo $profile['id']; ?>&return_to=barangay&barangay=<?php echo urlencode($selectedBarangay); ?>" 
                                           class="btn-icon edit" 
                                           title="Edit Profile">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <!-- Print Button -->
                                        <a href="view_profile.php?id=<?php echo $profile['id']; ?>&print=1" 
                                           class="btn-icon print" 
                                           title="Print Profile"
                                           target="_blank">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4 text-muted">
                    <small>
                        <i class="fas fa-database"></i> 
                        Showing <strong><?php echo count($profiles); ?></strong> profiles from <strong><?php echo htmlspecialchars($selectedBarangay); ?></strong>
                    </small>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open fa-3x" style="color: #cbd5e1; margin-bottom: 15px;"></i>
                    <h4 style="color: #475569;">No Profiles Found</h4>
                    <p style="color: #64748b;">No profiles found in <?php echo htmlspecialchars($selectedBarangay); ?> matching your filters.</p>
                    <a href="barangay_profiles.php?barangay=<?php echo urlencode($selectedBarangay); ?>" class="btn-filter" style="display: inline-block; text-decoration: none; margin-top: 10px;">
                        <i class="fas fa-redo"></i> Clear Filters
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Report Footer for Print -->
        <div class="report-footer no-print">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <i class="fas fa-file-alt" style="color: #c9a959;"></i>
                    <strong> Report ID:</strong> PNP-MFPS-BRG-<?php echo date('Ymd'); ?>-<?php echo str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT); ?>
                </div>
                <div>
                    <i class="fas fa-calendar-alt" style="color: #c9a959;"></i>
                    <strong> Generated:</strong> <?php echo $currentDate; ?>
                </div>
            </div>
            <div class="signature">
                <div>
                    <p><strong>Prepared by:</strong></p>
                    <p style="margin-top: 30px;"><?php echo $generatedBy; ?></p>
                    <p style="font-size: 11px;">Reporting Officer</p>
                </div>
                <div>
                    <p><strong>Noted by:</strong></p>
                    <p style="margin-top: 30px;">P/CHIEF OF POLICE</p>
                    <p style="font-size: 11px;">Manolo Fortich Police Station</p>
                </div>
            </div>
            <p style="margin-top: 20px; font-size: 11px;">
                <i class="fas fa-lock"></i> This document is CONFIDENTIAL and for official use only.
            </p>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer no-print">
        <div class="container">
            <p>© <?php echo date('Y'); ?> Philippine National Police - Manolo Fortich Police Station. All rights reserved.</p>
            <p>Department of the Interior and Local Government | Bukidnon Police Provincial Office</p>
        </div>
    </footer>

    <script>
        // Auto-submit form when dropdown changes
        document.querySelector('select[name="year"]')?.addEventListener('change', function() {
            this.form.submit();
        });
        
        document.querySelector('select[name="month"]')?.addEventListener('change', function() {
            this.form.submit();
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>