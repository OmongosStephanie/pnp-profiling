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
                // Update profile picture in database
                $update_query = "UPDATE biographical_profiles SET profile_picture = :profile_picture WHERE id = :id";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->execute([
                    ':profile_picture' => $target_file,
                    ':id' => $id
                ]);
                $profile['profile_picture'] = $target_file;
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_FILES['profile_picture'])) {
    try {
        // Begin transaction
        $db->beginTransaction();
        
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
            ':position_roles' => $position_roles,
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
            ':drugs_involved' => $drug_types,
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

// Parse drug types for display
$drugTypesArray = !empty($profile['drugs_involved']) ? explode(', ', $profile['drugs_involved']) : [];

// Parse position roles for display
$positionRolesArray = !empty($profile['position_roles']) ? explode(', ', $profile['position_roles']) : [];

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
            padding: 20px;
        }

        /* Form Container */
        .form-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        /* Official PNP Header - Inside Form */
        .form-pnp-header {
            background: #0a2f4d;
            color: white;
            padding: 25px 30px;
            border-bottom: 4px solid #c9a959;
            position: relative;
        }
        
        .header-content {
            text-align: center;
            position: relative;
        }
        
        .header-content .dilg {
            font-size: 14px;
            font-weight: 300;
            letter-spacing: 1px;
            color: #e0e0e0;
            display: block;
            margin-bottom: 3px;
        }
        
        .header-content .pnp {
            font-size: 26px;
            font-weight: 700;
            color: white;
            display: block;
            margin: 3px 0;
            letter-spacing: 1px;
        }
        
        .header-content .provincial {
            font-size: 20px;
            font-weight: 500;
            color: #c9a959;
            display: block;
            margin: 3px 0;
        }
        
        .header-content .station {
            font-size: 18px;
            font-weight: 500;
            color: white;
            display: block;
            margin: 3px 0;
        }
        
        .header-content .address {
            font-size: 15px;
            color: #b0c4de;
            display: block;
            margin-top: 8px;
        }
        
        .header-content .phone {
            font-size: 15px;
            color: #b0c4de;
            display: block;
            margin-top: 2px;
        }
        
        .header-content .phone i {
            color: #c9a959;
            margin-right: 5px;
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

        /* Form Content */
        .form-content {
            padding: 30px;
        }

        .form-title {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-title h2 {
            font-size: 28px;
            font-weight: 600;
            color: #0f172a;
            letter-spacing: -0.02em;
        }

        .form-title p {
            color: #64748b;
            font-size: 14px;
            margin-top: 8px;
        }

          /* Profile Picture Section */
        .profile-picture-section {
            display: flex;
            margin-left: 900px;
            margin-bottom: 20px;
        }

        .picture-container {
            width: 150px;
            text-align: center;
        }

        .picture-box {
            width: 200px;
            height: 200px;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border: 3px solid #0a2f4d;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-bottom: 5px;
            overflow: hidden;
            position: relative;
        }

        .picture-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .picture-box i {
            font-size: 50px;
            color: #94a3b8;
            margin-bottom: 5px;
        }

        .picture-box span {
            font-size: 12px;
            color: #64748b;
        }

        .btn-upload {
            background: #0a2f4d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            margin-left: 25px;
            transition: all 0.2s;
            width: 100%;
        }

        .btn-upload:hover {
            background: #c9a959;
            color: #0a2f4d;
        }

        .file-input {
            display: none;
        }

        /* Form Section */
        .form-section {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 28px;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e2e8f0;
        }

        .section-header i {
            font-size: 20px;
            color: #94a3b8;
        }

        .section-header h4 {
            font-size: 18px;
            font-weight: 600;
            color: #0f172a;
            margin: 0;
            letter-spacing: -0.01em;
        }

        .profile-id-badge {
            background: #0a2f4d;
            color: white;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 14px;
            display: inline-block;
            margin-bottom: 20px;
        }

        .profile-id-badge i {
            margin-right: 5px;
            color: #c9a959;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .form-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-field.full-width {
            grid-column: 1 / -1;
        }

        .form-field label {
            font-size: 13px;
            font-weight: 500;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .form-control, .form-select {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 14px;
            color: #1e293b;
            background: #ffffff;
            transition: all 0.15s;
            width: 100%;
        }

        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: #94a3b8;
            box-shadow: 0 0 0 3px rgba(148, 163, 184, 0.1);
        }

        .checkbox-container {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
        }

        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 8px;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-check-input {
            width: 16px;
            height: 16px;
            border: 1.5px solid #cbd5e1;
            border-radius: 4px;
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: #0f172a;
            border-color: #0f172a;
        }

        .form-check-label {
            font-size: 14px;
            color: #334155;
        }

        .table-container {
            overflow-x: auto;
            margin-top: 16px;
        }

        .minimal-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .minimal-table th {
            text-align: left;
            padding: 12px 16px;
            background: #f8fafc;
            color: #475569;
            font-weight: 500;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border-bottom: 1px solid #e2e8f0;
        }

        .minimal-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
        }

        .minimal-table tr:last-child td {
            border-bottom: none;
        }

        .form-actions {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin-top: 40px;
            padding: 20px 0;
        }

        .btn-primary {
            background: #0f172a;
            color: white;
            border: none;
            padding: 12px 32px;
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
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #e2e8f0;
            padding: 12px 32px;
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
            background: #e2e8f0;
        }

        .btn-info {
            background: #0891b2;
            color: white;
            border: none;
            padding: 12px 32px;
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

        .btn-info:hover {
            background: #0e7490;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 30px;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 12px;
        }

        .alert {
            background: #f8fafc;
            border-left: 4px solid #0f172a;
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            border-left-color: #10b981;
            background: #f0fdf4;
        }

        .alert-danger {
            border-left-color: #ef4444;
            background: #fef2f2;
        }

        .site-footer {
            text-align: center;
            padding: 24px 0;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 12px;
            margin-top: 20px;
        }

        .drug-checkbox-group {
            border: 1px solid #e2e8f0;
            padding: 15px;
            border-radius: 10px;
            background: white;
        }

        @media (max-width: 768px) {
            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .user-info-header {
                position: relative;
                top: 0;
                right: 0;
                margin-top: 15px;
                justify-content: center;
            }

            .profile-picture-section {
                justify-content: center;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="form-wrapper">
        <!-- PNP Header Inside the Form -->
        <div class="form-pnp-header">
            <div class="header-content">
                <span class="dilg">Department of the Interior and Local Government</span>
                <span class="pnp">PHILIPPINE NATIONAL POLICE</span>
                <span class="provincial">BUKIDNON POLICE PROVINCIAL OFFICE</span>
                <span class="station">MANOLO FORTICH POLICE STATION</span>
                <span class="address">Manolo Fortich, Bukidnon, 8703</span>
                <span class="phone"><i class="fas fa-phone"></i> (088-228) 2244</span>
                
                <div class="user-info-header">
                    <i class="fas fa-user-shield"></i>
                    <span><?php echo $_SESSION['rank'] . ' ' . $_SESSION['full_name']; ?></span>
                </div>
            </div>
        </div>

        <!-- Form Content -->
        <div class="form-content">
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $message; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <div class="text-center mb-4">
                <span class="profile-id-badge">
                    <i class="fas fa-id-card"></i> Editing Profile ID: #<?php echo str_pad($profile['id'], 6, '0', STR_PAD_LEFT); ?>
                </span>
            </div>

            <!-- Profile Picture Section (Upper Right) -->
            <div class="profile-picture-section">
                <div class="picture-container">
                    <div class="picture-box" id="picturePreview">
                        <?php if (!empty($profile['profile_picture'])): ?>
                            <img src="<?php echo $profile['profile_picture']; ?>" alt="Profile Picture">
                        <?php else: ?>
                            <i class="fas fa-user-circle"></i>
                            <span>2x2 Photo</span>
                        <?php endif; ?>
                    </div>
                    <form method="POST" enctype="multipart/form-data" id="pictureForm">
                        <input type="file" name="profile_picture" id="profilePicture" class="file-input" accept="image/*">
                        <button type="button" class="btn-upload" onclick="document.getElementById('profilePicture').click();">
                            <i class="fas fa-camera"></i> Upload Picture
                        </button>
                    </form>
                </div>
            </div>
            
            <form method="POST" action="" id="profileForm">
                <!-- I. Personal Data -->
                <section class="form-section">
                    <div class="section-header">
                        <i class="fas fa-user"></i>
                        <h4>I. Personal Data</h4>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-field full-width">
                            <label>Full Name</label>
                            <input type="text" class="form-control" name="full_name" required 
                                   value="<?php echo htmlspecialchars($profile['full_name']); ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Alias</label>
                            <input type="text" class="form-control" name="alias" 
                                   value="<?php echo htmlspecialchars($profile['alias']); ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Group/Gang Affiliation</label>
                            <input type="text" class="form-control" name="group_affiliation"
                                   value="<?php echo htmlspecialchars($profile['group_affiliation']); ?>">
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Position/Role</label>
                            <div class="checkbox-container">
                                <div class="checkbox-group">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="position_roles[]" value="User" id="posUser"
                                               <?php echo in_array('User', $positionRolesArray) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="posUser">User</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="position_roles[]" value="Pusher" id="posPusher"
                                               <?php echo in_array('Pusher', $positionRolesArray) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="posPusher">Pusher</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="position_roles[]" value="Runner" id="posRunner"
                                               <?php echo in_array('Runner', $positionRolesArray) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="posRunner">Runner</label>
                                    </div>
                                </div>
                                <input type="text" class="form-control" style="margin-top: 12px;" name="position_roles_other" 
                                       placeholder="Other position/s (separate with commas)"
                                       value="">
                            </div>
                        </div>
                        
                        <div class="form-field">
                            <label>Age</label>
                            <input type="number" class="form-control" name="age" required
                                   value="<?php echo $profile['age']; ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Sex</label>
                            <select class="form-select" name="sex" required>
                                <option value="">Select</option>
                                <option value="Male" <?php echo $profile['sex'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo $profile['sex'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo $profile['sex'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="form-field">
                            <label>Date of Birth</label>
                            <input type="date" class="form-control" name="dob" required
                                   value="<?php echo $profile['dob']; ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Place of Birth</label>
                            <input type="text" class="form-control" name="pob" required
                                   value="<?php echo htmlspecialchars($profile['pob']); ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Educational Attainment</label>
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
                        </div>
                        
                        <div class="form-field">
                            <label>Occupation</label>
                            <input type="text" class="form-control" name="occupation"
                                   value="<?php echo htmlspecialchars($profile['occupation']); ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Company/Office</label>
                            <input type="text" class="form-control" name="company_office"
                                   value="<?php echo htmlspecialchars($profile['company_office']); ?>">
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Present Address</label>
                            <input type="text" class="form-control" name="present_address"
                                   value="<?php echo htmlspecialchars($profile['present_address']); ?>">
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Provincial Address</label>
                            <input type="text" class="form-control" name="provincial_address"
                                   value="<?php echo htmlspecialchars($profile['provincial_address']); ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Civil Status</label>
                            <select class="form-select" name="civil_status">
                                <option value="">Select</option>
                                <option value="Single" <?php echo $profile['civil_status'] == 'Single' ? 'selected' : ''; ?>>Single</option>
                                <option value="Married" <?php echo $profile['civil_status'] == 'Married' ? 'selected' : ''; ?>>Married</option>
                                <option value="Widowed" <?php echo $profile['civil_status'] == 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                                <option value="Separated" <?php echo $profile['civil_status'] == 'Separated' ? 'selected' : ''; ?>>Separated</option>
                                <option value="Divorced" <?php echo $profile['civil_status'] == 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                            </select>
                        </div>
                        
                        <div class="form-field">
                            <label>Citizenship</label>
                            <input type="text" class="form-control" name="citizenship"
                                   value="<?php echo htmlspecialchars($profile['citizenship']); ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Religion</label>
                            <input type="text" class="form-control" name="religion"
                                   value="<?php echo htmlspecialchars($profile['religion']); ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Height (ft/in)</label>
                            <input type="text" class="form-control" name="height_ft" placeholder="e.g., 5'5&quot;"
                                   value="<?php echo htmlspecialchars($profile['height_ft']); ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Weight (kg)</label>
                            <input type="number" step="0.01" class="form-control" name="weight_kg"
                                   value="<?php echo $profile['weight_kg']; ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Eyes Color</label>
                            <input type="text" class="form-control" name="eyes_color"
                                   value="<?php echo htmlspecialchars($profile['eyes_color']); ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Hair Color</label>
                            <input type="text" class="form-control" name="hair_color"
                                   value="<?php echo htmlspecialchars($profile['hair_color']); ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Built</label>
                            <select class="form-select" name="built">
                                <option value="">Select</option>
                                <option value="Small" <?php echo $profile['built'] == 'Small' ? 'selected' : ''; ?>>Small</option>
                                <option value="Medium" <?php echo $profile['built'] == 'Medium' ? 'selected' : ''; ?>>Medium</option>
                                <option value="Large" <?php echo $profile['built'] == 'Large' ? 'selected' : ''; ?>>Large</option>
                            </select>
                        </div>
                        
                        <div class="form-field">
                            <label>Complexion</label>
                            <input type="text" class="form-control" name="complexion"
                                   value="<?php echo htmlspecialchars($profile['complexion']); ?>">
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Distinguishing Marks/Tattoo</label>
                            <textarea class="form-control" name="distinguishing_marks" rows="2"><?php echo htmlspecialchars($profile['distinguishing_marks']); ?></textarea>
                        </div>
                    </div>
                </section>

                <!-- II. Family Background -->
                <section class="form-section">
                    <div class="section-header">
                        <i class="fas fa-users"></i>
                        <h4>II. Family Background</h4>
                    </div>
                    
                    <h5 style="margin: 20px 0 12px; font-weight: 500; color: #475569;">Parents</h5>
                    <div class="table-container">
                        <table class="minimal-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Father</th>
                                    <th>Mother</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Name</strong></td>
                                    <td><input type="text" class="form-control" name="father_name" value="<?php echo htmlspecialchars($profile['father_name']); ?>"></td>
                                    <td><input type="text" class="form-control" name="mother_name" value="<?php echo htmlspecialchars($profile['mother_name']); ?>"></td>
                                </tr>
                                <tr>
                                    <td><strong>Address</strong></td>
                                    <td><input type="text" class="form-control" name="father_address" value="<?php echo htmlspecialchars($profile['father_address']); ?>"></td>
                                    <td><input type="text" class="form-control" name="mother_address" value="<?php echo htmlspecialchars($profile['mother_address']); ?>"></td>
                                </tr>
                                <tr>
                                    <td><strong>Date of Birth</strong></td>
                                    <td><input type="date" class="form-control" name="father_dob" value="<?php echo $profile['father_dob']; ?>"></td>
                                    <td><input type="date" class="form-control" name="mother_dob" value="<?php echo $profile['mother_dob']; ?>"></td>
                                </tr>
                                <tr>
                                    <td><strong>Place of Birth</strong></td>
                                    <td><input type="text" class="form-control" name="father_pob" value="<?php echo htmlspecialchars($profile['father_pob']); ?>"></td>
                                    <td><input type="text" class="form-control" name="mother_pob" value="<?php echo htmlspecialchars($profile['mother_pob']); ?>"></td>
                                </tr>
                                <tr>
                                    <td><strong>Age</strong></td>
                                    <td><input type="number" class="form-control" name="father_age" value="<?php echo $profile['father_age']; ?>"></td>
                                    <td><input type="number" class="form-control" name="mother_age" value="<?php echo $profile['mother_age']; ?>"></td>
                                </tr>
                                <tr>
                                    <td><strong>Occupation</strong></td>
                                    <td><input type="text" class="form-control" name="father_occupation" value="<?php echo htmlspecialchars($profile['father_occupation']); ?>"></td>
                                    <td><input type="text" class="form-control" name="mother_occupation" value="<?php echo htmlspecialchars($profile['mother_occupation']); ?>"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <h5 style="margin: 30px 0 12px; font-weight: 500; color: #475569;">Spouse</h5>
                    <div class="table-container">
                        <table class="minimal-table">
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
                                    <td><input type="text" class="form-control" name="spouse_name" value="<?php echo htmlspecialchars($profile['spouse_name']); ?>"></td>
                                    <td><input type="number" class="form-control" name="spouse_age" value="<?php echo $profile['spouse_age']; ?>"></td>
                                    <td><input type="text" class="form-control" name="spouse_occupation" value="<?php echo htmlspecialchars($profile['spouse_occupation']); ?>"></td>
                                    <td><input type="text" class="form-control" name="spouse_address" value="<?php echo htmlspecialchars($profile['spouse_address']); ?>"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <h5 style="margin: 30px 0 12px; font-weight: 500; color: #475569;">Siblings</h5>
                    <div id="siblingsContainer">
                        <div class="table-container">
                            <table class="minimal-table" id="siblingsTable">
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
                                        <?php foreach ($siblings as $sibling): ?>
                                        <tr>
                                            <td><input type="text" class="form-control" name="sibling_name[]" value="<?php echo htmlspecialchars($sibling['name']); ?>"></td>
                                            <td><input type="number" class="form-control" name="sibling_age[]" value="<?php echo $sibling['age']; ?>"></td>
                                            <td><input type="text" class="form-control" name="sibling_occupation[]" value="<?php echo htmlspecialchars($sibling['occupation']); ?>"></td>
                                            <td><input type="text" class="form-control" name="sibling_status[]" value="<?php echo htmlspecialchars($sibling['status']); ?>"></td>
                                            <td><input type="text" class="form-control" name="sibling_address[]" value="<?php echo htmlspecialchars($sibling['address']); ?>"></td>
                                            <td><button type="button" class="btn-danger btn-sm" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td><input type="text" class="form-control" name="sibling_name[]"></td>
                                            <td><input type="number" class="form-control" name="sibling_age[]"></td>
                                            <td><input type="text" class="form-control" name="sibling_occupation[]"></td>
                                            <td><input type="text" class="form-control" name="sibling_status[]"></td>
                                            <td><input type="text" class="form-control" name="sibling_address[]"></td>
                                            <td><button type="button" class="btn-danger btn-sm" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn-secondary btn-sm" style="margin-top: 12px;" onclick="addSiblingRow()">
                            <i class="fas fa-plus"></i> Add Sibling
                        </button>
                    </div>
                </section>
                
                <!-- III. Tactical Information -->
                <section class="form-section">
                    <div class="section-header">
                        <i class="fas fa-info-circle"></i>
                        <h4>III. Tactical Information</h4>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-field full-width">
                            <label>Drugs Involved</label>
                            <input type="text" class="form-control" name="drugs_involved" 
                                   placeholder="Separate multiple drugs with commas"
                                   value="<?php echo htmlspecialchars($profile['drugs_involved']); ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Relationship to Source</label>
                            <input type="text" class="form-control" name="source_relationship" 
                                   value="<?php echo htmlspecialchars($profile['source_relationship']); ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Source Address</label>
                            <input type="text" class="form-control" name="source_address" 
                                   value="<?php echo htmlspecialchars($profile['source_address']); ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Source Name</label>
                            <input type="text" class="form-control" name="source_name" 
                                   value="<?php echo htmlspecialchars($profile['source_name']); ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Source Alias</label>
                            <input type="text" class="form-control" name="source_nickname" 
                                   value="<?php echo htmlspecialchars($profile['source_nickname']); ?>">
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Complete Address of Alleged Source</label>
                            <textarea class="form-control" name="source_full_address" rows="2"><?php echo htmlspecialchars($profile['source_full_address']); ?></textarea>
                        </div>
                        
                        <div class="form-field">
                            <label>Other Drugs Supplied by Source</label>
                            <input type="text" class="form-control" name="source_other_drugs" 
                                   value="<?php echo htmlspecialchars($profile['source_other_drugs']); ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Subgroup Name</label>
                            <input type="text" class="form-control" name="subgroup_name" 
                                   value="<?php echo htmlspecialchars($profile['subgroup_name']); ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Area of Responsibility (AOR)</label>
                            <input type="text" class="form-control" name="specific_aor" 
                                   value="<?php echo htmlspecialchars($profile['specific_aor']); ?>">
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Types of Drugs Pushed by Subject</label>
                            <div class="checkbox-container">
                                <div class="checkbox-group">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="drugs_pushed[]" value="Shabu" id="drugShabu"
                                               <?php echo isDrugPushed('Shabu', $drugsPushedArray) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="drugShabu">Shabu</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="drugs_pushed[]" value="Marijuana" id="drugMarijuana"
                                               <?php echo isDrugPushed('Marijuana', $drugsPushedArray) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="drugMarijuana">Marijuana</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="drugs_pushed[]" value="Ecstasy" id="drugEcstasy"
                                               <?php echo isDrugPushed('Ecstasy', $drugsPushedArray) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="drugEcstasy">Ecstasy</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="drugs_pushed[]" value="Cocaine" id="drugCocaine"
                                               <?php echo isDrugPushed('Cocaine', $drugsPushedArray) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="drugCocaine">Cocaine</label>
                                    </div>
                                </div>
                                <input type="text" class="form-control" style="margin-top: 12px;" name="other_drugs_pushed" 
                                       placeholder="Other drugs not listed above"
                                       value="<?php echo htmlspecialchars($profile['other_drugs_pushed']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-field">
                            <label>Vehicles Used</label>
                            <input type="text" class="form-control" name="vehicles_used"
                                   value="<?php echo htmlspecialchars($profile['vehicles_used']); ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Armaments</label>
                            <input type="text" class="form-control" name="armaments"
                                   value="<?php echo htmlspecialchars($profile['armaments']); ?>">
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Companions During Arrest</label>
                            <textarea class="form-control" name="companions_arrest" rows="2"><?php echo htmlspecialchars($profile['companions_arrest']); ?></textarea>
                        </div>
                    </div>
                </section>

                <!-- IV. Recruitment Summary -->
                <section class="form-section">
                    <div class="section-header">
                        <i class="fas fa-user-plus"></i>
                        <h4>IV. Recruitment Summary</h4>
                    </div>
                    <div class="form-field full-width">
                        <textarea class="form-control" name="recruitment_summary" rows="4"><?php echo htmlspecialchars($profile['recruitment_summary']); ?></textarea>
                    </div>
                </section>

                <!-- V. Drug Operations -->
                <section class="form-section">
                    <div class="section-header">
                        <i class="fas fa-exchange-alt"></i>
                        <h4>V. Modus Operandi</h4>
                    </div>
                    <div class="form-field full-width">
                        <textarea class="form-control" name="modus_operandi" rows="4"><?php echo htmlspecialchars($profile['modus_operandi']); ?></textarea>
                    </div>
                </section>

                <!-- VI. Organizational Structure -->
                <section class="form-section">
                    <div class="section-header">
                        <i class="fas fa-sitemap"></i>
                        <h4>VI. Organizational Structure</h4>
                    </div>
                    <div class="form-field full-width">
                        <textarea class="form-control" name="organizational_structure" rows="3"><?php echo htmlspecialchars($profile['organizational_structure']); ?></textarea>
                    </div>
                </section>

                <!-- VII. CI Matters -->
                <section class="form-section">
                    <div class="section-header">
                        <i class="fas fa-user-secret"></i>
                        <h4>VII. CI Matters</h4>
                    </div>
                    <div class="form-field full-width">
                        <textarea class="form-control" name="ci_matters" rows="3"><?php echo htmlspecialchars($profile['ci_matters']); ?></textarea>
                    </div>
                    
                    <div class="form-field full-width" style="margin-top: 20px;">
                        <label>Other Significant Revelations</label>
                        <textarea class="form-control" name="other_revelations" rows="3"><?php echo htmlspecialchars($profile['other_revelations']); ?></textarea>
                    </div>
                </section>

                <!-- VIII. Recommendation & Status -->
                <section class="form-section">
                    <div class="section-header">
                        <i class="fas fa-check-circle"></i>
                        <h4>VIII. Recommendation & Status</h4>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-field">
                            <label>Recommendation</label>
                            <select class="form-select" name="recommendation">
                                <option value="">Select</option>
                                <option value="For Filing" <?php echo $profile['recommendation'] == 'For Filing' ? 'selected' : ''; ?>>For Filing</option>
                                <option value="For Investigation" <?php echo $profile['recommendation'] == 'For Investigation' ? 'selected' : ''; ?>>For Investigation</option>
                                <option value="For Delisting" <?php echo $profile['recommendation'] == 'For Delisting' ? 'selected' : ''; ?>>For Delisting</option>
                                <option value="For Prosecution" <?php echo $profile['recommendation'] == 'For Prosecution' ? 'selected' : ''; ?>>For Prosecution</option>
                                <option value="Closed" <?php echo $profile['recommendation'] == 'Closed' ? 'selected' : ''; ?>>Closed</option>
                            </select>
                        </div>
                        
                        <div class="form-field">
                            <label>Profile Status</label>
                            <select class="form-select" name="status">
                                <option value="active" <?php echo $profile['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="archived" <?php echo $profile['status'] == 'archived' ? 'selected' : ''; ?>>Archived</option>
                                <option value="delisted" <?php echo $profile['status'] == 'delisted' ? 'selected' : ''; ?>>Delisted</option>
                            </select>
                        </div>
                    </div>
                </section>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="view_profile.php?id=<?php echo $profile['id']; ?>" class="btn-info">
                        <i class="fas fa-eye"></i> View Profile
                    </a>
                    <a href="dashboard.php" class="btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>

        <footer class="site-footer">
            <div class="container">
                <small>Department of the Interior and Local Government | PHILIPPINE NATIONAL POLICE<br>
                BUKIDNON POLICE PROVINCIAL OFFICE | MANOLO FORTICH POLICE STATION<br>
                All data is confidential and for official use only.</small>
            </div>
        </footer>
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
            cells[5].innerHTML = '<button type="button" class="btn-danger btn-sm" onclick="removeRow(this)"><i class="fas fa-trash"></i></button>';
        }
        
        // Function to remove sibling row
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