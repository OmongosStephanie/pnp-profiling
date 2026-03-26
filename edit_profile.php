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

// Get barangay parameter from URL
$barangay = isset($_GET['barangay']) ? $_GET['barangay'] : '';

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
                $update_query = "UPDATE biographical_profiles SET profile_picture = :profile_picture WHERE id = :id";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->execute([
                    ':profile_picture' => $target_file,
                    ':id' => $id
                ]);
                
                $_SESSION['success_message'] = "Profile picture updated successfully!";
                
                // Redirect back to barangay page if barangay exists
                if (!empty($barangay)) {
                    header("Location: barangay_profiles.php?barangay=" . urlencode($barangay));
                } else {
                    header("Location: view_profile.php?id=" . $id);
                }
                exit();
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_FILES['profile_picture'])) {
    try {
        $db->beginTransaction();
        
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
            date_time_place_of_arrest = :date_time_place_of_arrest,
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
        
        $params = [
            ':id' => $id,
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
            ':previous_arrest' => $_POST['previous_arrest'] ?? '',
            ':specific_charge' => $_POST['specific_charge'] ?? '',
            ':date_time_place_of_arrest' => isset($_POST['date_time_place_of_arrest']) && $_POST['date_time_place_of_arrest'] !== '' ? $_POST['date_time_place_of_arrest'] : null,
            ':arresting_officer' => $_POST['arresting_officer'] ?? '',
            ':arresting_unit' => $_POST['arresting_unit'] ?? '',
            ':drugs_involved' => $drug_types,
            ':source_relationship' => $_POST['source_relationship'] ?? '',
            ':source_address' => $_POST['source_address'] ?? '',
            ':source_name' => $_POST['source_name'] ?? '',
            ':source_nickname' => $_POST['source_nickname'] ?? '',
            ':source_full_address' => $_POST['source_full_address'] ?? '',
            ':source_other_drugs' => $_POST['source_other_drugs'] ?? '',
            ':subgroup_name' => $_POST['subgroup_name'] ?? '',
            ':specific_aor' => $_POST['specific_aor'] ?? '',
            ':other_source_name' => $_POST['other_source_name'] ?? '',
            ':other_source_alias' => $_POST['other_source_alias'] ?? '',
            ':other_source_details' => $_POST['other_source_details'] ?? '',
            ':drugs_pushed' => isset($_POST['drugs_pushed']) && is_array($_POST['drugs_pushed']) ? implode(', ', $_POST['drugs_pushed']) : null,
            ':other_drugs_pushed' => $_POST['other_drugs_pushed'] ?? '',
            ':vehicles_used' => $_POST['vehicles_used'] ?? '',
            ':armaments' => $_POST['armaments'] ?? '',
            ':companions_arrest' => $_POST['companions_arrest'] ?? '',
            ':recruitment_summary' => $_POST['recruitment_summary'] ?? '',
            ':modus_operandi' => $_POST['modus_operandi'] ?? '',
            ':organizational_structure' => $_POST['organizational_structure'] ?? '',
            ':ci_matters' => $_POST['ci_matters'] ?? '',
            ':other_revelations' => $_POST['other_revelations'] ?? '',
            ':recommendation' => $_POST['recommendation'] ?? '',
            ':status' => $_POST['status'] ?? 'active'
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
                        ':name' => $_POST['sibling_name'][$i] ?? '',
                        ':age' => isset($_POST['sibling_age'][$i]) && $_POST['sibling_age'][$i] !== '' ? $_POST['sibling_age'][$i] : null,
                        ':occupation' => $_POST['sibling_occupation'][$i] ?? '',
                        ':status' => $_POST['sibling_status'][$i] ?? '',
                        ':address' => $_POST['sibling_address'][$i] ?? ''
                    ]);
                }
            }
        }
        
        $db->commit();
        
        $_SESSION['success_message'] = "Profile updated successfully!";
        
        // Redirect back to barangay page if barangay exists
        if (!empty($barangay)) {
            header("Location: barangay_profiles.php?barangay=" . urlencode($barangay));
        } else {
            header("Location: view_profile.php?id=" . $id);
        }
        exit();
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Error updating profile: " . $e->getMessage();
    }
}

