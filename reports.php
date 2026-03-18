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
$yearsQuery = "SELECT DISTINCT YEAR(arrest_datetime) as year 
               FROM biographical_profiles 
               WHERE arrest_datetime IS NOT NULL 
               AND YEAR(arrest_datetime) >= 2016
               ORDER BY year DESC";
$yearsStmt = $db->query($yearsQuery);
$availableYears = $yearsStmt->fetchAll(PDO::FETCH_ASSOC);

// If no years in database, show current year as option
if (empty($availableYears)) {
    $availableYears = [['year' => date('Y')]];
}

// Get monthly arrest statistics for SELECTED year
$monthlyQuery = "SELECT 
                  MONTH(arrest_datetime) as month,
                  MONTHNAME(arrest_datetime) as month_name,
                  COUNT(*) as arrest_count
                  FROM biographical_profiles 
                  WHERE arrest_datetime IS NOT NULL 
                  AND YEAR(arrest_datetime) = :year
                  GROUP BY MONTH(arrest_datetime)
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

// Get barangay statistics
$barangayQuery = "SELECT 
                   TRIM(SUBSTRING_INDEX(present_address, ',', 1)) as barangay,
                   COUNT(*) as total,
                   SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                   SUM(CASE WHEN status = 'delisted' THEN 1 ELSE 0 END) as delisted
                   FROM biographical_profiles 
                   WHERE present_address IS NOT NULL AND present_address != ''
                   GROUP BY barangay
                   ORDER BY total DESC";
$barangayStmt = $db->query($barangayQuery);
$barangayReports = $barangayStmt->fetchAll(PDO::FETCH_ASSOC);

// Get status distribution
$statusQuery = "SELECT 
                 status,
                 COUNT(*) as count
                 FROM biographical_profiles 
                 GROUP BY status";
$statusStmt = $db->query($statusQuery);
$statusDistribution = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

// Get drug types statistics
$drugQuery = "SELECT drugs_involved, other_drugs_pushed, drugs_pushed FROM biographical_profiles";
$drugStmt = $db->query($drugQuery);
$allDrugs = $drugStmt->fetchAll(PDO::FETCH_ASSOC);

// Process drug statistics
$drugCategories = [
    'Shabu' => 0,
    'Marijuana' => 0,
];

foreach ($allDrugs as $profile) {
    // Check drugs_involved
    if (!empty($profile['drugs_involved'])) {
        $drugs = explode(',', $profile['drugs_involved']);
        foreach ($drugs as $drug) {
            $drug = trim(strtolower($drug));
            if (strpos($drug, 'shabu') !== false) $drugCategories['Shabu']++;
            elseif (strpos($drug, 'marijuana') !== false) $drugCategories['Marijuana']++;
        }
    }
    
    // Check drugs_pushed
    if (!empty($profile['drugs_pushed'])) {
        $drugs = explode(',', $profile['drugs_pushed']);
        foreach ($drugs as $drug) {
            $drug = trim(strtolower($drug));
            if (strpos($drug, 'shabu') !== false) $drugCategories['Shabu']++;
            elseif (strpos($drug, 'marijuana') !== false) $drugCategories['Marijuana']++;
        }
    }
    
    // Check other_drugs_pushed
    if (!empty($profile['other_drugs_pushed'])) {
        $drugCategories['Other']++;
    }
}

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
              GROUP BY age_group";
