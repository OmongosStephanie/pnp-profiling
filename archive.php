<?php
// archive.php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get available years
$yearQuery = "SELECT DISTINCT YEAR(created_at) as year, 
              COUNT(*) as total 
              FROM biographical_profiles 
              GROUP BY YEAR(created_at) 
              ORDER BY year DESC";
$yearStmt = $db->query($yearQuery);
$years = $yearStmt->fetchAll(PDO::FETCH_ASSOC);

$selectedYear = isset($_GET['year']) ? $_GET['year'] : '';
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : '';

// Get profiles based on selection
$query = "SELECT * FROM biographical_profiles WHERE 1=1";
if (!empty($selectedYear)) {
    $query .= " AND YEAR(created_at) = :year";
}
if (!empty($selectedMonth)) {
    $query .= " AND MONTH(created_at) = :month";
}
$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);
if (!empty($selectedYear)) {
    $stmt->bindParam(':year', $selectedYear);
}
if (!empty($selectedMonth)) {
    $stmt->bindParam(':month', $selectedMonth);
}
$stmt->execute();
$profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get months for selected year
$months = [];
if (!empty($selectedYear)) {
    $monthQuery = "SELECT DISTINCT MONTH(created_at) as month, 
                   MONTHNAME(created_at) as month_name,
                   COUNT(*) as total
                   FROM biographical_profiles 
                   WHERE YEAR(created_at) = :year
                   GROUP BY MONTH(created_at)
                   ORDER BY month";
    $monthStmt = $db->prepare($monthQuery);
    $monthStmt->bindParam(':year', $selectedYear);
    $monthStmt->execute();
    $months = $monthStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Profile Archive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f0f2f5;
            font-family: 'Arial', sans-serif;
        }
        .header {
            background: #0a2f4d;
            color: white;
            padding: 15px 0;
            border-bottom: 3px solid #c9a959;
            margin-bottom: 30px;
        }
        .archive-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .year-box {
            background: #0a2f4d;
            color: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s;
            margin-bottom: 15px;
        }
        .year-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .year-box.active {
            background: #c9a959;
            color: #0a2f4d;
        }
        .year-number {
            font-size: 24px;
            font-weight: bold;
        }
        .year-count {
            font-size: 14px;
            opacity: 0.9;
        }
        .month-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            margin-bottom: 10px;
        }
        .month-box:hover {
            background: #e9ecef;
        }
        .month-box.active {
            background: #0a2f4d;
            color: white;
            border-color: #0a2f4d;
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
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-shield-alt fa-2x me-2"></i>
                    <span class="h5">Profile Archive</span>
                    <br><small>Manolo Fortich Police Station</small>
                </div>
                <div>
                    <a href="dashboard.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <!-- Years Column -->
            <div class="col-md-3">
                <div class="archive-card">
                    <h5><i class="fas fa-calendar"></i> Years</h5>
                    <hr>
                    <a href="archive.php" class="text-decoration-none">
                        <div class="year-box <?php echo empty($selectedYear) ? 'active' : ''; ?>">
                            <div class="year-number">All</div>
                            <div class="year-count">All Profiles</div>
                        </div>
                    </a>
                    <?php foreach ($years as $year): ?>
                    <a href="?year=<?php echo $year['year']; ?>" class="text-decoration-none">
                        <div class="year-box <?php echo $selectedYear == $year['year'] ? 'active' : ''; ?>">
                            <div class="year-number"><?php echo $year['year']; ?></div>
                            <div class="year-count"><?php echo $year['total']; ?> profiles</div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Months Column -->
            <div class="col-md-3">
                <?php if (!empty($selectedYear)): ?>
                <div class="archive-card">
                    <h5><i class="fas fa-calendar-alt"></i> Months of <?php echo $selectedYear; ?></h5>
                    <hr>
                    <a href="?year=<?php echo $selectedYear; ?>" class="text-decoration-none">
                        <div class="month-box <?php echo empty($selectedMonth) ? 'active' : ''; ?>">
                            <strong>All Months</strong>
                        </div>
                    </a>
                    <?php foreach ($months as $month): ?>
                    <a href="?year=<?php echo $selectedYear; ?>&month=<?php echo $month['month']; ?>" 
                       class="text-decoration-none">
                        <div class="month-box <?php echo $selectedMonth == $month['month'] ? 'active' : ''; ?>">
                            <strong><?php echo $month['month_name']; ?></strong>
                            <br><small><?php echo $month['total']; ?> profiles</small>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Results Column -->
            <div class="col-md-6">
                <div class="archive-card">
                    <h5>
                        <i class="fas fa-list"></i> 
                        <?php 
                        if (!empty($selectedYear) && !empty($selectedMonth)) {
                            $monthName = date('F', mktime(0, 0, 0, $selectedMonth, 1));
                            echo "Profiles for $monthName $selectedYear";
                        } elseif (!empty($selectedYear)) {
                            echo "Profiles for Year $selectedYear";
                        } else {
                            echo "All Profiles";
                        }
                        ?>
                    </h5>
                    <hr>
                    
                    <?php if (count($profiles) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Name</th>
                                        <th>Alias</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($profiles as $profile): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($profile['created_at'])); ?></td>
                                        <td><?php echo $profile['full_name']; ?></td>
                                        <td><?php echo $profile['alias'] ?: 'N/A'; ?></td>
                                        <td>
                                            <a href="view_profile.php?id=<?php echo $profile['id']; ?>" 
                                               class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center">No profiles found for this period.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>