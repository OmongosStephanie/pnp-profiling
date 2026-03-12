<?php
// profile_form.php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Begin transaction
        $db->beginTransaction();
        
      $query = "INSERT INTO biographical_profiles (
    full_name, alias, group_affiliation, position_roles, age, sex, dob, pob,
    educational_attainment, occupation, company_office, technical_skills,
    ethnic_group, languages, present_address, provincial_address,
    civil_status, citizenship, religion, height_cm, weight_kg, height_ft,
    eyes_color, hair_color, built, complexion, distinguishing_marks,
    previous_arrest, specific_charge, arrest_datetime, arrest_place,
    arresting_officer, arresting_unit, drugs_involved,
    
    -- NEW FIELDS
    source_relationship, source_address, source_name, source_nickname, 
    source_full_address, source_other_drugs, subgroup_name, specific_aor,
    other_source_name, other_source_alias, other_source_details, 
    drugs_pushed, other_drugs_pushed,
    -- END NEW FIELDS
    
    vehicles_used, armaments, companions_arrest, recruitment_summary, 
    modus_operandi, organizational_structure, ci_matters, other_revelations, 
    recommendation, created_by, status
) VALUES (
    :full_name, :alias, :group_affiliation, :position_roles, :age, :sex, :dob, :pob,
    :educational_attainment, :occupation, :company_office, :technical_skills,
    :ethnic_group, :languages, :present_address, :provincial_address,
    :civil_status, :citizenship, :religion, :height_cm, :weight_kg, :height_ft,
    :eyes_color, :hair_color, :built, :complexion, :distinguishing_marks,
    :previous_arrest, :specific_charge, :arrest_datetime, :arrest_place,
    :arresting_officer, :arresting_unit, :drugs_involved,
    
    -- NEW FIELDS (must match order above)
    :source_relationship, :source_address, :source_name, :source_nickname,
    :source_full_address, :source_other_drugs, :subgroup_name, :specific_aor,
    :other_source_name, :other_source_alias, :other_source_details,
    :drugs_pushed, :other_drugs_pushed,
    -- END NEW FIELDS
    
    :vehicles_used, :armaments, :companions_arrest, :recruitment_summary,
    :modus_operandi, :organizational_structure, :ci_matters, :other_revelations,
    :recommendation, :created_by, :status
)";
        
        $stmt = $db->prepare($query);
        
            // Prepare parameters
