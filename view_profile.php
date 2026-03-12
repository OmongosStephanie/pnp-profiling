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

// Get creator info with error handling
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
function displayValue($value, $default = 'N/A') {
    return !empty($value) ? htmlspecialchars($value) : $default;
}

// Parse drugs_pushed if exists
$drugsPushedArray = !empty($profile['drugs_pushed']) ? explode(', ', $profile['drugs_pushed']) : [];

// Parse drug types if stored as comma-separated
$drugTypesArray = !empty($profile['drugs_involved']) ? explode(', ', $profile['drugs_involved']) : [];

// Parse position roles if stored as comma-separated
$positionRolesArray = !empty($profile['position_roles']) ? explode(', ', $profile['position_roles']) : [];

// Get current date for print header
$currentDate = date('F d, Y');
$currentTime = date('h:i A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Profile - PNP Biographical Profiling System</title>
    
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
            line-height: 1.5;
        }

        /* Minimalist Header */
        .app-header {
            background: #ffffff;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .app-header .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-left i {
            font-size: 22px;
            color: #0f172a;
        }

        .header-left h4 {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
            margin: 0;
        }

        .header-left small {
            font-size: 12px;
            color: #64748b;
            display: block;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #475569;
        }

        .user-info i {
            color: #94a3b8;
            font-size: 14px;
        }

        .btn-icon {
            background: #f1f5f9;
            color: #334155;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            border: 1px solid #e2e8f0;
        }

        .btn-icon:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .btn-icon i {
            font-size: 12px;
        }

        /* Main Container */
        .main-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 24px;
        }

        /* Profile View Container */
        .profile-view {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 30px;
        }

        /* Simplified PNP Header - Exactly like screenshot */
        .simple-header {
            background: #0a2f4d;
            color: white;
            padding: 15px 25px;
            text-align: center;
        }

        .simple-header .provincial {
            font-size: 20px;
            font-weight: 600;
            color: white;
            display: block;
            letter-spacing: 0.5px;
        }

        .simple-header .station {
            font-size: 18px;
            font-weight: 500;
            color: white;
            display: block;
            margin: 2px 0;
        }

        .simple-header .address {
            font-size: 14px;
            color: #e0e0e0;
            display: block;
            margin-top: 3px;
        }

        .simple-header .phone {
            font-size: 14px;
            color: #e0e0e0;
            display: block;
            margin-top: 2px;
        }

        .simple-header .phone i {
            margin-right: 5px;
        }

        /* Profile Header with Picture */
        .profile-header-with-pic {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .profile-info-left {
            flex: 1;
        }

        .profile-name-large {
            font-size: 28px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 10px;
        }

        .profile-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }

        .badge-item {
            background: white;
            border: 1px solid #e2e8f0;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 13px;
            color: #334155;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .badge-item i {
            color: #94a3b8;
            font-size: 11px;
        }

        .status-badge-large {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 6px 16px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 15px;
        }

        .meta-info-large {
            font-size: 13px;
            color: #64748b;
        }

        .meta-info-large i {
            margin-right: 4px;
            color: #94a3b8;
        }

        .meta-info-large span {
            margin-right: 20px;
        }

        /* Picture Box - 2x2 */
        .picture-box-2x2 {
            width: 140px;
            height: 140px;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border: 2px solid #0a2f4d;
            border-radius: 12px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr;
            gap: 4px;
            padding: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-left: 20px;
        }

        .picture-cell {
            background: white;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0a2f4d;
            font-size: 28px;
            border: 1px solid #cbd5e1;
        }

        .picture-label {
            text-align: center;
            margin-top: 5px;
            font-size: 11px;
            color: #64748b;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Table Styles - Exactly like screenshot */
        .data-section {
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .info-table th {
            background: #f8fafc;
            padding: 10px 12px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            font-size: 12px;
            text-transform: uppercase;
            border: 1px solid #e2e8f0;
        }

        .info-table td {
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            color: #334155;
            font-size: 14px;
        }

        .info-table td:first-child {
            font-weight: 500;
            background: #f8fafc;
            width: 150px;
        }

        /* Address section */
        .address-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .address-table td {
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
        }

        .address-table .label {
            background: #f8fafc;
            font-weight: 600;
            color: #475569;
            width: 150px;
            font-size: 12px;
            text-transform: uppercase;
        }

        /* Tag Styles */
        .tag-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 6px;
        }

        .drug-tag {
            padding: 3px 10px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 500;
            color: white;
        }

        .drug-tag.marijuana { background: #10b981; }
        .drug-tag.shabu { background: #ef4444; }
        .drug-tag.other { background: #6b7280; }

        .highlight-item {
            background: #fff3cd;
            border: 1px solid #ffc107;
        }

        .summary-item {
            background: #e8f4fd;
            border: 1px solid #b8daff;
        }

        /* Two Column Layout */
        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        /* Action Buttons */
        .action-bar {
            padding: 24px;
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        .btn-primary {
            background: #0f172a;
            color: white;
            border: none;
            padding: 10px 24px;
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
            background: #ffffff;
            color: #334155;
            border: 1px solid #e2e8f0;
            padding: 10px 24px;
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
            background: #f1f5f9;
        }

        .btn-print {
            background: #0891b2;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-print:hover {
            background: #0e7490;
        }

        .btn-delete {
            background: #fee2e2;
            color: #991b1b;
            border: none;
            padding: 10px 24px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-delete:hover {
            background: #fecaca;
        }

        /* Footer */
        .app-footer {
            background: #ffffff;
            padding: 20px 0;
            border-top: 1px solid #e2e8f0;
            margin-top: 40px;
        }

        .app-footer .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
            text-align: center;
            color: #64748b;
            font-size: 12px;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 10px;
        }

        .footer-links a {
            color: #475569;
            text-decoration: none;
            font-size: 12px;
        }

        .footer-links a:hover {
            color: #0f172a;
        }

        /* PRINT STYLES */
        @media print {
            @page {
                size: A4;
                margin: 1.5cm;
            }

            body {
                background: white;
                padding: 0;
            }

            /* Hide navigation elements */
            .app-header,
            .btn-icon,
            .action-bar,
            .app-footer,
            .footer-links,
            .btn-print,
            .btn-secondary,
            .btn-primary,
            .btn-delete {
                display: none !important;
            }

            .main-container {
                margin: 0;
                padding: 0;
            }

            .profile-view {
                border: none;
                box-shadow: none;
            }

            /* Simple header for print */
            .simple-header {
                background: #0a2f4d !important;
                color: white !important;
                padding: 15px 25px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            /* Picture box in print */
            .picture-box-2x2 {
                border: 2px solid #000 !important;
                background: #f0f0f0 !important;
            }

            .picture-cell {
                border: 1px solid #000 !important;
                color: #000 !important;
            }

            /* Table borders */
            .info-table th,
            .info-table td,
            .address-table td {
                border: 1px solid #000 !important;
            }

            .info-table th {
                background: #f0f0f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .status-badge-large {
                background: #28a745 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            /* Drug tags */
            .drug-tag.marijuana {
                background: #10b981 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .drug-tag.shabu {
                background: #ef4444 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .drug-tag.other {
                background: #6b7280 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .highlight-item {
                background: #fff3cd !important;
                border: 1px solid #ffc107 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .summary-item {
                background: #e8f4fd !important;
                border: 1px solid #b8daff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            /* Ensure proper page breaks */
            .data-section {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }

        @media (max-width: 768px) {
            .action-bar {
                flex-direction: column;
            }
            
            .action-bar .btn-primary,
            .action-bar .btn-secondary,
            .action-bar .btn-print,
            .action-bar .btn-delete {
                width: 100%;
                justify-content: center;
            }

            .two-col {
                grid-template-columns: 1fr;
            }

            .profile-header-with-pic {
                flex-direction: column;
            }

            .picture-box-2x2 {
                margin-left: 0;
                margin-top: 20px;
                align-self: center;
            }
        }
    </style>
</head>
<body>
    <!-- Minimalist Header -->
    <header class="app-header">
        <div class="container">
            <div class="header-left">
                <i class="fas fa-shield-alt"></i>
                <div>
                    <h4>PNP Biographical Profiling</h4>
                    <small>Manolo Fortich Police Station</small>
                </div>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo $_SESSION['rank'] . ' ' . $_SESSION['full_name']; ?></span>
                </div>
                <a href="dashboard.php" class="btn-icon">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>
    </header>

    <main class="main-container">
        <!-- Profile View -->
        <div class="profile-view">
            <!-- Simple Header - Exactly like screenshot -->
            <div class="simple-header">
                <div class="provincial">BUKIDNON POLICE PROVINCIAL OFFICE</div>
                <div class="station">MANOLO FORTICH POLICE STATION</div>
                <div class="address">Manolo Fortich, Bukidnon, 8703</div>
                <div class="phone"><i class="fas fa-phone"></i> (088-228) 2244</div>
            </div>

            <!-- Profile Header with Picture Box on the Right -->
            <div class="profile-header-with-pic">
                <div class="profile-info-left"> 
                    <div class="meta-info-large">
                    </div>
                </div>
                
                <!-- 2x2 Picture Box on the Right -->
                <div>
                    <div class="picture-box-2x2">
                        <div class="picture-cell"><i class="fas fa-user"></i></div>
                        <div class="picture-cell"><i class="fas fa-user"></i></div>
                        <div class="picture-cell"><i class="fas fa-user"></i></div>
                        <div class="picture-cell"><i class="fas fa-user"></i></div>
                    </div>
                    <div class="picture-label">2x2 PHOTO</div>
                </div>
            </div>

            <!-- PERSONAL DATA Section -->
            <div class="data-section">
                <div class="section-title">PERSONAL DATA</div>
                
                <!-- Row 1 - Basic Info -->
                <table class="info-table">
                    <thead>
                        <tr>
                            <th>FULL NAME</th>
                            <th>ALIAS</th>
                            <th>AGE</th>
                            <th>SEX</th>
                            <th>DATE OF BIRTH</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo displayValue($profile['full_name']); ?></td>
                            <td><?php echo displayValue($profile['alias']); ?></td>
                            <td><?php echo $profile['age']; ?></td>
                            <td><?php echo $profile['sex']; ?></td>
                            <td><?php echo !empty($profile['dob']) ? date('M d, Y', strtotime($profile['dob'])) : 'N/A'; ?></td>
                        </tr>
                    </tbody>
                </table>

                <!-- Row 2 - Place of Birth, Civil Status, Citizenship, Religion -->
                <table class="info-table">
                    <thead>
                        <tr>
                            <th>PLACE OF BIRTH</th>
                            <th>CIVIL STATUS</th>
                            <th>CITIZENSHIP</th>
                            <th>RELIGION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo displayValue($profile['pob']); ?></td>
                            <td><?php echo displayValue($profile['civil_status']); ?></td>
                            <td><?php echo displayValue($profile['citizenship']); ?></td>
                            <td><?php echo displayValue($profile['religion']); ?></td>
                        </tr>
                    </tbody>
                </table>

                <!-- Row 3 - Education, Occupation, Company/Office, Technical Skills -->
                <table class="info-table">
                    <thead>
                        <tr>
                            <th>EDUCATION</th>
                            <th>OCCUPATION</th>
                            <th>COMPANY/OFFICE</th>
                            <th>TECHNICAL SKILLS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo displayValue($profile['educational_attainment']); ?></td>
                            <td><?php echo displayValue($profile['occupation']); ?></td>
                            <td><?php echo displayValue($profile['company_office']); ?></td>
                            <td><?php echo displayValue($profile['technical_skills']); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- PRESENT ADDRESS Section -->
            <div class="data-section">
                <div class="section-title">PRESENT ADDRESS</div>
                <table class="address-table">
                    <tr>
                        <td class="label">PRESENT ADDRESS</td>
                        <td><?php echo displayValue($profile['present_address']); ?></td>
                    </tr>
                </table>
            </div>

            <!-- PROVINCIAL ADDRESS Section -->
            <div class="data-section">
                <div class="section-title">PROVINCIAL ADDRESS</div>
                <table class="address-table">
                    <tr>
                        <td class="label">PROVINCIAL ADDRESS</td>
                        <td><?php echo displayValue($profile['provincial_address']); ?></td>
                    </tr>
                </table>
            </div>

            <!-- Physical Attributes Section -->
            <div class="data-section">
                <div class="section-title">PHYSICAL ATTRIBUTES</div>
                
                <!-- Row 1 - Height, Weight, Built, Eyes Color -->
                <table class="info-table">
                    <thead>
                        <tr>
                            <th>HEIGHT</th>
                            <th>WEIGHT</th>
                            <th>BUILT</th>
                            <th>EYES COLOR</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo displayValue($profile['height_ft']); ?></td>
                            <td><?php echo displayValue($profile['weight_kg']); ?> kg</td>
                            <td><?php echo displayValue($profile['built']); ?></td>
                            <td><?php echo displayValue($profile['eyes_color']); ?></td>
                        </tr>
                    </tbody>
                </table>

                <!-- Row 2 - Hair Color, Complexion, Ethnic Group, Languages -->
                <table class="info-table" style="margin-top: 15px;">
                    <thead>
                        <tr>
                            <th>HAIR COLOR</th>
                            <th>COMPLEXION</th>
                            <th>ETHNIC GROUP</th>
                            <th>LANGUAGES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo displayValue($profile['hair_color']); ?></td>
                            <td><?php echo displayValue($profile['complexion']); ?></td>
                            <td><?php echo displayValue($profile['ethnic_group']); ?></td>
                            <td><?php echo displayValue($profile['languages']); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- DISTINGUISHING MARKS Section -->
            <div class="data-section">
                <div class="section-title">DISTINGUISHING MARKS / TATTOO</div>
                <table class="address-table">
                    <tr>
                        <td class="label">DISTINGUISHING MARKS</td>
                        <td><?php echo displayValue($profile['distinguishing_marks']); ?></td>
                    </tr>
                </table>
            </div>

            <!-- PREVIOUS ARREST RECORD Section -->
            <div class="data-section">
                <div class="section-title">PREVIOUS ARREST RECORD</div>
                <table class="address-table">
                    <tr>
                        <td class="label">PREVIOUS ARREST RECORD</td>
                        <td><?php echo displayValue($profile['previous_arrest']); ?></td>
                    </tr>
                </table>
            </div>

            <!-- SPECIFIC CHARGE Section -->
            <div class="data-section">
                <div class="section-title">SPECIFIC CHARGE</div>
                <table class="address-table">
                    <tr>
                        <td class="label">SPECIFIC CHARGE</td>
                        <td><?php echo displayValue($profile['specific_charge']); ?></td>
                    </tr>
                </table>
            </div>

            <!-- ARREST DETAILS Section -->
            <div class="data-section">
                <div class="section-title">ARREST DETAILS</div>
                
                <!-- Date/Time/Place of Arrest -->
                <table class="info-table">
                    <thead>
                        <tr>
                            <th>DATE/TIME OF ARREST</th>
                            <th>PLACE OF ARREST</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo !empty($profile['arrest_datetime']) ? date('M d, Y H:i', strtotime($profile['arrest_datetime'])) : 'N/A'; ?></td>
                            <td><?php echo displayValue($profile['arrest_place']); ?></td>
                        </tr>
                    </tbody>
                </table>

                <!-- Arresting Officer and Unit -->
                <table class="info-table" style="margin-top: 15px;">
                    <thead>
                        <tr>
                            <th>ARRESTING OFFICER</th>
                            <th>UNIT/OFFICE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo displayValue($profile['arresting_officer']); ?></td>
                            <td><?php echo displayValue($profile['arresting_unit']); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- II. Family Background -->
            <div class="data-section">
                <div class="section-title">II. FAMILY BACKGROUND</div>
                
                <h5 style="margin-bottom: 12px; font-size: 14px; color: #475569;">Parents</h5>
                <div class="table-responsive">
                    <table class="info-table">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Father</th>
                                <th>Mother</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Name</td>
                                <td><?php echo displayValue($profile['father_name']); ?></td>
                                <td><?php echo displayValue($profile['mother_name']); ?></td>
                            </tr>
                            <tr>
                                <td>Address</td>
                                <td><?php echo displayValue($profile['father_address']); ?></td>
                                <td><?php echo displayValue($profile['mother_address']); ?></td>
                            </tr>
                            <tr>
                                <td>Date of Birth</td>
                                <td><?php echo !empty($profile['father_dob']) ? date('M d, Y', strtotime($profile['father_dob'])) : 'N/A'; ?></td>
                                <td><?php echo !empty($profile['mother_dob']) ? date('M d, Y', strtotime($profile['mother_dob'])) : 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <td>Occupation</td>
                                <td><?php echo displayValue($profile['father_occupation']); ?></td>
                                <td><?php echo displayValue($profile['mother_occupation']); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <h5 style="margin: 24px 0 12px; font-size: 14px; color: #475569;">Spouse</h5>
                <div class="table-responsive">
                    <table class="info-table">
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
                </div>
                
                <h5 style="margin: 24px 0 12px; font-size: 14px; color: #475569;">Siblings</h5>
                <?php if (count($siblings) > 0): ?>
                    <div class="table-responsive">
                        <table class="info-table">
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
                <?php else: ?>
                    <p style="text-align: center; padding: 20px; color: #64748b; border: 1px dashed #e2e8f0; border-radius: 8px;">
                        No siblings recorded
                    </p>
                <?php endif; ?>
            </div>

            <!-- III. Tactical Information -->
            <div class="data-section">
                <div class="section-title">III. TACTICAL INFORMATION</div>
                
                <div class="info-item highlight-item" style="margin-bottom: 16px; padding: 15px;">
                    <div class="label" style="font-size: 12px; margin-bottom: 8px;">Drugs Involved</div>
                    <div class="tag-wrapper">
                        <?php 
                        if (!empty($drugTypesArray)) {
                            foreach ($drugTypesArray as $drug) {
                                $drug = trim($drug);
                                $drugClass = 'drug-tag';
                                if (stripos($drug, 'marijuana') !== false) $drugClass .= ' marijuana';
                                elseif (stripos($drug, 'shabu') !== false) $drugClass .= ' shabu';
                                else $drugClass .= ' other';
                                echo '<span class="' . $drugClass . '">' . $drug . '</span> ';
                            }
                        } else {
                            echo displayValue($profile['drugs_involved']);
                        }
                        ?>
                    </div>
                </div>
                
                <div class="two-col">
                    <div>
                        <div class="info-item" style="margin-bottom: 12px;">
                            <div class="label">Relationship to Source</div>
                            <div class="value"><?php echo displayValue($profile['source_relationship']); ?></div>
                        </div>
                        
                        <div class="info-item" style="margin-bottom: 12px;">
                            <div class="label">Source Address</div>
                            <div class="value"><?php echo displayValue($profile['source_address']); ?></div>
                        </div>
                        
                        <div class="info-item" style="margin-bottom: 12px;">
                            <div class="label">Source Name</div>
                            <div class="value"><?php echo displayValue($profile['source_name']); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="label">Source Alias</div>
                            <div class="value"><?php echo displayValue($profile['source_nickname']); ?></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="info-item" style="margin-bottom: 12px;">
                            <div class="label">Subgroup Name</div>
                            <div class="value"><?php echo displayValue($profile['subgroup_name']); ?></div>
                        </div>
                        
                        <div class="info-item" style="margin-bottom: 12px;">
                            <div class="label">Area of Responsibility</div>
                            <div class="value"><?php echo displayValue($profile['specific_aor']); ?></div>
                        </div>
                        
                        <div class="info-item" style="margin-bottom: 12px;">
                            <div class="label">Vehicles Used</div>
                            <div class="value"><?php echo displayValue($profile['vehicles_used']); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="label">Armaments</div>
                            <div class="value"><?php echo displayValue($profile['armaments']); ?></div>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 16px;">
                    <div class="info-item">
                        <div class="label">Complete Address of Alleged Source</div>
                        <div class="value"><?php echo displayValue($profile['source_full_address']); ?></div>
                    </div>
                </div>
                
                <div class="two-col" style="margin-top: 16px;">
                    <div class="info-item">
                        <div class="label">Other Drugs Supplied by Source</div>
                        <div class="value"><?php echo displayValue($profile['source_other_drugs']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Other Subject Known as Source</div>
                        <div class="value">
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
                
                <?php if (!empty($profile['other_source_details'])): ?>
                <div style="margin-top: 16px;">
                    <div class="info-item">
                        <div class="label">Other Source Details</div>
                        <div class="value"><?php echo displayValue($profile['other_source_details']); ?></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div style="margin-top: 16px;">
                    <div class="info-item">
                        <div class="label">Types of Drugs Pushed</div>
                        <div class="tag-wrapper">
                            <?php 
                            if (!empty($drugsPushedArray)) {
                                foreach ($drugsPushedArray as $drug) {
                                    $drugClass = 'drug-tag';
                                    if (stripos($drug, 'marijuana') !== false) $drugClass .= ' marijuana';
                                    elseif (stripos($drug, 'shabu') !== false) $drugClass .= ' shabu';
                                    else $drugClass .= ' other';
                                    echo '<span class="' . $drugClass . '">' . $drug . '</span> ';
                                }
                                if (!empty($profile['other_drugs_pushed'])) {
                                    echo '<span class="drug-tag other">' . $profile['other_drugs_pushed'] . '</span>';
                                }
                            } else {
                                echo displayValue($profile['other_drugs_pushed']);
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 16px;">
                    <div class="info-item">
                        <div class="label">Companions During Arrest</div>
                        <div class="value"><?php echo displayValue($profile['companions_arrest']); ?></div>
                    </div>
                </div>
            </div>

            <!-- IV. Summary and Recommendations -->
            <div class="data-section">
                <div class="section-title">IV. SUMMARY & RECOMMENDATIONS</div>
                
                <div class="info-item" style="margin-bottom: 16px;">
                    <div class="label">Detailed Summary on Recruitment of Suspect as User/Pusher/Drugs</div>
                    <div class="value"><?php echo displayValue($profile['recruitment_summary']); ?></div>
                </div>
                
                <div class="info-item" style="margin-bottom: 16px;">
                    <div class="label">Summary of Pushing/Supplying/Acquiring Drugs</div>
                    <div class="value"><?php echo displayValue($profile['modus_operandi']); ?></div>
                </div>
                
                <div class="info-item" style="margin-bottom: 16px;">
                    <div class="label">Organizational Structure of the Group (if any)</div>
                    <div class="value"><?php echo displayValue($profile['organizational_structure']); ?></div>
                </div>
                
                <div class="info-item" style="margin-bottom: 16px;">
                    <div class="label">CI Matters (AFP/PNP Government Officer's Instruments)</div>
                    <div class="value"><?php echo displayValue($profile['ci_matters']); ?></div>
                </div>
                
                <?php if (!empty($profile['other_revelations'])): ?>
                <div class="info-item" style="margin-bottom: 16px;">
                    <div class="label">Other Significant Revelations</div>
                    <div class="value"><?php echo displayValue($profile['other_revelations']); ?></div>
                </div>
                <?php endif; ?>
                
                <div class="two-col" style="margin-top: 24px;">
                    <div class="info-item summary-item" style="padding: 15px;">
                        <div class="label">Recommendation</div>
                        <div class="value" style="font-size: 16px; font-weight: 600;"><?php echo displayValue($profile['recommendation']); ?></div>
                    </div>
                </div>
            </div>

            <!-- Footer Information -->
            <div style="padding: 16px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; display: flex; justify-content: space-between;">
                <span><i class="fas fa-clock"></i> Created: <?php echo !empty($profile['created_at']) ? date('M d, Y h:i A', strtotime($profile['created_at'])) : 'N/A'; ?></span>
                <span><i class="fas fa-sync-alt"></i> Updated: <?php echo !empty($profile['updated_at']) ? date('M d, Y h:i A', strtotime($profile['updated_at'])) : 'N/A'; ?></span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-bar">
            <a href="dashboard.php" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <a href="edit_profile.php?id=<?php echo $profile['id']; ?>" class="btn-primary">
                <i class="fas fa-edit"></i> Edit Profile
            </a>
            <a href="profile_form.php" class="btn-secondary">
                <i class="fas fa-plus"></i> New
            </a>
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print"></i> Print Profile
            </button>
            <?php if ($_SESSION['role'] == 'admin'): ?>
            <button onclick="confirmDelete(<?php echo $profile['id']; ?>)" class="btn-delete">
                <i class="fas fa-trash"></i> Delete
            </button>
            <?php endif; ?>
        </div>
    </main>

    <!-- Minimalist Footer -->
    <footer class="app-footer">
        <div class="container">
            <div class="footer-links">
                <a href="#">Privacy</a>
                <a href="#">Terms</a>
                <a href="#">Contact</a>
            </div>
        </div>
    </footer>

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