// Parse arrays for checkboxes
$drugsPushedArray = !empty($profile['drugs_pushed']) ? explode(', ', $profile['drugs_pushed']) : [];
$drugTypesArray = !empty($profile['drugs_involved']) ? explode(', ', $profile['drugs_involved']) : [];
$positionRolesArray = !empty($profile['position_roles']) ? explode(', ', $profile['position_roles']) : [];

function isDrugPushed($drug, $drugsPushedArray) {
    return in_array($drug, $drugsPushedArray);
}

function inputValue($value) {
    return htmlspecialchars($value ?? '');
}

// Format date_time_place_of_arrest
$arrest_datetime = '';
if (!empty($profile['date_time_place_of_arrest'])) {
    $timestamp = strtotime($profile['date_time_place_of_arrest']);
    if ($timestamp) {
        $arrest_datetime = date('Y-m-d\TH:i', $timestamp);
    } else {
        $arrest_datetime = $profile['date_time_place_of_arrest'];
    }
}

// CANCEL LINK - ALWAYS GO BACK TO THE BARANGAY PAGE IF BARANGAY EXISTS
if (!empty($barangay)) {
    $cancel_link = "barangay_profiles.php?barangay=" . urlencode($barangay);
    $cancel_text = "Back to " . htmlspecialchars($barangay);
} else {
    $cancel_link = "view_profile.php?id=" . $id;
    $cancel_text = "Back to Profile";
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <style>
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
            max-width: 1100px;
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

        .user-info-header {
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

        .user-info-header i {
            color: #c9a959;
        }

        .user-info-header span {
            font-size: 14px;
            font-weight: 500;
        }

        .main-content {
            padding: 20px;
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
            width: 192px;
            height: 192px;
            border: 3px solid #0a2f4d;
            overflow: hidden;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
        }

        .photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-box i {
            font-size: 80px;
            color: #94a3b8;
        }

        .photo-label {
            font-size: 12px;
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

        .data-section {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 15px;
            overflow: hidden;
        }

        .section-title {
            background: #f8fafc;
            padding: 8px 15px;
            font-weight: 600;
            font-size: 14px;
            color: #0f172a;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i {
            color: #64748b;
            font-size: 14px;
        }

        .section-content {
            padding: 15px;
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
            font-weight: 600;
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

        .compact-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .compact-table th {
            background: #f8fafc;
            padding: 8px 10px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            font-size: 11px;
            text-transform: uppercase;
            border: 1px solid #e2e8f0;
        }

        .compact-table td {
            padding: 8px 10px;
            border: 1px solid #e2e8f0;
        }

        .compact-table .form-control {
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

        .action-bar {
            padding: 15px 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            margin-top: 20px;
        }

        .btn {
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
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

        .btn-info {
            background: #0891b2;
            color: white;
        }

        .btn-info:hover {
            background: #0e7490;
        }

        .btn-sm {
            padding: 4px 10px;
            font-size: 11px;
        }

        .btn-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-danger:hover {
            background: #fecaca;
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

        .profile-id-badge {
            background: #0a2f4d;
            color: white;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 12px;
            display: inline-block;
            margin-bottom: 15px;
        }

        .profile-id-badge i {
            margin-right: 5px;
            color: #c9a959;
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
            .user-info-header {
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
        <div class="pnp-header">
            <div class="header-content">
                <div class="dilg">Department of the Interior and Local Government</div>
                <div class="pnp">PHILIPPINE NATIONAL POLICE</div>
                <div class="provincial">BUKIDNON POLICE PROVINCIAL OFFICE</div>
                <div class="station">MANOLO FORTICH POLICE STATION</div>
                <div class="address">Manolo Fortich, Bukidnon, 8703</div>
                <div class="phone"><i class="fas fa-phone"></i> (088-228) 2244</div>
                
                <div class="user-info-header">
                    <i class="fas fa-user-shield"></i>
                    <span><?php echo $_SESSION['rank'] . ' ' . $_SESSION['full_name']; ?></span>
                </div>
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
            
            <div class="text-center">
                <span class="profile-id-badge">
                    <i class="fas fa-id-card"></i> Editing Profile #<?php echo str_pad($profile['id'], 5, '0', STR_PAD_LEFT); ?>
                </span>
                <?php if (!empty($barangay)): ?>
                    <span class="profile-id-badge" style="background: #c9a959; margin-left: 10px;">
                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($barangay); ?>
                    </span>
                <?php endif; ?>
            </div>

            <div class="photo-section">
                <div class="photo-container">
                    <div class="photo-box" id="picturePreview">
                        <?php if (!empty($profile['profile_picture'])): ?>
                            <img src="<?php echo $profile['profile_picture']; ?>" alt="Profile Picture">
                        <?php else: ?>
                            <i class="fas fa-user-circle"></i>
                        <?php endif; ?>
                    </div>
                    <form method="POST" enctype="multipart/form-data" id="pictureForm">
                        <input type="file" name="profile_picture" id="profilePicture" class="file-input" accept="image/*">
                        <button type="button" class="btn-upload" onclick="document.getElementById('profilePicture').click();">
                            <i class="fas fa-camera"></i> Update Photo
                        </button>
                    </form>
                    <div class="photo-label">2x2 OFFICIAL PHOTOGRAPH</div>
                </div>
            </div>

            <form method="POST" action="" id="profileForm">
                <!-- I. PERSONAL DATA -->
                <div class="data-section">
                    <div class="section-title">
                        <i class="fas fa-user"></i> I. PERSONAL DATA
                    </div>
                    <div class="section-content">
                        <div class="form-grid">
                            <div class="form-field full-width">
                                <label>FULL NAME</label>
                                <input type="text" class="form-control" name="full_name" required value="<?php echo inputValue($profile['full_name']); ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>ALIAS</label>
                                <input type="text" class="form-control" name="alias" value="<?php echo inputValue($profile['alias']); ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>GROUP/GANG AFFILIATION</label>
                                <input type="text" class="form-control" name="group_affiliation" value="<?php echo inputValue($profile['group_affiliation']); ?>">
                            </div>
                            
                            <div class="form-field full-width">
                                <label>POSITION/ROLE</label>
                                <div class="checkbox-container">
                                    <div class="checkbox-group">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="position_roles[]" value="User" id="posUser" <?php echo in_array('User', $positionRolesArray) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="posUser">User</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="position_roles[]" value="Pusher" id="posPusher" <?php echo in_array('Pusher', $positionRolesArray) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="posPusher">Pusher</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="position_roles[]" value="Runner" id="posRunner" <?php echo in_array('Runner', $positionRolesArray) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="posRunner">Runner</label>
                                        </div>
                                    </div>
                                    <input type="text" class="form-control" style="margin-top: 10px;" name="position_roles_other" placeholder="Other position/s (separate with commas)" value="">
                                </div>
                            </div>
                            
                            <div class="form-field">
                                <label>AGE</label>
                                <input type="number" class="form-control" name="age" required value="<?php echo inputValue($profile['age']); ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>SEX</label>
                                <select class="form-select" name="sex" required>
                                    <option value="">Select</option>
                                    <option value="Male" <?php echo ($profile['sex'] ?? '') == 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($profile['sex'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female</option>
                                </select>
                            </div>
                            
                            <div class="form-field">
                                <label>DATE OF BIRTH</label>
                                <input type="date" class="form-control" name="dob" required value="<?php echo inputValue($profile['dob']); ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>PLACE OF BIRTH</label>
                                <input type="text" class="form-control" name="pob" required value="<?php echo inputValue($profile['pob']); ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>EDUCATIONAL ATTAINMENT</label>
                                <select class="form-select" name="educational_attainment">
                                    <option value="">Select</option>
                                    <option value="Elementary Level" <?php echo ($profile['educational_attainment'] ?? '') == 'Elementary Level' ? 'selected' : ''; ?>>Elementary Level</option>
                                    <option value="Elementary Graduate" <?php echo ($profile['educational_attainment'] ?? '') == 'Elementary Graduate' ? 'selected' : ''; ?>>Elementary Graduate</option>
                                    <option value="High School Level" <?php echo ($profile['educational_attainment'] ?? '') == 'High School Level' ? 'selected' : ''; ?>>High School Level</option>
                                    <option value="High School Graduate" <?php echo ($profile['educational_attainment'] ?? '') == 'High School Graduate' ? 'selected' : ''; ?>>High School Graduate</option>
                                    <option value="College Level" <?php echo ($profile['educational_attainment'] ?? '') == 'College Level' ? 'selected' : ''; ?>>College Level</option>
                                    <option value="College Graduate" <?php echo ($profile['educational_attainment'] ?? '') == 'College Graduate' ? 'selected' : ''; ?>>College Graduate</option>
                                    <option value="Post Graduate" <?php echo ($profile['educational_attainment'] ?? '') == 'Post Graduate' ? 'selected' : ''; ?>>Post Graduate</option>
                                    <option value="Vocational" <?php echo ($profile['educational_attainment'] ?? '') == 'Vocational' ? 'selected' : ''; ?>>Vocational</option>
                                </select>
                            </div>
                            
                            <div class="form-field">
                                <label>OCCUPATION</label>
                                <input type="text" class="form-control" name="occupation" value="<?php echo inputValue($profile['occupation']); ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>COMPANY/OFFICE</label>
                                <input type="text" class="form-control" name="company_office" value="<?php echo inputValue($profile['company_office']); ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>TECHNICAL SKILLS</label>
                                <input type="text" class="form-control" name="technical_skills" value="<?php echo inputValue($profile['technical_skills']); ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>ETHNIC GROUP</label>
                                <input type="text" class="form-control" name="ethnic_group" value="<?php echo inputValue($profile['ethnic_group']); ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>LANGUAGES/DIALECTS</label>
                                <input type="text" class="form-control" name="languages" value="<?php echo inputValue($profile['languages']); ?>">
                            </div>
                            
                            <div class="form-field full-width">
                                <label>PRESENT ADDRESS</label>
                                <input type="text" class="form-control" name="present_address" value="<?php echo inputValue($profile['present_address']); ?>">
                            </div>
                            
                            <div class="form-field full-width">
                                <label>PROVINCIAL ADDRESS</label>
                                <input type="text" class="form-control" name="provincial_address" value="<?php echo inputValue($profile['provincial_address']); ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>CIVIL STATUS</label>
                                <select class="form-select" name="civil_status">
                                    <option value="">Select</option>
                                    <option value="Single" <?php echo ($profile['civil_status'] ?? '') == 'Single' ? 'selected' : ''; ?>>Single</option>
                                    <option value="Married" <?php echo ($profile['civil_status'] ?? '') == 'Married' ? 'selected' : ''; ?>>Married</option>
                                    <option value="Widowed" <?php echo ($profile['civil_status'] ?? '') == 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                                    <option value="Separated" <?php echo ($profile['civil_status'] ?? '') == 'Separated' ? 'selected' : ''; ?>>Separated</option>
                                </select>
                            </div>
                            
                            <div class="form-field">
                                <label>CITIZENSHIP</label>
                                <input type="text" class="form-control" name="citizenship" value="<?php echo inputValue($profile['citizenship']) ?: 'Filipino'; ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>RELIGION</label>
                                <input type="text" class="form-control" name="religion" value="<?php echo inputValue($profile['religion']); ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>HEIGHT (ft/in)</label>
                                <input type="text" class="form-control" name="height_ft" placeholder="e.g., 5'5&quot;" value="<?php echo inputValue($profile['height_ft']); ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>WEIGHT (kg)</label>
                                <input type="number" step="0.01" class="form-control" name="weight_kg" value="<?php echo inputValue($profile['weight_kg']); ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>EYES COLOR</label>
                                <input type="text" class="form-control" name="eyes_color" value="<?php echo inputValue($profile['eyes_color']); ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>HAIR COLOR</label>
                                <input type="text" class="form-control" name="hair_color" value="<?php echo inputValue($profile['hair_color']); ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>BUILT</label>
                                <select class="form-select" name="built">
                                    <option value="">Select</option>
                                    <option value="Small" <?php echo ($profile['built'] ?? '') == 'Small' ? 'selected' : ''; ?>>Small</option>
                                    <option value="Medium" <?php echo ($profile['built'] ?? '') == 'Medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="Large" <?php echo ($profile['built'] ?? '') == 'Large' ? 'selected' : ''; ?>>Large</option>
                                </select>
                            </div>
                            
                            <div class="form-field">
                                <label>COMPLEXION</label>
                                <input type="text" class="form-control" name="complexion" value="<?php echo inputValue($profile['complexion']); ?>">
                            </div>
                            
                            <div class="form-field full-width">
                                <label>DISTINGUISHING MARKS/TATTOO</label>
                                <textarea class="form-control" name="distinguishing_marks" rows="2"><?php echo inputValue($profile['distinguishing_marks']); ?></textarea>
                            </div>
                            
                            <div class="form-field full-width">
                                <label>PREVIOUS ARREST RECORD</label>
                                <textarea class="form-control" name="previous_arrest" rows="2"><?php echo inputValue($profile['previous_arrest']); ?></textarea>
                            </div>
                            
                            <div class="form-field full-width">
                                <label>SPECIFIC CHARGE</label>
                                <input type="text" class="form-control" name="specific_charge" value="<?php echo inputValue($profile['specific_charge']); ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>DATE/TIME/PLACE OF ARREST</label>
                                <input type="datetime-local" class="form-control" name="date_time_place_of_arrest" value="<?php echo $arrest_datetime; ?>">
                                <small class="text-muted">Enter date, time, and place of arrest</small>
                            </div>
                            
                            <div class="form-field">
                                <label>ARRESTING OFFICER</label>
                                <input type="text" class="form-control" name="arresting_officer" value="<?php echo inputValue($profile['arresting_officer']); ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>UNIT/OFFICE OF ARRESTING OFFICER</label>
                                <input type="text" class="form-control" name="arresting_unit" value="<?php echo inputValue($profile['arresting_unit']); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- II. FAMILY BACKGROUND -->
                <div class="data-section">
                    <div class="section-title">
                        <i class="fas fa-users"></i> II. FAMILY BACKGROUND
                    </div>
                    <div class="section-content">
                        <table class="compact-table">
                            <thead>
                                <th style="width: 100px;"></th>
                                <th>Father</th>
                                <th>Mother</th>
                            </thead>
                            <tbody>
                                
                                    <td><strong>Name</strong>\\
                                    <td><input type="text" class="form-control" name="father_name" value="<?php echo inputValue($profile['father_name']); ?>">\\
                                    <td><input type="text" class="form-control" name="mother_name" value="<?php echo inputValue($profile['mother_name']); ?>">\\
                                
                                
                                    <td><strong>Known Address</strong>\\
                                    <td><input type="text" class="form-control" name="father_address" value="<?php echo inputValue($profile['father_address']); ?>">\\
                                    <td><input type="text" class="form-control" name="mother_address" value="<?php echo inputValue($profile['mother_address']); ?>">\\
                                
                                
                                    <td><strong>Date of Birth</strong>\\
                                    <td><input type="date" class="form-control" name="father_dob" value="<?php echo inputValue($profile['father_dob']); ?>">\\
                                    <td><input type="date" class="form-control" name="mother_dob" value="<?php echo inputValue($profile['mother_dob']); ?>">\\
                                
                                
                                    <td><strong>Age</strong>\\
                                    <td><input type="number" class="form-control" name="father_age" value="<?php echo inputValue($profile['father_age']); ?>">\\
                                    <td><input type="number" class="form-control" name="mother_age" value="<?php echo inputValue($profile['mother_age']); ?>">\\
                                
                                
                                    <td><strong>Occupation</strong>\\
                                    <td><input type="text" class="form-control" name="father_occupation" value="<?php echo inputValue($profile['father_occupation']); ?>">\\
                                    <td><input type="text" class="form-control" name="mother_occupation" value="<?php echo inputValue($profile['mother_occupation']); ?>">\\
                                
                            </tbody>
                          
                        
                        <div style="margin-top: 15px;">
                            <table class="compact-table">
                                <thead>
                                    <th style="width: 100px;">Spouse</th>
                                    <td><input type="text" class="form-control" name="spouse_name" value="<?php echo inputValue($profile['spouse_name']); ?>">\\
                                    <th>Age</th>
                                    <td><input type="number" class="form-control" name="spouse_age" value="<?php echo inputValue($profile['spouse_age']); ?>">\\
                                    <th>Occupation</th>
                                    <td><input type="text" class="form-control" name="spouse_occupation" value="<?php echo inputValue($profile['spouse_occupation']); ?>">\\
                                    <th>Address</th>
                                    <td><input type="text" class="form-control" name="spouse_address" value="<?php echo inputValue($profile['spouse_address']); ?>">\\
                                </thead>
                              
                        </div>
                        
                        <div style="margin-top: 15px;">
                            <strong>Siblings</strong>
                            <div id="siblingsContainer">
                                <table class="compact-table" style="margin-top: 5px;" id="siblingsTable">
                                    <thead>
                                        <th>Name</th>
                                        <th>Age</th>
                                        <th>Occupation</th>
                                        <th>Status</th>
                                        <th>Address</th>
                                        <th>Action</th>
                                    </thead>
                                    <tbody>
                                        <?php if (count($siblings) > 0): ?>
                                            <?php foreach ($siblings as $sibling): ?>
                                              
                                                <input type="text" class="form-control" name="sibling_name[]" value="<?php echo inputValue($sibling['name']); ?>">
                                                <input type="number" class="form-control" name="sibling_age[]" value="<?php echo inputValue($sibling['age']); ?>">
                                                <input type="text" class="form-control" name="sibling_occupation[]" value="<?php echo inputValue($sibling['occupation']); ?>">
                                                <input type="text" class="form-control" name="sibling_status[]" value="<?php echo inputValue($sibling['status']); ?>">
                                                <input type="text" class="form-control" name="sibling_address[]" value="<?php echo inputValue($sibling['address']); ?>">
                                                <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)"><i class="fas fa-trash"></i></button>
                                              
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                              
                                                <input type="text" class="form-control" name="sibling_name[]">
                                                <input type="number" class="form-control" name="sibling_age[]">
                                                <input type="text" class="form-control" name="sibling_occupation[]">
                                                <input type="text" class="form-control" name="sibling_status[]">
                                                <input type="text" class="form-control" name="sibling_address[]">
                                                <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)"><i class="fas fa-trash"></i></button>
                                              
                                        <?php endif; ?>
                                    </tbody>
                                  
                                <button type="button" class="btn btn-secondary btn-sm" style="margin-top: 10px;" onclick="addSiblingRow()">
                                    <i class="fas fa-plus"></i> Add Sibling
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- III. TACTICAL INFORMATION -->
                <div class="data-section">
                    <div class="section-title">
                        <i class="fas fa-info-circle"></i> III. TACTICAL INFORMATION
                    </div>
                    <div class="section-content">
                        <div class="form-grid">
                            <div class="form-field full-width">
                                <label>DRUGS INVOLVED</label>
                                <input type="text" class="form-control" name="drugs_involved" value="<?php echo inputValue($profile['drugs_involved']); ?>">
                            </div>
                            
                            <div class="form-field full-width">
                                <label>VEHICLES USED</label>
                                <input type="text" class="form-control" name="vehicles_used" value="<?php echo inputValue($profile['vehicles_used']); ?>">
                            </div>
                            
                            <div class="form-field full-width">
                                <label>ARMAMENTS</label>
                                <textarea class="form-control" name="armaments" rows="2"><?php echo inputValue($profile['armaments']); ?></textarea>
                            </div>
                            
                            <div class="form-field">
                                <label>COMPANION/S DURING ARREST</label>
                                <input type="text" class="form-control" name="companions_arrest" value="<?php echo inputValue($profile['companions_arrest']); ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>RELATIONSHIP TO SOURCE</label>
                                <input type="text" class="form-control" name="source_relationship" value="<?php echo inputValue($profile['source_relationship']); ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>NAME/SOURCE OF DRUGS INVOLVED</label>
                                <input type="text" class="form-control" name="source_name" value="<?php echo inputValue($profile['source_name']); ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>ADDRESS OF ALLEGED SOURCE</label>
                                <input type="text" class="form-control" name="source_address" value="<?php echo inputValue($profile['source_address']); ?>">
                            </div>
                            
                            <div class="form-field full-width">
                                <label>OTHER DRUGS SUPPLIED BY SOURCE</label>
                                <input type="text" class="form-control" name="source_other_drugs" value="<?php echo inputValue($profile['source_other_drugs']); ?>">
                            </div>
                            
                            <div class="form-field full-width">
                                <label>SUBGROUPS AND SPECIFIC AOR</label>
                                <input type="text" class="form-control" name="subgroup_name" value="<?php echo inputValue($profile['subgroup_name']); ?>">
                                <small class="text-muted">Format: Subgroup - AOR</small>
                            </div>
                            
                            <div class="form-field full-width">
                                <label>OTHER SUBJECT KNOWN AS SOURCE</label>
                                <input type="text" class="form-control" name="other_source_name" value="<?php echo inputValue($profile['other_source_name']); ?>">
                            </div>
                            
                            <div class="form-field full-width">
                                <label>OTHER SOURCE ALIAS</label>
                                <input type="text" class="form-control" name="other_source_alias" value="<?php echo inputValue($profile['other_source_alias']); ?>">
                            </div>
                            
                            <div class="form-field full-width">
                                <label>OTHER SOURCE DETAILS</label>
                                <textarea class="form-control" name="other_source_details" rows="2"><?php echo inputValue($profile['other_source_details']); ?></textarea>
                            </div>
                            
                            <div class="form-field full-width">
                                <label>DRUGS PUSHED BY SUBJECT</label>
                                <div class="checkbox-container">
                                    <div class="checkbox-group">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="drugs_pushed[]" value="Shabu" id="drugShabu" <?php echo isDrugPushed('Shabu', $drugsPushedArray) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="drugShabu">Shabu</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="drugs_pushed[]" value="Marijuana" id="drugMarijuana" <?php echo isDrugPushed('Marijuana', $drugsPushedArray) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="drugMarijuana">Marijuana</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="drugs_pushed[]" value="Ecstasy" id="drugEcstasy" <?php echo isDrugPushed('Ecstasy', $drugsPushedArray) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="drugEcstasy">Ecstasy</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="drugs_pushed[]" value="Cocaine" id="drugCocaine" <?php echo isDrugPushed('Cocaine', $drugsPushedArray) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="drugCocaine">Cocaine</label>
                                        </div>
                                    </div>
                                    <input type="text" class="form-control" style="margin-top: 10px;" name="other_drugs_pushed" placeholder="Other drugs not listed above" value="<?php echo inputValue($profile['other_drugs_pushed']); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- IV. SUMMARY & RECOMMENDATION -->
                <div class="data-section">
                    <div class="section-title">
                        <i class="fas fa-file-alt"></i> IV. SUMMARY & RECOMMENDATION
                    </div>
                    <div class="section-content">
                        <div class="form-grid">
                            <div class="form-field full-width">
                                <label>RECRUITMENT SUMMARY</label>
                                <textarea class="form-control" name="recruitment_summary" rows="3"><?php echo inputValue($profile['recruitment_summary']); ?></textarea>
                            </div>
                            
                            <div class="form-field full-width">
                                <label>MODUS OPERANDI</label>
                                <textarea class="form-control" name="modus_operandi" rows="3"><?php echo inputValue($profile['modus_operandi']); ?></textarea>
                            </div>
                            
                            <div class="form-field full-width">
                                <label>ORGANIZATIONAL STRUCTURE</label>
                                <textarea class="form-control" name="organizational_structure" rows="3"><?php echo inputValue($profile['organizational_structure']); ?></textarea>
                            </div>
                            
                            <div class="form-field full-width">
                                <label>CI MATTERS</label>
                                <textarea class="form-control" name="ci_matters" rows="3"><?php echo inputValue($profile['ci_matters']); ?></textarea>
                            </div>
                            
                            <div class="form-field full-width">
                                <label>OTHER REVELATIONS</label>
                                <textarea class="form-control" name="other_revelations" rows="3"><?php echo inputValue($profile['other_revelations']); ?></textarea>
                            </div>
                            
                            <div class="form-field full-width">
                                <label>RECOMMENDATION</label>
                                <textarea class="form-control" name="recommendation" rows="3"><?php echo inputValue($profile['recommendation']); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-bar">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="<?php echo $cancel_link; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> <?php echo $cancel_text; ?>
                    </a>
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
        
        function removeRow(button) {
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
        
        let formChanged = false;
        const form = document.getElementById('profileForm');
        const formInputs = form.querySelectorAll('input, select, textarea');
        formInputs.forEach(input => {
            input.addEventListener('change', () => { formChanged = true; });
        });
        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            }
        });
        form.addEventListener('submit', function() { formChanged = false; });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>