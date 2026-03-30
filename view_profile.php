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
    <title>PNP Biographical Profiling System</title>
    
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
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.4;
            padding: 20px;
        }

        /* Print-optimized container */
        .print-container {
            max-width: 1100px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        /* Official PNP Header - Compact */
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

        /* Main Content */
        .main-content {
            padding: 20px;
        }

        /* PHOTO SECTION - Right Aligned */
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

        .photo-caption {
            font-size: 10px;
            color: #64748b;
            margin-top: 4px;
            text-align: center;
        }

        /* Section Styles */
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

        /* Compact Table */
        .compact-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .compact-table th {
            background: #f8fafc;
            padding: 6px 8px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            font-size: 11px;
            text-transform: uppercase;
            border: 1px solid #e2e8f0;
        }

        .compact-table td {
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            color: #1e293b;
        }

        /* Personal Data Table */
        .personal-data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .personal-data-table th {
            background: #f8fafc;
            padding: 8px 10px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            font-size: 11px;
            text-transform: uppercase;
            border: 1px solid #e2e8f0;
            width: 220px;
        }

        .personal-data-table td {
            padding: 8px 10px;
            border: 1px solid #e2e8f0;
            color: #1e293b;
        }

        /* Tags */
        .tag-group {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }

        .tag {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            color: white;
        }

        .tag.shabu { background: #ef4444; }
        .tag.marijuana { background: #10b981; }
        .tag.other { background: #6b7280; }

        /* Action Bar */
        .action-bar {
            padding: 15px 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        .btn {
            padding: 8px 16px;
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

        .btn-secondary {
            background: white;
            color: #334155;
            border: 1px solid #e2e8f0;
        }

        .btn-print {
            background: #0891b2;
            color: white;
        }

        .btn-delete {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Arrest Date Badge */
        .arrest-date-link {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
        }

        .arrest-date-link:hover {
            background: #fde68a;
            transform: translateY(-1px);
        }

        /* Official Footer */
        .official-footer {
            margin-top: 30px;
            padding: 20px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            text-align: center;
        }

        .footer-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .footer-logo {
            width: 60px;
            height: 60px;
            background: #0a2f4d;
            color: #c9a959;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 30px;
        }

        .footer-text {
            font-size: 11px;
            color: #475569;
            line-height: 1.6;
        }

        .footer-text strong {
            color: #0a2f4d;
            font-weight: 600;
        }

        .footer-divider {
            width: 100px;
            height: 2px;
            background: #c9a959;
            margin: 15px auto;
        }

        .footer-signature {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed #e2e8f0;
            font-size: 10px;
            color: #64748b;
        }

        /* Action Bar */
        .action-bar {
            padding: 15px 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            margin-top: 20px;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .personal-data-table th,
            .personal-data-table td {
                display: block;
                width: 100%;
            }
            
            .personal-data-table tr {
                display: block;
                margin-bottom: 10px;
                border: 1px solid #e2e8f0;
            }
            
            .compact-table {
                font-size: 11px;
            }
            
            .compact-table th,
            .compact-table td {
                padding: 4px 6px;
            }
            
            .action-bar {
                flex-direction: column;
            }
            
            .btn {
                justify-content: center;
                width: 100%;
            }
            
            .photo-section {
                justify-content: center;
            }
        }

        /* PRINT STYLES */
        @media print {
            @page {
                size: A4;
                margin: 1.5cm;
                @top-left { content: "" !important; }
                @top-center { content: "" !important; }
                @top-right { content: "" !important; }
                @bottom-left { content: "" !important; }
                @bottom-center { content: "" !important; }
                @bottom-right { content: "" !important; }
            }

            @page :first {
                margin-top: 1.5cm;
            }

            html, body {
                height: 100%;
                margin: 0 !important;
                padding: 0 !important;
                background: white;
            }

            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                font-size: 10px;
            }

            .action-bar,
            .btn,
            .no-print {
                display: none !important;
            }

            .print-container {
                max-width: 100%;
                box-shadow: none;
                margin: 0;
                background: white;
            }

            .pnp-header {
                background: #0a2f4d !important;
                padding: 8px 12px !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .pnp-header .provincial {
                color: #c9a959 !important;
            }

            .photo-section {
                border: 1px solid #000 !important;
                margin-bottom: 15px !important;
                padding: 10px !important;
                display: flex !important;
                justify-content: flex-end !important;
                break-inside: avoid;
            }

            .photo-box {
                width: 2in !important;
                height: 2in !important;
                border: 2px solid #000 !important;
                background: #f0f0f0 !important;
            }

            .data-section {
                border: 1px solid #000 !important;
                break-inside: auto;
                margin-bottom: 12px;
            }

            .section-title {
                background: #f0f0f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .compact-table th,
            .personal-data-table th {
                background: #f0f0f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .compact-table td,
            .compact-table th,
            .personal-data-table td,
            .personal-data-table th {
                border: 1px solid #000 !important;
            }

            .tag {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .tag.shabu { background: #ef4444 !important; }
            .tag.marijuana { background: #10b981 !important; }
            .tag.other { background: #6b7280 !important; }

            .official-footer {
                border: 1px solid #000 !important;
                page-break-inside: avoid;
                background: white !important;
            }

            .footer-logo {
                background: #0a2f4d !important;
                color: #c9a959 !important;
            }

            .footer-divider {
                background: #000 !important;
            }

            .main-content {
                padding: 10px;
            }

            .section-content {
                padding: 8px 10px;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <!-- PNP Header - Compact -->
        <div class="pnp-header">
            <div class="header-content">
                <div class="dilg">Department of the Interior and Local Government</div>
                <div class="pnp">PHILIPPINE NATIONAL POLICE</div>
                <div class="provincial">BUKIDNON POLICE PROVINCIAL OFFICE</div>
                <div class="station">MANOLO FORTICH POLICE STATION</div>
                <div class="address">Manolo Fortich, Bukidnon, 8703</div>
                <div class="phone"><i class="fas fa-phone"></i> (088-228) 2244</div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- PHOTO SECTION - Right Aligned -->
            <div class="photo-section">
                <div class="photo-container">
                    <div class="photo-box">
                        <?php if (!empty($profile['profile_picture'])): ?>
                            <img src="<?php echo $profile['profile_picture']; ?>" alt="Profile Picture - 2x2">
                        <?php else: ?>
                            <i class="fas fa-user-circle"></i>
                        <?php endif; ?>
                    </div>
                    <div class="photo-label"></div>
                    <div class="photo-caption"></div>
                </div>
            </div>

            <!-- PERSONAL DATA Section -->
            <div class="data-section">
                <div class="section-title">
                    <i class="fas fa-user"></i> I. PERSONAL DATA
                </div>
                <div class="section-content">
                    <table class="personal-data-table">
                        <thead>
                            <th>FULL NAME</th>
                            <td colspan="3"><?php echo displayValue($profile['full_name']); ?></td>
                        </thead>
                        <tbody>
                            <tr>
                                <th>ALIAS</th>
                                <td colspan="3"><?php echo displayValue($profile['alias']); ?></td>
                            </tr>
                            <tr>
                                <th>Name of Group/Gang Affiliation (if any)</th>
                                <td colspan="3"><?php echo displayValue($profile['group_affiliation']); ?></td>
                            </tr>
                            <tr>
                                <th>Position/Role (if any)</th>
                                <td colspan="3"><?php echo displayValue($profile['position_roles']); ?></td>
                            </tr>
                            <tr>
                                <th>AGE</th>
                                <td colspan="3"><?php echo displayValue($profile['age']); ?></td>
                            </tr>
                            <tr>
                                <th>SEX</th>
                                <td colspan="3"><?php echo displayValue($profile['sex']); ?></td>
                            </tr>
                            <tr>
                                <th>DATE OF BIRTH</th>
                                <td colspan="3"><?php echo !empty($profile['dob']) ? date('M d, Y', strtotime($profile['dob'])) : '—'; ?></td>
                            </tr>
                            <tr>
                                <th>PLACE OF BIRTH</th>
                                <td colspan="3"><?php echo displayValue($profile['pob']); ?></td>
                            </tr>
                            <tr>
                                <th>EDUCATIONAL ATTAINMENT</th>
                                <td colspan="3"><?php echo displayValue($profile['educational_attainment']); ?></td>
                            </tr>
                            <tr>
                                <th>OCCUPATION/PROFESSION</th>
                                <td colspan="3"><?php echo displayValue($profile['occupation']); ?></td>
                            </tr>
                            <tr>
                                <th>COMPANY/OFFICE</th>
                                <td colspan="3"><?php echo displayValue($profile['company_office']); ?></td>
                            </tr>
                            <tr>
                                <th>TECHNICAL SKILLS</th>
                                <td colspan="3"><?php echo displayValue($profile['technical_skills']); ?></td>
                            </tr>
                            <tr>
                                <th>ETHNIC GROUP</th>
                                <td colspan="3"><?php echo displayValue($profile['ethnic_group']); ?></td>
                            </tr>
                            <tr>
                                <th>LANGUAGE/DIALECT</th>
                                <td colspan="3"><?php echo displayValue($profile['languages']); ?></td>
                            </tr>
                            <tr>
                                <th>PRESENT ADDRESS</th>
                                <td colspan="3"><?php echo displayValue($profile['present_address']); ?></td>
                            </tr>
                            <tr>
                                <th>PROVINCIAL ADDRESS</th>
                                <td colspan="3"><?php echo displayValue($profile['provincial_address']); ?></td>
                            </tr>
                            <tr>
                                <th>CIVIL STATUS</th>
                                <td colspan="3"><?php echo displayValue($profile['civil_status']); ?></td>
                            </tr>
                            <tr>
                                <th>CITIZENSHIP</th>
                                <td colspan="3"><?php echo displayValue($profile['citizenship']); ?></td>
                            </tr>
                            <tr>
                                <th>RELIGION</th>
                                <td colspan="3"><?php echo displayValue($profile['religion']); ?></td>
                            </tr>
                            <tr>
                                <th>HEIGHT</th>
                                <td><?php echo displayValue($profile['height_ft']); ?></td>
                                <th>WEIGHT</th>
                                <td><?php echo displayValue($profile['weight_kg']); ?> kg</td>
                            </tr>
                            <tr>
                                <th>EYES</th>
                                <td><?php echo displayValue($profile['eyes_color']); ?></td>
                                <th>HAIR</th>
                                <td><?php echo displayValue($profile['hair_color']); ?></td>
                            </tr>
                            <tr>
                                <th>BUILT</th>
                                <td><?php echo displayValue($profile['built']); ?></td>
                                <th>COMPLEXION</th>
                                <td><?php echo displayValue($profile['complexion']); ?></td>
                            </tr>
                            <tr>
                                <th>DISTINGUISHING MARKS/TATTOO</th>
                                <td colspan="3"><?php echo displayValue($profile['distinguishing_marks']); ?></td>
                            </tr>
                            <tr>
                                <th>PREVIOUS ARREST</th>
                                <td colspan="3"><?php echo displayValue($profile['previous_arrest']); ?></td>
                            </tr>
                            <tr>
                                <th>SPECIFIC CHARGE</th>
                                <td colspan="3"><?php echo displayValue($profile['specific_charge']); ?></td>
                            </tr>
                            <tr>
                                <th>DATE/TIME OF ARREST</th>
                                <td colspan="3">
                                    <?php if (!empty($arrest_datetime_formatted)): ?>
                                        <a href="dashboard.php?year=<?php echo $arrest_year; ?>&month=<?php echo $arrest_month; ?>" class="arrest-date-link" target="_blank">
                                            <i class="fas fa-calendar-check"></i> <?php echo $arrest_datetime_formatted; ?>
                                        </a>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>PLACE OF ARREST</th>
                                <td colspan="3">
                                    <?php 
                                    if (!empty($arrest_place)) {
                                        echo displayValue($arrest_place);
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>NAME OF ARRESTING OFFICER</th>
                                <td colspan="3"><?php echo displayValue($profile['arresting_officer']); ?></td>
                            </tr>
                            <tr>
                                <th>UNIT/OFFICE OF ARRESTING OFFICER</th>
                                <td colspan="3"><?php echo displayValue($profile['arresting_unit']); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- FAMILY BACKGROUND Section -->
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
                                <td><strong>Occupation</strong></td>
                                <td><?php echo displayValue($profile['father_occupation']); ?></td>
                                <td><?php echo displayValue($profile['mother_occupation']); ?></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 10px;">
                        <table class="compact-table">
                            <thead>
                                <th style="width: 100px;">Spouse</th>
                                <td><?php echo displayValue($profile['spouse_name']); ?></td>
                                <th>Age</th>
                                <td><?php echo displayValue($profile['spouse_age']); ?></td>
                                <th>Occupation</th>
                                <td><?php echo displayValue($profile['spouse_occupation']); ?></td>
                                <th>Address</th>
                                <td><?php echo displayValue($profile['spouse_address']); ?></td>
                            </thead>
                        </table>
                    </div>
                    
                    <?php if (count($siblings) > 0): ?>
                    <div style="margin-top: 10px;">
                        <strong>Siblings:</strong>
                        <table class="compact-table" style="margin-top: 5px;">
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
                                <?php 
                                $siblingCount = 0;
                                foreach ($siblings as $sibling): 
                                    if ($siblingCount >= 3) break;
                                    $siblingCount++;
                                ?>
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
                        <?php if (count($siblings) > 3): ?>
                            <div style="font-size: 10px; margin-top: 4px; color: #64748b;">
                                + <?php echo (count($siblings) - 3); ?> more sibling(s)
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TACTICAL INFORMATION Section -->
            <div class="data-section">
                <div class="section-title">
                    <i class="fas fa-info-circle"></i> III. TACTICAL INFORMATION
                </div>
                <div class="section-content">
                    <table class="compact-table">
                        <tr>
                            <th style="width: 220px;">Drugs Involved</th>
                            <td>
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
                            </td>
                        </tr>
                        <tr>
                            <th>VEHICLES USED</th>
                            <td><?php echo displayValue($profile['vehicles_used']); ?></td>
                        </tr>
                        <tr>
                            <th>ARMAMENTS</th>
                            <td><?php echo displayValue($profile['armaments']); ?></td>
                        </tr>
                        <tr>
                            <th>COMPANION/S DURING ARREST</th>
                            <td><?php echo displayValue($profile['companions_arrest']); ?></td>
                        </tr>
                        <tr>
                            <th>NAME/SOURCE OF DRUGS INVOLVED</th>
                            <td><?php echo displayValue($profile['source_name']); ?></td>
                        </tr>
                        <tr>
                            <th>ADDRESS OF ALLEGED SOURCE</th>
                            <td><?php echo displayValue($profile['source_address']); ?></td>
                        </tr>
                        <tr>
                            <th>OTHER TYPES OF DRUGS SUPPLIED BY SOURCE</th>
                            <td><?php echo displayValue($profile['source_other_drugs']); ?></td>
                        </tr>
                        <tr>
                            <th>SUBGROUPS AND SPECIFIC AOR</th>
                            <td><?php echo displayValue($profile['subgroup_name']); ?> - <?php echo displayValue($profile['specific_aor']); ?></td>
                        </tr>
                        <tr>
                            <th>TYPES OF DRUGS PUSHED BY SUBJECT</th>
                            <td>
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
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- SUMMARY & RECOMMENDATION Section -->
            <div class="data-section">
                <div class="section-title">
                    <i class="fas fa-file-alt"></i> IV. SUMMARY & RECOMMENDATION
                </div>
                <div class="section-content">
                    <table class="compact-table">
                        <tr>
                            <th style="width: 220px;">Recruitment Summary</th>
                            <td><?php echo displayValue($profile['recruitment_summary'], 'Not specified'); ?></td>
                        </tr>
                        <tr>
                            <th>Modus Operandi</th>
                            <td><?php echo displayValue($profile['modus_operandi'], 'Not specified'); ?></td>
                        </tr>
                        <tr>
                            <th>Organizational Structure</th>
                            <td><?php echo displayValue($profile['organizational_structure'], 'Not specified'); ?></td>
                        </tr>
                        <tr>
                            <th>CI Matters</th>
                            <td><?php echo displayValue($profile['ci_matters'], 'Not specified'); ?></td>
                        </tr>
                        <tr>
                            <th>Other Revelations</th>
                            <td><?php echo displayValue($profile['other_revelations'], 'Not specified'); ?></td>
                        </tr>
                        <tr>
                            <th>Recommendation</th>
                            <td><strong><?php echo displayValue($profile['recommendation']); ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Action Buttons - Edit Button Removed -->
            <div class="action-bar no-print">
                <a href="<?php echo $back_link; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> <?php echo $back_text; ?>
                </a>
                <button onclick="window.print()" class="btn btn-print">
                    <i class="fas fa-print"></i> Print Profile
                </button>
            </div>
        </div>
    </div>
</body>
</html>