$ageStmt = $db->query($ageQuery);
$ageGroups = $ageStmt->fetchAll(PDO::FETCH_ASSOC);

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            font-family: 'Inter', sans-serif;
            background: #f4f7fb;
            color: #1e293b;
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

        .title-area .station {
            font-size: 14px;
            color: #c9a959;
            font-weight: 500;
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

       /* Year Navigation */
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

/* Year Dropdown */
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

@media (max-width: 768px) {
    .year-select {
        width: 100%;
    }
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
        }

        .btn-print:hover {
            background: #c9a959;
            color: #0a2f4d;
        }

        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
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
            .navbar-modern,
            .nav-menu,
            .year-nav,
            .btn-print,
            .footer,
            .no-print {
                display: none !important;
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
        }

        @media (max-width: 768px) {
            .reports-grid {
                grid-template-columns: 1fr;
            }
            
            .year-nav {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .year-buttons {
                width: 100%;
            }
        }
    </style>
</head>
<body>
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
                    </div>
                </div>
                <div class="user-area">
                    <div class="user-profile">
                        <div class="user-avatar"><?php echo substr($_SESSION['full_name'], 0, 1); ?></div>
                        <div class="user-info">
                            <div class="user-name"><?php echo $_SESSION['full_name']; ?></div>
                            <div class="user-rank"><?php echo $_SESSION['rank']; ?></div>
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
                    <li><a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a></li>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                    <li><a href="users.php"><i class="fas fa-users-cog"></i> Accounts</a></li>
                    <?php endif; ?>
                    <li style="margin-left: auto;"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="page-header">
            <h2><i class="fas fa-chart-bar"></i> Statistical Reports</h2>
            <button onclick="window.print()" class="btn-print no-print">
                <i class="fas fa-print"></i> Print Report
            </button>
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
            // Define years from 2016 to 2026
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

        <!-- Report Header for Print -->
        <div class="report-header-print" style="text-align: center; margin-bottom: 30px; display: none;">
            <h2 style="color: #0a2f4d; margin: 0;">PHILIPPINE NATIONAL POLICE</h2>
            <h3 style="color: #c9a959; margin: 5px 0;">Manolo Fortich Police Station</h3>
            <p style="color: #64748b;">Statistical Report for Year <?php echo $selectedYear; ?> - Generated on <?php echo $currentDate; ?></p>
            <hr style="border: 1px solid #c9a959; width: 50%;">
        </div>

        <div class="reports-grid">
            <!-- Barangay Distribution Report -->
            <div class="report-card">
                <div class="report-header">
                    <i class="fas fa-map-marker-alt"></i>
                    <h3>Barangay Distribution</h3>
                </div>
                <div class="chart-container">
                    <canvas id="barangayChart"></canvas>
                </div>
                <table class="stats-table">
                    <thead>
                        <tr>
                            <th>Barangay</th>
                            <th>Total</th>
                        </tr>
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

            <!-- Monthly Arrests Summary for Selected Year - NOW PIE CHART -->
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalArrestsForYear = 0;
                        for ($m = 1; $m <= 12; $m++): 
                            $monthName = date('F', mktime(0, 0, 0, $m, 1));
                            $count = isset($monthCounts[$m]) ? $monthCounts[$m] : 0;
                            $totalArrestsForYear += $count;
                            if ($count > 0):
                        ?>
                        <tr>
                            <td><?php echo $monthName; ?></td>
                            <td><span class="badge-count"><?php echo $count; ?></span></td>
                        </tr>
                        <?php 
                            endif;
                        endfor; 
                        ?>
                        <?php if ($totalArrestsForYear > 0): ?>
                        <tr style="background: #f8fafc; font-weight: 600;">
                            <td>TOTAL</td>
                            <td><span class="badge-count" style="background: #c9a959;"><?php echo $totalArrestsForYear; ?></span></td>
                        </tr>
                        <?php else: ?>
                        <tr>
                            <td colspan="2" style="text-align: center; color: #94a3b8; padding: 20px;">
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
                    <h3>Drug Types Involved</h3>
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
                            $rowClass = ($rank == 1) ? 'style="background: #fff3cd;"' : '';
                        ?>
                        <tr <?php echo $rowClass; ?>>
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
        </div>


    <script>
        // Barangay Chart
        const barangayCtx = document.getElementById('barangayChart').getContext('2d');
        new Chart(barangayCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column(array_slice($barangayReports, 0, 5), 'barangay')); ?>,
                datasets: [{
                    label: 'Total Profiles',
                    data: <?php echo json_encode(array_column(array_slice($barangayReports, 0, 5), 'total')); ?>,
                    backgroundColor: '#0a2f4d'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });

        <?php if ($hasArrests): ?>
        // Monthly Chart - NOW PIE CHART
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'pie',
            data: {
                labels: <?php 
                    // Get only months with arrests
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
                        labels: {
                            font: { size: 11 }
                        }
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
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
                plugins: { legend: { display: false } }
            }
        });
    </script>
</body>
</html>