$params = [
    ':full_name' => $_POST['full_name'],
    ':alias' => $_POST['alias'],
    ':group_affiliation' => $_POST['group_affiliation'],
    ':position_roles' => $_POST['position_roles'],
    ':age' => $_POST['age'],
    ':sex' => $_POST['sex'],
    ':dob' => $_POST['dob'],
    ':pob' => $_POST['pob'],
    ':educational_attainment' => $_POST['educational_attainment'],
    ':occupation' => $_POST['occupation'],
    ':company_office' => $_POST['company_office'],
    ':technical_skills' => $_POST['technical_skills'],
    ':ethnic_group' => $_POST['ethnic_group'],
    ':languages' => $_POST['languages'],
    ':present_address' => $_POST['present_address'],
    ':provincial_address' => $_POST['provincial_address'],
    ':civil_status' => $_POST['civil_status'],
    ':citizenship' => $_POST['citizenship'],
    ':religion' => $_POST['religion'],
    ':height_cm' => $_POST['height_cm'] ?: null,
    ':weight_kg' => $_POST['weight_kg'] ?: null,
    ':height_ft' => $_POST['height_ft'],
    ':eyes_color' => $_POST['eyes_color'],
    ':hair_color' => $_POST['hair_color'],
    ':built' => $_POST['built'],
    ':complexion' => $_POST['complexion'],
    ':distinguishing_marks' => $_POST['distinguishing_marks'],
    ':previous_arrest' => $_POST['previous_arrest'],
    ':specific_charge' => $_POST['specific_charge'],
    ':arrest_datetime' => $_POST['arrest_datetime'] ?: null,
    ':arrest_place' => $_POST['arrest_place'],
    ':arresting_officer' => $_POST['arresting_officer'],
    ':arresting_unit' => $_POST['arresting_unit'],
    ':drugs_involved' => $_POST['drugs_involved'],
    
    // NEW FIELDS ADDED HERE
    ':source_relationship' => $_POST['source_relationship'] ?? null,
    ':source_address' => $_POST['source_address'] ?? null,
    ':source_name' => $_POST['source_name'] ?? null,
    ':source_nickname' => $_POST['source_nickname'] ?? null,
    ':source_full_address' => $_POST['source_full_address'] ?? null,
    ':source_other_drugs' => $_POST['source_other_drugs'] ?? null,
    ':subgroup_name' => $_POST['subgroup_name'] ?? null,
    ':specific_aor' => $_POST['specific_aor'] ?? null,
    ':other_source_name' => $_POST['other_source_name'] ?? null,
    ':other_source_alias' => $_POST['other_source_alias'] ?? null,
    ':other_source_details' => $_POST['other_source_details'] ?? null,
    ':drugs_pushed' => isset($_POST['drugs_pushed']) ? implode(', ', $_POST['drugs_pushed']) : null,
    ':other_drugs_pushed' => $_POST['other_drugs_pushed'] ?? null,
    // END OF NEW FIELDS
    
    ':vehicles_used' => $_POST['vehicles_used'],
    ':armaments' => $_POST['armaments'],
    ':companions_arrest' => $_POST['companions_arrest'],
    ':recruitment_summary' => $_POST['recruitment_summary'],
    ':modus_operandi' => $_POST['modus_operandi'],
    ':organizational_structure' => $_POST['organizational_structure'],
    ':ci_matters' => $_POST['ci_matters'],
    ':other_revelations' => $_POST['other_revelations'],
    ':recommendation' => $_POST['recommendation'],
    ':created_by' => $_SESSION['user_id'],
    ':status' => $_POST['status']
];
        
        $stmt->execute($params);
        
        $profile_id = $db->lastInsertId();
        
        // Insert siblings if any
        if (isset($_POST['sibling_name']) && is_array($_POST['sibling_name'])) {
            $sibling_query = "INSERT INTO siblings (profile_id, name, age, occupation, status, address) 
                              VALUES (:profile_id, :name, :age, :occupation, :status, :address)";
            $sibling_stmt = $db->prepare($sibling_query);
            
            for ($i = 0; $i < count($_POST['sibling_name']); $i++) {
                if (!empty($_POST['sibling_name'][$i])) {
                    $sibling_stmt->execute([
                        ':profile_id' => $profile_id,
                        ':name' => $_POST['sibling_name'][$i],
                        ':age' => $_POST['sibling_age'][$i] ?: null,
                        ':occupation' => $_POST['sibling_occupation'][$i],
                        ':status' => $_POST['sibling_status'][$i],
                        ':address' => $_POST['sibling_address'][$i]
                    ]);
                }
            }
        }
        
        $db->commit();
        $message = "Profile saved successfully!";
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Error saving profile: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biographical Profile Form</title>
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
        }
        
        .form-container {
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .form-section {
            background: #f8f9fa;
            border-left: 4px solid #0a2f4d;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 0 5px 5px 0;
        }
        
        .form-section h4 {
            color: #0a2f4d;
            margin-bottom: 20px;
            font-weight: bold;
            font-size: 18px;
            text-transform: uppercase;
        }
        
        .form-section h4 i {
            margin-right: 10px;
            color: #c9a959;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .form-control, .form-select {
            border: 1px solid #ced4da;
            border-radius: 3px;
            padding: 8px 12px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #0a2f4d;
            box-shadow: 0 0 0 0.2rem rgba(10,47,77,0.25);
        }
        
        .btn-pnp {
            background: #0a2f4d;
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 3px;
            font-weight: 600;
        }
        
        .btn-pnp:hover {
            background: #c9a959;
            color: #0a2f4d;
        }
        
        .btn-secondary {
            background: #6c757d;
            border: none;
            padding: 10px 30px;
        }
        
        .table-bordered {
            border: 1px solid #dee2e6;
        }
        
        .table-bordered th {
            background: #0a2f4d;
            color: white;
            font-size: 14px;
            font-weight: 600;
            text-align: center;
            vertical-align: middle;
        }
        
        .table-bordered td {
            vertical-align: middle;
        }
        
        .note {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 10px 15px;
            font-size: 13px;
            margin-top: 10px;
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
        
        /* Conditional dropdown styles */
        .position-container {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .position-container select,
        .position-container input {
            flex: 1;
        }
        
        .drug-type-badge {
            background: #c9a959;
            color: #0a2f4d;
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 12px;
            display: inline-block;
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
                    <a href="dashboard.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <div class="text-center mb-4">
                <h3 style="color: #0a2f4d; font-weight: bold;">BIOGRAPHICAL PROFILE FORM</h3>
                <p class="text-muted">All information is confidential and for official use only</p>
            </div>
            
            <form method="POST" action="" id="profileForm">
                <!-- I. Personal Data -->
                <div class="form-section">
                    <h4><i class="fas fa-user"></i> I. Personal Data</h4>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 200px;">Name:</th>
                                    <td>
                                        <input type="text" class="form-control" name="full_name" required 
                                               placeholder="Full Name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Alias:</th>
                                    <td>
                                        <input type="text" class="form-control" name="alias" 
                                               value="<?php echo isset($_POST['alias']) ? htmlspecialchars($_POST['alias']) : ''; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Name of Group/Gang Affiliation (if any):</th>
                                    <td>
                                        <input type="text" class="form-control" name="group_affiliation"
                                               value="<?php echo isset($_POST['group_affiliation']) ? htmlspecialchars($_POST['group_affiliation']) : ''; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Position (if any):</th>
                                    <td>
                                        <div class="position-container">
                                            <select class="form-select" id="drugType" onchange="togglePositionFields()">
                                                <option value="">Select Drug Type</option>
                                                <option value="marijuana" <?php echo (isset($_POST['drug_type']) && $_POST['drug_type'] == 'marijuana') ? 'selected' : ''; ?>>Marijuana</option>
                                                <option value="shabu" <?php echo (isset($_POST['drug_type']) && $_POST['drug_type'] == 'shabu') ? 'selected' : ''; ?>>Shabu</option>
                                                <option value="other" <?php echo (isset($_POST['drug_type']) && $_POST['drug_type'] == 'other') ? 'selected' : ''; ?>>Other Drugs</option>
                                            </select>
                                            
                                            <select class="form-select" id="drugRole" name="position_roles" style="display: none;">
                                                <option value="">Select Role</option>
                                                <option value="User">User</option>
                                                <option value="Pusher">Pusher</option>
                                                <option value="Runner">Runner</option>
                                            </select>
                                            
                                            <input type="text" class="form-control" id="otherPosition" name="position_roles" 
                                                   placeholder="Enter position" style="display: none;"
                                                   value="<?php echo isset($_POST['position_roles']) ? htmlspecialchars($_POST['position_roles']) : ''; ?>">
                                        </div>
                                        <small class="text-muted">Select drug type first to see relevant positions</small>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Age:</th>
                                    <td>
                                        <input type="number" class="form-control" name="age" required
                                               value="<?php echo isset($_POST['age']) ? htmlspecialchars($_POST['age']) : ''; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Sex:</th>
                                    <td>
                                        <select class="form-select" name="sex" required>
                                            <option value="">Select Sex</option>
                                            <option value="Male" <?php echo (isset($_POST['sex']) && $_POST['sex'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo (isset($_POST['sex']) && $_POST['sex'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="Other" <?php echo (isset($_POST['sex']) && $_POST['sex'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th>DOB:</th>
                                    <td>
                                        <input type="date" class="form-control" name="dob" required
                                               value="<?php echo isset($_POST['dob']) ? htmlspecialchars($_POST['dob']) : ''; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>POB:</th>
                                    <td>
                                        <input type="text" class="form-control" name="pob" required
                                               value="<?php echo isset($_POST['pob']) ? htmlspecialchars($_POST['pob']) : ''; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Educational Attainment:</th>
                                    <td>
                                        <select class="form-select" name="educational_attainment">
                                            <option value="">Select</option>
                                            <option value="Elementary Level" <?php echo (isset($_POST['educational_attainment']) && $_POST['educational_attainment'] == 'Elementary Level') ? 'selected' : ''; ?>>Elementary Level</option>
                                            <option value="Elementary Graduate" <?php echo (isset($_POST['educational_attainment']) && $_POST['educational_attainment'] == 'Elementary Graduate') ? 'selected' : ''; ?>>Elementary Graduate</option>
                                            <option value="High School Level" <?php echo (isset($_POST['educational_attainment']) && $_POST['educational_attainment'] == 'High School Level') ? 'selected' : ''; ?>>High School Level</option>
                                            <option value="High School Graduate" <?php echo (isset($_POST['educational_attainment']) && $_POST['educational_attainment'] == 'High School Graduate') ? 'selected' : ''; ?>>High School Graduate</option>
                                            <option value="College Level" <?php echo (isset($_POST['educational_attainment']) && $_POST['educational_attainment'] == 'College Level') ? 'selected' : ''; ?>>College Level</option>
                                            <option value="College Graduate" <?php echo (isset($_POST['educational_attainment']) && $_POST['educational_attainment'] == 'College Graduate') ? 'selected' : ''; ?>>College Graduate</option>
                                            <option value="Post Graduate" <?php echo (isset($_POST['educational_attainment']) && $_POST['educational_attainment'] == 'Post Graduate') ? 'selected' : ''; ?>>Post Graduate</option>
                                            <option value="Vocational" <?php echo (isset($_POST['educational_attainment']) && $_POST['educational_attainment'] == 'Vocational') ? 'selected' : ''; ?>>Vocational</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Occupation/Profession:</th>
                                    <td>
                                        <input type="text" class="form-control" name="occupation"
                                               value="<?php echo isset($_POST['occupation']) ? htmlspecialchars($_POST['occupation']) : ''; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Company/Office:</th>
                                    <td>
                                        <input type="text" class="form-control" name="company_office"
                                               value="<?php echo isset($_POST['company_office']) ? htmlspecialchars($_POST['company_office']) : ''; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Technical Skills:</th>
                                    <td>
                                        <input type="text" class="form-control" name="technical_skills"
                                               value="<?php echo isset($_POST['technical_skills']) ? htmlspecialchars($_POST['technical_skills']) : ''; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Ethnic Group:</th>
                                    <td>
                                        <input type="text" class="form-control" name="ethnic_group"
                                               value="<?php echo isset($_POST['ethnic_group']) ? htmlspecialchars($_POST['ethnic_group']) : ''; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Language/Dialect:</th>
                                    <td>
                                        <input type="text" class="form-control" name="languages"
                                               value="<?php echo isset($_POST['languages']) ? htmlspecialchars($_POST['languages']) : ''; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Present Address:</th>
                                    <td>
                                        <input type="text" class="form-control" name="present_address"
                                               value="<?php echo isset($_POST['present_address']) ? htmlspecialchars($_POST['present_address']) : ''; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Provincial Address:</th>
                                    <td>
                                        <input type="text" class="form-control" name="provincial_address"
                                               value="<?php echo isset($_POST['provincial_address']) ? htmlspecialchars($_POST['provincial_address']) : ''; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Civil Status:</th>
                                    <td>
                                        <select class="form-select" name="civil_status">
                                            <option value="">Select</option>
                                            <option value="Single" <?php echo (isset($_POST['civil_status']) && $_POST['civil_status'] == 'Single') ? 'selected' : ''; ?>>Single</option>
                                            <option value="Married" <?php echo (isset($_POST['civil_status']) && $_POST['civil_status'] == 'Married') ? 'selected' : ''; ?>>Married</option>
                                            <option value="Widowed" <?php echo (isset($_POST['civil_status']) && $_POST['civil_status'] == 'Widowed') ? 'selected' : ''; ?>>Widowed</option>
                                            <option value="Separated" <?php echo (isset($_POST['civil_status']) && $_POST['civil_status'] == 'Separated') ? 'selected' : ''; ?>>Separated</option>
                                            <option value="Divorced" <?php echo (isset($_POST['civil_status']) && $_POST['civil_status'] == 'Divorced') ? 'selected' : ''; ?>>Divorced</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Citizenship:</th>
                                    <td>
                                        <input type="text" class="form-control" name="citizenship"
                                               value="<?php echo isset($_POST['citizenship']) ? htmlspecialchars($_POST['citizenship']) : 'Filipino'; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Religion:</th>
                                    <td>
                                        <input type="text" class="form-control" name="religion"
                                               value="<?php echo isset($_POST['religion']) ? htmlspecialchars($_POST['religion']) : ''; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Height (in cm):</th>
                                    <td>
                                        <input type="number" step="0.01" class="form-control" name="height_cm"
                                               value="<?php echo isset($_POST['height_cm']) ? htmlspecialchars($_POST['height_cm']) : ''; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Height (ft/in):</th>
                                    <td>
                                        <input type="text" class="form-control" name="height_ft" placeholder="e.g., 5'5&quot;"
                                               value="<?php echo isset($_POST['height_ft']) ? htmlspecialchars($_POST['height_ft']) : ''; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Weight (in kg):</th>
                                    <td>
                                        <input type="number" step="0.01" class="form-control" name="weight_kg"
                                               value="<?php echo isset($_POST['weight_kg']) ? htmlspecialchars($_POST['weight_kg']) : ''; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Eyes Color:</th>
                                    <td>
                                        <input type="text" class="form-control" name="eyes_color"
                                               value="<?php echo isset($_POST['eyes_color']) ? htmlspecialchars($_POST['eyes_color']) : ''; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Hair Color:</th>
                                    <td>
                                        <input type="text" class="form-control" name="hair_color"
                                               value="<?php echo isset($_POST['hair_color']) ? htmlspecialchars($_POST['hair_color']) : ''; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Built:</th>
                                    <td>
                                        <select class="form-select" name="built">
                                            <option value="">Select Built</option>
                                            <option value="Small" <?php echo (isset($_POST['built']) && $_POST['built'] == 'Small') ? 'selected' : ''; ?>>Small (130-140 cm)</option>
                                            <option value="Medium" <?php echo (isset($_POST['built']) && $_POST['built'] == 'Medium') ? 'selected' : ''; ?>>Medium (140-150 cm)</option>
                                            <option value="Large" <?php echo (isset($_POST['built']) && $_POST['built'] == 'Large') ? 'selected' : ''; ?>>Large (150+ cm)</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Complexion:</th>
                                    <td>
                                        <input type="text" class="form-control" name="complexion"
                                               value="<?php echo isset($_POST['complexion']) ? htmlspecialchars($_POST['complexion']) : ''; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Distinguishing Marks/Tattoo:</th>
                                    <td>
                                        <textarea class="form-control" name="distinguishing_marks" rows="2"><?php echo isset($_POST['distinguishing_marks']) ? htmlspecialchars($_POST['distinguishing_marks']) : ''; ?></textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Previous Arrest Record:</th>
                                    <td>
                                        <textarea class="form-control" name="previous_arrest" rows="2"><?php echo isset($_POST['previous_arrest']) ? htmlspecialchars($_POST['previous_arrest']) : ''; ?></textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Specific Charge:</th>
                                    <td>
                                        <input type="text" class="form-control" name="specific_charge"
                                               value="<?php echo isset($_POST['specific_charge']) ? htmlspecialchars($_POST['specific_charge']) : ''; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Date/Time/Place of Arrest:</th>
                                    <td>
                                        <input type="datetime-local" class="form-control" name="arrest_datetime"
                                               value="<?php echo isset($_POST['arrest_datetime']) ? htmlspecialchars($_POST['arrest_datetime']) : ''; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Name of Arresting Officer:</th>
                                    <td>
                                        <input type="text" class="form-control" name="arresting_officer"
                                               value="<?php echo isset($_POST['arresting_officer']) ? htmlspecialchars($_POST['arresting_officer']) : ''; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Unit/Office of Arresting Officer:</th>
                                    <td>
                                        <input type="text" class="form-control" name="arresting_unit"
                                               value="<?php echo isset($_POST['arresting_unit']) ? htmlspecialchars($_POST['arresting_unit']) : ''; ?>">
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- II. Family Background -->
                <div class="form-section">
                    <h4><i class="fas fa-family"></i> II. Family Background</h4>
                    
                    <h5 class="mt-3">Parents:</h5>
                    <table class="table table-bordered">
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
                                <td>
                                    <input type="text" class="form-control" name="father_name"
                                           value="<?php echo isset($_POST['father_name']) ? htmlspecialchars($_POST['father_name']) : ''; ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="mother_name"
                                           value="<?php echo isset($_POST['mother_name']) ? htmlspecialchars($_POST['mother_name']) : ''; ?>">
                                </td>
                            </tr>
                            <tr>
                                <th>Known Address</th>
                                <td>
                                    <input type="text" class="form-control" name="father_address"
                                           value="<?php echo isset($_POST['father_address']) ? htmlspecialchars($_POST['father_address']) : ''; ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="mother_address"
                                           value="<?php echo isset($_POST['mother_address']) ? htmlspecialchars($_POST['mother_address']) : ''; ?>">
                                </td>
                            </tr>
                            <tr>
                                <th>DOB</th>
                                <td>
                                    <input type="date" class="form-control" name="father_dob"
                                           value="<?php echo isset($_POST['father_dob']) ? htmlspecialchars($_POST['father_dob']) : ''; ?>">
                                </td>
                                <td>
                                    <input type="date" class="form-control" name="mother_dob"
                                           value="<?php echo isset($_POST['mother_dob']) ? htmlspecialchars($_POST['mother_dob']) : ''; ?>">
                                </td>
                            </tr>
                            <tr>
                                <th>POB</th>
                                <td>
                                    <input type="text" class="form-control" name="father_pob"
                                           value="<?php echo isset($_POST['father_pob']) ? htmlspecialchars($_POST['father_pob']) : ''; ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="mother_pob"
                                           value="<?php echo isset($_POST['mother_pob']) ? htmlspecialchars($_POST['mother_pob']) : ''; ?>">
                                </td>
                            </tr>
                            <tr>
                                <th>Age</th>
                                <td>
                                    <input type="number" class="form-control" name="father_age"
                                           value="<?php echo isset($_POST['father_age']) ? htmlspecialchars($_POST['father_age']) : ''; ?>">
                                </td>
                                <td>
                                    <input type="number" class="form-control" name="mother_age"
                                           value="<?php echo isset($_POST['mother_age']) ? htmlspecialchars($_POST['mother_age']) : ''; ?>">
                                </td>
                            </tr>
                            <tr>
                                <th>Occupation</th>
                                <td>
                                    <input type="text" class="form-control" name="father_occupation"
                                           value="<?php echo isset($_POST['father_occupation']) ? htmlspecialchars($_POST['father_occupation']) : ''; ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="mother_occupation"
                                           value="<?php echo isset($_POST['mother_occupation']) ? htmlspecialchars($_POST['mother_occupation']) : ''; ?>">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h5 class="mt-4">Spouse:</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Age</th>
                                <th>Occupation</th>
                                <th>Provincial Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <input type="text" class="form-control" name="spouse_name"
                                           value="<?php echo isset($_POST['spouse_name']) ? htmlspecialchars($_POST['spouse_name']) : ''; ?>">
                                </td>
                                <td>
                                    <input type="number" class="form-control" name="spouse_age"
                                           value="<?php echo isset($_POST['spouse_age']) ? htmlspecialchars($_POST['spouse_age']) : ''; ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="spouse_occupation"
                                           value="<?php echo isset($_POST['spouse_occupation']) ? htmlspecialchars($_POST['spouse_occupation']) : ''; ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="spouse_address"
                                           value="<?php echo isset($_POST['spouse_address']) ? htmlspecialchars($_POST['spouse_address']) : ''; ?>">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h5 class="mt-4">Siblings:</h5>
                    <div id="siblingsContainer">
                        <table class="table table-bordered" id="siblingsTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Age</th>
                                    <th>Occupation</th>
                                    <th>Status</th>
                                    <th>Address</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <input type="text" class="form-control" name="sibling_name[]">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" name="sibling_age[]">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" name="sibling_occupation[]">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" name="sibling_status[]">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" name="sibling_address[]">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-sm btn-success" onclick="addSiblingRow()">
                            <i class="fas fa-plus"></i> Add Sibling
                        </button>
                    </div>
                </div>

                <!-- III. Tactical Information -->
                <!-- III. Tactical Information -->
<div class="form-section">
    <h4><i class="fas fa-info-circle"></i> III. Tactical Information</h4>
    
    <table class="table table-bordered">
        <tr>
            <th style="width: 250px;">Drugs Involved:</th>
            <td>
                <input type="text" class="form-control" name="drugs_involved" id="drugs_involved"
                       value="<?php echo isset($_POST['drugs_involved']) ? htmlspecialchars($_POST['drugs_involved']) : ''; ?>">
                <small class="text-muted">Separate multiple drugs with commas</small>
            </td>
        </tr>
        
        <!-- New Fields for Drug Source Information -->
        <tr>
            <th>Relationship/Address of Source:</th>
            <td>
                <div class="row mb-2">
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="source_relationship" 
                               placeholder="Relationship to source"
                               value="<?php echo isset($_POST['source_relationship']) ? htmlspecialchars($_POST['source_relationship']) : ''; ?>">
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="source_address" 
                               placeholder="Address of source"
                               value="<?php echo isset($_POST['source_address']) ? htmlspecialchars($_POST['source_address']) : ''; ?>">
                    </div>
                </div>
                <small class="text-muted">e.g., Friend, Relative, Acquaintance / Complete address of drug source</small>
            </td>
        </tr>
        
        <tr>
            <th>Name/Source of Drugs Involved:</th>
            <td>
                <div class="row mb-2">
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="source_name" 
                               placeholder="Name of source/person"
                               value="<?php echo isset($_POST['source_name']) ? htmlspecialchars($_POST['source_name']) : ''; ?>">
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="source_nickname" 
                               placeholder="Alias/Nickname"
                               value="<?php echo isset($_POST['source_nickname']) ? htmlspecialchars($_POST['source_nickname']) : ''; ?>">
                    </div>
                </div>
                <small class="text-muted">Full name and alias of the person supplying drugs</small>
            </td>
        </tr>
        
        <tr>
            <th>Address of Alleged Source:</th>
            <td>
                <textarea class="form-control" name="source_full_address" rows="2"><?php echo isset($_POST['source_full_address']) ? htmlspecialchars($_POST['source_full_address']) : ''; ?></textarea>
                <small class="text-muted">Complete address including barangay, city, province</small>
            </td>
        </tr>
        
        <tr>
            <th>Other Types of Drugs Supplied by Source:</th>
            <td>
                <input type="text" class="form-control" name="source_other_drugs" 
                       placeholder="e.g., Shabu, Marijuana, Ecstasy, etc."
                       value="<?php echo isset($_POST['source_other_drugs']) ? htmlspecialchars($_POST['source_other_drugs']) : ''; ?>">
                <small class="text-muted">Separate multiple drugs with commas</small>
            </td>
        </tr>
        
        <tr>
            <th>Subgroups and Specific AOR:</th>
            <td>
                <div class="row mb-2">
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="subgroup_name" 
                               placeholder="Subgroup name"
                               value="<?php echo isset($_POST['subgroup_name']) ? htmlspecialchars($_POST['subgroup_name']) : ''; ?>">
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="specific_aor" 
                               placeholder="Area of Responsibility (AOR)"
                               value="<?php echo isset($_POST['specific_aor']) ? htmlspecialchars($_POST['specific_aor']) : ''; ?>">
                    </div>
                </div>
                <small class="text-muted">Specific group/subgroup and their area of responsibility</small>
            </td>
        </tr>
        
        <tr>
            <th>Other Subject Known as Source:</th>
            <td>
                <div class="row mb-2">
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="other_source_name" 
                               placeholder="Name of other source"
                               value="<?php echo isset($_POST['other_source_name']) ? htmlspecialchars($_POST['other_source_name']) : ''; ?>">
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="other_source_alias" 
                               placeholder="Alias"
                               value="<?php echo isset($_POST['other_source_alias']) ? htmlspecialchars($_POST['other_source_alias']) : ''; ?>">
                    </div>
                </div>
                <textarea class="form-control mt-2" name="other_source_details" rows="2" 
                          placeholder="Additional details about other source"><?php echo isset($_POST['other_source_details']) ? htmlspecialchars($_POST['other_source_details']) : ''; ?></textarea>
                <small class="text-muted">Other individuals known as drug sources</small>
            </td>
        </tr>
        
        <tr>
            <th>Types of Drugs Pushed by Subject:</th>
            <td>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="drugs_pushed[]" value="Shabu" 
                                   <?php echo (isset($_POST['drugs_pushed']) && in_array('Shabu', $_POST['drugs_pushed'])) ? 'checked' : ''; ?>>
                            <label class="form-check-label">Shabu</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="drugs_pushed[]" value="Marijuana"
                                   <?php echo (isset($_POST['drugs_pushed']) && in_array('Marijuana', $_POST['drugs_pushed'])) ? 'checked' : ''; ?>>
                            <label class="form-check-label">Marijuana</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="drugs_pushed[]" value="Ecstasy"
                                   <?php echo (isset($_POST['drugs_pushed']) && in_array('Ecstasy', $_POST['drugs_pushed'])) ? 'checked' : ''; ?>>
                            <label class="form-check-label">Ecstasy</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="drugs_pushed[]" value="Cocaine"
                                   <?php echo (isset($_POST['drugs_pushed']) && in_array('Cocaine', $_POST['drugs_pushed'])) ? 'checked' : ''; ?>>
                            <label class="form-check-label">Cocaine</label>
                        </div>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-12">
                        <input type="text" class="form-control" name="other_drugs_pushed" 
                               placeholder="Other drugs not listed above"
                               value="<?php echo isset($_POST['other_drugs_pushed']) ? htmlspecialchars($_POST['other_drugs_pushed']) : ''; ?>">
                    </div>
                </div>
                <small class="text-muted">Check all that apply or specify other drugs</small>
            </td>
        </tr>
        
        <tr>
            <th>Vehicles Used:</th>
            <td>
                <input type="text" class="form-control" name="vehicles_used"
                       placeholder="e.g., Motorcycle, Toyota Vios (ABC-123)"
                       value="<?php echo isset($_POST['vehicles_used']) ? htmlspecialchars($_POST['vehicles_used']) : ''; ?>">
            </td>
        </tr>
        
        <tr>
            <th>Armaments:</th>
            <td>
                <input type="text" class="form-control" name="armaments"
                       placeholder="e.g., .45 caliber pistol, Revolver"
                       value="<?php echo isset($_POST['armaments']) ? htmlspecialchars($_POST['armaments']) : ''; ?>">
            </td>
        </tr>
        
        <tr>
            <th>Companion/s During Arrest:</th>
            <td>
                <textarea class="form-control" name="companions_arrest" rows="2" 
                          placeholder="Names of companions during arrest"><?php echo isset($_POST['companions_arrest']) ? htmlspecialchars($_POST['companions_arrest']) : ''; ?></textarea>
            </td>
        </tr>
    </table>
</div>

                <!-- IV. Recruitment Summary -->
                <div class="form-section">
                    <h4><i class="fas fa-user-plus"></i> IV. Detailed Summary on Recruitment</h4>
                    <div class="mb-3">
                        <label class="form-label">Recruitment Details:</label>
                        <textarea class="form-control" name="recruitment_summary" rows="4"><?php echo isset($_POST['recruitment_summary']) ? htmlspecialchars($_POST['recruitment_summary']) : ''; ?></textarea>
                        <small class="text-muted">Describe how the suspect was recruited as user/pusher/drugs influence by friends</small>
                    </div>
                </div>

                <!-- V. Drug Operations -->
                <div class="form-section">
                    <h4><i class="fas fa-exchange-alt"></i> V. Summary of Pushing/Supplying/Acquiring Drugs</h4>
                    <div class="mb-3">
                        <label class="form-label">Modus Operandi:</label>
                        <textarea class="form-control" name="modus_operandi" rows="4"><?php echo isset($_POST['modus_operandi']) ? htmlspecialchars($_POST['modus_operandi']) : ''; ?></textarea>
                        <small class="text-muted">Indicate the Modus Operandi, Sale/Distribution and Transportation of Dangerous Drugs</small>
                    </div>
                </div>

                <!-- VI. Organizational Structure -->
                <div class="form-section">
                    <h4><i class="fas fa-sitemap"></i> VI. Organizational Structure of the Group (if any)</h4>
                    <div class="mb-3">
                        <label class="form-label">Structure Details:</label>
                        <textarea class="form-control" name="organizational_structure" rows="3"><?php echo isset($_POST['organizational_structure']) ? htmlspecialchars($_POST['organizational_structure']) : 'NO ORGANIZATIONAL GROUP'; ?></textarea>
                        <small class="text-muted">Indicate the whole membership of the group</small>
                    </div>
                </div>

                <!-- VII. CI Matters -->
                <div class="form-section">
                    <h4><i class="fas fa-user-secret"></i> VII. CI Matters</h4>
                    <div class="mb-3">
                        <label class="form-label">CI Information:</label>
                        <textarea class="form-control" name="ci_matters" rows="3"><?php echo isset($_POST['ci_matters']) ? htmlspecialchars($_POST['ci_matters']) : 'NONE'; ?></textarea>
                        <small class="text-muted">AFP/PNP Government Officer's Instrument</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Other Significant Revelations:</label>
                        <textarea class="form-control" name="other_revelations" rows="3"><?php echo isset($_POST['other_revelations']) ? htmlspecialchars($_POST['other_revelations']) : ''; ?></textarea>
                    </div>
                </div>

                <!-- VIII. Recommendation -->
                <div class="form-section">
                    <h4><i class="fas fa-check-circle"></i> VIII. Recommendation</h4>
                    <div class="mb-3">
                        <select class="form-select" name="recommendation">
                            <option value="">Select Recommendation</option>
                            <option value="For Filing" <?php echo (isset($_POST['recommendation']) && $_POST['recommendation'] == 'For Filing') ? 'selected' : ''; ?>>For Filing</option>
                            <option value="For Investigation" <?php echo (isset($_POST['recommendation']) && $_POST['recommendation'] == 'For Investigation') ? 'selected' : ''; ?>>For Investigation</option>
                            <option value="For Delisting" <?php echo (isset($_POST['recommendation']) && $_POST['recommendation'] == 'For Delisting') ? 'selected' : ''; ?>>For Delisting</option>
                            <option value="For Prosecution" <?php echo (isset($_POST['recommendation']) && $_POST['recommendation'] == 'For Prosecution') ? 'selected' : ''; ?>>For Prosecution</option>
                            <option value="Closed" <?php echo (isset($_POST['recommendation']) && $_POST['recommendation'] == 'Closed') ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>
                </div>

                <!-- Status -->
                <div class="form-section">
                    <h4><i class="fas fa-tag"></i> Profile Status</h4>
                    <div class="mb-3">
                        <select class="form-select" name="status">
                            <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="archived" <?php echo (isset($_POST['status']) && $_POST['status'] == 'archived') ? 'selected' : ''; ?>>Archived</option>
                            <option value="delisted" <?php echo (isset($_POST['status']) && $_POST['status'] == 'delisted') ? 'selected' : ''; ?>>Delisted</option>
                        </select>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-pnp">
                        <i class="fas fa-save"></i> Save Profile
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="footer">
        <div class="container">
            <small>Department of the Interior and Local Government | PHILIPPINE NATIONAL POLICE<br>
            BUKIDNON POLICE PROVINCIAL OFFICE | MANOLO FORTICH POLICE STATION<br>
            All data is confidential and for official use only.</small>
        </div>
    </div>

    <script>
        // Function to toggle position fields based on drug type selection
        function togglePositionFields() {
            const drugType = document.getElementById('drugType').value;
            const drugRole = document.getElementById('drugRole');
            const otherPosition = document.getElementById('otherPosition');
            
            // Hide both initially
            drugRole.style.display = 'none';
            otherPosition.style.display = 'none';
            
            if (drugType === 'marijuana' || drugType === 'shabu') {
                // Show role dropdown for marijuana and shabu
                drugRole.style.display = 'block';
                otherPosition.style.display = 'none';
                
                // Update dropdown label based on drug type
                const options = drugRole.options;
                for (let i = 0; i < options.length; i++) {
                    if (options[i].value) {
                        options[i].text = options[i].value + ' (' + drugType.toUpperCase() + ')';
                    }
                }
            } else if (drugType === 'other') {
                // Show text input for other drugs
                drugRole.style.display = 'none';
                otherPosition.style.display = 'block';
            }
        }
        
        // Function to add sibling row
        function addSiblingRow() {
            const table = document.getElementById('siblingsTable').getElementsByTagName('tbody')[0];
            const newRow = table.insertRow();
            
            const cells = [];
            for (let i = 0; i < 6; i++) {
                cells.push(newRow.insertCell(i));
            }
            
            cells[0].innerHTML = '<input type="text" class="form-control" name="sibling_name[]">';
            cells[1].innerHTML = '<input type="number" class="form-control" name="sibling_age[]">';
            cells[2].innerHTML = '<input type="text" class="form-control" name="sibling_occupation[]">';
            cells[3].innerHTML = '<input type="text" class="form-control" name="sibling_status[]">';
            cells[4].innerHTML = '<input type="text" class="form-control" name="sibling_address[]">';
            cells[5].innerHTML = '<button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)"><i class="fas fa-trash"></i></button>';
        }
        
        // Function to remove sibling row
        function removeRow(button) {
            const row = button.closest('tr');
            const tableBody = row.parentNode;
            
            // Don't remove if it's the last row
            if (tableBody.rows.length > 1) {
                row.remove();
            } else {
                alert('At least one sibling row must remain. Clear the fields instead.');
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            togglePositionFields();
            
            // Auto-calculate age from DOB
            const dobInput = document.querySelector('input[name="dob"]');
            const ageInput = document.querySelector('input[name="age"]');
            
            if (dobInput) {
                dobInput.addEventListener('change', function() {
                    if (this.value) {
                        const birthDate = new Date(this.value);
                        const today = new Date();
                        let age = today.getFullYear() - birthDate.getFullYear();
                        const monthDiff = today.getMonth() - birthDate.getMonth();
                        
                        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                            age--;
                        }
                        
                        ageInput.value = age;
                    }
                });
            }
            
            // Auto-populate drugs_involved based on drug type selection
            const drugTypeSelect = document.getElementById('drugType');
            const drugsInvolvedInput = document.getElementById('drugs_involved');
            
            if (drugTypeSelect && drugsInvolvedInput) {
                drugTypeSelect.addEventListener('change', function() {
                    if (this.value && this.value !== 'other' && !drugsInvolvedInput.value) {
                        drugsInvolvedInput.value = this.value.charAt(0).toUpperCase() + this.value.slice(1);
                    }
                });
            }
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>