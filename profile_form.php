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
    <title>Biographical Profile Form</title>
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
            top: 0;
            right: 0;
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

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 30px;
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

        @media (max-width: 768px) {
            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .user-info-header {
                position: relative;
                margin-top: 15px;
                justify-content: center;
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
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <div class="form-title">
                <h2>Biographical Profile Form</h2>
                <p>All information is confidential and for official use only</p>
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
                                   placeholder="Enter full name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Alias</label>
                            <input type="text" class="form-control" name="alias" 
                                   placeholder="Enter alias" value="<?php echo isset($_POST['alias']) ? htmlspecialchars($_POST['alias']) : ''; ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Group/Gang Affiliation</label>
                            <input type="text" class="form-control" name="group_affiliation"
                                   placeholder="If any" value="<?php echo isset($_POST['group_affiliation']) ? htmlspecialchars($_POST['group_affiliation']) : ''; ?>">
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
                                <input type="text" class="form-control" style="margin-top: 12px;" name="position_roles_other" 
                                       placeholder="Other position/s (separate with commas)"
                                       value="<?php echo isset($_POST['position_roles_other']) ? htmlspecialchars($_POST['position_roles_other']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-field">
                            <label>Age</label>
                            <input type="number" class="form-control" name="age" required
                                   value="<?php echo isset($_POST['age']) ? htmlspecialchars($_POST['age']) : ''; ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Sex</label>
                            <select class="form-select" name="sex" required>
                                <option value="">Select</option>
                                <option value="Male" <?php echo (isset($_POST['sex']) && $_POST['sex'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo (isset($_POST['sex']) && $_POST['sex'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo (isset($_POST['sex']) && $_POST['sex'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="form-field">
                            <label>Date of Birth</label>
                            <input type="date" class="form-control" name="dob" required
                                   value="<?php echo isset($_POST['dob']) ? htmlspecialchars($_POST['dob']) : ''; ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Place of Birth</label>
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
                            <input type="text" class="form-control" name="occupation"
                                   value="<?php echo isset($_POST['occupation']) ? htmlspecialchars($_POST['occupation']) : ''; ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Company/Office</label>
                            <input type="text" class="form-control" name="company_office"
                                   value="<?php echo isset($_POST['company_office']) ? htmlspecialchars($_POST['company_office']) : ''; ?>">
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Present Address</label>
                            <input type="text" class="form-control" name="present_address"
                                   value="<?php echo isset($_POST['present_address']) ? htmlspecialchars($_POST['present_address']) : ''; ?>">
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Provincial Address</label>
                            <input type="text" class="form-control" name="provincial_address"
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
                                <option value="Divorced" <?php echo (isset($_POST['civil_status']) && $_POST['civil_status'] == 'Divorced') ? 'selected' : ''; ?>>Divorced</option>
                            </select>
                        </div>
                        
                        <div class="form-field">
                            <label>Citizenship</label>
                            <input type="text" class="form-control" name="citizenship"
                                   value="<?php echo isset($_POST['citizenship']) ? htmlspecialchars($_POST['citizenship']) : 'Filipino'; ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Religion</label>
                            <input type="text" class="form-control" name="religion"
                                   value="<?php echo isset($_POST['religion']) ? htmlspecialchars($_POST['religion']) : ''; ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Height (ft/in)</label>
                            <input type="text" class="form-control" name="height_ft" placeholder="e.g., 5'5&quot;"
                                   value="<?php echo isset($_POST['height_ft']) ? htmlspecialchars($_POST['height_ft']) : ''; ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Weight (kg)</label>
                            <input type="number" step="0.01" class="form-control" name="weight_kg"
                                   value="<?php echo isset($_POST['weight_kg']) ? htmlspecialchars($_POST['weight_kg']) : ''; ?>">
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
                        
                        <div class="form-field full-width">
                            <label>Distinguishing Marks/Tattoo</label>
                            <textarea class="form-control" name="distinguishing_marks" rows="2"><?php echo isset($_POST['distinguishing_marks']) ? htmlspecialchars($_POST['distinguishing_marks']) : ''; ?></textarea>
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
                                    <td><input type="text" class="form-control" name="father_name" value="<?php echo isset($_POST['father_name']) ? htmlspecialchars($_POST['father_name']) : ''; ?>"></td>
                                    <td><input type="text" class="form-control" name="mother_name" value="<?php echo isset($_POST['mother_name']) ? htmlspecialchars($_POST['mother_name']) : ''; ?>"></td>
                                </tr>
                                <tr>
                                    <td><strong>Address</strong></td>
                                    <td><input type="text" class="form-control" name="father_address" value="<?php echo isset($_POST['father_address']) ? htmlspecialchars($_POST['father_address']) : ''; ?>"></td>
                                    <td><input type="text" class="form-control" name="mother_address" value="<?php echo isset($_POST['mother_address']) ? htmlspecialchars($_POST['mother_address']) : ''; ?>"></td>
                                </tr>
                                <tr>
                                    <td><strong>Date of Birth</strong></td>
                                    <td><input type="date" class="form-control" name="father_dob" value="<?php echo isset($_POST['father_dob']) ? htmlspecialchars($_POST['father_dob']) : ''; ?>"></td>
                                    <td><input type="date" class="form-control" name="mother_dob" value="<?php echo isset($_POST['mother_dob']) ? htmlspecialchars($_POST['mother_dob']) : ''; ?>"></td>
                                </tr>
                                <tr>
                                    <td><strong>Occupation</strong></td>
                                    <td><input type="text" class="form-control" name="father_occupation" value="<?php echo isset($_POST['father_occupation']) ? htmlspecialchars($_POST['father_occupation']) : ''; ?>"></td>
                                    <td><input type="text" class="form-control" name="mother_occupation" value="<?php echo isset($_POST['mother_occupation']) ? htmlspecialchars($_POST['mother_occupation']) : ''; ?>"></td>
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
                                    <td><input type="text" class="form-control" name="spouse_name" value="<?php echo isset($_POST['spouse_name']) ? htmlspecialchars($_POST['spouse_name']) : ''; ?>"></td>
                                    <td><input type="number" class="form-control" name="spouse_age" value="<?php echo isset($_POST['spouse_age']) ? htmlspecialchars($_POST['spouse_age']) : ''; ?>"></td>
                                    <td><input type="text" class="form-control" name="spouse_occupation" value="<?php echo isset($_POST['spouse_occupation']) ? htmlspecialchars($_POST['spouse_occupation']) : ''; ?>"></td>
                                    <td><input type="text" class="form-control" name="spouse_address" value="<?php echo isset($_POST['spouse_address']) ? htmlspecialchars($_POST['spouse_address']) : ''; ?>"></td>
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
                                    <tr>
                                        <td><input type="text" class="form-control" name="sibling_name[]"></td>
                                        <td><input type="number" class="form-control" name="sibling_age[]"></td>
                                        <td><input type="text" class="form-control" name="sibling_occupation[]"></td>
                                        <td><input type="text" class="form-control" name="sibling_status[]"></td>
                                        <td><input type="text" class="form-control" name="sibling_address[]"></td>
                                        <td><button type="button" class="btn-secondary btn-sm" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
                                    </tr>
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
                                   value="<?php echo isset($_POST['drugs_involved']) ? htmlspecialchars($_POST['drugs_involved']) : ''; ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Relationship to Source</label>
                            <input type="text" class="form-control" name="source_relationship" 
                                   value="<?php echo isset($_POST['source_relationship']) ? htmlspecialchars($_POST['source_relationship']) : ''; ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Source Address</label>
                            <input type="text" class="form-control" name="source_address" 
                                   value="<?php echo isset($_POST['source_address']) ? htmlspecialchars($_POST['source_address']) : ''; ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Source Name</label>
                            <input type="text" class="form-control" name="source_name" 
                                   value="<?php echo isset($_POST['source_name']) ? htmlspecialchars($_POST['source_name']) : ''; ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Source Alias</label>
                            <input type="text" class="form-control" name="source_nickname" 
                                   value="<?php echo isset($_POST['source_nickname']) ? htmlspecialchars($_POST['source_nickname']) : ''; ?>">
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Complete Address of Alleged Source</label>
                            <textarea class="form-control" name="source_full_address" rows="2"><?php echo isset($_POST['source_full_address']) ? htmlspecialchars($_POST['source_full_address']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-field">
                            <label>Other Drugs Supplied by Source</label>
                            <input type="text" class="form-control" name="source_other_drugs" 
                                   value="<?php echo isset($_POST['source_other_drugs']) ? htmlspecialchars($_POST['source_other_drugs']) : ''; ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Subgroup Name</label>
                            <input type="text" class="form-control" name="subgroup_name" 
                                   value="<?php echo isset($_POST['subgroup_name']) ? htmlspecialchars($_POST['subgroup_name']) : ''; ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Area of Responsibility (AOR)</label>
                            <input type="text" class="form-control" name="specific_aor" 
                                   value="<?php echo isset($_POST['specific_aor']) ? htmlspecialchars($_POST['specific_aor']) : ''; ?>">
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Types of Drugs Pushed by Subject</label>
                            <div class="checkbox-container">
                                <div class="checkbox-group">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="drugs_pushed[]" value="Shabu" id="drugShabu"
                                               <?php echo (isset($_POST['drugs_pushed']) && in_array('Shabu', $_POST['drugs_pushed'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="drugShabu">Shabu</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="drugs_pushed[]" value="Marijuana" id="drugMarijuana"
                                               <?php echo (isset($_POST['drugs_pushed']) && in_array('Marijuana', $_POST['drugs_pushed'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="drugMarijuana">Marijuana</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="drugs_pushed[]" value="Ecstasy" id="drugEcstasy"
                                               <?php echo (isset($_POST['drugs_pushed']) && in_array('Ecstasy', $_POST['drugs_pushed'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="drugEcstasy">Ecstasy</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="drugs_pushed[]" value="Cocaine" id="drugCocaine"
                                               <?php echo (isset($_POST['drugs_pushed']) && in_array('Cocaine', $_POST['drugs_pushed'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="drugCocaine">Cocaine</label>
                                    </div>
                                </div>
                                <input type="text" class="form-control" style="margin-top: 12px;" name="other_drugs_pushed" 
                                       placeholder="Other drugs not listed above"
                                       value="<?php echo isset($_POST['other_drugs_pushed']) ? htmlspecialchars($_POST['other_drugs_pushed']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-field">
                            <label>Vehicles Used</label>
                            <input type="text" class="form-control" name="vehicles_used"
                                   value="<?php echo isset($_POST['vehicles_used']) ? htmlspecialchars($_POST['vehicles_used']) : ''; ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Armaments</label>
                            <input type="text" class="form-control" name="armaments"
                                   value="<?php echo isset($_POST['armaments']) ? htmlspecialchars($_POST['armaments']) : ''; ?>">
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
                        <textarea class="form-control" name="recruitment_summary" rows="4" 
                                  placeholder="Describe how the suspect was recruited..."><?php echo isset($_POST['recruitment_summary']) ? htmlspecialchars($_POST['recruitment_summary']) : ''; ?></textarea>
                    </div>
                </section>

                <!-- V. Drug Operations -->
                <section class="form-section">
                    <div class="section-header">
                        <i class="fas fa-exchange-alt"></i>
                        <h4>V. Modus Operandi</h4>
                    </div>
                    <div class="form-field full-width">
                        <textarea class="form-control" name="modus_operandi" rows="4" 
                                  placeholder="Describe the modus operandi..."><?php echo isset($_POST['modus_operandi']) ? htmlspecialchars($_POST['modus_operandi']) : ''; ?></textarea>
                    </div>
                </section>

                <!-- VI. Arrest Record -->
                <section class="form-section">
                    <div class="section-header">
                        <i class="fas fa-gavel"></i>
                        <h4>VI. Arrest Record</h4>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-field full-width">
                            <label>Previous Arrest Record</label>
                            <textarea class="form-control" name="previous_arrest" rows="2"><?php echo isset($_POST['previous_arrest']) ? htmlspecialchars($_POST['previous_arrest']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Specific Charge</label>
                            <input type="text" class="form-control" name="specific_charge"
                                   value="<?php echo isset($_POST['specific_charge']) ? htmlspecialchars($_POST['specific_charge']) : ''; ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Date/Time of Arrest</label>
                            <input type="datetime-local" class="form-control" name="arrest_datetime"
                                   value="<?php echo isset($_POST['arrest_datetime']) ? htmlspecialchars($_POST['arrest_datetime']) : ''; ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Place of Arrest</label>
                            <input type="text" class="form-control" name="arrest_place"
                                   value="<?php echo isset($_POST['arrest_place']) ? htmlspecialchars($_POST['arrest_place']) : ''; ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Arresting Officer</label>
                            <input type="text" class="form-control" name="arresting_officer"
                                   value="<?php echo isset($_POST['arresting_officer']) ? htmlspecialchars($_POST['arresting_officer']) : ''; ?>">
                        </div>
                        
                        <div class="form-field">
                            <label>Unit/Office</label>
                            <input type="text" class="form-control" name="arresting_unit"
                                   value="<?php echo isset($_POST['arresting_unit']) ? htmlspecialchars($_POST['arresting_unit']) : ''; ?>">
                        </div>
                    </div>
                </section>

                <!-- VII. Recommendation & Status -->
                <section class="form-section">
                    <div class="section-header">
                        <i class="fas fa-check-circle"></i>
                        <h4>VII. Recommendation & Status</h4>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-field">
                            <label>Recommendation</label>
                            <select class="form-select" name="recommendation">
                                <option value="">Select</option>
                                <option value="For Filing" <?php echo (isset($_POST['recommendation']) && $_POST['recommendation'] == 'For Filing') ? 'selected' : ''; ?>>For Filing</option>
                                <option value="For Investigation" <?php echo (isset($_POST['recommendation']) && $_POST['recommendation'] == 'For Investigation') ? 'selected' : ''; ?>>For Investigation</option>
                                <option value="For Delisting" <?php echo (isset($_POST['recommendation']) && $_POST['recommendation'] == 'For Delisting') ? 'selected' : ''; ?>>For Delisting</option>
                                <option value="For Prosecution" <?php echo (isset($_POST['recommendation']) && $_POST['recommendation'] == 'For Prosecution') ? 'selected' : ''; ?>>For Prosecution</option>
                                <option value="Closed" <?php echo (isset($_POST['recommendation']) && $_POST['recommendation'] == 'Closed') ? 'selected' : ''; ?>>Closed</option>
                            </select>
                        </div>
                        
                        <div class="form-field">
                            <label>Profile Status</label>
                            <select class="form-select" name="status">
                                <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="archived" <?php echo (isset($_POST['status']) && $_POST['status'] == 'archived') ? 'selected' : ''; ?>>Archived</option>
                                <option value="delisted" <?php echo (isset($_POST['status']) && $_POST['status'] == 'delisted') ? 'selected' : ''; ?>>Delisted</option>
                            </select>
                        </div>
                    </div>
                </section>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Save Profile
                    </button>
                    <button type="reset" class="btn-secondary">
                        <i class="fas fa-undo"></i> Reset
                    </button>
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
            cells[5].innerHTML = '<button type="button" class="btn-secondary btn-sm" onclick="removeRow(this)"><i class="fas fa-trash"></i></button>';
        }
        
        function removeRow(button) {
            const row = button.closest('tr');
            const tableBody = row.parentNode;
            
            if (tableBody.rows.length > 1) {
                row.remove();
            } else {
                alert('At least one sibling row must remain.');
            }
        }
        
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