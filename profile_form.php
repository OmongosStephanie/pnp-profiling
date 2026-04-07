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

// Get barangay parameter from URL (for return navigation)
$barangay = isset($_GET['barangay']) ? $_GET['barangay'] : '';

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
    
    $check = getimagesize($_FILES['profile_picture']['tmp_name']);
    if ($check !== false) {
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
        $db->beginTransaction();
        
        $profile_picture = isset($_SESSION['profile_picture']) ? $_SESSION['profile_picture'] : null;
        
        // Process position_roles
        if (isset($_POST['position_roles']) && is_array($_POST['position_roles'])) {
            $position_roles = implode(', ', $_POST['position_roles']);
        } elseif (isset($_POST['position_roles_other']) && !empty($_POST['position_roles_other'])) {
            $position_roles = $_POST['position_roles_other'];
        } else {
            $position_roles = '';
        }
        
        // Process drug types
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
            previous_arrest_record, previous_arrest, specific_charge, date_time_place_of_arrest,
            arresting_officer, arresting_unit, drugs_involved,
            source_relationship, source_address, source_name, source_nickname, 
            source_full_address, source_other_drugs, subgroup_name, specific_aor,
            other_source_name, other_source_alias, other_source_details, 
            drugs_pushed, other_drugs_pushed, profile_picture,
            vehicles_used, armaments, companions_arrest, recruitment_summary, 
            modus_operandi, organizational_structure, ci_matters, other_revelations, 
            recommendation, created_by, status,
            father_name, father_address, father_dob, father_age, father_occupation,
            mother_name, mother_address, mother_dob, mother_age, mother_occupation,
            spouse_name, spouse_age, spouse_birthday, spouse_occupation, spouse_address
        ) VALUES (
            :full_name, :alias, :group_affiliation, :position_roles, :age, :sex, :dob, :pob,
            :educational_attainment, :occupation, :company_office, :technical_skills,
            :ethnic_group, :languages, :present_address, :provincial_address,
            :civil_status, :citizenship, :religion, :height_cm, :weight_kg, :height_ft,
            :eyes_color, :hair_color, :built, :complexion, :distinguishing_marks,
            :previous_arrest_record, :previous_arrest, :specific_charge, :date_time_place_of_arrest,
            :arresting_officer, :arresting_unit, :drugs_involved,
            :source_relationship, :source_address, :source_name, :source_nickname,
            :source_full_address, :source_other_drugs, :subgroup_name, :specific_aor,
            :other_source_name, :other_source_alias, :other_source_details,
            :drugs_pushed, :other_drugs_pushed, :profile_picture,
            :vehicles_used, :armaments, :companions_arrest, :recruitment_summary,
            :modus_operandi, :organizational_structure, :ci_matters, :other_revelations,
            :recommendation, :created_by, :status,
            :father_name, :father_address, :father_dob, :father_age, :father_occupation,
            :mother_name, :mother_address, :mother_dob, :mother_age, :mother_occupation,
            :spouse_name, :spouse_age, :spouse_birthday, :spouse_occupation, :spouse_address
        )";
        
        $stmt = $db->prepare($query);
        
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
            ':height_cm' => isset($_POST['height_cm']) && $_POST['height_cm'] !== '' ? $_POST['height_cm'] : null,
            ':weight_kg' => isset($_POST['weight_kg']) && $_POST['weight_kg'] !== '' ? $_POST['weight_kg'] : null,
            ':height_ft' => $_POST['height_ft'] ?? '',
            ':eyes_color' => $_POST['eyes_color'] ?? '',
            ':hair_color' => $_POST['hair_color'] ?? '',
            ':built' => $_POST['built'] ?? '',
            ':complexion' => $_POST['complexion'] ?? '',
            ':distinguishing_marks' => $_POST['distinguishing_marks'] ?? '',
            ':previous_arrest_record' => $_POST['previous_arrest_record'] ?? '',
            ':previous_arrest' => $_POST['previous_arrest_record'] ?? '',
            ':specific_charge' => $_POST['specific_charge'] ?? '',
            ':date_time_place_of_arrest' => $_POST['date_time_place_of_arrest'] ?? null,
            ':arresting_officer' => $_POST['arresting_officer_name'] ?? '',
            ':arresting_unit' => $_POST['arresting_officer_unit'] ?? '',
            ':drugs_involved' => $drug_types,
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
            ':status' => $_POST['status'] ?? 'active',
            // Parent fields
            ':father_name' => $_POST['father_name'] ?? '',
            ':father_address' => $_POST['father_address'] ?? '',
            ':father_dob' => $_POST['father_dob'] ?? null,
            ':father_age' => $_POST['father_age'] ?? null,
            ':father_occupation' => $_POST['father_occupation'] ?? '',
            ':mother_name' => $_POST['mother_name'] ?? '',
            ':mother_address' => $_POST['mother_address'] ?? '',
            ':mother_dob' => $_POST['mother_dob'] ?? null,
            ':mother_age' => $_POST['mother_age'] ?? null,
            ':mother_occupation' => $_POST['mother_occupation'] ?? '',
            ':spouse_name' => $_POST['spouse_name'] ?? '',
            ':spouse_age' => $_POST['spouse_age'] ?? null,
            ':spouse_birthday' => $_POST['spouse_birthday'] ?? null,
            ':spouse_occupation' => $_POST['spouse_occupation'] ?? '',
            ':spouse_address' => $_POST['spouse_address'] ?? '',
        ];
        
        $stmt->execute($params);
        
        $profile_id = $db->lastInsertId();
        
        // Insert siblings
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
        
        unset($_SESSION['profile_picture']);
        $_SESSION['success_message'] = "Profile saved successfully!";
        
        // Redirect back to barangay page if barangay exists
        if (!empty($barangay)) {
            header("Location: barangay_profiles.php?barangay=" . urlencode($barangay));
        } else {
            header("Location: dashboard.php");
        }
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>PNP Biographical Profiling System - Profile Form</title>
    
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
            background: #f0f2f5;
            color: #1e293b;
            line-height: 1.5;
            padding: 20px;
        }

        .form-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        /* Clean Header Design */
        .pnp-header {
            background: linear-gradient(135deg, #0a2f4d 0%, #0c3d62 100%);
            color: white;
            padding: 25px 30px;
            text-align: center;
            border-bottom: 4px solid #c9a959;
        }
        
        .org-name {
            margin-bottom: 5px;
        }
        
        .org-name .dilg {
            font-size: 11px;
            font-weight: 300;
            letter-spacing: 0.5px;
            color: #b0c4de;
        }
        
        .org-name .pnp {
            font-size: 24px;
            font-weight: 700;
            color: white;
            letter-spacing: 1px;
        }
        
        .org-name .provincial {
            font-size: 14px;
            font-weight: 500;
            color: #c9a959;
        }
        
        .station-details {
            margin-top: 8px;
        }
        
        .station-name {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 3px;
        }
        
        .station-address, .station-phone {
            font-size: 12px;
            color: #b0c4de;
        }
        
        .station-phone i {
            color: #c9a959;
            margin-right: 4px;
        }
        
        .user-info {
            margin-top: 12px;
            display: inline-block;
            background: rgba(255,255,255,0.1);
            padding: 6px 20px;
            border-radius: 30px;
            font-size: 12px;
        }
        
        .user-info i {
            color: #c9a959;
            margin-right: 6px;
        }

        .main-content {
            padding: 25px;
        }

        .form-title {
            text-align: center;
            margin-bottom: 25px;
        }

        .form-title h2 {
            font-size: 22px;
            font-weight: 700;
            color: #0a2f4d;
            margin-bottom: 5px;
        }

        .form-title p {
            font-size: 12px;
            color: #64748b;
        }

        .photo-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
        }

        .photo-container {
            text-align: center;
        }

        .photo-box {
            width: 130px;
            height: 130px;
            border: 3px solid #0a2f4d;
            border-radius: 8px;
            overflow: hidden;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
        }

        .photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-box i {
            font-size: 60px;
            color: #94a3b8;
        }

        .photo-label {
            font-size: 10px;
            color: #475569;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 5px;
        }

        .btn-upload {
            background: #0a2f4d;
            color: white;
            border: none;
            padding: 5px 12px;
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
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            margin-bottom: 20px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }

        .section-title {
            background: #f8fafc;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 14px;
            color: #0a2f4d;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i {
            color: #c9a959;
            font-size: 16px;
        }

        .section-content {
            padding: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
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
            font-size: 11px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control, .form-select {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            color: #1e293b;
            background: white;
            transition: all 0.2s;
            width: 100%;
        }

        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: #c9a959;
            box-shadow: 0 0 0 3px rgba(201, 169, 89, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 70px;
        }

        .form-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .form-table th {
            background: #f8fafc;
            padding: 10px 12px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            font-size: 11px;
            text-transform: uppercase;
            border: 1px solid #e2e8f0;
        }

        .form-table td {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
        }

        .form-table .form-control {
            padding: 6px 10px;
            font-size: 12px;
        }

        .checkbox-container {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
        }

        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-check-input {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .form-check-label {
            font-size: 13px;
            color: #334155;
        }

        .btn {
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-sm {
            padding: 5px 12px;
            font-size: 11px;
        }

        .btn-primary {
            background: #0a2f4d;
            color: white;
        }

        .btn-primary:hover {
            background: #c9a959;
            color: #0a2f4d;
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
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
            gap: 15px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            margin-top: 20px;
            border-radius: 0 0 12px 12px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
        }

        .alert-success {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            color: #065f46;
        }

        .alert-danger {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            color: #991b1b;
        }

        .form-footer {
            text-align: center;
            padding: 15px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 10px;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .org-name .pnp {
                font-size: 18px;
            }
            
            .org-name .provincial {
                font-size: 11px;
            }
            
            .station-name {
                font-size: 14px;
            }
            
            .photo-section {
                justify-content: center;
            }
            
            .action-bar {
                flex-direction: column;
                align-items: center;
            }
            
            .action-bar .btn {
                width: 100%;
                justify-content: center;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .main-content {
                padding: 15px;
            }
            
            .section-content {
                padding: 15px;
            }
            
            .form-table {
                font-size: 11px;
            }
            
            .form-table th, .form-table td {
                padding: 6px 8px;
            }
        }

        @media print {
            .action-bar, .btn-upload, .photo-section form, .no-print {
                display: none !important;
            }
            
            .form-container {
                box-shadow: none;
                margin: 0;
                padding: 0;
            }
            
            .pnp-header {
                background: #0a2f4d !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <!-- Clean Header -->
        <div class="pnp-header">
            <div class="org-name">
                <div class="dilg">Republic of the Philippines</div>
                <div class="pnp">PHILIPPINE NATIONAL POLICE</div>
                <div class="provincial">Bukidnon Police Provincial Office</div>
            </div>
            <div class="station-details">
                <div class="station-name">MANOLO FORTICH POLICE STATION</div>
                <div class="station-address">Manolo Fortich, Bukidnon, 8703</div>
                <div class="station-phone"><i class="fas fa-phone"></i> (088-228) 2244</div>
            </div>
            <div class="user-info">
                <i class="fas fa-user-shield"></i>
                <span><?php echo $_SESSION['rank'] . ' ' . $_SESSION['full_name']; ?></span>
            </div>
        </div>

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
                <h2>BIOGRAPHICAL DATA FORM</h2>
                <p>All information is confidential and for official use only</p>
            </div>

            <div class="photo-section">
                <div class="photo-container">
                    <div class="photo-box" id="picturePreview">
                        <?php if (isset($_SESSION['profile_picture'])): ?>
                            <img src="<?php echo $_SESSION['profile_picture']; ?>" alt="Profile Picture">
                        <?php else: ?>
                            <i class="fas fa-user-circle"></i>
                        <?php endif; ?>
                    </div>
                    <form method="POST" enctype="multipart/form-data" id="pictureForm">
                        <input type="file" name="profile_picture" id="profilePicture" class="file-input" accept="image/*">
                        <button type="button" class="btn-upload" onclick="document.getElementById('profilePicture').click();">
                            <i class="fas fa-camera"></i> Upload Photo
                        </button>
                    </form>
                    <div class="photo-label">2x2 Official Photo</div>
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
                            
                            <div class="form-field">
                                <label>Alias <span style="color: #ef4444;">*</span></label>
                                <input type="text" class="form-control" name="alias" required 
                                       placeholder="Enter alias" value="<?php echo isset($_POST['alias']) ? htmlspecialchars($_POST['alias']) : ''; ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>Group/Gang Affiliation</label>
                                <input type="text" class="form-control" name="group_affiliation" 
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
                                    <input type="text" class="form-control" style="margin-top: 10px;" name="position_roles_other" placeholder="Other position/s">
                                </div>
                            </div>
                            
                            <div class="form-field">
                                <label>Age <span style="color: #ef4444;">*</span></label>
                                <input type="number" class="form-control" name="age" required placeholder="Age" value="<?php echo isset($_POST['age']) ? htmlspecialchars($_POST['age']) : ''; ?>">
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
                                <input type="date" class="form-control" name="dob" required value="<?php echo isset($_POST['dob']) ? htmlspecialchars($_POST['dob']) : ''; ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>Place of Birth <span style="color: #ef4444;">*</span></label>
                                <input type="text" class="form-control" name="pob" required value="<?php echo isset($_POST['pob']) ? htmlspecialchars($_POST['pob']) : ''; ?>">
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
                                       value="<?php echo isset($_POST['company_office']) ? htmlspecialchars($_POST['company_office']) : ''; ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>Technical Skills</label>
                                <input type="text" class="form-control" name="technical_skills" 
                                       value="<?php echo isset($_POST['technical_skills']) ? htmlspecialchars($_POST['technical_skills']) : ''; ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>Ethnic Group</label>
                                <select class="form-select" name="ethnic_group">
                                    <option value="">Select</option>
                                    <option value="Cebuano" <?php echo (isset($_POST['ethnic_group']) && $_POST['ethnic_group'] == 'Cebuano') ? 'selected' : ''; ?>>Cebuano</option>
                                    <option value="Bisaya" <?php echo (isset($_POST['ethnic_group']) && $_POST['ethnic_group'] == 'Bisaya') ? 'selected' : ''; ?>>Bisaya</option>
                                    <option value="Ilocano" <?php echo (isset($_POST['ethnic_group']) && $_POST['ethnic_group'] == 'Ilocano') ? 'selected' : ''; ?>>Ilocano</option>
                                    <option value="Tagalog" <?php echo (isset($_POST['ethnic_group']) && $_POST['ethnic_group'] == 'Tagalog') ? 'selected' : ''; ?>>Tagalog</option>
                                    <option value="Bukidnon" <?php echo (isset($_POST['ethnic_group']) && $_POST['ethnic_group'] == 'Bukidnon') ? 'selected' : ''; ?>>Bukidnon</option>
                                    <option value="Other" <?php echo (isset($_POST['ethnic_group']) && $_POST['ethnic_group'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <!-- Languages/Dialects - Changed to Select Dropdown -->
                            <div class="form-field">
                                <label>Languages/Dialects</label>
                                <select class="form-select" name="languages">
                                    <option value="">Select Language/Dialect</option>
                                    <option value="Cebuano" <?php echo (isset($_POST['languages']) && $_POST['languages'] == 'Cebuano') ? 'selected' : ''; ?>>Cebuano</option>
                                    <option value="Bisaya" <?php echo (isset($_POST['languages']) && $_POST['languages'] == 'Bisaya') ? 'selected' : ''; ?>>Bisaya</option>
                                    <option value="Tagalog" <?php echo (isset($_POST['languages']) && $_POST['languages'] == 'Tagalog') ? 'selected' : ''; ?>>Tagalog</option>
                                    <option value="Ilocano" <?php echo (isset($_POST['languages']) && $_POST['languages'] == 'Ilocano') ? 'selected' : ''; ?>>Ilocano</option>
                                    <option value="English" <?php echo (isset($_POST['languages']) && $_POST['languages'] == 'English') ? 'selected' : ''; ?>>English</option>
                                    <option value="Waray" <?php echo (isset($_POST['languages']) && $_POST['languages'] == 'Waray') ? 'selected' : ''; ?>>Waray</option>
                                    <option value="Hiligaynon" <?php echo (isset($_POST['languages']) && $_POST['languages'] == 'Hiligaynon') ? 'selected' : ''; ?>>Hiligaynon</option>
                                    <option value="Kapampangan" <?php echo (isset($_POST['languages']) && $_POST['languages'] == 'Kapampangan') ? 'selected' : ''; ?>>Kapampangan</option>
                                    <option value="Pangasinan" <?php echo (isset($_POST['languages']) && $_POST['languages'] == 'Pangasinan') ? 'selected' : ''; ?>>Pangasinan</option>
                                    <option value="Bicolano" <?php echo (isset($_POST['languages']) && $_POST['languages'] == 'Bicolano') ? 'selected' : ''; ?>>Bicolano</option>
                                    <option value="Maranao" <?php echo (isset($_POST['languages']) && $_POST['languages'] == 'Maranao') ? 'selected' : ''; ?>>Maranao</option>
                                    <option value="Tausug" <?php echo (isset($_POST['languages']) && $_POST['languages'] == 'Tausug') ? 'selected' : ''; ?>>Tausug</option>
                                    <option value="Maguindanao" <?php echo (isset($_POST['languages']) && $_POST['languages'] == 'Maguindanao') ? 'selected' : ''; ?>>Maguindanao</option>
                                    <option value="Chavacano" <?php echo (isset($_POST['languages']) && $_POST['languages'] == 'Chavacano') ? 'selected' : ''; ?>>Chavacano</option>
                                    <option value="Other" <?php echo (isset($_POST['languages']) && $_POST['languages'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
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
                                    <option value="">Select</option>
                                    <option value="Roman Catholic" <?php echo (isset($_POST['religion']) && $_POST['religion'] == 'Roman Catholic') ? 'selected' : ''; ?>>Roman Catholic</option>
                                    <option value="Islam" <?php echo (isset($_POST['religion']) && $_POST['religion'] == 'Islam') ? 'selected' : ''; ?>>Islam</option>
                                    <option value="Iglesia Ni Cristo" <?php echo (isset($_POST['religion']) && $_POST['religion'] == 'Iglesia Ni Cristo') ? 'selected' : ''; ?>>Iglesia Ni Cristo</option>
                                    <option value="Born Again Christian" <?php echo (isset($_POST['religion']) && $_POST['religion'] == 'Born Again Christian') ? 'selected' : ''; ?>>Born Again Christian</option>
                                    <option value="Seventh Day Adventist" <?php echo (isset($_POST['religion']) && $_POST['religion'] == 'Seventh Day Adventist') ? 'selected' : ''; ?>>Seventh Day Adventist</option>
                                    <option value="Other" <?php echo (isset($_POST['religion']) && $_POST['religion'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="form-field">
                                <label>Height (ft/in)</label>
                                <input type="text" class="form-control" name="height_ft" placeholder="e.g., 5'5&quot;" value="<?php echo isset($_POST['height_ft']) ? htmlspecialchars($_POST['height_ft']) : ''; ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>Weight (kg)</label>
                                <input type="number" step="0.01" class="form-control" name="weight_kg" value="<?php echo isset($_POST['weight_kg']) ? htmlspecialchars($_POST['weight_kg']) : ''; ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>Eyes Color</label>
                                <input type="text" class="form-control" name="eyes_color" value="<?php echo isset($_POST['eyes_color']) ? htmlspecialchars($_POST['eyes_color']) : ''; ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>Hair Color</label>
                                <input type="text" class="form-control" name="hair_color" value="<?php echo isset($_POST['hair_color']) ? htmlspecialchars($_POST['hair_color']) : ''; ?>">
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
                                <input type="text" class="form-control" name="complexion" value="<?php echo isset($_POST['complexion']) ? htmlspecialchars($_POST['complexion']) : ''; ?>">
                            </div>
                            
                            <div class="form-field full-width">
                                <label>Distinguishing Marks/Tattoo</label>
                                <textarea class="form-control" name="distinguishing_marks" rows="2" 
                                          placeholder="Describe any tattoos, scars, or distinguishing marks"><?php echo isset($_POST['distinguishing_marks']) ? htmlspecialchars($_POST['distinguishing_marks']) : ''; ?></textarea>
                            </div>
                            
                            <div class="form-field full-width">
                                <label>Previous Arrest Record</label>
                                <select class="form-select" name="previous_arrest_record">
                                    <option value="">Select</option>
                                    <option value="No Previous Record" <?php echo (isset($_POST['previous_arrest_record']) && $_POST['previous_arrest_record'] == 'No Previous Record') ? 'selected' : ''; ?>>No Previous Record</option>
                                    <option value="With Previous Record" <?php echo (isset($_POST['previous_arrest_record']) && $_POST['previous_arrest_record'] == 'With Previous Record') ? 'selected' : ''; ?>>With Previous Record</option>
                                </select>
                            </div>
                            
                            <div class="form-field full-width">
                                <label>Specific Charge</label>
                                <select class="form-select" name="specific_charge">
                                    <option value="">Select Charge</option>
                                    <option value="Violation of RA 9165" <?php echo (isset($_POST['specific_charge']) && $_POST['specific_charge'] == 'Violation of RA 9165') ? 'selected' : ''; ?>>Violation of RA 9165</option>
                                    <option value="Illegal Possession of Firearms" <?php echo (isset($_POST['specific_charge']) && $_POST['specific_charge'] == 'Illegal Possession of Firearms') ? 'selected' : ''; ?>>Illegal Possession of Firearms</option>
                                    <option value="Theft/Robbery" <?php echo (isset($_POST['specific_charge']) && $_POST['specific_charge'] == 'Theft/Robbery') ? 'selected' : ''; ?>>Theft/Robbery</option>
                                    <option value="Other" <?php echo (isset($_POST['specific_charge']) && $_POST['specific_charge'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="form-field full-width">
                                <label>Date/Time/Place of Arrest</label>
                                <input type="datetime-local" class="form-control" name="date_time_place_of_arrest" 
                                       value="<?php echo isset($_POST['date_time_place_of_arrest']) ? htmlspecialchars($_POST['date_time_place_of_arrest']) : ''; ?>">
                            </div>
                            
                            <div class="form-field full-width">
                                <label>Name of Arresting Officer</label>
                                <input type="text" class="form-control" name="arresting_officer_name" 
                                       value="<?php echo isset($_POST['arresting_officer_name']) ? htmlspecialchars($_POST['arresting_officer_name']) : ''; ?>">
                            </div>
                            
                            <div class="form-field full-width">
                                <label>Unit/Office of Arresting Officer</label>
                                <input type="text" class="form-control" name="arresting_officer_unit" 
                                       value="<?php echo isset($_POST['arresting_officer_unit']) ? htmlspecialchars($_POST['arresting_officer_unit']) : ''; ?>">
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
                                <tr>
                                    <th style="width: 100px;"></th>
                                    <th>Father</th>
                                    <th>Mother</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Name</strong></td>
                                    <td><input type="text" class="form-control" name="father_name" placeholder="Father's full name" value="<?php echo isset($_POST['father_name']) ? htmlspecialchars($_POST['father_name']) : ''; ?>"></td>
                                    <td><input type="text" class="form-control" name="mother_name" placeholder="Mother's full name" value="<?php echo isset($_POST['mother_name']) ? htmlspecialchars($_POST['mother_name']) : ''; ?>"></td>
                                </tr>
                                <tr>
                                    <td><strong>Address</strong></td>
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
                                    <tr>
                                        <th colspan="5">Spouse</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <th style="width: 120px;">Name</th>
                                        <th style="width: 100px;">Age</th>
                                        <th style="width: 130px;">Birthday</th>
                                        <th>Occupation</th>
                                        <th>Address</th>
                                    </tr>
                                    <tr>
                                        <td><input type="text" class="form-control" name="spouse_name" placeholder="Spouse's name" value="<?php echo isset($_POST['spouse_name']) ? htmlspecialchars($_POST['spouse_name']) : ''; ?>"></td>
                                        <td><input type="number" class="form-control" name="spouse_age" placeholder="Age" value="<?php echo isset($_POST['spouse_age']) ? htmlspecialchars($_POST['spouse_age']) : ''; ?>"></td>
                                        <td><input type="date" class="form-control" name="spouse_birthday" placeholder="Birthday" value="<?php echo isset($_POST['spouse_birthday']) ? htmlspecialchars($_POST['spouse_birthday']) : ''; ?>"></td>
                                        <td><input type="text" class="form-control" name="spouse_occupation" placeholder="Occupation" value="<?php echo isset($_POST['spouse_occupation']) ? htmlspecialchars($_POST['spouse_occupation']) : ''; ?>"></td>
                                        <td><input type="text" class="form-control" name="spouse_address" placeholder="Address" value="<?php echo isset($_POST['spouse_address']) ? htmlspecialchars($_POST['spouse_address']) : ''; ?>"></td>
                                    </tr>
                                </tbody>
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
                                            <th style="width: 50px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (isset($_POST['sibling_name']) && is_array($_POST['sibling_name']) && count($_POST['sibling_name']) > 0): ?>
                                            <?php for ($i = 0; $i < count($_POST['sibling_name']); $i++): ?>
                                                <tr>
                                                    <td><input type="text" class="form-control" name="sibling_name[]" placeholder="Sibling name" value="<?php echo htmlspecialchars($_POST['sibling_name'][$i] ?? ''); ?>"></td>
                                                    <td><input type="number" class="form-control" name="sibling_age[]" placeholder="Age" value="<?php echo htmlspecialchars($_POST['sibling_age'][$i] ?? ''); ?>"></td>
                                                    <td><input type="text" class="form-control" name="sibling_occupation[]" placeholder="Occupation" value="<?php echo htmlspecialchars($_POST['sibling_occupation'][$i] ?? ''); ?>"></td>
                                                    <td><input type="text" class="form-control" name="sibling_status[]" placeholder="Status" value="<?php echo htmlspecialchars($_POST['sibling_status'][$i] ?? ''); ?>"></td>
                                                    <td><input type="text" class="form-control" name="sibling_address[]" placeholder="Address" value="<?php echo htmlspecialchars($_POST['sibling_address'][$i] ?? ''); ?>"></td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeSiblingRow(this)"><i class="fas fa-trash-alt"></i></button>
                                                    </td>
                                                </tr>
                                            <?php endfor; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td><input type="text" class="form-control" name="sibling_name[]" placeholder="Sibling name"></td>
                                                <td><input type="number" class="form-control" name="sibling_age[]" placeholder="Age"></td>
                                                <td><input type="text" class="form-control" name="sibling_occupation[]" placeholder="Occupation"></td>
                                                <td><input type="text" class="form-control" name="sibling_status[]" placeholder="Status"></td>
                                                <td><input type="text" class="form-control" name="sibling_address[]" placeholder="Address"></td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeSiblingRow(this)"><i class="fas fa-trash-alt"></i></button>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
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
                        <i class="fas fa-info-circle"></i> III. TACTICAL INFORMATION
                    </div>
                    <div class="section-content">
                        <div class="form-grid">
                            <div class="form-field full-width">
                                <label>Drugs Involved</label>
                                <div class="checkbox-container">
                                    <div class="checkbox-group">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="drug_types[]" value="Shabu" id="drugShabu"
                                                   <?php echo (isset($_POST['drug_types']) && is_array($_POST['drug_types']) && in_array('Shabu', $_POST['drug_types'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="drugShabu">Shabu</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="drug_types[]" value="Marijuana" id="drugMarijuana"
                                                   <?php echo (isset($_POST['drug_types']) && is_array($_POST['drug_types']) && in_array('Marijuana', $_POST['drug_types'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="drugMarijuana">Marijuana</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="drug_types[]" value="Ecstasy" id="drugEcstasy"
                                                   <?php echo (isset($_POST['drug_types']) && is_array($_POST['drug_types']) && in_array('Ecstasy', $_POST['drug_types'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="drugEcstasy">Ecstasy</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="drug_types[]" value="Cocaine" id="drugCocaine"
                                                   <?php echo (isset($_POST['drug_types']) && is_array($_POST['drug_types']) && in_array('Cocaine', $_POST['drug_types'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="drugCocaine">Cocaine</label>
                                        </div>
                                    </div>
                                    <input type="text" class="form-control" style="margin-top: 10px;" name="drug_types_other" placeholder="Other drugs">
                                </div>
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
                            
                            <!-- ADDITIONAL TACTICAL FIELDS -->
                            <div class="form-field full-width">
                                <label>Relationship/Address of Source</label>
                                <input type="text" class="form-control" name="source_relationship" 
                                       placeholder="Relationship to source and address"
                                       value="<?php echo isset($_POST['source_relationship']) ? htmlspecialchars($_POST['source_relationship']) : ''; ?>">
                            </div>
                            
                            <div class="form-field full-width">
                                <label>Name/Source of Drugs Involved</label>
                                <input type="text" class="form-control" name="source_name" 
                                       placeholder="Name of source of drugs"
                                       value="<?php echo isset($_POST['source_name']) ? htmlspecialchars($_POST['source_name']) : ''; ?>">
                            </div>
                            
                            <div class="form-field full-width">
                                <label>Address of Alleged Source</label>
                                <input type="text" class="form-control" name="source_address" 
                                       placeholder="Address of alleged source"
                                       value="<?php echo isset($_POST['source_address']) ? htmlspecialchars($_POST['source_address']) : ''; ?>">
                            </div>
                            
                            <div class="form-field full-width">
                                <label>Other Types of Drugs Supplied by Source</label>
                                <input type="text" class="form-control" name="source_other_drugs" 
                                       placeholder="Other drugs supplied by source"
                                       value="<?php echo isset($_POST['source_other_drugs']) ? htmlspecialchars($_POST['source_other_drugs']) : ''; ?>">
                            </div>
                            
                            <div class="form-field full-width">
                                <label>Subgroups and Specific AOR (Area of Responsibility)</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="subgroup_name" 
                                               placeholder="Subgroup name"
                                               value="<?php echo isset($_POST['subgroup_name']) ? htmlspecialchars($_POST['subgroup_name']) : ''; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="specific_aor" 
                                               placeholder="Specific AOR"
                                               value="<?php echo isset($_POST['specific_aor']) ? htmlspecialchars($_POST['specific_aor']) : ''; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-field full-width">
                                <label>Other Subject Known as Source</label>
                                <input type="text" class="form-control" name="other_source_name" 
                                       placeholder="Other subject known as source"
                                       value="<?php echo isset($_POST['other_source_name']) ? htmlspecialchars($_POST['other_source_name']) : ''; ?>">
                            </div>
                            
                            <div class="form-field full-width">
                                <label>Types of Drugs Pushed by Subject</label>
                                <div class="checkbox-container">
                                    <div class="checkbox-group">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="drugs_pushed[]" value="Shabu" id="pushShabu"
                                                   <?php echo (isset($_POST['drugs_pushed']) && is_array($_POST['drugs_pushed']) && in_array('Shabu', $_POST['drugs_pushed'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="pushShabu">Shabu</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="drugs_pushed[]" value="Marijuana" id="pushMarijuana"
                                                   <?php echo (isset($_POST['drugs_pushed']) && is_array($_POST['drugs_pushed']) && in_array('Marijuana', $_POST['drugs_pushed'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="pushMarijuana">Marijuana</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="drugs_pushed[]" value="Ecstasy" id="pushEcstasy"
                                                   <?php echo (isset($_POST['drugs_pushed']) && is_array($_POST['drugs_pushed']) && in_array('Ecstasy', $_POST['drugs_pushed'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="pushEcstasy">Ecstasy</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="drugs_pushed[]" value="Cocaine" id="pushCocaine"
                                                   <?php echo (isset($_POST['drugs_pushed']) && is_array($_POST['drugs_pushed']) && in_array('Cocaine', $_POST['drugs_pushed'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="pushCocaine">Cocaine</label>
                                        </div>
                                    </div>
                                    <input type="text" class="form-control" style="margin-top: 10px;" name="other_drugs_pushed" placeholder="Other drugs pushed by subject">
                                </div>
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
                    <?php if (!empty($barangay)): ?>
                        <a href="barangay_profiles.php?barangay=<?php echo urlencode($barangay); ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    <?php else: ?>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="form-footer">
            <small>Department of the Interior and Local Government | PHILIPPINE NATIONAL POLICE<br>
            BUKIDNON POLICE PROVINCIAL OFFICE | MANOLO FORTICH POLICE STATION<br>
            All data is confidential and for official use only.</small>
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
                    document.getElementById('pictureForm').submit();
                }
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Add sibling row
        function addSiblingRow() {
            const table = document.getElementById('siblingsTable').getElementsByTagName('tbody')[0];
            const newRow = table.insertRow();
            
            const cells = [];
            for (let i = 0; i < 6; i++) {
                cells.push(newRow.insertCell(i));
            }
            
            cells[0].innerHTML = '<input type="text" class="form-control" name="sibling_name[]" placeholder="Sibling name">';
            cells[1].innerHTML = '<input type="number" class="form-control" name="sibling_age[]" placeholder="Age">';
            cells[2].innerHTML = '<input type="text" class="form-control" name="sibling_occupation[]" placeholder="Occupation">';
            cells[3].innerHTML = '<input type="text" class="form-control" name="sibling_status[]" placeholder="Status">';
            cells[4].innerHTML = '<input type="text" class="form-control" name="sibling_address[]" placeholder="Address">';
            cells[5].innerHTML = '<button type="button" class="btn btn-danger btn-sm" onclick="removeSiblingRow(this)"><i class="fas fa-trash-alt"></i></button>';
        }
        
        // Remove sibling row
        function removeSiblingRow(button) {
            const row = button.closest('tr');
            const tableBody = row.parentNode;
            if (tableBody.rows.length > 1) {
                row.remove();
            } else {
                if (confirm('Clear all fields instead of removing the last row?')) {
                    const inputs = row.querySelectorAll('input');
                    inputs.forEach(input => input.value = '');
                }
            }
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
            
            // Auto-calculate spouse age from birthday (if birthday is entered)
            const spouseBirthdayInput = document.querySelector('input[name="spouse_birthday"]');
            const spouseAgeInput = document.querySelector('input[name="spouse_age"]');
            
            if (spouseBirthdayInput && spouseAgeInput) {
                spouseBirthdayInput.addEventListener('change', function() {
                    if (this.value) {
                        const birthDate = new Date(this.value);
                        const today = new Date();
                        let age = today.getFullYear() - birthDate.getFullYear();
                        const monthDiff = today.getMonth() - birthDate.getMonth();
                        
                        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                            age--;
                        }
                        
                        spouseAgeInput.value = age;
                    }
                });
            }
        });
        
        // Auto-hide alert messages after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>