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

// Parse drug types if stored as comma-separated
$drugTypesArray = !empty($profile['drugs_involved']) ? explode(', ', $profile['drugs_involved']) : [];

// Parse position roles if stored as comma-separated
$positionRolesArray = !empty($profile['position_roles']) ? explode(', ', $profile['position_roles']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Profile - PNP Biographical Profiling System</title>
    
    <!-- External CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <style>
        /* Global Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.5;
        }

        /* New Minimalist Header */
        .app-header {
            background: #ffffff;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .app-header .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-left i {
            font-size: 22px;
            color: #0f172a;
        }

        .header-left h4 {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
            margin: 0;
        }

        .header-left small {
            font-size: 12px;
            color: #64748b;
            display: block;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #475569;
        }

        .user-info i {
            color: #94a3b8;
            font-size: 14px;
        }

        .btn-icon {
            background: #f1f5f9;
            color: #334155;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            border: 1px solid #e2e8f0;
        }

        .btn-icon:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .btn-icon i {
            font-size: 12px;
        }

        /* Main Container */
        .main-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 24px;
        }

        /* Form-like Profile View */
        .profile-view {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
        }

        .view-header {
            background: #f8fafc;
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .view-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .view-header h3 i {
            color: #94a3b8;
            font-size: 18px;
        }

        .profile-id {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 13px;
            color: #475569;
        }

        /* Profile Info Section */
        .profile-info-section {
            padding: 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }

        .info-main {
            flex: 1;
            min-width: 300px;
        }

        .info-main h1 {
            font-size: 28px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 15px;
            letter-spacing: -0.02em;
        }

        .badge-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }

        .light-badge {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 13px;
            color: #334155;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .light-badge i {
            color: #94a3b8;
            font-size: 11px;
        }

        .status-tag {
            display: inline-block;
            padding: 4px 16px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-tag.active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-tag.archived {
            background: #fed7aa;
            color: #92400e;
        }

        .status-tag.delisted {
            background: #fee2e2;
            color: #991b1b;
        }

        .meta-info {
            margin-top: 15px;
            font-size: 13px;
            color: #64748b;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .meta-info i {
            color: #94a3b8;
            margin-right: 4px;
        }

        /* 2x2 Picture Box */
        .picture-box-2x2 {
            width: 140px;
            height: 140px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr;
            gap: 4px;
            padding: 4px;
        }

        .picture-cell {
            background: white;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #cbd5e1;
            font-size: 24px;
            border: 1px solid #f1f5f9;
        }

        /* Section Styles */
        .view-section {
            padding: 24px;
            border-bottom: 1px solid #e2e8f0;
        }

        .view-section:last-child {
            border-bottom: none;
        }

        .section-heading {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .section-heading i {
            font-size: 18px;
            color: #94a3b8;
        }

        .section-heading h4 {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        /* Grid Layout */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 16px;
        }

        .info-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px;
        }

        .info-item .label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .info-item .value {
            font-size: 15px;
            font-weight: 500;
            color: #0f172a;
        }

        /* Tag Styles */
        .tag-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 6px;
        }

        .role-tag {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            color: #334155;
            padding: 3px 10px;
            border-radius: 30px;
            font-size: 12px;
        }

        .drug-tag {
            padding: 3px 10px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 500;
            color: white;
        }

        .drug-tag.marijuana { background: #10b981; }
        .drug-tag.shabu { background: #ef4444; }
        .drug-tag.other { background: #6b7280; }

        .highlight-item {
            background: #fff3cd;
            border: 1px solid #ffc107;
        }

        .summary-item {
            background: #e8f4fd;
            border: 1px solid #b8daff;
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .data-table th {
            background: #f8fafc;
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            font-size: 12px;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
        }

        .data-table td {
            padding: 10px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        /* Action Buttons */
        .action-bar {
            padding: 24px;
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        .btn-primary {
            background: #0f172a;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-primary:hover {
            background: #1e293b;
        }

        .btn-secondary {
            background: #ffffff;
            color: #334155;
            border: 1px solid #e2e8f0;
            padding: 10px 24px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-secondary:hover {
            background: #f1f5f9;
        }

        .btn-delete {
            background: #fee2e2;
            color: #991b1b;
            border: none;
            padding: 10px 24px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-delete:hover {
            background: #fecaca;
        }

        /* Footer */
        .app-footer {
            background: #ffffff;
            padding: 20px 0;
            border-top: 1px solid #e2e8f0;
            margin-top: 40px;
        }

        .app-footer .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
            text-align: center;
            color: #64748b;
            font-size: 12px;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 10px;
        }

        .footer-links a {
            color: #475569;
            text-decoration: none;
            font-size: 12px;
        }

        .footer-links a:hover {
            color: #0f172a;
        }

        /* Two Column Layout */
        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        @media (max-width: 768px) {
            .two-col {
                grid-template-columns: 1fr;
            }
            
            .profile-info-section {
                flex-direction: column;
            }
            
            .picture-box-2x2 {
                align-self: center;
            }
            
            .action-bar {
                flex-direction: column;
            }
            
            .action-bar .btn-primary,
            .action-bar .btn-secondary,
            .action-bar .btn-delete {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- New Minimalist Header -->
    <header class="app-header">
        <div class="container">
            <div class="header-left">
                <i class="fas fa-shield-alt"></i>
                <div>
                    <h4>PNP Biographical Profiling</h4>
                    <small>Manolo Fortich Police Station</small>
                </div>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo $_SESSION['rank'] . ' ' . $_SESSION['full_name']; ?></span>
                </div>
                <a href="dashboard.php" class="btn-icon">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>
    </header>

    <main class="main-container">
        <!-- Profile View (Form-like) -->
        <div class="profile-view">
            <!-- View Header -->
            <div class="view-header">
                <h3>
                    <i class="fas fa-id-card"></i>
                    BIOGRAPHICAL PROFILE
                </h3>
                <span class="profile-id">#<?php echo str_pad($profile['id'] ?? 0, 6, '0', STR_PAD_LEFT); ?></span>
            </div>
            
            <!-- Profile Header with Picture -->
            <div class="profile-info-section">
                <div class="info-main">
                    <h1><?php echo displayValue($profile['full_name']); ?></h1>
                    
                    <div class="badge-container">
                        <span class="light-badge"><i class="fas fa-tag"></i> <?php echo displayValue($profile['alias']); ?></span>
                        <span class="light-badge"><i class="fas fa-users"></i> <?php echo displayValue($profile['group_affiliation']); ?></span>
                        <span class="light-badge"><i class="fas fa-briefcase"></i> <?php echo displayValue($profile['position_roles']); ?></span>
                    </div>
                    
                    <div>
                        <span class="status-tag <?php echo $profile['status']; ?>">
                            <?php echo strtoupper($profile['status'] ?? 'ACTIVE'); ?>
                        </span>
                    </div>
                    
                    <div class="meta-info">
                        <span><i class="fas fa-calendar"></i> Created: <?php echo !empty($profile['created_at']) ? date('M d, Y', strtotime($profile['created_at'])) : 'N/A'; ?></span>
                        <span><i class="fas fa-user"></i> by <?php echo $creator_rank . ' ' . $creator_name; ?></span>
                    </div>
                </div>
                
                <!-- 2x2 Picture Box -->
                <div class="picture-box-2x2">
                    <div class="picture-cell"><i class="fas fa-user"></i></div>
                    <div class="picture-cell"><i class="fas fa-user"></i></div>
                    <div class="picture-cell"><i class="fas fa-user"></i></div>
                    <div class="picture-cell"><i class="fas fa-user"></i></div>
                </div>
            </div>

            <!-- I. Personal Data -->
            <section class="view-section">
                <div class="section-heading">
                    <i class="fas fa-user"></i>
                    <h4>I. PERSONAL DATA</h4>
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <div class="label">Full Name</div>
                        <div class="value"><?php echo displayValue($profile['full_name']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Alias</div>
                        <div class="value"><?php echo displayValue($profile['alias']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Age / Sex</div>
                        <div class="value"><?php echo $profile['age']; ?> / <?php echo $profile['sex']; ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Date of Birth</div>
                        <div class="value"><?php echo !empty($profile['dob']) ? date('M d, Y', strtotime($profile['dob'])) : 'N/A'; ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Place of Birth</div>
                        <div class="value"><?php echo displayValue($profile['pob']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Civil Status</div>
                        <div class="value"><?php echo displayValue($profile['civil_status']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Citizenship</div>
                        <div class="value"><?php echo displayValue($profile['citizenship']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Religion</div>
                        <div class="value"><?php echo displayValue($profile['religion']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Education</div>
                        <div class="value"><?php echo displayValue($profile['educational_attainment']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Occupation</div>
                        <div class="value"><?php echo displayValue($profile['occupation']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Company/Office</div>
                        <div class="value"><?php echo displayValue($profile['company_office']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Technical Skills</div>
                        <div class="value"><?php echo displayValue($profile['technical_skills']); ?></div>
                    </div>
                </div>
                
                <div style="margin-top: 16px;">
                    <div class="info-item">
                        <div class="label">Present Address</div>
                        <div class="value"><?php echo displayValue($profile['present_address']); ?></div>
                    </div>
                </div>
                
                <div style="margin-top: 16px;">
                    <div class="info-item">
                        <div class="label">Provincial Address</div>
                        <div class="value"><?php echo displayValue($profile['provincial_address']); ?></div>
                    </div>
                </div>
                
                <div class="info-grid" style="margin-top: 16px;">
                    <div class="info-item">
                        <div class="label">Height</div>
                        <div class="value"><?php echo displayValue($profile['height_ft']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Weight</div>
                        <div class="value"><?php echo displayValue($profile['weight_kg']); ?> kg</div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Built</div>
                        <div class="value"><?php echo displayValue($profile['built']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Eyes Color</div>
                        <div class="value"><?php echo displayValue($profile['eyes_color']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Hair Color</div>
                        <div class="value"><?php echo displayValue($profile['hair_color']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Complexion</div>
                        <div class="value"><?php echo displayValue($profile['complexion']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Ethnic Group</div>
                        <div class="value"><?php echo displayValue($profile['ethnic_group']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Languages</div>
                        <div class="value"><?php echo displayValue($profile['languages']); ?></div>
                    </div>
                </div>
                
                <div style="margin-top: 16px;">
                    <div class="info-item">
                        <div class="label">Distinguishing Marks</div>
                        <div class="value"><?php echo displayValue($profile['distinguishing_marks']); ?></div>
                    </div>
                </div>
            </section>

            <!-- II. Family Background -->
            <section class="view-section">
                <div class="section-heading">
                    <i class="fas fa-users"></i>
                    <h4>II. FAMILY BACKGROUND</h4>
                </div>
                
                <h5 style="margin-bottom: 12px; font-size: 14px; color: #475569;">Parents</h5>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Father</th>
                                <th>Mother</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Name</td>
                                <td><?php echo displayValue($profile['father_name']); ?></td>
                                <td><?php echo displayValue($profile['mother_name']); ?></td>
                            </tr>
                            <tr>
                                <td>Address</td>
                                <td><?php echo displayValue($profile['father_address']); ?></td>
                                <td><?php echo displayValue($profile['mother_address']); ?></td>
                            </tr>
                            <tr>
                                <td>Date of Birth</td>
                                <td><?php echo !empty($profile['father_dob']) ? date('M d, Y', strtotime($profile['father_dob'])) : 'N/A'; ?></td>
                                <td><?php echo !empty($profile['mother_dob']) ? date('M d, Y', strtotime($profile['mother_dob'])) : 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <td>Occupation</td>
                                <td><?php echo displayValue($profile['father_occupation']); ?></td>
                                <td><?php echo displayValue($profile['mother_occupation']); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <h5 style="margin: 24px 0 12px; font-size: 14px; color: #475569;">Spouse</h5>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Age</th>
                                <th>Occupation</th>
                                <th>Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo displayValue($profile['spouse_name']); ?></td>
                                <td><?php echo displayValue($profile['spouse_age']); ?></td>
                                <td><?php echo displayValue($profile['spouse_occupation']); ?></td>
                                <td><?php echo displayValue($profile['spouse_address']); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <h5 style="margin: 24px 0 12px; font-size: 14px; color: #475569;">Siblings</h5>
                <?php if (count($siblings) > 0): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Age</th>
                                    <th>Occupation</th>
                                    <th>Status</th>
                                    <th>Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($siblings as $sibling): ?>
                                <tr>
                                    <td><?php echo displayValue($sibling['name']); ?></td>
                                    <td><?php echo displayValue($sibling['age']); ?></td>
                                    <td><?php echo displayValue($sibling['occupation']); ?></td>
                                    <td><?php echo displayValue($sibling['status']); ?></td>
                                    <td><?php echo displayValue($sibling['address']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; padding: 20px; color: #64748b; border: 1px dashed #e2e8f0; border-radius: 8px;">
                        No siblings recorded
                    </p>
                <?php endif; ?>
            </section>

            <!-- III. Tactical Information -->
            <section class="view-section">
                <div class="section-heading">
                    <i class="fas fa-info-circle"></i>
                    <h4>III. TACTICAL INFORMATION</h4>
                </div>
                
                <div class="info-item highlight-item" style="margin-bottom: 16px;">
                    <div class="label">Drugs Involved</div>
                    <div class="tag-wrapper">
                        <?php 
                        if (!empty($drugTypesArray)) {
                            foreach ($drugTypesArray as $drug) {
                                $drug = trim($drug);
                                $drugClass = 'drug-tag';
                                if (stripos($drug, 'marijuana') !== false) $drugClass .= ' marijuana';
                                elseif (stripos($drug, 'shabu') !== false) $drugClass .= ' shabu';
                                else $drugClass .= ' other';
                                echo '<span class="' . $drugClass . '">' . $drug . '</span>';
                            }
                        } else {
                            echo displayValue($profile['drugs_involved']);
                        }
                        ?>
                    </div>
                </div>
                
                <div class="two-col">
                    <div>
                        <div class="info-item">
                            <div class="label">Relationship to Source</div>
                            <div class="value"><?php echo displayValue($profile['source_relationship']); ?></div>
                        </div>
                        
                        <div class="info-item" style="margin-top: 12px;">
                            <div class="label">Source Address</div>
                            <div class="value"><?php echo displayValue($profile['source_address']); ?></div>
                        </div>
                        
                        <div class="info-item" style="margin-top: 12px;">
                            <div class="label">Source Name</div>
                            <div class="value"><?php echo displayValue($profile['source_name']); ?></div>
                        </div>
                        
                        <div class="info-item" style="margin-top: 12px;">
                            <div class="label">Source Alias</div>
                            <div class="value"><?php echo displayValue($profile['source_nickname']); ?></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="info-item">
                            <div class="label">Subgroup Name</div>
                            <div class="value"><?php echo displayValue($profile['subgroup_name']); ?></div>
                        </div>
                        
                        <div class="info-item" style="margin-top: 12px;">
                            <div class="label">Area of Responsibility</div>
                            <div class="value"><?php echo displayValue($profile['specific_aor']); ?></div>
                        </div>
                        
                        <div class="info-item" style="margin-top: 12px;">
                            <div class="label">Vehicles Used</div>
                            <div class="value"><?php echo displayValue($profile['vehicles_used']); ?></div>
                        </div>
                        
                        <div class="info-item" style="margin-top: 12px;">
                            <div class="label">Armaments</div>
                            <div class="value"><?php echo displayValue($profile['armaments']); ?></div>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 16px;">
                    <div class="info-item">
                        <div class="label">Complete Address of Alleged Source</div>
                        <div class="value"><?php echo displayValue($profile['source_full_address']); ?></div>
                    </div>
                </div>
                
                <div class="two-col" style="margin-top: 16px;">
                    <div class="info-item">
                        <div class="label">Other Drugs Supplied by Source</div>
                        <div class="value"><?php echo displayValue($profile['source_other_drugs']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Other Subject Known as Source</div>
                        <div class="value">
                            <?php 
                            if (!empty($profile['other_source_name'])) {
                                echo displayValue($profile['other_source_name']);
                                if (!empty($profile['other_source_alias'])) {
                                    echo ' aka ' . $profile['other_source_alias'];
                                }
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($profile['other_source_details'])): ?>
                <div style="margin-top: 16px;">
                    <div class="info-item">
                        <div class="label">Other Source Details</div>
                        <div class="value"><?php echo displayValue($profile['other_source_details']); ?></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div style="margin-top: 16px;">
                    <div class="info-item">
                        <div class="label">Types of Drugs Pushed</div>
                        <div class="tag-wrapper">
                            <?php 
                            if (!empty($drugsPushedArray)) {
                                foreach ($drugsPushedArray as $drug) {
                                    $drugClass = 'drug-tag';
                                    if (stripos($drug, 'marijuana') !== false) $drugClass .= ' marijuana';
                                    elseif (stripos($drug, 'shabu') !== false) $drugClass .= ' shabu';
                                    else $drugClass .= ' other';
                                    echo '<span class="' . $drugClass . '">' . $drug . '</span>';
                                }
                                if (!empty($profile['other_drugs_pushed'])) {
                                    echo '<span class="drug-tag other">' . $profile['other_drugs_pushed'] . '</span>';
                                }
                            } else {
                                echo displayValue($profile['other_drugs_pushed']);
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 16px;">
                    <div class="info-item">
                        <div class="label">Companions During Arrest</div>
                        <div class="value"><?php echo displayValue($profile['companions_arrest']); ?></div>
                    </div>
                </div>
            </section>

            <!-- IV. Arrest Record -->
            <section class="view-section">
                <div class="section-heading">
                    <i class="fas fa-gavel"></i>
                    <h4>IV. ARREST RECORD</h4>
                </div>
                
                <div class="two-col">
                    <div class="info-item">
                        <div class="label">Previous Arrest Record</div>
                        <div class="value"><?php echo displayValue($profile['previous_arrest']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Specific Charge</div>
                        <div class="value"><?php echo displayValue($profile['specific_charge']); ?></div>
                    </div>
                </div>
                
                <div class="two-col" style="margin-top: 16px;">
                    <div class="info-item">
                        <div class="label">Date/Time of Arrest</div>
                        <div class="value"><?php echo !empty($profile['arrest_datetime']) ? date('M d, Y H:i', strtotime($profile['arrest_datetime'])) : 'N/A'; ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Place of Arrest</div>
                        <div class="value"><?php echo displayValue($profile['arrest_place']); ?></div>
                    </div>
                </div>
                
                <div class="two-col" style="margin-top: 16px;">
                    <div class="info-item">
                        <div class="label">Arresting Officer</div>
                        <div class="value"><?php echo displayValue($profile['arresting_officer']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Unit/Office</div>
                        <div class="value"><?php echo displayValue($profile['arresting_unit']); ?></div>
                    </div>
                </div>
            </section>

            <!-- V. Summary and Recommendations -->
            <section class="view-section">
                <div class="section-heading">
                    <i class="fas fa-file-alt"></i>
                    <h4>V. SUMMARY & RECOMMENDATIONS</h4>
                </div>
                
                <div class="info-item">
                    <div class="label">Recruitment Summary</div>
                    <div class="value"><?php echo displayValue($profile['recruitment_summary']); ?></div>
                </div>
                
                <div class="info-item" style="margin-top: 16px;">
                    <div class="label">Modus Operandi</div>
                    <div class="value"><?php echo displayValue($profile['modus_operandi']); ?></div>
                </div>
                
                <div class="info-item" style="margin-top: 16px;">
                    <div class="label">Organizational Structure</div>
                    <div class="value"><?php echo displayValue($profile['organizational_structure']); ?></div>
                </div>
                
                <div class="info-item" style="margin-top: 16px;">
                    <div class="label">CI Matters</div>
                    <div class="value"><?php echo displayValue($profile['ci_matters']); ?></div>
                </div>
                
                <?php if (!empty($profile['other_revelations'])): ?>
                <div class="info-item" style="margin-top: 16px;">
                    <div class="label">Other Revelations</div>
                    <div class="value"><?php echo displayValue($profile['other_revelations']); ?></div>
                </div>
                <?php endif; ?>
                
                <div class="two-col" style="margin-top: 24px;">
                    <div class="info-item summary-item">
                        <div class="label">Recommendation</div>
                        <div class="value" style="font-size: 16px; font-weight: 600;"><?php echo displayValue($profile['recommendation']); ?></div>
                    </div>
                    
                    <div class="info-item summary-item">
                        <div class="label">Profile Status</div>
                        <div class="value">
                            <span class="status-tag <?php echo $profile['status']; ?>" style="margin-left: 0;">
                                <?php echo strtoupper($profile['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Footer Information -->
            <div style="padding: 16px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; display: flex; justify-content: space-between;">
                <span><i class="fas fa-clock"></i> Created: <?php echo !empty($profile['created_at']) ? date('M d, Y h:i A', strtotime($profile['created_at'])) : 'N/A'; ?></span>
                <span><i class="fas fa-sync-alt"></i> Updated: <?php echo !empty($profile['updated_at']) ? date('M d, Y h:i A', strtotime($profile['updated_at'])) : 'N/A'; ?></span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-bar">
            <a href="dashboard.php" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <a href="edit_profile.php?id=<?php echo $profile['id']; ?>" class="btn-primary">
                <i class="fas fa-edit"></i> Edit Profile
            </a>
            <a href="profile_form.php" class="btn-secondary">
                <i class="fas fa-plus"></i> New
            </a>
            <button onclick="window.print()" class="btn-secondary">
                <i class="fas fa-print"></i> Print
            </button>
            <?php if ($_SESSION['role'] == 'admin'): ?>
            <button onclick="confirmDelete(<?php echo $profile['id']; ?>)" class="btn-delete">
                <i class="fas fa-trash"></i> Delete
            </button>
            <?php endif; ?>
        </div>
    </main>

    <!-- New Minimalist Footer -->
    <footer class="app-footer">
        <div class="container">
            <div class="footer-links">
                <a href="#">Privacy</a>
                <a href="#">Terms</a>
                <a href="#">Contact</a>
            </div>
            <small>Department of the Interior and Local Government | PHILIPPINE NATIONAL POLICE<br>
            BUKIDNON POLICE PROVINCIAL OFFICE | MANOLO FORTICH POLICE STATION<br>
            © 2024 All rights reserved. This document is CONFIDENTIAL.</small>
        </div>
    </footer>

    <script>
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this profile? This action cannot be undone.')) {
                window.location.href = 'delete_profile.php?id=' + id;
            }
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>