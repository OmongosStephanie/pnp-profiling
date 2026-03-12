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
        
        /* Form-like styling */
        .profile-form {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
        }
        
        .form-header {
            background: #0a2f4d;
            color: white;
            padding: 15px 20px;
            margin: -30px -30px 20px -30px;
            border-radius: 10px 10px 0 0;
            border-bottom: 3px solid #c9a959;
        }
        
        .form-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
        }
        
        .form-header h3 i {
            margin-right: 10px;
            color: #c9a959;
        }
        
        .profile-header-new {
            display: flex;
            margin-bottom: 30px;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            border: 1px solid #dee2e6;
        }
        
        .picture-box {
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, #0a2f4d, #1a4b7a);
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            border: 3px solid #c9a959;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            margin-right: 30px;
        }
        
        .picture-box i {
            font-size: 60px;
            margin-bottom: 10px;
        }
        
        .picture-box span {
            font-size: 12px;
            text-align: center;
            padding: 0 10px;
        }
        
        .profile-info-box {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .profile-info-box .name {
            font-size: 32px;
            font-weight: bold;
            color: #0a2f4d;
            margin-bottom: 10px;
        }
        
        .profile-info-box .details {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .profile-info-box .detail-item {
            background: white;
            padding: 8px 15px;
            border-radius: 20px;
            border: 1px solid #dee2e6;
            font-size: 14px;
        }
        
        .profile-info-box .detail-item i {
            color: #c9a959;
            margin-right: 5px;
        }
        
        .status-badge-new {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 14px;
        }
        
        .section-card-new {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid #dee2e6;
            position: relative;
        }
        
        .section-title-new {
            color: #0a2f4d;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #c9a959;
            text-transform: uppercase;
            background: white;
            padding: 10px 15px;
            margin: -20px -20px 20px -20px;
            border-radius: 8px 8px 0 0;
        }
        
        .section-title-new i {
            margin-right: 10px;
            color: #c9a959;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 15px;
            border-bottom: 1px dashed #dee2e6;
            padding-bottom: 10px;
        }
        
        .form-label-new {
            width: 200px;
            font-weight: 600;
            color: #0a2f4d;
            padding: 8px 0;
        }
        
        .form-value {
            flex: 1;
            padding: 8px 0;
            color: #333;
        }
        
        .form-value-plain {
            background: white;
            padding: 8px 15px;
            border-radius: 5px;
            border: 1px solid #e9ecef;
        }
        
        .info-grid-new {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }
        
        .info-item-new {
            background: white;
            padding: 12px 15px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        
        .info-label-new {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .info-value-new {
            font-size: 16px;
            font-weight: 600;
            color: #0a2f4d;
            word-break: break-word;
        }
        
        .table-custom-new {
            width: 100%;
            background: white;
            border-radius: 5px;
            overflow: hidden;
            border: 1px solid #dee2e6;
        }
        
        .table-custom-new th {
            background: #0a2f4d;
            color: white;
            font-weight: 600;
            font-size: 14px;
            padding: 12px;
        }
        
        .table-custom-new td {
            padding: 10px 12px;
            vertical-align: middle;
            border-bottom: 1px solid #dee2e6;
        }
        
        .table-custom-new tr:last-child td {
            border-bottom: none;
        }
        
        .table-custom-new tr:hover {
            background: #f5f5f5;
        }
        
        .tactical-highlight-new {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
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
            border: 1px solid #dee2e6;
        }
        
        .action-buttons .btn {
            margin: 0 5px;
            padding: 10px 25px;
        }
        
        .btn-pnp {
            background: #0a2f4d;
            color: white;
            border: none;
        }
        
        .btn-pnp:hover {
            background: #c9a959;
            color: #0a2f4d;
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
        
        .print-only {
            display: none;
        }
        
        @media print {
            body {
                background: white;
            }
            
            .header, .action-buttons, .footer, .btn, .picture-box i {
                display: none !important;
            }
            
            .profile-form {
                box-shadow: none;
                border: 1px solid #000;
            }
            
            .picture-box {
                border: 2px solid #000;
                background: #f0f0f0;
                color: #000;
            }
            
            .picture-box i {
                display: none;
            }
            
            .picture-box span {
                color: #000;
            }
            
            .section-title-new {
                border-bottom: 2px solid #000;
            }
            
            .table-custom-new th {
                background: #f0f0f0;
                color: #000;
            }
            
            .print-only {
                display: block;
            }
        }
        
        .badge {
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
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
        <div class="profile-form">
            <!-- Form Header -->
            <div class="form-header">
                <h3><i class="fas fa-id-card"></i> BIOGRAPHICAL PROFILE FORM</h3>
            </div>
            
            <!-- Profile Header with Picture Box -->
            <div class="profile-header-new" style="position: relative;">
                <!-- Picture Box (Upper Right) -->
                <div class="picture-box">
                    <i class="fas fa-user-circle"></i>
                    <span>Profile Picture</span>
                </div>
                
                <!-- Profile Info -->
                <div class="profile-info-box">
                    <div class="name"><?php echo displayValue($profile['full_name']); ?></div>
                    <div class="details">
                        <span class="detail-item"><i class="fas fa-tag"></i> Alias: <?php echo displayValue($profile['alias']); ?></span>
                        <span class="detail-item"><i class="fas fa-users"></i> Group: <?php echo displayValue($profile['group_affiliation']); ?></span>
                        <span class="detail-item"><i class="fas fa-briefcase"></i> Position: <?php echo displayValue($profile['position_roles']); ?></span>
                        <span class="detail-item"><i class="fas fa-id-number"></i> ID: #<?php echo str_pad($profile['id'] ?? 0, 6, '0', STR_PAD_LEFT); ?></span>
                    </div>
                </div>
                
                <!-- Status Badge -->
                <span class="status-badge-new badge-<?php echo $profile['status']; ?>">
                    <?php echo strtoupper($profile['status'] ?? 'ACTIVE'); ?>
                </span>
            </div>
            
            <!-- I. Personal Data -->
            <div class="section-card-new">
                <h4 class="section-title-new"><i class="fas fa-user"></i> I. PERSONAL DATA</h4>
                
                <div class="form-row">
                    <div class="form-label-new">Full Name:</div>
                    <div class="form-value"><span class="form-value-plain"><?php echo displayValue($profile['full_name']); ?></span></div>
                </div>
                
                <div class="form-row">
                    <div class="form-label-new">Alias:</div>
                    <div class="form-value"><span class="form-value-plain"><?php echo displayValue($profile['alias']); ?></span></div>
                </div>
                
                <div class="form-row">
                    <div class="form-label-new">Group/Gang Affiliation:</div>
                    <div class="form-value"><span class="form-value-plain"><?php echo displayValue($profile['group_affiliation']); ?></span></div>
                </div>
                
                <div class="form-row">
                    <div class="form-label-new">Position/Role:</div>
                    <div class="form-value">
                        <span class="form-value-plain">
                            <?php 
                            if (!empty($positionRolesArray)) {
                                foreach ($positionRolesArray as $role) {
                                    echo '<span class="drug-tag">' . trim($role) . '</span> ';
                                }
                            } else {
                                echo displayValue($profile['position_roles']);
                            }
                            ?>
                        </span>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-label-new">Age / Sex:</div>
                    <div class="form-value"><span class="form-value-plain"><?php echo $profile['age']; ?> / <?php echo $profile['sex']; ?></span></div>
                </div>
                
                <div class="form-row">
                    <div class="form-label-new">Date of Birth:</div>
                    <div class="form-value"><span class="form-value-plain"><?php echo !empty($profile['dob']) ? date('F d, Y', strtotime($profile['dob'])) : 'N/A'; ?></span></div>
                </div>
                
                <div class="form-row">
                    <div class="form-label-new">Place of Birth:</div>
                    <div class="form-value"><span class="form-value-plain"><?php echo displayValue($profile['pob']); ?></span></div>
                </div>
                
                <div class="form-row">
                    <div class="form-label-new">Educational Attainment:</div>
                    <div class="form-value"><span class="form-value-plain"><?php echo displayValue($profile['educational_attainment']); ?></span></div>
                </div>
                
                <div class="form-row">
                    <div class="form-label-new">Occupation/Profession:</div>
                    <div class="form-value"><span class="form-value-plain"><?php echo displayValue($profile['occupation']); ?></span></div>
                </div>
                
                <div class="form-row">
                    <div class="form-label-new">Company/Office:</div>
                    <div class="form-value"><span class="form-value-plain"><?php echo displayValue($profile['company_office']); ?></span></div>
                </div>
                
                <div class="form-row">
                    <div class="form-label-new">Technical Skills:</div>
                    <div class="form-value"><span class="form-value-plain"><?php echo displayValue($profile['technical_skills']); ?></span></div>
                </div>
                
                <div class="form-row">
                    <div class="form-label-new">Ethnic Group:</div>
                    <div class="form-value"><span class="form-value-plain"><?php echo displayValue($profile['ethnic_group']); ?></span></div>
                </div>
                
                <div class="form-row">
                    <div class="form-label-new">Languages/Dialects:</div>
                    <div class="form-value"><span class="form-value-plain"><?php echo displayValue($profile['languages']); ?></span></div>
                </div>
                
                <div class="form-row">
                    <div class="form-label-new">Present Address:</div>
                    <div class="form-value"><span class="form-value-plain"><?php echo displayValue($profile['present_address']); ?></span></div>
                </div>
                
                <div class="form-row">
                    <div class="form-label-new">Provincial Address:</div>
                    <div class="form-value"><span class="form-value-plain"><?php echo displayValue($profile['provincial_address']); ?></span></div>
                </div>
                
                <div class="form-row">
                    <div class="form-label-new">Civil Status:</div>
                    <div class="form-value"><span class="form-value-plain"><?php echo displayValue($profile['civil_status']); ?></span></div>
                </div>
                
                <div class="form-row">
                    <div class="form-label-new">Citizenship:</div>
                    <div class="form-value"><span class="form-value-plain"><?php echo displayValue($profile['citizenship']); ?></span></div>
                </div>
                
                <div class="form-row">
                    <div class="form-label-new">Religion:</div>
                    <div class="form-value"><span class="form-value-plain"><?php echo displayValue($profile['religion']); ?></span></div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-3">
                        <div class="info-item-new">
                            <div class="info-label-new">Height (cm)</div>
                            <div class="info-value-new"><?php echo displayValue($profile['height_cm']); ?> cm</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-item-new">
                            <div class="info-label-new">Height (ft/in)</div>
                            <div class="info-value-new"><?php echo displayValue($profile['height_ft']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-item-new">
                            <div class="info-label-new">Weight (kg)</div>
                            <div class="info-value-new"><?php echo displayValue($profile['weight_kg']); ?> kg</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-item-new">
                            <div class="info-label-new">Built</div>
                            <div class="info-value-new"><?php echo displayValue($profile['built']); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="info-item-new">
                            <div class="info-label-new">Eyes Color</div>
                            <div class="info-value-new"><?php echo displayValue($profile['eyes_color']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-item-new">
                            <div class="info-label-new">Hair Color</div>
                            <div class="info-value-new"><?php echo displayValue($profile['hair_color']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-item-new">
                            <div class="info-label-new">Complexion</div>
                            <div class="info-value-new"><?php echo displayValue($profile['complexion']); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="info-item-new">
                            <div class="info-label-new">Distinguishing Marks/Tattoo</div>
                            <div class="info-value-new"><?php echo displayValue($profile['distinguishing_marks']); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- II. Family Background -->
            <div class="section-card-new">
                <h4 class="section-title-new"><i class="fas fa-family"></i> II. FAMILY BACKGROUND</h4>
                
                <h5 class="mt-3 mb-3" style="color: #0a2f4d;">Parents</h5>
                <table class="table-custom-new">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Father</th>
                            <th>Mother</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th>Name</th>
                            <td><?php echo displayValue($profile['father_name']); ?></td>
                            <td><?php echo displayValue($profile['mother_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td><?php echo displayValue($profile['father_address']); ?></td>
                            <td><?php echo displayValue($profile['mother_address']); ?></td>
                        </tr>
                        <tr>
                            <th>Date of Birth</th>
                            <td><?php echo !empty($profile['father_dob']) ? date('F d, Y', strtotime($profile['father_dob'])) : 'N/A'; ?></td>
                            <td><?php echo !empty($profile['mother_dob']) ? date('F d, Y', strtotime($profile['mother_dob'])) : 'N/A'; ?></td>
                        </tr>
                        <tr>
                            <th>Place of Birth</th>
                            <td><?php echo displayValue($profile['father_pob']); ?></td>
                            <td><?php echo displayValue($profile['mother_pob']); ?></td>
                        </tr>
                        <tr>
                            <th>Age</th>
                            <td><?php echo displayValue($profile['father_age']); ?></td>
                            <td><?php echo displayValue($profile['mother_age']); ?></td>
                        </tr>
                        <tr>
                            <th>Occupation</th>
                            <td><?php echo displayValue($profile['father_occupation']); ?></td>
                            <td><?php echo displayValue($profile['mother_occupation']); ?></td>
                        </tr>
                    </tbody>
                </table>
                
                <h5 class="mt-4 mb-3" style="color: #0a2f4d;">Spouse</h5>
                <table class="table-custom-new">
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
                
                <h5 class="mt-4 mb-3" style="color: #0a2f4d;">Siblings</h5>
                <?php if (count($siblings) > 0): ?>
                    <table class="table-custom-new">
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
                <?php else: ?>
                    <p class="text-muted">No siblings recorded</p>
                <?php endif; ?>
            </div>

            <!-- III. Tactical Information -->
            <div class="section-card-new">
                <h4 class="section-title-new"><i class="fas fa-info-circle"></i> III. TACTICAL INFORMATION</h4>
                
                <div class="tactical-highlight-new">
                    <strong>Drugs Involved:</strong>
                    <?php 
                    if (!empty($drugTypesArray)) {
                        foreach ($drugTypesArray as $drug) {
                            $drug = trim($drug);
                            $drugClass = 'drug-tag';
                            if (stripos($drug, 'marijuana') !== false) {
                                $drugClass .= ' drug-tag-marijuana';
                            } elseif (stripos($drug, 'shabu') !== false) {
                                $drugClass .= ' drug-tag-shabu';
                            } else {
                                $drugClass .= ' drug-tag-other';
                            }
                            echo '<span class="' . $drugClass . '">' . $drug . '</span> ';
                        }
                    } else {
                        echo displayValue($profile['drugs_involved']);
                    }
                    ?>
                </div>
                
                <div class="info-grid-new mt-3">
                    <div class="info-item-new">
                        <div class="info-label-new">Relationship to Source</div>
                        <div class="info-value-new"><?php echo displayValue($profile['source_relationship']); ?></div>
                    </div>
                    
                    <div class="info-item-new">
                        <div class="info-label-new">Source Address</div>
                        <div class="info-value-new"><?php echo displayValue($profile['source_address']); ?></div>
                    </div>
                    
                    <div class="info-item-new">
                        <div class="info-label-new">Source Name</div>
                        <div class="info-value-new"><?php echo displayValue($profile['source_name']); ?></div>
                    </div>
                    
                    <div class="info-item-new">
                        <div class="info-label-new">Source Alias/Nickname</div>
                        <div class="info-value-new"><?php echo displayValue($profile['source_nickname']); ?></div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="info-item-new">
                            <div class="info-label-new">Complete Address of Alleged Source</div>
                            <div class="info-value-new"><?php echo displayValue($profile['source_full_address']); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="info-item-new">
                            <div class="info-label-new">Other Types of Drugs Supplied by Source</div>
                            <div class="info-value-new"><?php echo displayValue($profile['source_other_drugs']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item-new">
                            <div class="info-label-new">Other Subject Known as Source</div>
                            <div class="info-value-new">
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
                </div>
                
                <?php if (!empty($profile['other_source_details'])): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="info-item-new">
                            <div class="info-label-new">Other Source Details</div>
                            <div class="info-value-new"><?php echo displayValue($profile['other_source_details']); ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="info-item-new">
                            <div class="info-label-new">Subgroup Name</div>
                            <div class="info-value-new"><?php echo displayValue($profile['subgroup_name']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item-new">
                            <div class="info-label-new">Specific Area of Responsibility (AOR)</div>
                            <div class="info-value-new"><?php echo displayValue($profile['specific_aor']); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="info-item-new">
                            <div class="info-label-new">Types of Drugs Pushed by Subject</div>
                            <div class="info-value-new">
                                <?php 
                                if (!empty($drugsPushedArray)) {
                                    foreach ($drugsPushedArray as $drug) {
                                        $drugClass = 'drug-tag';
                                        if (stripos($drug, 'marijuana') !== false) {
                                            $drugClass .= ' drug-tag-marijuana';
                                        } elseif (stripos($drug, 'shabu') !== false) {
                                            $drugClass .= ' drug-tag-shabu';
                                        } else {
                                            $drugClass .= ' drug-tag-other';
                                        }
                                        echo '<span class="' . $drugClass . '">' . $drug . '</span> ';
                                    }
                                    if (!empty($profile['other_drugs_pushed'])) {
                                        echo '<span class="drug-tag drug-tag-other">' . $profile['other_drugs_pushed'] . '</span>';
                                    }
                                } else {
                                    echo displayValue($profile['other_drugs_pushed']);
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="info-item-new">
                            <div class="info-label-new">Vehicles Used</div>
                            <div class="info-value-new"><?php echo displayValue($profile['vehicles_used']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-item-new">
                            <div class="info-label-new">Armaments</div>
                            <div class="info-value-new"><?php echo displayValue($profile['armaments']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-item-new">
                            <div class="info-label-new">Companions During Arrest</div>
                            <div class="info-value-new"><?php echo displayValue($profile['companions_arrest']); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- IV. Arrest Record -->
            <div class="section-card-new">
                <h4 class="section-title-new"><i class="fas fa-gavel"></i> IV. ARREST RECORD</h4>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item-new">
                            <div class="info-label-new">Previous Arrest Record</div>
                            <div class="info-value-new"><?php echo displayValue($profile['previous_arrest']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item-new">
                            <div class="info-label-new">Specific Charge</div>
                            <div class="info-value-new"><?php echo displayValue($profile['specific_charge']); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="info-item-new">
                            <div class="info-label-new">Date/Time of Arrest</div>
                            <div class="info-value-new"><?php echo !empty($profile['arrest_datetime']) ? date('F d, Y H:i', strtotime($profile['arrest_datetime'])) : 'N/A'; ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-item-new">
                            <div class="info-label-new">Place of Arrest</div>
                            <div class="info-value-new"><?php echo displayValue($profile['arrest_place']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-item-new">
                            <div class="info-label-new">Arresting Officer</div>
                            <div class="info-value-new"><?php echo displayValue($profile['arresting_officer']); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="info-item-new">
                            <div class="info-label-new">Unit/Office of Arresting Officer</div>
                            <div class="info-value-new"><?php echo displayValue($profile['arresting_unit']); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- V. Summary and Recommendations -->
            <div class="section-card-new">
                <h4 class="section-title-new"><i class="fas fa-file-alt"></i> V. SUMMARY AND RECOMMENDATIONS</h4>
                
                <div class="row">
                    <div class="col-12">
                        <div class="info-item-new">
                            <div class="info-label-new">Recruitment Summary</div>
                            <div class="info-value-new"><?php echo displayValue($profile['recruitment_summary']); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="info-item-new">
                            <div class="info-label-new">Modus Operandi</div>
                            <div class="info-value-new"><?php echo displayValue($profile['modus_operandi']); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="info-item-new">
                            <div class="info-label-new">Organizational Structure</div>
                            <div class="info-value-new"><?php echo displayValue($profile['organizational_structure']); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="info-item-new">
                            <div class="info-label-new">CI Matters</div>
                            <div class="info-value-new"><?php echo displayValue($profile['ci_matters']); ?></div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($profile['other_revelations'])): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="info-item-new">
                            <div class="info-label-new">Other Significant Revelations</div>
                            <div class="info-value-new"><?php echo displayValue($profile['other_revelations']); ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="info-item-new" style="background: #e8f4fd;">
                            <div class="info-label-new">Recommendation</div>
                            <div class="info-value-new" style="font-size: 18px; color: #0a2f4d;"><?php echo displayValue($profile['recommendation']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item-new" style="background: #e8f4fd;">
                            <div class="info-label-new">Profile Status</div>
                            <div class="info-value-new" style="font-size: 18px;">
                                <span class="badge badge-<?php echo $profile['status']; ?>">
                                    <?php echo strtoupper($profile['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Information -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <small class="text-muted">Created: <?php echo !empty($profile['created_at']) ? date('F d, Y h:i A', strtotime($profile['created_at'])) : 'N/A'; ?></small>
                </div>
                <div class="col-md-6 text-end">
                    <small class="text-muted">Last Updated: <?php echo !empty($profile['updated_at']) ? date('F d, Y h:i A', strtotime($profile['updated_at'])) : 'N/A'; ?></small>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <a href="edit_profile.php?id=<?php echo $profile['id']; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit Profile
            </a>
            <a href="profile_form.php" class="btn btn-success">
                <i class="fas fa-plus"></i> New Profile
            </a>
            <button onclick="window.print()" class="btn btn-info">
                <i class="fas fa-print"></i> Print Profile
            </button>
            <?php if ($_SESSION['role'] == 'admin'): ?>
            <button onclick="confirmDelete(<?php echo $profile['id']; ?>)" class="btn btn-danger">
                <i class="fas fa-trash"></i> Delete
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="footer">
        <div class="container">
            <small>Department of the Interior and Local Government | PHILIPPINE NATIONAL POLICE<br>
            BUKIDNON POLICE PROVINCIAL OFFICE | MANOLO FORTICH POLICE STATION<br>
            This document is CONFIDENTIAL and for official use only.</small>
        </div>
    </div>

    <script>
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this profile? This action cannot be undone.')) {
                window.location.href = 'delete_profile.php?id=' + id;
            }
        }
        
        // Add print functionality
        document.querySelector('button[onclick="window.print()"]').addEventListener('click', function() {
            window.print();
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>