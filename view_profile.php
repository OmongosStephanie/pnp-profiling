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

// Get return URL (where to go back)
$return_to = isset($_GET['return_to']) ? $_GET['return_to'] : 'profiles.php';
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
$query = "SELECT * FROM siblings WHERE profile_id = :profile_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':profile_id', $id);
$stmt->execute();
$siblings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get creator info
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
function displayValue($value, $default = '—') {
    return !empty($value) ? htmlspecialchars($value) : $default;
}

// Parse arrays
$drugsPushedArray = !empty($profile['drugs_pushed']) ? explode(', ', $profile['drugs_pushed']) : [];
$drugTypesArray = !empty($profile['drugs_involved']) ? explode(', ', $profile['drugs_involved']) : [];
$positionRolesArray = !empty($profile['position_roles']) ? explode(', ', $profile['position_roles']) : [];

// Parse date_time_place_of_arrest to get formatted date and place
$arrest_datetime_formatted = '';
$arrest_place = '';
$arrest_year = '';
$arrest_month = '';
$arrest_date_full = '';

if (!empty($profile['date_time_place_of_arrest'])) {
    $arrest_value = $profile['date_time_place_of_arrest'];
    
    // Try to parse the datetime
    $timestamp = strtotime($arrest_value);
    if ($timestamp !== false) {
        $arrest_datetime_formatted = date('M d, Y H:i', $timestamp);
        $arrest_year = date('Y', $timestamp);
        $arrest_month = date('m', $timestamp);
        $arrest_date_full = date('F d, Y', $timestamp);
        
        // If the value contains a place (separated by space), try to extract it
        if (strpos($arrest_value, ' ') !== false) {
            $parts = explode(' ', $arrest_value, 2);
            if (count($parts) == 2 && strtotime($parts[0]) !== false) {
                $arrest_place = $parts[1];
            } else {
                $arrest_place = $arrest_value;
            }
        }
    } else {
        $arrest_datetime_formatted = $arrest_value;
        $arrest_place = $arrest_value;
    }
}

// Determine back link and text
if ($return_to == 'barangay' && !empty($barangay)) {
    $back_link = "barangay_profiles.php?barangay=" . urlencode($barangay);
    $back_text = "Back to " . htmlspecialchars($barangay);
} else {
    $back_link = "profiles.php";
    $back_text = "Back to Profiles";
}

