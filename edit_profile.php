<?php
// edit_profile.php
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
$query = "SELECT * FROM siblings WHERE profile_id = :profile_id ORDER BY id";
$stmt = $db->prepare($query);
$stmt->bindParam(':profile_id', $id);
$stmt->execute();
$siblings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Begin transaction
        $db->beginTransaction();
        
        // Update main profile
        $query = "UPDATE biographical_profiles SET
            full_name = :full_name,
            alias = :alias,
            group_affiliation = :group_affiliation,
            position_roles = :position_roles,
            age = :age,
            sex = :sex,
            dob = :dob,
            pob = :pob,
            educational_attainment = :educational_attainment,
            occupation = :occupation,
            company_office = :company_office,
            technical_skills = :technical_skills,
            ethnic_group = :ethnic_group,
            languages = :languages,
            present_address = :present_address,
            provincial_address = :provincial_address,
            civil_status = :civil_status,
            citizenship = :citizenship,
            religion = :religion,
            height_cm = :height_cm,
            weight_kg = :weight_kg,
            height_ft = :height_ft,
            eyes_color = :eyes_color,
            hair_color = :hair_color,
            built = :built,
            complexion = :complexion,
            distinguishing_marks = :distinguishing_marks,
            previous_arrest = :previous_arrest,
            specific_charge = :specific_charge,
            arrest_datetime = :arrest_datetime,
            arrest_place = :arrest_place,
            arresting_officer = :arresting_officer,
            arresting_unit = :arresting_unit,
            drugs_involved = :drugs_involved,
            source_relationship = :source_relationship,
            source_address = :source_address,
            source_name = :source_name,
            source_nickname = :source_nickname,
            source_full_address = :source_full_address,
            source_other_drugs = :source_other_drugs,
            subgroup_name = :subgroup_name,
            specific_aor = :specific_aor,
            other_source_name = :other_source_name,
            other_source_alias = :other_source_alias,
            other_source_details = :other_source_details,
            drugs_pushed = :drugs_pushed,
            other_drugs_pushed = :other_drugs_pushed,
            vehicles_used = :vehicles_used,
            armaments = :armaments,
            companions_arrest = :companions_arrest,
            recruitment_summary = :recruitment_summary,
            modus_operandi = :modus_operandi,
            organizational_structure = :organizational_structure,
            ci_matters = :ci_matters,
            other_revelations = :other_revelations,
            recommendation = :recommendation,
            status = :status
            WHERE id = :id";
        
        $stmt = $db->prepare($query);
        
        // Prepare parameters
        $params = [
            ':id' => $id,
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
            ':source_relationship' => $_POST['source_relationship'],
            ':source_address' => $_POST['source_address'],
            ':source_name' => $_POST['source_name'],
            ':source_nickname' => $_POST['source_nickname'],
            ':source_full_address' => $_POST['source_full_address'],
            ':source_other_drugs' => $_POST['source_other_drugs'],
            ':subgroup_name' => $_POST['subgroup_name'],
            ':specific_aor' => $_POST['specific_aor'],
            ':other_source_name' => $_POST['other_source_name'],
            ':other_source_alias' => $_POST['other_source_alias'],
            ':other_source_details' => $_POST['other_source_details'],
            ':drugs_pushed' => isset($_POST['drugs_pushed']) ? implode(', ', $_POST['drugs_pushed']) : null,
            ':other_drugs_pushed' => $_POST['other_drugs_pushed'],
            ':vehicles_used' => $_POST['vehicles_used'],
            ':armaments' => $_POST['armaments'],
            ':companions_arrest' => $_POST['companions_arrest'],
            ':recruitment_summary' => $_POST['recruitment_summary'],
            ':modus_operandi' => $_POST['modus_operandi'],
            ':organizational_structure' => $_POST['organizational_structure'],
            ':ci_matters' => $_POST['ci_matters'],
            ':other_revelations' => $_POST['other_revelations'],
            ':recommendation' => $_POST['recommendation'],
            ':status' => $_POST['status']
        ];
        
        $stmt->execute($params);
        
        // Delete existing siblings
        $query = "DELETE FROM siblings WHERE profile_id = :profile_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':profile_id', $id);
        $stmt->execute();
        
        // Insert updated siblings
        if (isset($_POST['sibling_name']) && is_array($_POST['sibling_name'])) {
            $sibling_query = "INSERT INTO siblings (profile_id, name, age, occupation, status, address) 
                              VALUES (:profile_id, :name, :age, :occupation, :status, :address)";
            $sibling_stmt = $db->prepare($sibling_query);
            
            for ($i = 0; $i < count($_POST['sibling_name']); $i++) {
                if (!empty($_POST['sibling_name'][$i])) {
                    $sibling_stmt->execute([
                        ':profile_id' => $id,
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
        $message = "Profile updated successfully!";
        
        // Refresh profile data
        $stmt = $db->prepare("SELECT * FROM biographical_profiles WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Refresh siblings
        $stmt = $db->prepare("SELECT * FROM siblings WHERE profile_id = :profile_id");
        $stmt->bindParam(':profile_id', $id);
        $stmt->execute();
        $siblings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Error updating profile: " . $e->getMessage();
    }
}

// Parse drugs_pushed for checkbox checking
$drugsPushedArray = !empty($profile['drugs_pushed']) ? explode(', ', $profile['drugs_pushed']) : [];

// Function to check if a drug is in the pushed list
function isDrugPushed($drug, $drugsPushedArray) {
    return in_array($drug, $drugsPushedArray);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - PNP Biographical Profiling System</title>
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
        
        .btn-warning {
            background: #ffc107;
            border: none;
            padding: 10px 30px;
            color: #333;
        }
        
        .btn-danger {
            background: #dc3545;
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
        
        .action-buttons {
            text-align: center;
            margin-top: 30px;
        }
        
        .action-buttons .btn {
            margin: 0 5px;
        }
        
        .profile-id-badge {
            background: #0a2f4d;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 14px;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .drug-checkbox-group {
            border: 1px solid #dee2e6;
            padding: 15px;
            border-radius: 5px;
            background: white;
        }
        
        .form-check-inline {
            margin-right: 20px;
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
                <span class="profile-id-badge">
                    <i class="fas fa-id-card"></i> Editing Profile ID: #<?php echo str_pad($profile['id'], 6, '0', STR_PAD_LEFT); ?>
                </span>
                <h3 style="color: #0a2f4d; font-weight: bold;">EDIT BIOGRAPHICAL PROFILE</h3>
                <p class="text-muted">Update the information below and click Save Changes</p>
            </div>
            
            <form method="POST" action="" id="profileForm">
                <!-- I. Personal Data -->
                <div class="form-section">
                    <h4><i class="fas fa-user"></i> I. PERSONAL DATA</h4>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 200px;">Full Name:</th>
                                    <td>
                                        <input type="text" class="form-control" name="full_name" required 
                                               value="<?php echo htmlspecialchars($profile['full_name']); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Alias:</th>
                                    <td>
                                        <input type="text" class="form-control" name="alias" 
                                               value="<?php echo htmlspecialchars($profile['alias']); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Group/Gang Affiliation:</th>
                                    <td>
                                        <input type="text" class="form-control" name="group_affiliation"
                                               value="<?php echo htmlspecialchars($profile['group_affiliation']); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Position/Role:</th>
                                    <td>
                                        <input type="text" class="form-control" name="position_roles"
                                               value="<?php echo htmlspecialchars($profile['position_roles']); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Age:</th>
                                    <td>
                                        <input type="number" class="form-control" name="age" required
                                               value="<?php echo $profile['age']; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Sex:</th>
                                    <td>
                                        <select class="form-select" name="sex" required>
                                            <option value="">Select Sex</option>
                                            <option value="Male" <?php echo $profile['sex'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo $profile['sex'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                            <option value="Other" <?php echo $profile['sex'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Date of Birth:</th>
                                    <td>
                                        <input type="date" class="form-control" name="dob" required
                                               value="<?php echo $profile['dob']; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Place of Birth:</th>
                                    <td>
                                        <input type="text" class="form-control" name="pob" required
                                               value="<?php echo htmlspecialchars($profile['pob']); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Educational Attainment:</th>
                                    <td>
                                        <select class="form-select" name="educational_attainment">
                                            <option value="">Select</option>
                                            <option value="Elementary Level" <?php echo $profile['educational_attainment'] == 'Elementary Level' ? 'selected' : ''; ?>>Elementary Level</option>
                                            <option value="Elementary Graduate" <?php echo $profile['educational_attainment'] == 'Elementary Graduate' ? 'selected' : ''; ?>>Elementary Graduate</option>
                                            <option value="High School Level" <?php echo $profile['educational_attainment'] == 'High School Level' ? 'selected' : ''; ?>>High School Level</option>
                                            <option value="High School Graduate" <?php echo $profile['educational_attainment'] == 'High School Graduate' ? 'selected' : ''; ?>>High School Graduate</option>
                                            <option value="College Level" <?php echo $profile['educational_attainment'] == 'College Level' ? 'selected' : ''; ?>>College Level</option>
                                            <option value="College Graduate" <?php echo $profile['educational_attainment'] == 'College Graduate' ? 'selected' : ''; ?>>College Graduate</option>
                                            <option value="Post Graduate" <?php echo $profile['educational_attainment'] == 'Post Graduate' ? 'selected' : ''; ?>>Post Graduate</option>
                                            <option value="Vocational" <?php echo $profile['educational_attainment'] == 'Vocational' ? 'selected' : ''; ?>>Vocational</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Occupation/Profession:</th>
                                    <td>
                                        <input type="text" class="form-control" name="occupation"
                                               value="<?php echo htmlspecialchars($profile['occupation']); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Company/Office:</th>
                                    <td>
                                        <input type="text" class="form-control" name="company_office"
                                               value="<?php echo htmlspecialchars($profile['company_office']); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Technical Skills:</th>
                                    <td>
                                        <input type="text" class="form-control" name="technical_skills"
                                               value="<?php echo htmlspecialchars($profile['technical_skills']); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Ethnic Group:</th>
                                    <td>
                                        <input type="text" class="form-control" name="ethnic_group"
                                               value="<?php echo htmlspecialchars($profile['ethnic_group']); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Languages/Dialects:</th>
                                    <td>
                                        <input type="text" class="form-control" name="languages"
                                               value="<?php echo htmlspecialchars($profile['languages']); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Present Address:</th>
                                    <td>
                                        <input type="text" class="form-control" name="present_address"
                                               value="<?php echo htmlspecialchars($profile['present_address']); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Provincial Address:</th>
                                    <td>
                                        <input type="text" class="form-control" name="provincial_address"
                                               value="<?php echo htmlspecialchars($profile['provincial_address']); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Civil Status:</th>
                                    <td>
                                        <select class="form-select" name="civil_status">
                                            <option value="">Select</option>
                                            <option value="Single" <?php echo $profile['civil_status'] == 'Single' ? 'selected' : ''; ?>>Single</option>
                                            <option value="Married" <?php echo $profile['civil_status'] == 'Married' ? 'selected' : ''; ?>>Married</option>
                                            <option value="Widowed" <?php echo $profile['civil_status'] == 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                                            <option value="Separated" <?php echo $profile['civil_status'] == 'Separated' ? 'selected' : ''; ?>>Separated</option>
                                            <option value="Divorced" <?php echo $profile['civil_status'] == 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Citizenship:</th>
                                    <td>
                                        <input type="text" class="form-control" name="citizenship"
                                               value="<?php echo htmlspecialchars($profile['citizenship']); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Religion:</th>
                                    <td>
                                        <input type="text" class="form-control" name="religion"
                                               value="<?php echo htmlspecialchars($profile['religion']); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Height (ft/in):</th>
                                    <td>
                                        <input type="text" class="form-control" name="height_ft" placeholder="e.g., 5'5&quot;"
                                               value="<?php echo htmlspecialchars($profile['height_ft']); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Weight (kg):</th>
                                    <td>
                                        <input type="number" step="0.01" class="form-control" name="weight_kg"
                                               value="<?php echo $profile['weight_kg']; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Eyes Color:</th>
                                    <td>
                                        <input type="text" class="form-control" name="eyes_color"
                                               value="<?php echo htmlspecialchars($profile['eyes_color']); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Hair Color:</th>
                                    <td>
                                        <input type="text" class="form-control" name="hair_color"
                                               value="<?php echo htmlspecialchars($profile['hair_color']); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Built:</th>
                                    <td>
                                        <select class="form-select" name="built">
                                            <option value="">Select Built</option>
                                            <option value="Small" <?php echo $profile['built'] == 'Small' ? 'selected' : ''; ?>>Small (130-140 cm)</option>
                                            <option value="Medium" <?php echo $profile['built'] == 'Medium' ? 'selected' : ''; ?>>Medium (140-150 cm)</option>
                                            <option value="Large" <?php echo $profile['built'] == 'Large' ? 'selected' : ''; ?>>Large (150+ cm)</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Complexion:</th>
                                    <td>
                                        <input type="text" class="form-control" name="complexion"
                                               value="<?php echo htmlspecialchars($profile['complexion']); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Distinguishing Marks/Tattoo:</th>
                                    <td>
                                        <textarea class="form-control" name="distinguishing_marks" rows="2"><?php echo htmlspecialchars($profile['distinguishing_marks']); ?></textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Previous Arrest Record:</th>
                                    <td>
                                        <textarea class="form-control" name="previous_arrest" rows="2"><?php echo htmlspecialchars($profile['previous_arrest']); ?></textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Specific Charge:</th>
                                    <td>
                                        <input type="text" class="form-control" name="specific_charge"
                                               value="<?php echo htmlspecialchars($profile['specific_charge']); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Date/Time of Arrest:</th>
                                    <td>
                                        <input type="datetime-local" class="form-control" name="arrest_datetime"
                                               value="<?php echo $profile['arrest_datetime']; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Place of Arrest:</th>
                                    <td>
                                        <input type="text" class="form-control" name="arrest_place"
                                               value="<?php echo htmlspecialchars($profile['arrest_place']); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Arresting Officer:</th>
                                    <td>
                                        <input type="text" class="form-control" name="arresting_officer"
                                               value="<?php echo htmlspecialchars($profile['arresting_officer']); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Unit/Office of Arresting Officer:</th>
                                    <td>
                                        <input type="text" class="form-control" name="arresting_unit"
                                               value="<?php echo htmlspecialchars($profile['arresting_unit']); ?>">
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- II. Family Background -->
                <div class="form-section">
                    <h4><i class="fas fa-family"></i> II. FAMILY BACKGROUND</h4>
                    
                    <h5 class="mt-3 mb-3" style="color: #0a2f4d;">Parents</h5>
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
                                           value="<?php echo htmlspecialchars($profile['father_name']); ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="mother_name"
                                           value="<?php echo htmlspecialchars($profile['mother_name']); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th>Address</th>
                                <td>
                                    <input type="text" class="form-control" name="father_address"
                                           value="<?php echo htmlspecialchars($profile['father_address']); ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="mother_address"
                                           value="<?php echo htmlspecialchars($profile['mother_address']); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th>Date of Birth</th>
                                <td>
                                    <input type="date" class="form-control" name="father_dob"
                                           value="<?php echo $profile['father_dob']; ?>">
                                </td>
                                <td>
                                    <input type="date" class="form-control" name="mother_dob"
                                           value="<?php echo $profile['mother_dob']; ?>">
                                </td>
                            </tr>
                            <tr>
                                <th>Place of Birth</th>
                                <td>
                                    <input type="text" class="form-control" name="father_pob"
                                           value="<?php echo htmlspecialchars($profile['father_pob']); ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="mother_pob"
                                           value="<?php echo htmlspecialchars($profile['mother_pob']); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th>Age</th>
                                <td>
                                    <input type="number" class="form-control" name="father_age"
                                           value="<?php echo $profile['father_age']; ?>">
                                </td>
                                <td>
                                    <input type="number" class="form-control" name="mother_age"
                                           value="<?php echo $profile['mother_age']; ?>">
                                </td>
                            </tr>
                            <tr>
                                <th>Occupation</th>
                                <td>
                                    <input type="text" class="form-control" name="father_occupation"
                                           value="<?php echo htmlspecialchars($profile['father_occupation']); ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="mother_occupation"
                                           value="<?php echo htmlspecialchars($profile['mother_occupation']); ?>">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h5 class="mt-4 mb-3" style="color: #0a2f4d;">Spouse</h5>
                    <table class="table table-bordered">
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
                                <td>
                                    <input type="text" class="form-control" name="spouse_name"
                                           value="<?php echo htmlspecialchars($profile['spouse_name']); ?>">
                                </td>
                                <td>
                                    <input type="number" class="form-control" name="spouse_age"
                                           value="<?php echo $profile['spouse_age']; ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="spouse_occupation"
                                           value="<?php echo htmlspecialchars($profile['spouse_occupation']); ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="spouse_address"
                                           value="<?php echo htmlspecialchars($profile['spouse_address']); ?>">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h5 class="mt-4 mb-3" style="color: #0a2f4d;">Siblings</h5>
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
                                <?php if (count($siblings) > 0): ?>
                                    <?php foreach ($siblings as $index => $sibling): ?>
                                    <tr>
                                        <td>
                                            <input type="text" class="form-control" name="sibling_name[]" 
                                                   value="<?php echo htmlspecialchars($sibling['name']); ?>">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" name="sibling_age[]" 
                                                   value="<?php echo $sibling['age']; ?>">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" name="sibling_occupation[]" 
                                                   value="<?php echo htmlspecialchars($sibling['occupation']); ?>">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" name="sibling_status[]" 
                                                   value="<?php echo htmlspecialchars($sibling['status']); ?>">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" name="sibling_address[]" 
                                                   value="<?php echo htmlspecialchars($sibling['address']); ?>">
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
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
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-sm btn-success" onclick="addSiblingRow()">
                            <i class="fas fa-plus"></i> Add Sibling
                        </button>
                    </div>
                </div>

                <!-- III. Tactical Information -->
                <div class="form-section">
                    <h4><i class="fas fa-info-circle"></i> III. TACTICAL INFORMATION</h4>
                    
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 250px;">Drugs Involved:</th>
                            <td>
                                <input type="text" class="form-control" name="drugs_involved" id="drugs_involved"
                                       value="<?php echo htmlspecialchars($profile['drugs_involved']); ?>">
                                <small class="text-muted">Separate multiple drugs with commas</small>
                            </td>
                        </tr>
                        
                        <tr>
                            <th>Relationship to Source:</th>
                            <td>
                                <input type="text" class="form-control" name="source_relationship" 
                                       placeholder="e.g., Friend, Relative, Acquaintance"
                                       value="<?php echo htmlspecialchars($profile['source_relationship']); ?>">
                            </td>
                        </tr>
                        
                        <tr>
                            <th>Source Address:</th>
                            <td>
                                <input type="text" class="form-control" name="source_address" 
                                       placeholder="Address of source"
                                       value="<?php echo htmlspecialchars($profile['source_address']); ?>">
                            </td>
                        </tr>
                        
                        <tr>
                            <th>Source Name:</th>
                            <td>
                                <input type="text" class="form-control" name="source_name" 
                                       placeholder="Full name of source"
                                       value="<?php echo htmlspecialchars($profile['source_name']); ?>">
                            </td>
                        </tr>
                        
                        <tr>
                            <th>Source Alias/Nickname:</th>
                            <td>
                                <input type="text" class="form-control" name="source_nickname" 
                                       placeholder="Alias of source"
                                       value="<?php echo htmlspecialchars($profile['source_nickname']); ?>">
                            </td>
                        </tr>
                        
                        <tr>
                            <th>Complete Address of Alleged Source:</th>
                            <td>
                                <textarea class="form-control" name="source_full_address" rows="2"><?php echo htmlspecialchars($profile['source_full_address']); ?></textarea>
                            </td>
                        </tr>
                        
                        <tr>
                            <th>Other Types of Drugs Supplied by Source:</th>
                            <td>
                                <input type="text" class="form-control" name="source_other_drugs" 
                                       placeholder="e.g., Shabu, Marijuana, etc."
                                       value="<?php echo htmlspecialchars($profile['source_other_drugs']); ?>">
                            </td>
                        </tr>
                        
                        <tr>
                            <th>Subgroup Name:</th>
                            <td>
                                <input type="text" class="form-control" name="subgroup_name" 
                                       placeholder="Subgroup name"
                                       value="<?php echo htmlspecialchars($profile['subgroup_name']); ?>">
                            </td>
                        </tr>
                        
                        <tr>
                            <th>Specific Area of Responsibility (AOR):</th>
                            <td>
                                <input type="text" class="form-control" name="specific_aor" 
                                       placeholder="Area of responsibility"
                                       value="<?php echo htmlspecialchars($profile['specific_aor']); ?>">
                            </td>
                        </tr>
                        
                        <tr>
                            <th>Other Subject Known as Source:</th>
                            <td>
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="other_source_name" 
                                               placeholder="Name of other source"
                                               value="<?php echo htmlspecialchars($profile['other_source_name']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="other_source_alias" 
                                               placeholder="Alias"
                                               value="<?php echo htmlspecialchars($profile['other_source_alias']); ?>">
                                    </div>
                                </div>
                                <textarea class="form-control mt-2" name="other_source_details" rows="2" 
                                          placeholder="Additional details about other source"><?php echo htmlspecialchars($profile['other_source_details']); ?></textarea>
                            </td>
                        </tr>
                        
                        <tr>
                            <th>Types of Drugs Pushed by Subject:</th>
                            <td>
                                <div class="drug-checkbox-group">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" name="drugs_pushed[]" value="Shabu" 
                                                       <?php echo isDrugPushed('Shabu', $drugsPushedArray) ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Shabu</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" name="drugs_pushed[]" value="Marijuana"
                                                       <?php echo isDrugPushed('Marijuana', $drugsPushedArray) ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Marijuana</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" name="drugs_pushed[]" value="Ecstasy"
                                                       <?php echo isDrugPushed('Ecstasy', $drugsPushedArray) ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Ecstasy</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" name="drugs_pushed[]" value="Cocaine"
                                                       <?php echo isDrugPushed('Cocaine', $drugsPushedArray) ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Cocaine</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-12">
                                            <input type="text" class="form-control" name="other_drugs_pushed" 
                                                   placeholder="Other drugs not listed above"
                                                   value="<?php echo htmlspecialchars($profile['other_drugs_pushed']); ?>">
                                        </div>
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
                                       value="<?php echo htmlspecialchars($profile['vehicles_used']); ?>">
                            </td>
                        </tr>
                        
                        <tr>
                            <th>Armaments:</th>
                            <td>
                                <input type="text" class="form-control" name="armaments"
                                       placeholder="e.g., .45 caliber pistol, Revolver"
                                       value="<?php echo htmlspecialchars($profile['armaments']); ?>">
                            </td>
                        </tr>
                        
                        <tr>
                            <th>Companions During Arrest:</th>
                            <td>
                                <textarea class="form-control" name="companions_arrest" rows="2" 
                                          placeholder="Names of companions during arrest"><?php echo htmlspecialchars($profile['companions_arrest']); ?></textarea>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- IV. Recruitment Summary -->
                <div class="form-section">
                    <h4><i class="fas fa-user-plus"></i> IV. DETAILED SUMMARY ON RECRUITMENT</h4>
                    <div class="mb-3">
                        <label class="form-label">Recruitment Details:</label>
                        <textarea class="form-control" name="recruitment_summary" rows="4"><?php echo htmlspecialchars($profile['recruitment_summary']); ?></textarea>
                        <small class="text-muted">Describe how the suspect was recruited as user/pusher/drugs influence by friends</small>
                    </div>
                </div>

                <!-- V. Drug Operations -->
                <div class="form-section">
                    <h4><i class="fas fa-exchange-alt"></i> V. SUMMARY OF PUSHING/SUPPLYING/ACQUIRING DRUGS</h4>
                    <div class="mb-3">
                        <label class="form-label">Modus Operandi:</label>
                        <textarea class="form-control" name="modus_operandi" rows="4"><?php echo htmlspecialchars($profile['modus_operandi']); ?></textarea>
                        <small class="text-muted">Indicate the Modus Operandi, Sale/Distribution and Transportation of Dangerous Drugs</small>
                    </div>
                </div>

                <!-- VI. Organizational Structure -->
                <div class="form-section">
                    <h4><i class="fas fa-sitemap"></i> VI. ORGANIZATIONAL STRUCTURE OF THE GROUP</h4>
                    <div class="mb-3">
                        <label class="form-label">Structure Details:</label>
                        <textarea class="form-control" name="organizational_structure" rows="3"><?php echo htmlspecialchars($profile['organizational_structure']); ?></textarea>
                        <small class="text-muted">Indicate the whole membership of the group</small>
                    </div>
                </div>

                <!-- VII. CI Matters -->
                <div class="form-section">
                    <h4><i class="fas fa-user-secret"></i> VII. CI MATTERS</h4>
                    <div class="mb-3">
                        <label class="form-label">CI Information:</label>
                        <textarea class="form-control" name="ci_matters" rows="3"><?php echo htmlspecialchars($profile['ci_matters']); ?></textarea>
                        <small class="text-muted">AFP/PNP Government Officer's Instrument</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Other Significant Revelations:</label>
                        <textarea class="form-control" name="other_revelations" rows="3"><?php echo htmlspecialchars($profile['other_revelations']); ?></textarea>
                    </div>
                </div>

                <!-- VIII. Recommendation -->
                <div class="form-section">
                    <h4><i class="fas fa-check-circle"></i> VIII. RECOMMENDATION</h4>
                    <div class="mb-3">
                        <select class="form-select" name="recommendation">
                            <option value="">Select Recommendation</option>
                            <option value="For Filing" <?php echo $profile['recommendation'] == 'For Filing' ? 'selected' : ''; ?>>For Filing</option>
                            <option value="For Investigation" <?php echo $profile['recommendation'] == 'For Investigation' ? 'selected' : ''; ?>>For Investigation</option>
                            <option value="For Delisting" <?php echo $profile['recommendation'] == 'For Delisting' ? 'selected' : ''; ?>>For Delisting</option>
                            <option value="For Prosecution" <?php echo $profile['recommendation'] == 'For Prosecution' ? 'selected' : ''; ?>>For Prosecution</option>
                            <option value="Closed" <?php echo $profile['recommendation'] == 'Closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>
                </div>

                <!-- Profile Status -->
                <div class="form-section">
                    <h4><i class="fas fa-tag"></i> PROFILE STATUS</h4>
                    <div class="mb-3">
                        <select class="form-select" name="status">
                            <option value="active" <?php echo $profile['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="archived" <?php echo $profile['status'] == 'archived' ? 'selected' : ''; ?>>Archived</option>
                            <option value="delisted" <?php echo $profile['status'] == 'delisted' ? 'selected' : ''; ?>>Delisted</option>
                        </select>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button type="submit" class="btn btn-pnp">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="view_profile.php?id=<?php echo $profile['id']; ?>" class="btn btn-info">
                        <i class="fas fa-eye"></i> View Profile
                    </a>
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
                if (confirm('Clear all fields instead of removing the last row?')) {
                    const inputs = row.querySelectorAll('input');
                    inputs.forEach(input => input.value = '');
                }
            }
        }
        
        // Auto-calculate age from DOB
        const dobInput = document.querySelector('input[name="dob"]');
        const ageInput = document.querySelector('input[name="age"]');
        
        if (dobInput && ageInput) {
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
        
        // Confirm before leaving with unsaved changes
        let formChanged = false;
        const form = document.getElementById('profileForm');
        const formInputs = form.querySelectorAll('input, select, textarea');
        
        formInputs.forEach(input => {
            input.addEventListener('change', () => {
                formChanged = true;
            });
        });
        
        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            }
        });
        
        form.addEventListener('submit', function() {
            formChanged = false;
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>