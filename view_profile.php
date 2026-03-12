<?php
// view_profile.php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get profile ID from URL
$id = isset($_GET['id']) ? $_GET['id'] : 0;

if ($id == 0) {
    header("Location: dashboard.php");
    exit();
}

// Fetch profile data
$query = "SELECT * FROM biographical_profiles WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: dashboard.php");
    exit();
}

$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch siblings data
$query = "SELECT * FROM siblings WHERE profile_id = :profile_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':profile_id', $id);
$stmt->execute();
$siblings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get creator info with error handling
$creator_name = 'Unknown';
$creator_rank = '';

if (!empty($profile['created_by'])) {
    $query = "SELECT full_name, rank FROM users WHERE id = :created_by";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':created_by', $profile['created_by']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $creator = $stmt->fetch(PDO::FETCH_ASSOC);
        $creator_name = $creator['full_name'] ?? 'Unknown';
        $creator_rank = $creator['rank'] ?? '';
    }
} else {
    $creator_name = 'System';
    $creator_rank = '';
}

// Function to safely display data
function displayValue($value, $default = 'N/A') {
    return !empty($value) ? htmlspecialchars($value) : $default;
}

// Parse drugs_pushed if exists
$drugsPushedArray = !empty($profile['drugs_pushed']) ? explode(', ', $profile['drugs_pushed']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Profile - PNP Biographical Profiling System</title>
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
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .profile-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #0a2f4d 0%, #1a4b7a 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            position: relative;
            border-left: 5px solid #c9a959;
        }
        
        .profile-header h2 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        
        .profile-header .badge-status {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .badge-active {
            background: #28a745;
            color: white;
        }
        
        .badge-delisted {
            background: #dc3545;
            color: white;
        }
        
        .badge-archived {
            background: #ffc107;
            color: #333;
        }
        
        .section-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid #0a2f4d;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .section-title {
            color: #0a2f4d;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #c9a959;
            text-transform: uppercase;
        }
        
        .section-title i {
            margin-right: 10px;
            color: #c9a959;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            background: white;
            padding: 12px 15px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        
        .info-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #0a2f4d;
            word-break: break-word;
        }
        
        .info-value-small {
            font-size: 14px;
            font-weight: normal;
        }
        
        .table-custom {
            background: white;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .table-custom th {
            background: #0a2f4d;
            color: white;
            font-weight: 600;
            font-size: 14px;
            padding: 12px;
        }
        
        .table-custom td {
            padding: 10px 12px;
            vertical-align: middle;
        }
        
        .table-custom tr:hover {
            background: #f5f5f5;
        }
        
        .tactical-highlight {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 0 5px 5px 0;
        }
        
        .drug-tag {
            background: #0a2f4d;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            display: inline-block;
            margin: 2px;
        }
        
        .drug-tag-marijuana {
            background: #28a745;
        }
        
        .drug-tag-shabu {
            background: #dc3545;
        }
        
        .drug-tag-other {
            background: #6c757d;
        }
        
        .action-buttons {
            position: sticky;
            bottom: 20px;
            background: white;
            padding: 15px;
            border-radius: 50px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            margin-top: 30px;
        }
        
        .action-buttons .btn {
            margin: 0 5px;
            padding: 10px 25px;
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
        
        @media print {
            .header, .action-buttons, .footer {
                display: none;
            }
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
                <div>
                    <span class="me-3">
                        <i class="fas fa-user"></i> <?php echo $_SESSION['rank'] . ' ' . $_SESSION['full_name']; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="row">
                <div class="col-md-8">
                    <h2><i class="fas fa-id-card me-3"></i><?php echo displayValue($profile['full_name']); ?></h2>
                    <p class="mb-1"><i class="fas fa-tag me-2"></i>Alias: <?php echo displayValue($profile['alias']); ?></p>
                    <p class="mb-1"><i class="fas fa-users me-2"></i>Group/Gang: <?php echo displayValue($profile['group_affiliation']); ?></p>
                    <p class="mb-0"><i class="fas fa-briefcase me-2"></i>Position/Role: <?php echo displayValue($profile['position_roles']); ?></p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge-status badge-<?php echo $profile['status']; ?>">
                        <?php echo strtoupper($profile['status'] ?? 'ACTIVE'); ?>
                    </span>
                    <p class="mt-4 mb-0"><small>Profile ID: #<?php echo str_pad($profile['id'] ?? 0, 6, '0', STR_PAD_LEFT); ?></small></p>
                    <p class="mb-0"><small>Created: <?php echo !empty($profile['created_at']) ? date('F d, Y', strtotime($profile['created_at'])) : 'N/A'; ?></small></p>
                    <p class="mb-0"><small>By: <?php 
                        $display_name = '';
                        if (!empty($creator_rank)) {
                            $display_name .= $creator_rank . ' ';
                        }
                        $display_name .= $creator_name;
                        echo !empty(trim($display_name)) ? htmlspecialchars($display_name) : 'Unknown';
                    ?></small></p>
                </div>
            </div>
        </div>

        <!-- Rest of your code continues exactly as is from here... -->
        <!-- I. Personal Data section, etc. -->
        
<?php
// Continue with the rest of your HTML/PHP code from your original file
// (All the sections after the profile header remain exactly the same)
?>