// Get current date
$currentDate = date('F d, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>PNP Biographical Profiling System - View Profile</title>
    
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
            background: #f0f2f5;
            color: #1e293b;
            line-height: 1.5;
            padding: 20px;
        }

        /* Form Container */
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
            padding: 20px 25px;
            text-align: center;
            border-bottom: 3px solid #c9a959;
        }
        
        .org-name {
            margin-bottom: 5px;
        }
        
        .org-name .dilg {
            font-size: 10px;
            font-weight: 300;
            letter-spacing: 0.5px;
            color: #b0c4de;
        }
        
        .org-name .pnp {
            font-size: 20px;
            font-weight: 700;
            color: white;
            letter-spacing: 1px;
        }
        
        .org-name .provincial {
            font-size: 12px;
            font-weight: 500;
            color: #c9a959;
        }
        
        .station-details {
            margin-top: 5px;
        }
        
        .station-name {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 2px;
        }
        
        .station-address, .station-phone {
            font-size: 10px;
            color: #b0c4de;
        }
        
        .station-phone i {
            color: #c9a959;
            margin-right: 4px;
        }
        
        .user-info {
            margin-top: 8px;
            display: inline-block;
            background: rgba(255,255,255,0.1);
            padding: 4px 15px;
            border-radius: 30px;
            font-size: 10px;
        }
        
        .user-info i {
            color: #c9a959;
            margin-right: 6px;
        }

        .main-content {
            padding: 20px;
        }

        .form-title {
            text-align: center;
            margin-bottom: 15px;
        }

        .form-title h2 {
            font-size: 18px;
            font-weight: 700;
            color: #0a2f4d;
            margin-bottom: 3px;
        }

        .form-title p {
            font-size: 9px;
            color: #64748b;
        }

        .profile-id-badge {
            background: #0a2f4d;
            color: white;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 10px;
            display: inline-block;
            margin-bottom: 12px;
        }

        .profile-id-badge i {
            margin-right: 5px;
            color: #c9a959;
        }

        .photo-section {
            background: #f8fafc;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
        }

        .photo-container {
            text-align: center;
        }

        .photo-box {
            width: 100px;
            height: 100px;
            border: 2px solid #0a2f4d;
            border-radius: 6px;
            overflow: hidden;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 5px;
        }

        .photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-box i {
            font-size: 45px;
            color: #94a3b8;
        }

        .photo-label {
            font-size: 8px;
            color: #475569;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 3px;
        }

        .form-section {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            margin-bottom: 12px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }

        .section-title {
            background: #f8fafc;
            padding: 8px 15px;
            font-weight: 600;
            font-size: 12px;
            color: #0a2f4d;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .section-title i {
            color: #c9a959;
            font-size: 12px;
        }

        .section-content {
            padding: 12px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 10px;
        }

        .form-field {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .form-field.full-width {
            grid-column: 1 / -1;
        }

        .form-field label {
            font-size: 9px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 10px;
            color: #1e293b;
            background: #f8fafc;
            width: 100%;
        }

        .form-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .form-table th {
            background: #f8fafc;
            padding: 6px 8px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            font-size: 9px;
            text-transform: uppercase;
            border: 1px solid #e2e8f0;
        }

        .form-table td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
        }

        .tag-group {
            display: flex;
            flex-wrap: wrap;
            gap: 3px;
        }

        .tag {
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 500;
            color: white;
        }

        .tag.shabu { background: #ef4444; }
        .tag.marijuana { background: #10b981; }
        .tag.other { background: #6b7280; }

        .btn {
            padding: 6px 16px;
            border-radius: 25px;
            font-size: 11px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        .btn-print {
            background: #0891b2;
            color: white;
        }

        .btn-print:hover {
            background: #0e7490;
        }

        .action-bar {
            padding: 15px;
            display: flex;
            justify-content: center;
            gap: 10px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            margin-top: 15px;
            border-radius: 0 0 12px 12px;
        }

        .arrest-date-link {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 9px;
            font-weight: 500;
            text-decoration: none;
        }

        .form-footer {
            text-align: center;
            padding: 10px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 8px;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .org-name .pnp {
                font-size: 16px;
            }
            
            .org-name .provincial {
                font-size: 10px;
            }
            
            .station-name {
                font-size: 13px;
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
                padding: 12px;
            }
            
            .section-content {
                padding: 10px;
            }
            
            .form-table {
                font-size: 9px;
            }
            
            .form-table th, .form-table td {
                padding: 4px 6px;
            }
        }

        /* PRINT STYLES - HEADER UG PICTURE DILI MAG BULAG */
        @media print {
            @page {
                size: A4;
                margin: 0.5cm;
            }

            /* Reset all elements for print */
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            html, body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                height: auto !important;
                width: 100% !important;
                overflow: visible !important;
            }

            body {
                font-size: 9pt;
                line-height: 1.2;
            }

            /* Hide non-printable */
            .action-bar,
            .btn,
            .btn-print,
            .no-print,
            .user-info {
                display: none !important;
            }

            /* Container */
            .form-container {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                background: white !important;
                overflow: visible !important;
                border-radius: 0 !important;
            }

            /* HEADER UG PICTURE - DILI MAG BULAG, MAG STAY TOGETHER */
            .pnp-header,
            .photo-section {
                break-inside: avoid !important;
                page-break-inside: avoid !important;
                page-break-after: avoid !important;
                page-break-before: avoid !important;
            }

            .pnp-header {
                background: #0a2f4d !important;
                padding: 5px 8px !important;
                border-bottom: 2px solid #c9a959 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                width: 100% !important;
                display: block !important;
            }

            .pnp-header .pnp {
                font-size: 12px !important;
            }

            .pnp-header .station {
                font-size: 10px !important;
            }

            .pnp-header .provincial {
                font-size: 9px !important;
            }

            .pnp-header .dilg,
            .pnp-header .address,
            .pnp-header .phone {
                font-size: 7px !important;
            }

            /* PHOTO SECTION - DILI MAG BULAG */
            .photo-section {
                width: 100% !important;
                display: block !important;
                margin-bottom: 10px !important;
            }

            .photo-box {
                width: 70px !important;
                height: 70px !important;
            }

            .photo-box i {
                font-size: 30px !important;
            }

            .photo-label {
                font-size: 6px !important;
            }

            /* Sections - DILI MAG BREAK */
            .form-section {
                break-inside: avoid !important;
                page-break-inside: avoid !important;
                border: 1px solid #ccc !important;
                margin-bottom: 8px !important;
                overflow: visible !important;
                width: 100% !important;
                display: block !important;
            }

            .section-title {
                background: #f0f0f0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                padding: 4px 8px !important;
                font-size: 9px !important;
            }

            /* Tables */
            .form-table {
                width: 100% !important;
                border-collapse: collapse !important;
            }

            .form-table th,
            .form-table td {
                border: 1px solid #ccc !important;
                padding: 3px 5px !important;
                font-size: 8px !important;
            }

            .form-table th {
                background: #f5f5f5 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* Form controls */
            .form-control {
                border: 1px solid #ccc !important;
                background: white !important;
                padding: 3px 6px !important;
                font-size: 8px !important;
            }

            /* GRID TO BLOCK - DILI MAG BREAK */
            .form-grid {
                display: block !important;
                width: 100% !important;
            }

            .form-field {
                width: 100% !important;
                margin-bottom: 4px !important;
                display: block !important;
            }

            /* Labels */
            .form-field label {
                font-size: 7px !important;
            }

            /* Tags */
            .tag {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                display: inline-block !important;
                padding: 1px 3px !important;
                font-size: 6px !important;
                margin: 1px !important;
            }
            
            .tag.shabu { background: #ef4444 !important; color: white !important; }
            .tag.marijuana { background: #10b981 !important; color: white !important; }
            .tag.other { background: #6b7280 !important; color: white !important; }

            /* Arrest badge */
            .arrest-date-link {
                background: #fef3c7 !important;
                color: #92400e !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                display: inline-block !important;
                padding: 1px 4px !important;
                font-size: 7px !important;
            }

            /* Profile badge */
            .profile-id-badge {
                background: #0a2f4d !important;
                color: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                padding: 2px 5px !important;
                font-size: 7px !important;
            }

            /* Title */
            .form-title h2 {
                font-size: 11px !important;
                margin-bottom: 2px !important;
            }

            .form-title p {
                font-size: 6px !important;
            }

            /* Footer */
            .form-footer {
                border-top: 1px solid #ccc !important;
                margin-top: 8px !important;
                padding: 4px !important;
                font-size: 6px !important;
            }

            /* Main content */
            .main-content {
                padding: 8px !important;
            }

            .section-content {
                padding: 6px !important;
            }

            /* Force all colors */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
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
            <div class="user-info no-print">
                <i class="fas fa-user-shield"></i>
                <span><?php echo $_SESSION['rank'] . ' ' . $_SESSION['full_name']; ?></span>
            </div>
        </div>

        <div class="main-content">
            <div class="form-title">
                <h2>BIOGRAPHICAL DATA FORM</h2>
                <p>All information is confidential and for official use only</p>
            </div>

            <!-- PHOTO SECTION -->
            <div class="photo-section">
                <div class="photo-container">
                    <div class="photo-box">
                        <?php if (!empty($profile['profile_picture'])): ?>
                            <img src="<?php echo $profile['profile_picture']; ?>" alt="Profile Picture">
                        <?php else: ?>
                            <i class="fas fa-user-circle"></i>
                        <?php endif; ?>
                    </div>
                    <div class="photo-label">2x2 Official Photo</div>
                </div>
            </div>

            <!-- I. PERSONAL DATA -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fas fa-user"></i> I. PERSONAL DATA
                </div>
                <div class="section-content">
                    <div class="form-grid">
                        <div class="form-field full-width">
                            <label>Full Name</label>
                            <div class="form-control"><?php echo displayValue($profile['full_name']); ?></div>
                        </div>
                        
                        <div class="form-field">
                            <label>Alias</label>
                            <div class="form-control"><?php echo displayValue($profile['alias']); ?></div>
                        </div>
                        
                        <div class="form-field">
                            <label>Group/Gang Affiliation</label>
                            <div class="form-control"><?php echo displayValue($profile['group_affiliation']); ?></div>
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Position/Role</label>
                            <div class="form-control"><?php echo displayValue($profile['position_roles']); ?></div>
                        </div>
                        
                        <div class="form-field">
                            <label>Age</label>
                            <div class="form-control"><?php echo displayValue($profile['age']); ?></div>
                        </div>
                        
                        <div class="form-field">
                            <label>Sex</label>
                            <div class="form-control"><?php echo displayValue($profile['sex']); ?></div>
                        </div>
                        
                        <div class="form-field">
                            <label>Date of Birth</label>
                            <div class="form-control"><?php echo !empty($profile['dob']) ? date('M d, Y', strtotime($profile['dob'])) : '—'; ?></div>
                        </div>
                        
                        <div class="form-field">
                            <label>Place of Birth</label>
                            <div class="form-control"><?php echo displayValue($profile['pob']); ?></div>
                        </div>
                        
                        <div class="form-field">
                            <label>Educational Attainment</label>
                            <div class="form-control"><?php echo displayValue($profile['educational_attainment']); ?></div>
                        </div>
                        
                        <div class="form-field">
                            <label>Occupation</label>
                            <div class="form-control"><?php echo displayValue($profile['occupation']); ?></div>
                        </div>
                        
                        <div class="form-field">
                            <label>Company/Office</label>
                            <div class="form-control"><?php echo displayValue($profile['company_office']); ?></div>
                        </div>
                        
                        <div class="form-field">
                            <label>Technical Skills</label>
                            <div class="form-control"><?php echo displayValue($profile['technical_skills']); ?></div>
                        </div>
                        
                        <div class="form-field">
                            <label>Ethnic Group</label>
                            <div class="form-control"><?php echo displayValue($profile['ethnic_group']); ?></div>
                        </div>
                        
                        <div class="form-field">
                            <label>Languages/Dialects</label>
                            <div class="form-control"><?php echo displayValue($profile['languages']); ?></div>
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Present Address</label>
                            <div class="form-control"><?php echo displayValue($profile['present_address']); ?></div>
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Provincial Address</label>
                            <div class="form-control"><?php echo displayValue($profile['provincial_address']); ?></div>
                        </div>
                        
                        <div class="form-field">
                            <label>Civil Status</label>
                            <div class="form-control"><?php echo displayValue($profile['civil_status']); ?></div>
                        </div>
                        
                        <div class="form-field">
                            <label>Citizenship</label>
                            <div class="form-control"><?php echo displayValue($profile['citizenship']); ?></div>
                        </div>
                        
                        <div class="form-field">
                            <label>Religion</label>
                            <div class="form-control"><?php echo displayValue($profile['religion']); ?></div>
                        </div>
                        
                        <div class="form-field">
                            <label>Height (ft/in)</label>
                            <div class="form-control"><?php echo displayValue($profile['height_ft']); ?></div>
                        </div>
                        
                        <div class="form-field">
                            <label>Weight (kg)</label>
                            <div class="form-control"><?php echo displayValue($profile['weight_kg']); ?></div>
                        </div>
                        
                        <div class="form-field">
                            <label>Eyes Color</label>
                            <div class="form-control"><?php echo displayValue($profile['eyes_color']); ?></div>
                        </div>
                        
                        <div class="form-field">
                            <label>Hair Color</label>
                            <div class="form-control"><?php echo displayValue($profile['hair_color']); ?></div>
                        </div>
                        
                        <div class="form-field">
                            <label>Built</label>
                            <div class="form-control"><?php echo displayValue($profile['built']); ?></div>
                        </div>
                        
                        <div class="form-field">
                            <label>Complexion</label>
                            <div class="form-control"><?php echo displayValue($profile['complexion']); ?></div>
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Distinguishing Marks/Tattoo</label>
                            <div class="form-control" style="min-height: 50px;"><?php echo displayValue($profile['distinguishing_marks']); ?></div>
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Previous Arrest Record</label>
                            <div class="form-control"><?php echo displayValue($profile['previous_arrest']); ?></div>
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Specific Charge</label>
                            <div class="form-control"><?php echo displayValue($profile['specific_charge']); ?></div>
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Date/Time/Place of Arrest</label>
                            <div class="form-control">
                                <?php if (!empty($arrest_datetime_formatted)): ?>
                                    <a href="dashboard.php?year=<?php echo $arrest_year; ?>&month=<?php echo $arrest_month; ?>" class="arrest-date-link" target="_blank">
                                        <i class="fas fa-calendar-check"></i> <?php echo $arrest_datetime_formatted; ?>
                                    </a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Name of Arresting Officer</label>
                            <div class="form-control"><?php echo displayValue($profile['arresting_officer']); ?></div>
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Unit/Office of Arresting Officer</label>
                            <div class="form-control"><?php echo displayValue($profile['arresting_unit']); ?></div>
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
                                <th style="width: 80px;"></th>
                                <th>Father</th>
                                <th>Mother</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Name</strong></td>
                                <td><?php echo displayValue($profile['father_name']); ?></td>
                                <td><?php echo displayValue($profile['mother_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Address</strong></td>
                                <td><?php echo displayValue($profile['father_address']); ?></td>
                                <td><?php echo displayValue($profile['mother_address']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Date of Birth</strong></td>
                                <td><?php echo !empty($profile['father_dob']) ? date('M d, Y', strtotime($profile['father_dob'])) : '—'; ?></td>
                                <td><?php echo !empty($profile['mother_dob']) ? date('M d, Y', strtotime($profile['mother_dob'])) : '—'; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Age</strong></td>
                                <td><?php echo displayValue($profile['father_age']); ?></td>
                                <td><?php echo displayValue($profile['mother_age']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Occupation</strong></td>
                                <td><?php echo displayValue($profile['father_occupation']); ?></td>
                                <td><?php echo displayValue($profile['mother_occupation']); ?></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 10px;">
                        <table class="form-table">
                            <thead>
                                <tr>
                                    <th colspan="5">Spouse</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <th style="width: 100px;">Name</th>
                                    <th style="width: 60px;">Age</th>
                                    <th style="width: 100px;">Birthday</th>
                                    <th>Occupation</th>
                                    <th>Address</th>
                                </tr>
                                <tr>
                                    <td><?php echo displayValue($profile['spouse_name']); ?></td>
                                    <td><?php echo displayValue($profile['spouse_age']); ?></td>
                                    <td><?php echo !empty($profile['spouse_birthday']) ? date('M d, Y', strtotime($profile['spouse_birthday'])) : '—'; ?></td>
                                    <td><?php echo displayValue($profile['spouse_occupation']); ?></td>
                                    <td><?php echo displayValue($profile['spouse_address']); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (count($siblings) > 0): ?>
                    <div style="margin-top: 10px;">
                        <label><strong>Siblings</strong></label>
                        <table class="form-table" style="margin-top: 3px;">
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
                    <?php endif; ?>
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
                            <div class="form-control">
                                <?php if (!empty($drugTypesArray)): ?>
                                    <div class="tag-group">
                                    <?php foreach ($drugTypesArray as $drug): ?>
                                        <span class="tag <?php echo stripos($drug, 'shabu') !== false ? 'shabu' : (stripos($drug, 'marijuana') !== false ? 'marijuana' : 'other'); ?>">
                                            <?php echo trim($drug); ?>
                                        </span>
                                    <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <?php echo displayValue($profile['drugs_involved']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Vehicles Used</label>
                            <div class="form-control"><?php echo displayValue($profile['vehicles_used']); ?></div>
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Armaments</label>
                            <div class="form-control" style="min-height: 50px;"><?php echo displayValue($profile['armaments']); ?></div>
                        </div>
                        
                        <div class="form-field">
                            <label>Companion/s During Arrest</label>
                            <div class="form-control"><?php echo displayValue($profile['companions_arrest']); ?></div>
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Relationship/Address of Source</label>
                            <div class="form-control"><?php echo displayValue($profile['source_relationship']); ?></div>
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Name/Source of Drugs Involved</label>
                            <div class="form-control"><?php echo displayValue($profile['source_name']); ?></div>
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Address of Alleged Source</label>
                            <div class="form-control"><?php echo displayValue($profile['source_address']); ?></div>
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Other Types of Drugs Supplied by Source</label>
                            <div class="form-control"><?php echo displayValue($profile['source_other_drugs']); ?></div>
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Subgroups and Specific AOR</label>
                            <div class="form-control"><?php echo displayValue($profile['subgroup_name']); ?> - <?php echo displayValue($profile['specific_aor']); ?></div>
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Other Subject Known as Source</label>
                            <div class="form-control"><?php echo displayValue($profile['other_source_name']); ?></div>
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Types of Drugs Pushed by Subject</label>
                            <div class="form-control">
                                <?php if (!empty($drugsPushedArray)): ?>
                                    <div class="tag-group">
                                    <?php foreach ($drugsPushedArray as $drug): ?>
                                        <span class="tag <?php echo stripos($drug, 'shabu') !== false ? 'shabu' : (stripos($drug, 'marijuana') !== false ? 'marijuana' : 'other'); ?>">
                                            <?php echo trim($drug); ?>
                                        </span>
                                    <?php endforeach; ?>
                                    <?php if (!empty($profile['other_drugs_pushed'])): ?>
                                        <span class="tag other"><?php echo $profile['other_drugs_pushed']; ?></span>
                                    <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <?php echo displayValue($profile['other_drugs_pushed']); ?>
                                <?php endif; ?>
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
                        <div class="form-control" style="min-height: 50px;"><?php echo displayValue($profile['recruitment_summary'], 'Not specified'); ?></div>
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
                        <div class="form-control" style="min-height: 50px;"><?php echo displayValue($profile['modus_operandi'], 'Not specified'); ?></div>
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
                            <div class="form-control" style="min-height: 40px;"><?php echo displayValue($profile['ci_matters'], 'NONE'); ?></div>
                        </div>
                        
                        <div class="form-field full-width">
                            <label>Other Revelations</label>
                            <div class="form-control" style="min-height: 40px;"><?php echo displayValue($profile['other_revelations'], 'Not specified'); ?></div>
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
                        <div class="form-control" style="min-height: 50px; font-weight: 500;"><?php echo displayValue($profile['recommendation'], 'Not specified'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-bar no-print">
                <a href="<?php echo $back_link; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> <?php echo $back_text; ?>
                </a>
                <button onclick="window.print()" class="btn btn-print">
                    <i class="fas fa-print"></i> Print Profile
                </button>
            </div>
        </div>

        <div class="form-footer">
            <small>Department of the Interior and Local Government | PHILIPPINE NATIONAL POLICE<br>
            BUKIDNON POLICE PROVINCIAL OFFICE | MANOLO FORTICH POLICE STATION<br>
            All data is confidential and for official use only.</small>
        </div>
    </div>
</body>
</html>