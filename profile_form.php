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

// Handle picture upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_picture'])) {
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_name = time() . '_' . basename($_FILES['profile_picture']['name']);
    $target_file = $target_dir . $file_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Check if image file is actual image
    $check = getimagesize($_FILES['profile_picture']['tmp_name']);
    if ($check !== false) {
        // Allow certain file formats
        if ($imageFileType == "jpg" || $imageFileType == "png" || $imageFileType == "jpeg" || $imageFileType == "gif") {
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                $_SESSION['profile_picture'] = $target_file;
                $message = "Picture uploaded successfully!";
            } else {
                $error = "Sorry, there was an error uploading your file.";
            }
        } else {
            $error = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        }
    } else {
        $error = "File is not an image.";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_FILES['profile_picture'])) {
    try {
        // Begin transaction
        $db->beginTransaction();
        
        // Get profile picture from session if exists
        $profile_picture = isset($_SESSION['profile_picture']) ? $_SESSION['profile_picture'] : null;
        
        // Process position_roles - combine multiple checkboxes
        if (isset($_POST['position_roles']) && is_array($_POST['position_roles'])) {
            $position_roles = implode(', ', $_POST['position_roles']);
        } elseif (isset($_POST['position_roles_other']) && !empty($_POST['position_roles_other'])) {
            $position_roles = $_POST['position_roles_other'];
        } else {
            $position_roles = '';
        }
        
        // Process drug types - combine multiple checkboxes
        if (isset($_POST['drug_types']) && is_array($_POST['drug_types'])) {
            $drug_types = implode(', ', $_POST['drug_types']);
        } elseif (isset($_POST['drug_types_other']) && !empty($_POST['drug_types_other'])) {
            $drug_types = $_POST['drug_types_other'];
        } else {
            $drug_types = '';
        }
        
        $query = "INSERT INTO biographical_profiles (
            full_name, alias, group_affiliation, position_roles, age, sex, dob, pob,
            educational_attainment, occupation, company_office, technical_skills,
            ethnic_group, languages, present_address, provincial_address,
            civil_status, citizenship, religion, height_cm, weight_kg, height_ft,
            eyes_color, hair_color, built, complexion, distinguishing_marks,
            previous_arrest, specific_charge, date_time_place_of_arrest,
            arresting_officer, arresting_unit, drugs_involved,
            source_relationship, source_address, source_name, source_nickname, 
            source_full_address, source_other_drugs, subgroup_name, specific_aor,
            other_source_name, other_source_alias, other_source_details, 
            drugs_pushed, other_drugs_pushed, profile_picture,
            vehicles_used, armaments, companions_arrest, recruitment_summary, 
            modus_operandi, organizational_structure, ci_matters, other_revelations, 
            recommendation, created_by, status
        ) VALUES (
            :full_name, :alias, :group_affiliation, :position_roles, :age, :sex, :dob, :pob,
            :educational_attainment, :occupation, :company_office, :technical_skills,
            :ethnic_group, :languages, :present_address, :provincial_address,
            :civil_status, :citizenship, :religion, :height_cm, :weight_kg, :height_ft,
            :eyes_color, :hair_color, :built, :complexion, :distinguishing_marks,
            :previous_arrest, :specific_charge, :date_time_place_of_arrest,
            :arresting_officer, :arresting_unit, :drugs_involved,
            :source_relationship, :source_address, :source_name, :source_nickname,
            :source_full_address, :source_other_drugs, :subgroup_name, :specific_aor,
            :other_source_name, :other_source_alias, :other_source_details,
            :drugs_pushed, :other_drugs_pushed, :profile_picture,
            :vehicles_used, :armaments, :companions_arrest, :recruitment_summary,
            :modus_operandi, :organizational_structure, :ci_matters, :other_revelations,
            :recommendation, :created_by, :status
        )";
        
        $stmt = $db->prepare($query);
        
        // Prepare parameters
        $params = [
            ':full_name' => $_POST['full_name'] ?? '',
            ':alias' => $_POST['alias'] ?? '',
            ':group_affiliation' => $_POST['group_affiliation'] ?? '',
            ':position_roles' => $position_roles,
            ':age' => $_POST['age'] ?? 0,
            ':sex' => $_POST['sex'] ?? '',
            ':dob' => $_POST['dob'] ?? null,
            ':pob' => $_POST['pob'] ?? '',
            ':educational_attainment' => $_POST['educational_attainment'] ?? '',
            ':occupation' => $_POST['occupation'] ?? '',
            ':company_office' => $_POST['company_office'] ?? '',
            ':technical_skills' => $_POST['technical_skills'] ?? '',
            ':ethnic_group' => $_POST['ethnic_group'] ?? '',
            ':languages' => $_POST['languages'] ?? '',
            ':present_address' => $_POST['present_address'] ?? '',
            ':provincial_address' => $_POST['provincial_address'] ?? '',
            ':civil_status' => $_POST['civil_status'] ?? '',
            ':citizenship' => $_POST['citizenship'] ?? '',
            ':religion' => $_POST['religion'] ?? '',
            ':height_cm' => isset($_POST['height_cm']) ? $_POST['height_cm'] : null,
            ':weight_kg' => isset($_POST['weight_kg']) ? $_POST['weight_kg'] : null,
            ':height_ft' => $_POST['height_ft'] ?? '',
            ':eyes_color' => $_POST['eyes_color'] ?? '',
            ':hair_color' => $_POST['hair_color'] ?? '',
            ':built' => $_POST['built'] ?? '',
            ':complexion' => $_POST['complexion'] ?? '',
            ':distinguishing_marks' => $_POST['distinguishing_marks'] ?? '',
            ':previous_arrest' => $_POST['previous_arrest_record'] ?? '',
            ':specific_charge' => $_POST['specific_charge'] ?? '',
            ':date_time_place_of_arrest' => $_POST['date_time_place_of_arrest'] ?? null,
            ':arresting_officer' => $_POST['arresting_officer_name'] ?? '',
            ':arresting_unit' => $_POST['arresting_officer_unit'] ?? '',
            ':drugs_involved' => $drug_types ?? '',
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
            ':profile_picture' => $profile_picture,
            ':vehicles_used' => $_POST['vehicles_used'] ?? '',
            ':armaments' => $_POST['armaments'] ?? '',
            ':companions_arrest' => $_POST['companions_arrest'] ?? '',
            ':recruitment_summary' => $_POST['recruitment_summary'] ?? '',
            ':modus_operandi' => $_POST['modus_operandi'] ?? '',
            ':organizational_structure' => $_POST['organizational_structure'] ?? '',
            ':ci_matters' => $_POST['ci_matters'] ?? '',
            ':other_revelations' => $_POST['other_revelations'] ?? '',
            ':recommendation' => $_POST['recommendation'] ?? '',
            ':created_by' => $_SESSION['user_id'],
            ':status' => $_POST['status'] ?? 'active'
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
                        ':occupation' => $_POST['sibling_occupation'][$i] ?? '',
                        ':status' => $_POST['sibling_status'][$i] ?? '',
                        ':address' => $_POST['sibling_address'][$i] ?? ''
                    ]);
                }
            }
        }
        
        $db->commit();
        
        // Clear session picture after saving
        unset($_SESSION['profile_picture']);
        
        // Set success message in session
        $_SESSION['success_message'] = "Profile saved successfully!";
        
        // Redirect to dashboard
        header("Location: dashboard.php");
        exit();
        
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
    <title>PNP Biographical Profiling System - Profile Form</title>
    
    <!-- External CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <style>
        /* All existing CSS styles remain the same */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.4;
            padding: 20px;
        }

        .form-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .pnp-header {
            background: #0a2f4d;
            color: white;
            padding: 15px 20px;
            border-bottom: 3px solid #c9a959;
        }
        
        .header-content {
            text-align: center;
        }
        
        .header-content .dilg {
            font-size: 11px;
            font-weight: 300;
            letter-spacing: 0.5px;
            color: #e0e0e0;
        }
        
        .header-content .pnp {
            font-size: 20px;
            font-weight: 700;
            color: white;
            line-height: 1.2;
        }
        
        .header-content .provincial {
            font-size: 16px;
            font-weight: 500;
            color: #c9a959;
        }
        
        .header-content .station {
            font-size: 14px;
            font-weight: 500;
            color: white;
        }
        
        .header-content .address,
        .header-content .phone {
            font-size: 12px;
            color: #b0c4de;
        }

        .user-info {
            position: absolute;
            top: 20px;
            right: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255,255,255,0.1);
            padding: 8px 20px;
            border-radius: 40px;
        }

        .user-info i {
            color: #c9a959;
        }

        .user-info span {
            font-size: 14px;
            font-weight: 500;
        }

        .main-content {
            padding: 20px;
        }

        .form-title {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-title h2 {
            font-size: 24px;
            font-weight: 600;
            color: #0f172a;
        }

        .form-title p {
            font-size: 13px;
            color: #64748b;
            margin-top: 5px;
        }

        .photo-section {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 15px;
            background: #ffffff;
            display: flex;
            justify-content: flex-end;
        }

        .photo-container {
            display: inline-block;
            text-align: center;
        }

        .photo-box {
            width: 150px;
            height: 150px;
            border: 3px solid #0a2f4d;
            overflow: hidden;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            position: relative;
        }

        .photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-box i {
            font-size: 50px;
            color: #94a3b8;
            margin-bottom: 5px;
        }

        .photo-box span {
            font-size: 11px;
            color: #64748b;
        }

        .photo-label {
            font-size: 11px;
            color: #475569;
            margin-top: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            text-align: center;
        }

        .btn-upload {
            background: #0a2f4d;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
            margin-top: 8px;
        }

        .btn-upload:hover {
            background: #c9a959;
            color: #0a2f4d;
        }

        .file-input {
            display: none;
        }

        .form-section {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .section-title {
            background: #f8fafc;
            padding: 10px 15px;
            font-weight: 600;
            font-size: 15px;
            color: #0f172a;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i {
            color: #64748b;
            font-size: 15px;
        }

        .section-content {
            padding: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }

        .form-field {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .form-field.full-width {
            grid-column: 1 / -1;
        }

        .form-field label {
            font-size: 12px;
            font-weight: 500;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .form-control, .form-select {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 13px;
            color: #1e293b;
            background: #ffffff;
            transition: all 0.15s;
            width: 100%;
        }

        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: #94a3b8;
            box-shadow: 0 0 0 2px rgba(148, 163, 184, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 60px;
        }

        .table-container {
            overflow-x: auto;
            margin-top: 10px;
        }

        .form-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .form-table th {
            background: #f8fafc;
            padding: 8px 10px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            font-size: 11px;
            text-transform: uppercase;
            border: 1px solid #e2e8f0;
        }

        .form-table td {
            padding: 8px 10px;
            border: 1px solid #e2e8f0;
        }

        .form-table .form-control {
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
            font-size: 12px;
        }

        .checkbox-container {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px;
        }

        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-check-input {
            width: 14px;
            height: 14px;
            border: 1.5px solid #cbd5e1;
            border-radius: 3px;
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: #0f172a;
            border-color: #0f172a;
        }

        .form-check-label {
            font-size: 13px;
            color: #334155;
        }

        .btn {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 11px;
        }

        .btn-primary {
            background: #0f172a;
            color: white;
        }

        .btn-primary:hover {
            background: #1e293b;
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #e2e8f0;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        .btn-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-danger:hover {
            background: #fecaca;
        }

        .action-bar {
            padding: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            margin-top: 20px;
        }

        .action-bar .btn {
            padding: 8px 20px;
            font-size: 13px;
        }

        .alert {
            background: #f8fafc;
            border-left: 4px solid #0f172a;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
        }

        .alert-success {
            border-left-color: #10b981;
            background: #f0fdf4;
        }

        .alert-danger {
            border-left-color: #ef4444;
            background: #fef2f2;
        }

        .form-footer {
            text-align: center;
            padding: 20px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 11px;
            background: #ffffff;
        }

        @media (max-width: 768px) {
            .user-info {
                position: relative;
                top: 0;
                right: 0;
                margin-top: 15px;
                justify-content: center;
            }

            .photo-section {
                justify-content: center;
            }

            .action-bar {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <!-- PNP Header -->
        <div class="pnp-header">
            <div class="header-content">
                <div class="dilg">Department of the Interior and Local Government</div>
                <div class="pnp">PHILIPPINE NATIONAL POLICE</div>
                <div class="provincial">BUKIDNON POLICE PROVINCIAL OFFICE</div>
                <div class="station">MANOLO FORTICH POLICE STATION</div>
                <div class="address">Manolo Fortich, Bukidnon, 8703</div>
                <div class="phone"><i class="fas fa-phone"></i> (088-228) 2244</div>
                
                <div class="user-info">
                    <i class="fas fa-user-shield"></i>
                    <span><?php echo $_SESSION['rank'] . ' ' . $_SESSION['full_name']; ?></span>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-title">
                <h2>BIOGRAPHICAL PROFILE FORM</h2>
                <p>All information is confidential and for official use only</p>
            </div>

            <!-- PHOTO SECTION -->
            <div class="photo-section">
                <div class="photo-container">
                    <div class="photo-box" id="picturePreview">
                        <?php if (isset($_SESSION['profile_picture'])): ?>
                            <img src="<?php echo $_SESSION['profile_picture']; ?>" alt="Profile Picture">
                        <?php else: ?>
                            <i class="fas fa-user-circle"></i>
                            <span></span>
                        <?php endif; ?>
                    </div>
                    <form method="POST" enctype="multipart/form-data" id="pictureForm">
                        <input type="file" name="profile_picture" id="profilePicture" class="file-input" accept="image/*">
                        <button type="button" class="btn-upload" onclick="document.getElementById('profilePicture').click();">
                            <i class="fas fa-camera"></i> 
                        </button>
                    </form>
                    <div class="photo-label"></div>
                </div>
            </div>

            <form method="POST" action="" id="profileForm">
                <!-- I. PERSONAL DATA -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-user"></i> I. PERSONAL DATA
                    </div>
                    <div class="section-content">
                        <div class="form-grid">
                            <div class="form-field full-width">
                                <label>Full Name <span style="color: #ef4444;">*</span></label>
                                <input type="text" class="form-control" name="full_name" required 
                                       placeholder="Enter complete name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                            </div>
                            
                            <div class="form-field full-width">
                                <label>Alias <span style="color: #ef4444;">*</span></label>
                                <input type="text" class="form-control" name="alias" required 
                                       placeholder="Enter alias" value="<?php echo isset($_POST['alias']) ? htmlspecialchars($_POST['alias']) : ''; ?>">
                            </div>
                            
                            <div class="form-field full-width">
                                <label>Name of Group/Gang Affiliation <span style="color: #ef4444;">*</span></label>
                                <input type="text" class="form-control" name="group_affiliation" required 
                                       placeholder="Enter group/gang affiliation" value="<?php echo isset($_POST['group_affiliation']) ? htmlspecialchars($_POST['group_affiliation']) : ''; ?>">
                            </div>
                            
                            <div class="form-field full-width">
                                <label>Position/Role</label>
                                <div class="checkbox-container">
                                    <div class="checkbox-group">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="position_roles[]" value="User" id="posUser"
                                                   <?php echo (isset($_POST['position_roles']) && is_array($_POST['position_roles']) && in_array('User', $_POST['position_roles'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="posUser">User</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="position_roles[]" value="Pusher" id="posPusher"
                                                   <?php echo (isset($_POST['position_roles']) && is_array($_POST['position_roles']) && in_array('Pusher', $_POST['position_roles'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="posPusher">Pusher</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="position_roles[]" value="Runner" id="posRunner"
                                                   <?php echo (isset($_POST['position_roles']) && is_array($_POST['position_roles']) && in_array('Runner', $_POST['position_roles'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="posRunner">Runner</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-field">
                                <label>Age<span style="color: #ef4444;">*</span></label>
                                <input type="number" class="form-control" name="age" required
                                       placeholder="Enter age" value="<?php echo isset($_POST['age']) ? htmlspecialchars($_POST['age']) : ''; ?>">
                            </div> 
                            
                            <div class="form-field">
                                <label>Sex <span style="color: #ef4444;">*</span></label>
                                <select class="form-select" name="sex" required>
                                    <option value="">Select</option>
                                    <option value="Male" <?php echo (isset($_POST['sex']) && $_POST['sex'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo (isset($_POST['sex']) && $_POST['sex'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                </select>
                            </div>
                            
                            <div class="form-field">
                                <label>Date of Birth <span style="color: #ef4444;">*</span></label>
                                <input type="date" class="form-control" name="dob" required
                                       value="<?php echo isset($_POST['dob']) ? htmlspecialchars($_POST['dob']) : ''; ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>Place of Birth <span style="color: #ef4444;">*</span></label>
                                <input type="text" class="form-control" name="pob" required
                                       value="<?php echo isset($_POST['pob']) ? htmlspecialchars($_POST['pob']) : ''; ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>Educational Attainment</label>
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
                            </div>
                            
                            <div class="form-field">
                                <label>Occupation</label>
                                <select class="form-select" name="occupation">
                                    <option value="">Select Occupation</option>
                                    <option value="Laborer" <?php echo (isset($_POST['occupation']) && $_POST['occupation'] == 'Laborer') ? 'selected' : ''; ?>>Laborer</option>
                                    <option value="Farmer" <?php echo (isset($_POST['occupation']) && $_POST['occupation'] == 'Farmer') ? 'selected' : ''; ?>>Farmer</option>
                                    <option value="Fisherman" <?php echo (isset($_POST['occupation']) && $_POST['occupation'] == 'Fisherman') ? 'selected' : ''; ?>>Fisherman</option>
                                    <option value="Driver" <?php echo (isset($_POST['occupation']) && $_POST['occupation'] == 'Driver') ? 'selected' : ''; ?>>Driver</option>
                                    <option value="Government Employee" <?php echo (isset($_POST['occupation']) && $_POST['occupation'] == 'Government Employee') ? 'selected' : ''; ?>>Government Employee</option>
                                    <option value="Private Employee" <?php echo (isset($_POST['occupation']) && $_POST['occupation'] == 'Private Employee') ? 'selected' : ''; ?>>Private Employee</option>
                                    <option value="Self-Employed" <?php echo (isset($_POST['occupation']) && $_POST['occupation'] == 'Self-Employed') ? 'selected' : ''; ?>>Self-Employed</option>
                                    <option value="Unemployed" <?php echo (isset($_POST['occupation']) && $_POST['occupation'] == 'Unemployed') ? 'selected' : ''; ?>>Unemployed</option>
                                    <option value="Student" <?php echo (isset($_POST['occupation']) && $_POST['occupation'] == 'Student') ? 'selected' : ''; ?>>Student</option>
                                    <option value="Other" <?php echo (isset($_POST['occupation']) && $_POST['occupation'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="form-field">
                                <label>Company/Office</label>
                                <input type="text" class="form-control" name="company_office"
                                       placeholder="If employed, enter company/office name"
                                       value="<?php echo isset($_POST['company_office']) ? htmlspecialchars($_POST['company_office']) : ''; ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>Technical Skills</label>
                                <input type="text" class="form-control" name="technical_skills" 
                                       placeholder="Enter technical skills"
                                       value="<?php echo isset($_POST['technical_skills']) ? htmlspecialchars($_POST['technical_skills']) : ''; ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>Ethnic Group</label>
                                <select class="form-select" name="ethnic_group">
                                    <option value="">Select Ethnic Group</option>
                                    <option value="Cebuano" <?php echo (isset($_POST['ethnic_group']) && $_POST['ethnic_group'] == 'Cebuano') ? 'selected' : ''; ?>>Cebuano</option>
                                    <option value="Bisaya" <?php echo (isset($_POST['ethnic_group']) && $_POST['ethnic_group'] == 'Bisaya') ? 'selected' : ''; ?>>Bisaya</option>
                                    <option value="Ilocano" <?php echo (isset($_POST['ethnic_group']) && $_POST['ethnic_group'] == 'Ilocano') ? 'selected' : ''; ?>>Ilocano</option>
                                    <option value="Tagalog" <?php echo (isset($_POST['ethnic_group']) && $_POST['ethnic_group'] == 'Tagalog') ? 'selected' : ''; ?>>Tagalog</option>
                                    <option value="Waray" <?php echo (isset($_POST['ethnic_group']) && $_POST['ethnic_group'] == 'Waray') ? 'selected' : ''; ?>>Waray</option>
                                    <option value="Hiligaynon" <?php echo (isset($_POST['ethnic_group']) && $_POST['ethnic_group'] == 'Hiligaynon') ? 'selected' : ''; ?>>Hiligaynon</option>
                                    <option value="Bukidnon" <?php echo (isset($_POST['ethnic_group']) && $_POST['ethnic_group'] == 'Bukidnon') ? 'selected' : ''; ?>>Bukidnon</option>
                                    <option value="Other" <?php echo (isset($_POST['ethnic_group']) && $_POST['ethnic_group'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="form-field">
                                <label>Languages/Dialects</label>
                                <input type="text" class="form-control" name="languages" 
                                       placeholder="Enter languages/dialects (e.g., Cebuano, English, Tagalog)"
                                       value="<?php echo isset($_POST['languages']) ? htmlspecialchars($_POST['languages']) : ''; ?>">
                            </div>
                            
                            <div class="form-field full-width">
                                <label>Present Address</label>
                                <input type="text" class="form-control" name="present_address"
                                       placeholder="House No., Street, Barangay, City/Municipality"
                                       value="<?php echo isset($_POST['present_address']) ? htmlspecialchars($_POST['present_address']) : ''; ?>">
                            </div>
                            
                            <div class="form-field full-width">
                                <label>Provincial Address</label>
                                <input type="text" class="form-control" name="provincial_address"
                                       placeholder="House No., Street, Barangay, City/Municipality"
                                       value="<?php echo isset($_POST['provincial_address']) ? htmlspecialchars($_POST['provincial_address']) : ''; ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>Civil Status</label>
                                <select class="form-select" name="civil_status">
                                    <option value="">Select</option>
                                    <option value="Single" <?php echo (isset($_POST['civil_status']) && $_POST['civil_status'] == 'Single') ? 'selected' : ''; ?>>Single</option>
                                    <option value="Married" <?php echo (isset($_POST['civil_status']) && $_POST['civil_status'] == 'Married') ? 'selected' : ''; ?>>Married</option>
                                    <option value="Widowed" <?php echo (isset($_POST['civil_status']) && $_POST['civil_status'] == 'Widowed') ? 'selected' : ''; ?>>Widowed</option>
                                    <option value="Separated" <?php echo (isset($_POST['civil_status']) && $_POST['civil_status'] == 'Separated') ? 'selected' : ''; ?>>Separated</option>
                                </select>
                            </div>
                            
                            <div class="form-field">
                                <label>Citizenship</label>
                                <select class="form-select" name="citizenship">
                                    <option value="Filipino" <?php echo (isset($_POST['citizenship']) && $_POST['citizenship'] == 'Filipino') ? 'selected' : ''; ?>>Filipino</option>
                                    <option value="Dual Citizenship" <?php echo (isset($_POST['citizenship']) && $_POST['citizenship'] == 'Dual Citizenship') ? 'selected' : ''; ?>>Dual Citizenship</option>
                                    <option value="Other" <?php echo (isset($_POST['citizenship']) && $_POST['citizenship'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="form-field">
                                <label>Religion</label>
                                <select class="form-select" name="religion">
                                    <option value="">Select Religion</option>
                                    <option value="Roman Catholic" <?php echo (isset($_POST['religion']) && $_POST['religion'] == 'Roman Catholic') ? 'selected' : ''; ?>>Roman Catholic</option>
                                    <option value="Islam" <?php echo (isset($_POST['religion']) && $_POST['religion'] == 'Islam') ? 'selected' : ''; ?>>Islam</option>
                                    <option value="Iglesia Ni Cristo" <?php echo (isset($_POST['religion']) && $_POST['religion'] == 'Iglesia Ni Cristo') ? 'selected' : ''; ?>>Iglesia Ni Cristo</option>
                                    <option value="Born Again Christian" <?php echo (isset($_POST['religion']) && $_POST['religion'] == 'Born Again Christian') ? 'selected' : ''; ?>>Born Again Christian</option>
                                    <option value="Seventh Day Adventist" <?php echo (isset($_POST['religion']) && $_POST['religion'] == 'Seventh Day Adventist') ? 'selected' : ''; ?>>Seventh Day Adventist</option>
                                    <option value="Buddhist" <?php echo (isset($_POST['religion']) && $_POST['religion'] == 'Buddhist') ? 'selected' : ''; ?>>Buddhist</option>
                                    <option value="Other" <?php echo (isset($_POST['religion']) && $_POST['religion'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="form-field">
                                <label>Height (ft/in)</label>
                                <input type="text" class="form-control" name="height_ft" placeholder="e.g., 5'5&quot;"
                                       value="<?php echo isset($_POST['height_ft']) ? htmlspecialchars($_POST['height_ft']) : ''; ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>Weight (kg)</label>
                                <input type="number" step="0.01" class="form-control" name="weight_kg"
                                       placeholder="Enter weight in kilograms"
                                       value="<?php echo isset($_POST['weight_kg']) ? htmlspecialchars($_POST['weight_kg']) : ''; ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>Eyes Color</label>
                                <select class="form-select" name="eyes_color">
                                    <option value="">Select Eye Color</option>
                                    <option value="Black" <?php echo (isset($_POST['eyes_color']) && $_POST['eyes_color'] == 'Black') ? 'selected' : ''; ?>>Black</option>
                                    <option value="Brown" <?php echo (isset($_POST['eyes_color']) && $_POST['eyes_color'] == 'Brown') ? 'selected' : ''; ?>>Brown</option>
                                    <option value="Dark Brown" <?php echo (isset($_POST['eyes_color']) && $_POST['eyes_color'] == 'Dark Brown') ? 'selected' : ''; ?>>Dark Brown</option>
                                    <option value="Hazel" <?php echo (isset($_POST['eyes_color']) && $_POST['eyes_color'] == 'Hazel') ? 'selected' : ''; ?>>Hazel</option>
                                    <option value="Blue" <?php echo (isset($_POST['eyes_color']) && $_POST['eyes_color'] == 'Blue') ? 'selected' : ''; ?>>Blue</option>
                                    <option value="Gray" <?php echo (isset($_POST['eyes_color']) && $_POST['eyes_color'] == 'Gray') ? 'selected' : ''; ?>>Gray</option>
                                </select>
                            </div>
                            
                            <div class="form-field">
                                <label>Hair Color</label>
                                <select class="form-select" name="hair_color">
                                    <option value="">Select Hair Color</option>
                                    <option value="Black" <?php echo (isset($_POST['hair_color']) && $_POST['hair_color'] == 'Black') ? 'selected' : ''; ?>>Black</option>
                                    <option value="Brown" <?php echo (isset($_POST['hair_color']) && $_POST['hair_color'] == 'Brown') ? 'selected' : ''; ?>>Brown</option>
                                    <option value="Dark Brown" <?php echo (isset($_POST['hair_color']) && $_POST['hair_color'] == 'Dark Brown') ? 'selected' : ''; ?>>Dark Brown</option>
                                    <option value="Gray" <?php echo (isset($_POST['hair_color']) && $_POST['hair_color'] == 'Gray') ? 'selected' : ''; ?>>Gray</option>
                                    <option value="White" <?php echo (isset($_POST['hair_color']) && $_POST['hair_color'] == 'White') ? 'selected' : ''; ?>>White</option>
                                    <option value="Bald" <?php echo (isset($_POST['hair_color']) && $_POST['hair_color'] == 'Bald') ? 'selected' : ''; ?>>Bald</option>
                                </select>
                            </div>
                            
                            <div class="form-field">
                                <label>Built</label>
                                <select class="form-select" name="built">
                                    <option value="">Select</option>
                                    <option value="Small" <?php echo (isset($_POST['built']) && $_POST['built'] == 'Small') ? 'selected' : ''; ?>>Small</option>
                                    <option value="Medium" <?php echo (isset($_POST['built']) && $_POST['built'] == 'Medium') ? 'selected' : ''; ?>>Medium</option>
                                    <option value="Large" <?php echo (isset($_POST['built']) && $_POST['built'] == 'Large') ? 'selected' : ''; ?>>Large</option>
                                </select>
                            </div>
                            
                            <div class="form-field">
                                <label>Complexion</label>
                                <select class="form-select" name="complexion">
                                    <option value="">Select Complexion</option>
                                    <option value="Fair" <?php echo (isset($_POST['complexion']) && $_POST['complexion'] == 'Fair') ? 'selected' : ''; ?>>Fair</option>
                                    <option value="Light Brown" <?php echo (isset($_POST['complexion']) && $_POST['complexion'] == 'Light Brown') ? 'selected' : ''; ?>>Light Brown</option>
                                    <option value="Brown" <?php echo (isset($_POST['complexion']) && $_POST['complexion'] == 'Brown') ? 'selected' : ''; ?>>Brown</option>
                                    <option value="Dark Brown" <?php echo (isset($_POST['complexion']) && $_POST['complexion'] == 'Dark Brown') ? 'selected' : ''; ?>>Dark Brown</option>
                                    <option value="Dark" <?php echo (isset($_POST['complexion']) && $_POST['complexion'] == 'Dark') ? 'selected' : ''; ?>>Dark</option>
                                </select>
                            </div>
                            
                            <div class="form-field full-width">
                                <label>Distinguishing Marks/Tattoo</label>
                                <textarea class="form-control" name="distinguishing_marks" rows="2" 
                                          placeholder="Describe any tattoos, scars, or distinguishing marks"><?php echo isset($_POST['distinguishing_marks']) ? htmlspecialchars($_POST['distinguishing_marks']) : ''; ?></textarea>
                            </div>
                            
                            <!-- ARREST FIELDS -->
                            <div class="form-field full-width">
                                <label>Previous Arrest Record<span style="color: #ef4444;">*</span></label>
                                <select class="form-select" name="previous_arrest_record" required>
                                    <option value="">Select</option>
                                    <option value="No Previous Record" <?php echo (isset($_POST['previous_arrest_record']) && $_POST['previous_arrest_record'] == 'No Previous Record') ? 'selected' : ''; ?>>No Previous Record</option>
                                    <option value="With Previous Record" <?php echo (isset($_POST['previous_arrest_record']) && $_POST['previous_arrest_record'] == 'With Previous Record') ? 'selected' : ''; ?>>With Previous Record</option>
                                </select>
                            </div>
                            
                            <div class="form-field full-width">
                                <label>Specific Charge<span style="color: #ef4444;">*</span></label>
                                <select class="form-select" name="specific_charge" required>
                                    <option value="">Select Charge</option>
                                    <option value="Violation of RA 9165 (Comprehensive Dangerous Drugs Act)" <?php echo (isset($_POST['specific_charge']) && $_POST['specific_charge'] == 'Violation of RA 9165 (Comprehensive Dangerous Drugs Act)') ? 'selected' : ''; ?>>Violation of RA 9165 (Comprehensive Dangerous Drugs Act)</option>
                                    <option value="Illegal Possession of Firearms" <?php echo (isset($_POST['specific_charge']) && $_POST['specific_charge'] == 'Illegal Possession of Firearms') ? 'selected' : ''; ?>>Illegal Possession of Firearms</option>
                                    <option value="Theft/Robbery" <?php echo (isset($_POST['specific_charge']) && $_POST['specific_charge'] == 'Theft/Robbery') ? 'selected' : ''; ?>>Theft/Robbery</option>
                                    <option value="Physical Injury" <?php echo (isset($_POST['specific_charge']) && $_POST['specific_charge'] == 'Physical Injury') ? 'selected' : ''; ?>>Physical Injury</option>
                                    <option value="Homicide/Murder" <?php echo (isset($_POST['specific_charge']) && $_POST['specific_charge'] == 'Homicide/Murder') ? 'selected' : ''; ?>>Homicide/Murder</option>
                                    <option value="Other" <?php echo (isset($_POST['specific_charge']) && $_POST['specific_charge'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="form-field full-width">
                                <label>Date/Time/Place of Arrest<span style="color: #ef4444;">*</span></label>
                                <input type="datetime-local" class="form-control" name="date_time_place_of_arrest" required 
                                       value="<?php echo isset($_POST['date_time_place_of_arrest']) ? htmlspecialchars($_POST['date_time_place_of_arrest']) : ''; ?>">
                                <small class="text-muted">Format: YYYY-MM-DD HH:MM (e.g., 2024-03-24 14:30)</small>
                            </div>
                            
                            <div class="form-field full-width">
                                <label>Name of Arresting Officer<span style="color: #ef4444;">*</span></label>
                                <input type="text" class="form-control" name="arresting_officer_name" required 
                                       placeholder="Enter arresting officer's name" value="<?php echo isset($_POST['arresting_officer_name']) ? htmlspecialchars($_POST['arresting_officer_name']) : ''; ?>">
                            </div>
                            
                            <div class="form-field full-width">
                                <label>Unit/Office of Arresting Officer<span style="color: #ef4444;">*</span></label>
                                <select class="form-select" name="arresting_officer_unit" required>
                                    <option value="">Select Unit/Office</option>
                                    <option value="Manolo Fortich MPS" <?php echo (isset($_POST['arresting_officer_unit']) && $_POST['arresting_officer_unit'] == 'Manolo Fortich MPS') ? 'selected' : ''; ?>>Manolo Fortich MPS</option>
                                    <option value="Bukidnon PPO" <?php echo (isset($_POST['arresting_officer_unit']) && $_POST['arresting_officer_unit'] == 'Bukidnon PPO') ? 'selected' : ''; ?>>Bukidnon PPO</option>
                                    <option value="PDEA" <?php echo (isset($_POST['arresting_officer_unit']) && $_POST['arresting_officer_unit'] == 'PDEA') ? 'selected' : ''; ?>>PDEA</option>
                                    <option value="NBI" <?php echo (isset($_POST['arresting_officer_unit']) && $_POST['arresting_officer_unit'] == 'NBI') ? 'selected' : ''; ?>>NBI</option>
                                    <option value="Other" <?php echo (isset($_POST['arresting_officer_unit']) && $_POST['arresting_officer_unit'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- II. FAMILY BACKGROUND -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-users"></i> II. FAMILY BACKGROUND
                    </div>
                    <div class="section-content">
                        <table class="form-table">
                             <thead>
                                <th style="width: 100px;"></th>
                                <th>Father</th>
                                <th>Mother</th>
                             </thead>
                             <tbody>
                                  <tr>
                                    <td><strong>Name</strong></td>
                                     <td><input type="text" class="form-control" name="father_name" placeholder="Father's full name" value="<?php echo isset($_POST['father_name']) ? htmlspecialchars($_POST['father_name']) : ''; ?>"></td>
                                     <td><input type="text" class="form-control" name="mother_name" placeholder="Mother's full name" value="<?php echo isset($_POST['mother_name']) ? htmlspecialchars($_POST['mother_name']) : ''; ?>"></td>
                                  </tr>
                                  <tr>
                                     <td><strong>Known Address</strong></td>
                                     <td><input type="text" class="form-control" name="father_address" placeholder="Father's address" value="<?php echo isset($_POST['father_address']) ? htmlspecialchars($_POST['father_address']) : ''; ?>"></td>
                                     <td><input type="text" class="form-control" name="mother_address" placeholder="Mother's address" value="<?php echo isset($_POST['mother_address']) ? htmlspecialchars($_POST['mother_address']) : ''; ?>"></td>
                                  </tr>
                                  <tr>
                                     <td><strong>Date of Birth</strong></td>
                                     <td><input type="date" class="form-control" name="father_dob" value="<?php echo isset($_POST['father_dob']) ? htmlspecialchars($_POST['father_dob']) : ''; ?>"></td>
                                     <td><input type="date" class="form-control" name="mother_dob" value="<?php echo isset($_POST['mother_dob']) ? htmlspecialchars($_POST['mother_dob']) : ''; ?>"></td>
                                  </tr>
                                  <tr>
                                     <td><strong>Age</strong></td>
                                     <td><input type="number" class="form-control" name="father_age" placeholder="Age" value="<?php echo isset($_POST['father_age']) ? htmlspecialchars($_POST['father_age']) : ''; ?>"></td>
                                     <td><input type="number" class="form-control" name="mother_age" placeholder="Age" value="<?php echo isset($_POST['mother_age']) ? htmlspecialchars($_POST['mother_age']) : ''; ?>"></td>
                                  </tr>
                                  <tr>
                                     <td><strong>Occupation</strong></td>
                                     <td><input type="text" class="form-control" name="father_occupation" placeholder="Occupation" value="<?php echo isset($_POST['father_occupation']) ? htmlspecialchars($_POST['father_occupation']) : ''; ?>"></td>
                                     <td><input type="text" class="form-control" name="mother_occupation" placeholder="Occupation" value="<?php echo isset($_POST['mother_occupation']) ? htmlspecialchars($_POST['mother_occupation']) : ''; ?>"></td>
                                  </tr>
                             </tbody>
                          </table>
                        
                        <div style="margin-top: 20px;">
                            <table class="form-table">
                                 <thead>
                                    <th colspan="4">Spouse</th>
                                 </thead>
                                 <tr>
                                    <th>Name</th>
                                    <th>Age</th>
                                    <th>Occupation</th>
                                    <th>Address</th>
                                 </tr>
                                 <tr>
                                    <td><input type="text" class="form-control" name="spouse_name" placeholder="Spouse's name" value="<?php echo isset($_POST['spouse_name']) ? htmlspecialchars($_POST['spouse_name']) : ''; ?>"></td>
                                    <td><input type="number" class="form-control" name="spouse_age" placeholder="Age" value="<?php echo isset($_POST['spouse_age']) ? htmlspecialchars($_POST['spouse_age']) : ''; ?>"></td>
                                    <td><input type="text" class="form-control" name="spouse_occupation" placeholder="Occupation" value="<?php echo isset($_POST['spouse_occupation']) ? htmlspecialchars($_POST['spouse_occupation']) : ''; ?>"></td>
                                    <td><input type="text" class="form-control" name="spouse_address" placeholder="Address" value="<?php echo isset($_POST['spouse_address']) ? htmlspecialchars($_POST['spouse_address']) : ''; ?>"></td>
                                 </tr>
                              </table>
                        </div>
                        
                        <div style="margin-top: 20px;">
                            <label><strong>Siblings</strong></label>
                            <div id="siblingsContainer">
                                <table class="form-table" id="siblingsTable">
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
                                         <tr>
                                            <td><input type="text" class="form-control" name="sibling_name[]" placeholder="Sibling name"></td>
                                            <td><input type="number" class="form-control" name="sibling_age[]" placeholder="Age"></td>
                                            <td><input type="text" class="form-control" name="sibling_occupation[]" placeholder="Occupation"></td>
                                            <td><input type="text" class="form-control" name="sibling_status[]" placeholder="Status (e.g., Single, Married)"></td>
                                            <td><input type="text" class="form-control" name="sibling_address[]" placeholder="Address"></td>
                                         </tr>
                                    </tbody>
                                 </table>
                                <button type="button" class="btn btn-secondary btn-sm" style="margin-top: 10px;" onclick="addSiblingRow()">
                                    <i class="fas fa-plus"></i> Add Sibling
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- III. TACTICAL INFORMATION -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-user-secret"></i> III. TACTICAL INFORMATION
                    </div>
                    <div class="section-content">
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Drugs Involved</label>
                                <select class="form-select" name="drugs_involved">
                                    <option value="">Select Drug Type</option>
                                    <option value="Shabu" <?php echo (isset($_POST['drugs_involved']) && $_POST['drugs_involved'] == 'Shabu') ? 'selected' : ''; ?>>Shabu (Methamphetamine)</option>
                                    <option value="Marijuana" <?php echo (isset($_POST['drugs_involved']) && $_POST['drugs_involved'] == 'Marijuana') ? 'selected' : ''; ?>>Marijuana (Cannabis)</option>
                                    <option value="Ecstasy" <?php echo (isset($_POST['drugs_involved']) && $_POST['drugs_involved'] == 'Ecstasy') ? 'selected' : ''; ?>>Ecstasy (MDMA)</option>
                                    <option value="Cocaine" <?php echo (isset($_POST['drugs_involved']) && $_POST['drugs_involved'] == 'Cocaine') ? 'selected' : ''; ?>>Cocaine</option>
                                    <option value="Other" <?php echo (isset($_POST['drugs_involved']) && $_POST['drugs_involved'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="form-field">
                                <label>Vehicles Used</label>
                                <input type="text" class="form-control" name="vehicles_used" 
                                       placeholder="Type and plate number"
                                       value="<?php echo isset($_POST['vehicles_used']) ? htmlspecialchars($_POST['vehicles_used']) : ''; ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>Armaments</label>
                                <input type="text" class="form-control" name="armaments" 
                                       placeholder="Type of weapons/firearms"
                                       value="<?php echo isset($_POST['armaments']) ? htmlspecialchars($_POST['armaments']) : ''; ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>Companion/s During Arrest</label>
                                <input type="text" class="form-control" name="companions_arrest" 
                                       placeholder="Names of companions"
                                       value="<?php echo isset($_POST['companions_arrest']) ? htmlspecialchars($_POST['companions_arrest']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- IV. RECRUITMENT SUMMARY -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-user-plus"></i> IV. RECRUITMENT SUMMARY
                    </div>
                    <div class="section-content">
                        <div class="form-field full-width">
                            <textarea class="form-control" name="recruitment_summary" rows="3" 
                                      placeholder="Describe how the suspect was recruited..."><?php echo isset($_POST['recruitment_summary']) ? htmlspecialchars($_POST['recruitment_summary']) : ''; ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- V. MODUS OPERANDI -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-exchange-alt"></i> V. MODUS OPERANDI
                    </div>
                    <div class="section-content">
                        <div class="form-field full-width">
                            <textarea class="form-control" name="modus_operandi" rows="3" 
                                      placeholder="Describe the modus operandi..."><?php echo isset($_POST['modus_operandi']) ? htmlspecialchars($_POST['modus_operandi']) : ''; ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- VI. CI MATTERS -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-user-secret"></i> VI. CI MATTERS
                    </div>
                    <div class="section-content">
                        <div class="form-grid">
                            <div class="form-field full-width">
                                <label>CI Matters</label>
                                <textarea class="form-control" name="ci_matters" rows="2" 
                                          placeholder="AFP/PNP Government Officer's Instrument"><?php echo isset($_POST['ci_matters']) ? htmlspecialchars($_POST['ci_matters']) : 'NONE'; ?></textarea>
                            </div>
                            
                            <div class="form-field full-width">
                                <label>Other Revelations</label>
                                <textarea class="form-control" name="other_revelations" rows="2" 
                                          placeholder="Other information revealed"><?php echo isset($_POST['other_revelations']) ? htmlspecialchars($_POST['other_revelations']) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VII. RECOMMENDATION -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-exchange-alt"></i> VII. RECOMMENDATION
                    </div>
                    <div class="section-content">
                        <div class="form-field full-width">
                            <textarea class="form-control" name="recommendation" rows="3" 
                                      placeholder="Describe the recommendation..."><?php echo isset($_POST['recommendation']) ? htmlspecialchars($_POST['recommendation']) : ''; ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-bar">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Profile
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-undo"></i> Reset Form
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="form-footer">
            <div>
                <small>Department of the Interior and Local Government | PHILIPPINE NATIONAL POLICE<br>
                BUKIDNON POLICE PROVINCIAL OFFICE | MANOLO FORTICH POLICE STATION<br>
                All data is confidential and for official use only.</small>
            </div>
        </div>
    </div>

    <script>
        // Picture upload preview
        document.getElementById('profilePicture').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var pictureBox = document.getElementById('picturePreview');
                    pictureBox.innerHTML = '<img src="' + e.target.result + '" alt="Profile Picture">';
                    
                    // Auto-submit the picture form
                    document.getElementById('pictureForm').submit();
                }
                reader.readAsDataURL(this.files[0]);
            }
        });

        function addSiblingRow() {
            const table = document.getElementById('siblingsTable').getElementsByTagName('tbody')[0];
            const newRow = table.insertRow();
            
            const cells = [];
            for (let i = 0; i < 5; i++) {
                cells.push(newRow.insertCell(i));
            }
            
            cells[0].innerHTML = '<input type="text" class="form-control" name="sibling_name[]" placeholder="Sibling name">';
            cells[1].innerHTML = '<input type="number" class="form-control" name="sibling_age[]" placeholder="Age">';
            cells[2].innerHTML = '<input type="text" class="form-control" name="sibling_occupation[]" placeholder="Occupation">';
            cells[3].innerHTML = '<input type="text" class="form-control" name="sibling_status[]" placeholder="Status">';
            cells[4].innerHTML = '<input type="text" class="form-control" name="sibling_address[]" placeholder="Address">';
        }
        
        // Auto-calculate age from date of birth
        document.addEventListener('DOMContentLoaded', function() {
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
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>