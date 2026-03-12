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

// Get creator info
$query = "SELECT full_name, rank FROM users WHERE id = :created_by";
$stmt = $db->prepare($query);
$stmt->bindParam(':created_by', $profile['created_by']);
$stmt->execute();
$creator = $stmt->fetch(PDO::FETCH_ASSOC);

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
                        <?php echo strtoupper($profile['status']); ?>
                    </span>
                    <p class="mt-4 mb-0"><small>Profile ID: #<?php echo str_pad($profile['id'], 6, '0', STR_PAD_LEFT); ?></small></p>
                    <p class="mb-0"><small>Created: <?php echo date('F d, Y', strtotime($profile['created_at'])); ?></small></p>
                    <p class="mb-0"><small>By: <?php echo displayValue($creator['rank'] . ' ' . $creator['full_name']); ?></small></p>
                </div>
            </div>
        </div>

        <!-- I. Personal Data -->
        <div class="section-card">
            <h4 class="section-title"><i class="fas fa-user"></i> I. PERSONAL DATA</h4>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?php echo displayValue($profile['full_name']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Alias</div>
                    <div class="info-value"><?php echo displayValue($profile['alias']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Age / Sex</div>
                    <div class="info-value"><?php echo $profile['age']; ?> / <?php echo $profile['sex']; ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Date of Birth</div>
                    <div class="info-value"><?php echo date('F d, Y', strtotime($profile['dob'])); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Place of Birth</div>
                    <div class="info-value"><?php echo displayValue($profile['pob']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Civil Status</div>
                    <div class="info-value"><?php echo displayValue($profile['civil_status']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Citizenship</div>
                    <div class="info-value"><?php echo displayValue($profile['citizenship']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Religion</div>
                    <div class="info-value"><?php echo displayValue($profile['religion']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Educational Attainment</div>
                    <div class="info-value"><?php echo displayValue($profile['educational_attainment']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Occupation</div>
                    <div class="info-value"><?php echo displayValue($profile['occupation']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Company/Office</div>
                    <div class="info-value"><?php echo displayValue($profile['company_office']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Technical Skills</div>
                    <div class="info-value"><?php echo displayValue($profile['technical_skills']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Ethnic Group</div>
                    <div class="info-value"><?php echo displayValue($profile['ethnic_group']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Languages/Dialects</div>
                    <div class="info-value"><?php echo displayValue($profile['languages']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Present Address</div>
                    <div class="info-value"><?php echo displayValue($profile['present_address']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Provincial Address</div>
                    <div class="info-value"><?php echo displayValue($profile['provincial_address']); ?></div>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-md-3">
                    <div class="info-item">
                        <div class="info-label">Height (cm)</div>
                        <div class="info-value"><?php echo displayValue($profile['height_cm']); ?> cm</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-item">
                        <div class="info-label">Height (ft/in)</div>
                        <div class="info-value"><?php echo displayValue($profile['height_ft']); ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-item">
                        <div class="info-label">Weight (kg)</div>
                        <div class="info-value"><?php echo displayValue($profile['weight_kg']); ?> kg</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-item">
                        <div class="info-label">Built</div>
                        <div class="info-value"><?php echo displayValue($profile['built']); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-md-4">
                    <div class="info-item">
                        <div class="info-label">Eyes Color</div>
                        <div class="info-value"><?php echo displayValue($profile['eyes_color']); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-item">
                        <div class="info-label">Hair Color</div>
                        <div class="info-value"><?php echo displayValue($profile['hair_color']); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-item">
                        <div class="info-label">Complexion</div>
                        <div class="info-value"><?php echo displayValue($profile['complexion']); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <div class="info-item">
                        <div class="info-label">Distinguishing Marks/Tattoo</div>
                        <div class="info-value"><?php echo displayValue($profile['distinguishing_marks']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- II. Family Background -->
        <div class="section-card">
            <h4 class="section-title"><i class="fas fa-family"></i> II. FAMILY BACKGROUND</h4>
            
            <h5 class="mt-3 mb-3" style="color: #0a2f4d;">Parents</h5>
            <table class="table table-bordered table-custom">
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
            <table class="table table-bordered table-custom">
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
                <table class="table table-bordered table-custom">
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
        <div class="section-card">
            <h4 class="section-title"><i class="fas fa-info-circle"></i> III. TACTICAL INFORMATION</h4>
            
            <div class="tactical-highlight">
                <strong>Primary Drugs Involved:</strong>
                <?php 
                $drugs = explode(',', $profile['drugs_involved']);
                foreach ($drugs as $drug): 
                    $drug = trim($drug);
                    $drugClass = 'drug-tag';
                    if (stripos($drug, 'marijuana') !== false) {
                        $drugClass .= ' drug-tag-marijuana';
                    } elseif (stripos($drug, 'shabu') !== false) {
                        $drugClass .= ' drug-tag-shabu';
                    } else {
                        $drugClass .= ' drug-tag-other';
                    }
                ?>
                    <span class="<?php echo $drugClass; ?>"><?php echo $drug; ?></span>
                <?php endforeach; ?>
            </div>
            
            <div class="info-grid mt-3">
                <!-- Drug Source Information -->
                <div class="info-item">
                    <div class="info-label">Relationship to Source</div>
                    <div class="info-value"><?php echo displayValue($profile['source_relationship']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Source Address</div>
                    <div class="info-value"><?php echo displayValue($profile['source_address']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Source Name</div>
                    <div class="info-value"><?php echo displayValue($profile['source_name']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Source Alias/Nickname</div>
                    <div class="info-value"><?php echo displayValue($profile['source_nickname']); ?></div>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <div class="info-item">
                        <div class="info-label">Complete Address of Alleged Source</div>
                        <div class="info-value"><?php echo displayValue($profile['source_full_address']); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="info-item">
                        <div class="info-label">Other Types of Drugs Supplied by Source</div>
                        <div class="info-value"><?php echo displayValue($profile['source_other_drugs']); ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item">
                        <div class="info-label">Other Subject Known as Source</div>
                        <div class="info-value">
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
                    <div class="info-item">
                        <div class="info-label">Other Source Details</div>
                        <div class="info-value"><?php echo displayValue($profile['other_source_details']); ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="info-item">
                        <div class="info-label">Subgroup Name</div>
                        <div class="info-value"><?php echo displayValue($profile['subgroup_name']); ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item">
                        <div class="info-label">Specific Area of Responsibility (AOR)</div>
                        <div class="info-value"><?php echo displayValue($profile['specific_aor']); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <div class="info-item">
                        <div class="info-label">Types of Drugs Pushed by Subject</div>
                        <div class="info-value">
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
                    <div class="info-item">
                        <div class="info-label">Vehicles Used</div>
                        <div class="info-value"><?php echo displayValue($profile['vehicles_used']); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-item">
                        <div class="info-label">Armaments</div>
                        <div class="info-value"><?php echo displayValue($profile['armaments']); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-item">
                        <div class="info-label">Companions During Arrest</div>
                        <div class="info-value"><?php echo displayValue($profile['companions_arrest']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- IV. Arrest Record -->
        <div class="section-card">
            <h4 class="section-title"><i class="fas fa-gavel"></i> ARREST RECORD</h4>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="info-item">
                        <div class="info-label">Previous Arrest Record</div>
                        <div class="info-value"><?php echo displayValue($profile['previous_arrest']); ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item">
                        <div class="info-label">Specific Charge</div>
                        <div class="info-value"><?php echo displayValue($profile['specific_charge']); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-md-4">
                    <div class="info-item">
                        <div class="info-label">Date/Time of Arrest</div>
                        <div class="info-value"><?php echo !empty($profile['arrest_datetime']) ? date('F d, Y H:i', strtotime($profile['arrest_datetime'])) : 'N/A'; ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-item">
                        <div class="info-label">Place of Arrest</div>
                        <div class="info-value"><?php echo displayValue($profile['arrest_place']); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-item">
                        <div class="info-label">Arresting Officer</div>
                        <div class="info-value"><?php echo displayValue($profile['arresting_officer']); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <div class="info-item">
                        <div class="info-label">Unit/Office of Arresting Officer</div>
                        <div class="info-value"><?php echo displayValue($profile['arresting_unit']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- V. Summary and Recommendations -->
        <div class="section-card">
            <h4 class="section-title"><i class="fas fa-file-alt"></i> SUMMARY AND RECOMMENDATIONS</h4>
            
            <div class="row">
                <div class="col-12">
                    <div class="info-item">
                        <div class="info-label">Recruitment Summary</div>
                        <div class="info-value"><?php echo displayValue($profile['recruitment_summary']); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <div class="info-item">
                        <div class="info-label">Modus Operandi</div>
                        <div class="info-value"><?php echo displayValue($profile['modus_operandi']); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <div class="info-item">
                        <div class="info-label">Organizational Structure</div>
                        <div class="info-value"><?php echo displayValue($profile['organizational_structure']); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <div class="info-item">
                        <div class="info-label">CI Matters</div>
                        <div class="info-value"><?php echo displayValue($profile['ci_matters']); ?></div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($profile['other_revelations'])): ?>
            <div class="row mt-3">
                <div class="col-12">
                    <div class="info-item">
                        <div class="info-label">Other Significant Revelations</div>
                        <div class="info-value"><?php echo displayValue($profile['other_revelations']); ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="info-item" style="background: #e8f4fd;">
                        <div class="info-label">Recommendation</div>
                        <div class="info-value" style="font-size: 18px; color: #0a2f4d;"><?php echo displayValue($profile['recommendation']); ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item" style="background: #e8f4fd;">
                        <div class="info-label">Profile Status</div>
                        <div class="info-value" style="font-size: 18px;">
                            <span class="badge-status badge-<?php echo $profile['status']; ?>" style="position: relative; top: 0; right: 0;">
                                <?php echo strtoupper($profile['status']); ?>
                            </span>
                        </div>
                    </div>
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
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>