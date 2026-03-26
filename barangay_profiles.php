<?php
// barangay_profiles.php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: barangays.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get barangay from URL
$selectedBarangay = isset($_GET['barangay']) ? $_GET['barangay'] : '';

if (empty($selectedBarangay)) {
    header("Location: barangays.php");
    exit();
}

// Get filter values from URL
$selectedYear = isset($_GET['year']) ? $_GET['year'] : '';
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : '';
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Handle single delete request
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    // Check if user has permission (admin only)
    if ($_SESSION['role'] == 'admin') {
        try {
            // First delete siblings
            $delete_siblings = "DELETE FROM siblings WHERE profile_id = :profile_id";
            $stmt_siblings = $db->prepare($delete_siblings);
            $stmt_siblings->bindParam(':profile_id', $delete_id);
            $stmt_siblings->execute();
            
            // Then delete the profile
            $delete_query = "DELETE FROM biographical_profiles WHERE id = :id";
            $stmt_delete = $db->prepare($delete_query);
            $stmt_delete->bindParam(':id', $delete_id);
            $stmt_delete->execute();
            
            $_SESSION['success_message'] = "Profile deleted successfully!";
            
            // Redirect to refresh the page
            header("Location: barangay_profiles.php?barangay=" . urlencode($selectedBarangay) . 
                   (!empty($selectedYear) ? "&year=" . $selectedYear : "") .
                   (!empty($selectedMonth) ? "&month=" . $selectedMonth : "") .
                   (!empty($searchQuery) ? "&search=" . urlencode($searchQuery) : ""));
            exit();
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error deleting profile: " . $e->getMessage();
            header("Location: barangay_profiles.php?barangay=" . urlencode($selectedBarangay));
            exit();
        }
    } else {
        $_SESSION['error_message'] = "You don't have permission to delete profiles.";
        header("Location: barangay_profiles.php?barangay=" . urlencode($selectedBarangay));
        exit();
    }
}

// Handle bulk delete request
if (isset($_POST['bulk_delete']) && isset($_POST['selected_ids']) && !empty($_POST['selected_ids'])) {
    // Check if user has permission (admin only)
    if ($_SESSION['role'] == 'admin') {
        $selected_ids = explode(',', $_POST['selected_ids']);
        $deleted_count = 0;
        $error_count = 0;
        
        try {
            // Start transaction
            $db->beginTransaction();
            
            foreach ($selected_ids as $id) {
                // Delete siblings first
                $delete_siblings = "DELETE FROM siblings WHERE profile_id = :profile_id";
                $stmt_siblings = $db->prepare($delete_siblings);
                $stmt_siblings->bindParam(':profile_id', $id);
                $stmt_siblings->execute();
                
                // Delete the profile
                $delete_query = "DELETE FROM biographical_profiles WHERE id = :id";
                $stmt_delete = $db->prepare($delete_query);
                $stmt_delete->bindParam(':id', $id);
                
                if ($stmt_delete->execute()) {
                    $deleted_count++;
                } else {
                    $error_count++;
                }
            }
            
            // Commit transaction
            $db->commit();
            
            if ($deleted_count > 0) {
                $_SESSION['success_message'] = "Successfully deleted $deleted_count profile(s).";
            }
            if ($error_count > 0) {
                $_SESSION['error_message'] = "Failed to delete $error_count profile(s).";
            }
            
            // Redirect to refresh the page
            header("Location: barangay_profiles.php?barangay=" . urlencode($selectedBarangay) . 
                   (!empty($selectedYear) ? "&year=" . $selectedYear : "") .
                   (!empty($selectedMonth) ? "&month=" . $selectedMonth : "") .
                   (!empty($searchQuery) ? "&search=" . urlencode($searchQuery) : ""));
            exit();
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error_message'] = "Error deleting profiles: " . $e->getMessage();
            header("Location: barangay_profiles.php?barangay=" . urlencode($selectedBarangay));
            exit();
        }
    } else {
        $_SESSION['error_message'] = "You don't have permission to delete profiles.";
        header("Location: barangay_profiles.php?barangay=" . urlencode($selectedBarangay));
        exit();
    }
}

// Get profiles for this specific barangay with filters and search
$query = "SELECT * FROM biographical_profiles 
          WHERE present_address LIKE :barangay";

if (!empty($selectedYear)) {
    $query .= " AND YEAR(date_time_place_of_arrest) = :year";
}
if (!empty($selectedMonth)) {
    $query .= " AND MONTH(date_time_place_of_arrest) = :month";
}
if (!empty($searchQuery)) {
    $query .= " AND (full_name LIKE :search 
                OR alias LIKE :search 
                OR specific_charge LIKE :search 
                OR group_affiliation LIKE :search)";
}

$query .= " ORDER BY date_time_place_of_arrest DESC, created_at DESC";

$stmt = $db->prepare($query);
$barangayParam = '%' . $selectedBarangay . '%';
$stmt->bindParam(':barangay', $barangayParam);

if (!empty($selectedYear)) {
    $stmt->bindParam(':year', $selectedYear);
}
if (!empty($selectedMonth)) {
    $stmt->bindParam(':month', $selectedMonth);
}
if (!empty($searchQuery)) {
    $searchParam = '%' . $searchQuery . '%';
    $stmt->bindParam(':search', $searchParam);
}

$stmt->execute();
$profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics for this barangay
$statsQuery = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN date_time_place_of_arrest IS NOT NULL AND date_time_place_of_arrest != '' THEN 1 ELSE 0 END) as arrested_count
               FROM biographical_profiles 
               WHERE present_address LIKE :barangay";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->bindParam(':barangay', $barangayParam);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Get current date for report
$currentDate = date('F d, Y');
$generatedBy = $_SESSION['full_name'] . ' - ' . $_SESSION['rank'];

// Create array of all months (January to December)
$allMonths = [
    1 => 'January',
    2 => 'February',
    3 => 'March',
    4 => 'April',
    5 => 'May',
    6 => 'June',
    7 => 'July',
    8 => 'August',
    9 => 'September',
    10 => 'October',
    11 => 'November',
    12 => 'December'
];

// Create array of all years (2016 to 2026)
$allYears = [];
for ($y = 2016; $y <= 2026; $y++) {
    $allYears[] = $y;
}
// Sort years in descending order
$allYears = array_reverse($allYears);

// Function to format date_time_place_of_arrest
function formatArrestDate($dateTimePlace) {
    if (empty($dateTimePlace)) {
        return null;
    }
    
    // Parse the datetime string - assuming format like "2024-03-15 14:30:00"
    $timestamp = strtotime($dateTimePlace);
    if ($timestamp !== false && $timestamp > 0) {
        return [
            'date' => date('M d, Y', $timestamp),
            'time' => date('h:i A', $timestamp),
            'full' => date('M d, Y h:i A', $timestamp)
        ];
    }
    return null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($selectedBarangay); ?> - PNP Biographical Profiling System</title>
    
    <!-- External CSS -->
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
            background: #f4f7fb;
            color: #1e293b;
            line-height: 1.5;
        }

        /* Modern Navbar */
        .navbar-modern {
            background: linear-gradient(135deg, #0a2f4d 0%, #123b5e 100%);
            padding: 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .navbar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .pnp-logo {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #c9a959;
        }

        .pnp-logo i {
            font-size: 28px;
            color: #c9a959;
        }

        .title-area h1 {
            font-size: 22px;
            font-weight: 600;
            color: white;
            margin: 0;
            line-height: 1.2;
        }

        .title-area .subtitle {
            font-size: 13px;
            color: #b0c4de;
            margin: 0;
        }

        .title-area .station {
            font-size: 14px;
            color: #c9a959;
            font-weight: 500;
            margin: 2px 0 0;
        }

        .user-area {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255,255,255,0.1);
            padding: 8px 16px;
            border-radius: 40px;
            border: 1px solid rgba(201, 169, 89, 0.3);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: #c9a959;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0a2f4d;
            font-weight: 600;
            font-size: 18px;
        }

        .user-info {
            line-height: 1.3;
        }

        .user-name {
            font-weight: 600;
            color: white;
            font-size: 14px;
        }

        .user-rank {
            font-size: 12px;
            color: #b0c4de;
        }

        /* Navigation Menu */
        .nav-menu {
            background: rgba(0,0,0,0.2);
            padding: 0;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .nav-menu ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 5px;
        }

        .nav-menu li {
            margin: 0;
        }

        .nav-menu a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            color: #e0e0e0;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }

        .nav-menu a i {
            font-size: 16px;
            width: 20px;
        }

        .nav-menu a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            border-bottom-color: #c9a959;
        }

        .nav-menu a.active {
            background: rgba(255,255,255,0.15);
            color: white;
            border-bottom-color: #c9a959;
        }

        /* Main Content */
        .main-content {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-title i {
            font-size: 28px;
            color: #c9a959;
            background: #0a2f4d;
            padding: 12px;
            border-radius: 12px;
        }

        .title-text h2 {
            font-size: 24px;
            font-weight: 600;
            color: #0a2f4d;
            margin: 0 0 5px;
        }

        .title-text p {
            color: #64748b;
            margin: 0;
            font-size: 14px;
        }

        .title-text .barangay-name {
            color: #c9a959;
            font-weight: 700;
        }

        /* Action Buttons */
        .btn-back, .btn-delete-header, .btn-add-header {
            background: #64748b;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-add-header {
            background: #10b981;
        }

        .btn-add-header:hover {
            background: #059669;
        }

        .btn-delete-header {
            background: #ef4444;
        }

        .btn-delete-header:hover {
            background: #dc2626;
        }

        .btn-back:hover {
            background: #475569;
        }

        /* Alert Messages */
        .alert-message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
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

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Bulk Delete Bar */
        .bulk-delete-bar {
            background: white;
            border-radius: 16px;
            padding: 15px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #ef4444;
            display: none;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }

        .bulk-delete-bar.show {
            display: flex !important;
        }

        .bulk-delete-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .selected-count {
            background: #ef4444;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .btn-bulk-delete {
            background: #ef4444;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-bulk-delete:hover {
            background: #dc2626;
        }

        .btn-cancel-selection {
            background: #64748b;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-cancel-selection:hover {
            background: #475569;
        }

        /* Search Section */
        .search-section {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #c9a959;
        }

        .search-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .search-input-group {
            flex: 1;
            display: flex;
            gap: 10px;
        }

        .search-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            color: #1e293b;
            transition: all 0.2s;
        }

        .search-input:focus {
            outline: none;
            border-color: #c9a959;
            box-shadow: 0 0 0 2px rgba(201, 169, 89, 0.1);
        }

        .btn-search {
            background: #0a2f4d;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-search:hover {
            background: #c9a959;
            color: #0a2f4d;
        }

        .btn-clear-search {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-clear-search:hover {
            background: #e2e8f0;
            color: #0a2f4d;
        }

        .search-info {
            margin-top: 12px;
            padding: 8px 12px;
            background: #f0f9ff;
            border-radius: 8px;
            font-size: 13px;
            color: #0a2f4d;
        }

        /* Barangay Info Card */
        .barangay-info {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 5px solid #c9a959;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .barangay-info h3 {
            font-size: 28px;
            font-weight: 700;
            color: #0a2f4d;
            margin: 0;
        }

        .barangay-info h3 i {
            color: #c9a959;
            margin-right: 10px;
        }

        .barangay-stats {
            display: flex;
            gap: 30px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #0a2f4d;
        }

        .stat-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .filter-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .filter-header i {
            font-size: 20px;
            color: #c9a959;
        }

        .filter-header h4 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #0a2f4d;
        }

        .filter-form {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 180px;
        }

        .filter-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .filter-select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            color: #1e293b;
            background: white;
            cursor: pointer;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
        }

        .btn-filter {
            background: #0a2f4d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-filter:hover {
            background: #123b5e;
        }

        .btn-reset {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-reset:hover {
            background: #e2e8f0;
            color: #0a2f4d;
        }

        .active-filter-badge {
            margin-top: 15px;
            padding: 12px 15px;
            background: #f0f9ff;
            border-radius: 8px;
            border-left: 4px solid #0a2f4d;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            flex-wrap: wrap;
        }

        .filter-tag {
            background: #0a2f4d;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-left: 10px;
        }

        /* Table Container */
        .table-container {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
        }

        .modern-table th {
            background: #f8fafc;
            padding: 15px 12px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            border-bottom: 2px solid #e2e8f0;
        }

        .modern-table td {
            padding: 15px 12px;
            font-size: 14px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .modern-table tbody tr:hover {
            background: #f8fafc;
        }

        .arrest-date-badge {
            background: #fef3c7;
            color: #92400e;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
            white-space: nowrap;
        }

        .no-arrest {
            color: #94a3b8;
            font-style: italic;
            font-size: 12px;
        }

        .action-btns {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            font-size: 12px;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }

        .btn-icon.view { background: #0a2f4d; }
        .btn-icon.edit { background: #c9a959; }
        .btn-icon.certificate { background: #10b981; }
        .btn-icon.view:hover { background: #123b5e; transform: scale(1.05); }
        .btn-icon.edit:hover { background: #d4b36a; transform: scale(1.05); }
        .btn-icon.certificate:hover { background: #059669; transform: scale(1.05); }

        .checkbox-column {
            width: 40px;
            text-align: center;
        }

        .select-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .select-all-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        /* Report Footer */
        .report-footer {
            margin-top: 30px;
            padding: 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 3px solid #c9a959;
        }

        .report-footer .signature {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px dashed #e2e8f0;
        }

        .footer {
            background: white;
            padding: 20px 0;
            margin-top: 50px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }

        /* Highlight search term */
        .highlight {
            background: #fef3c7;
            padding: 0 2px;
            border-radius: 3px;
            font-weight: 500;
        }

        /* Tooltip */
        .btn-icon {
            position: relative;
        }

        .btn-icon:hover::after {
            content: attr(title);
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            background: #1e293b;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            white-space: nowrap;
            z-index: 10;
        }

        /* PRINT STYLES */
        @media print {
            .navbar-modern,
            .nav-menu,
            .btn-back,
            .btn-add-header,
            .btn-delete-header,
            .filter-section,
            .search-section,
            .bulk-delete-bar,
            .action-btns,
            .checkbox-column,
            .footer,
            .no-print {
                display: none !important;
            }

            @page {
                size: A4;
                margin: 1.5cm;
            }

            body {
                background: white;
                padding: 0;
                margin: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }

        @media (max-width: 768px) {
            .filter-form {
                flex-direction: column;
            }
            
            .barangay-info {
                flex-direction: column;
                text-align: center;
            }
            
            .barangay-stats {
                width: 100%;
                justify-content: space-around;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .search-input-group {
                flex-direction: column;
                width: 100%;
            }
            
            .modern-table {
                font-size: 12px;
            }
            
            .modern-table th,
            .modern-table td {
                padding: 10px 8px;
            }
            
            .action-btns {
                flex-direction: row;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Modern Navbar -->
    <nav class="navbar-modern no-print">
        <div class="navbar-container">
            <div class="navbar-header">
                <div class="logo-area">
                    <div class="pnp-logo">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="title-area">
                        <h1>PNP Biographical Profiling System</h1>
                        <div class="station">MANOLO FORTICH POLICE STATION</div>
                        <div class="subtitle">Bukidnon Police Provincial Office</div>
                    </div>
                </div>
                
                <div class="user-area">
                    <div class="user-profile">
                        <div class="user-avatar">
                            <?php echo substr($_SESSION['full_name'], 0, 1); ?>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?php echo $_SESSION['full_name']; ?></div>
                            <div class="user-rank"><?php echo $_SESSION['rank']; ?> • <?php echo $_SESSION['unit']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="nav-menu">
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="profile_form.php"><i class="fas fa-plus-circle"></i> New Profile</a></li>
                    <li><a href="profiles.php"><i class="fas fa-list"></i> View Profiles</a></li>
                    <li><a href="barangays.php" class="active"><i class="fas fa-map-marker-alt"></i> Barangays</a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                    <li><a href="users.php"><i class="fas fa-users-cog"></i> Account</a></li>
                    <?php endif; ?>
                    <li style="margin-left: auto;"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <i class="fas fa-map-marker-alt"></i>
                <div class="title-text">
                    <h2>Barangay: <span class="barangay-name"><?php echo htmlspecialchars($selectedBarangay); ?></span></h2>
                    <p>Viewing profiles from this barangay</p>
                </div>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="barangays.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Barangays
                </a>
                <a href="profile_form.php?barangay=<?php echo urlencode($selectedBarangay); ?>" class="btn-add-header">
                    <i class="fas fa-plus-circle"></i> Add New Profile
                </a>
                <?php if ($_SESSION['role'] == 'admin' && count($profiles) > 0): ?>
                <button type="button" id="deleteButtonHeader" class="btn-delete-header" onclick="toggleDeleteMode()">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert-message alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $_SESSION['success_message']; ?></span>
                <button type="button" style="margin-left: auto; background: none; border: none; cursor: pointer;" onclick="this.parentElement.remove();">&times;</button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert-message alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $_SESSION['error_message']; ?></span>
                <button type="button" style="margin-left: auto; background: none; border: none; cursor: pointer;" onclick="this.parentElement.remove();">&times;</button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Bulk Delete Bar -->
        <div id="bulkDeleteBar" class="bulk-delete-bar">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; width: 100%;">
                <div class="bulk-delete-info">
                    <i class="fas fa-trash-alt" style="color: #ef4444; font-size: 20px;"></i>
                    <span><strong id="selectedCountDisplay">0</strong> profile(s) selected</span>
                    <span class="selected-count" id="selectedCountBadge">0</span>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn-cancel-selection" onclick="cancelSelection()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="button" class="btn-bulk-delete" onclick="submitBulkDelete()">
                        <i class="fas fa-trash-alt"></i> Delete Selected
                    </button>
                </div>
            </div>
            <form id="bulkDeleteForm" method="POST" action="" style="display: none;">
                <input type="hidden" name="selected_ids" id="selectedIdsInput" value="">
                <input type="hidden" name="bulk_delete" value="1">
            </form>
        </div>

        <!-- Barangay Info Card -->
        <div class="barangay-info">
            <h3><i class="fas fa-map-pin"></i> <?php echo htmlspecialchars($selectedBarangay); ?></h3>
            <div class="barangay-stats">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Profiles</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $stats['arrested_count']; ?></div>
                    <div class="stat-label">With Arrest Records</div>
                </div>
            </div>
        </div>

        <!-- Search Bar Section -->
        <div class="search-section no-print">
            <div class="filter-header">
                <i class="fas fa-search"></i>
                <h4>Search Profiles</h4>
            </div>
            <form method="GET" action="" class="search-form">
                <input type="hidden" name="barangay" value="<?php echo htmlspecialchars($selectedBarangay); ?>">
                <input type="hidden" name="year" value="<?php echo $selectedYear; ?>">
                <input type="hidden" name="month" value="<?php echo $selectedMonth; ?>">
                
                <div class="search-input-group">
                    <input type="text" 
                           name="search" 
                           class="search-input" 
                           placeholder="Search by name, alias, charge, or group affiliation..." 
                           value="<?php echo htmlspecialchars($searchQuery); ?>">
                    <button type="submit" class="btn-search">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if (!empty($searchQuery)): ?>
                    <a href="barangay_profiles.php?barangay=<?php echo urlencode($selectedBarangay); ?><?php echo !empty($selectedYear) ? '&year=' . $selectedYear : ''; ?><?php echo !empty($selectedMonth) ? '&month=' . $selectedMonth : ''; ?>" class="btn-clear-search">
                        <i class="fas fa-times"></i> Clear Search
                    </a>
                    <?php endif; ?>
                </div>
            </form>
            
            <?php if (!empty($searchQuery)): ?>
            <div class="search-info">
                <i class="fas fa-info-circle"></i>
                Showing results for "<strong><?php echo htmlspecialchars($searchQuery); ?></strong>" in <strong><?php echo htmlspecialchars($selectedBarangay); ?></strong>
                <span style="margin-left: 10px;">Found <strong><?php echo count($profiles); ?></strong> result(s)</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Filter Section with Year and Month -->
        <div class="filter-section no-print">
            <div class="filter-header">
                <i class="fas fa-filter"></i>
                <h4>Filter by Date of Arrest</h4>
            </div>
            
            <form method="GET" action="" class="filter-form">
                <input type="hidden" name="barangay" value="<?php echo htmlspecialchars($selectedBarangay); ?>">
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
                
                <div class="filter-group">
                    <label><i class="fas fa-calendar"></i> Arrest Year</label>
                    <select name="year" class="filter-select">
                        <option value="">All Years</option>
                        <?php foreach ($allYears as $year): ?>
                            <option value="<?php echo $year; ?>" 
                                <?php echo $selectedYear == $year ? 'selected' : ''; ?>>
                                <?php echo $year; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label><i class="fas fa-calendar-alt"></i> Arrest Month</label>
                    <select name="month" class="filter-select">
                        <option value="">All Months</option>
                        <?php foreach ($allMonths as $monthNum => $monthName): ?>
                            <option value="<?php echo $monthNum; ?>" 
                                <?php echo $selectedMonth == $monthNum ? 'selected' : ''; ?>>
                                <?php echo $monthName; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                    <a href="barangay_profiles.php?barangay=<?php echo urlencode($selectedBarangay); ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="btn-reset">
                        <i class="fas fa-redo"></i> Reset Filters
                    </a>
                </div>
            </form>
            
            <!-- Active Filter Display -->
            <?php if (!empty($selectedYear) || !empty($selectedMonth)): ?>
            <div class="active-filter-badge">
                <i class="fas fa-info-circle"></i>
                <span>
                    <strong>Active Filters:</strong>
                    
                    <?php if (!empty($selectedYear)): ?>
                        <span class="filter-tag">
                            <i class="fas fa-calendar"></i> Year: <?php echo $selectedYear; ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($selectedMonth)): ?>
                        <span class="filter-tag">
                            <i class="fas fa-calendar-alt"></i> 
                            Month: <?php echo $allMonths[$selectedMonth]; ?>
                        </span>
                    <?php endif; ?>
                    
                    <span style="margin-left: 10px; color: #0a2f4d;">
                        <i class="fas fa-users"></i> <?php echo count($profiles); ?> records found
                    </span>
                </span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Profiles Table -->
        <div class="table-container">
            <?php if (count($profiles) > 0): ?>
                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                              <tr>
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                <th class="checkbox-column" id="checkboxHeader" style="display: none;">
                                    <input type="checkbox" id="selectAll" class="select-all-checkbox" onclick="toggleSelectAll()">
                                </th>
                                <?php endif; ?>
                                <th>Full Name</th>
                                <th>Alias</th>
                                <th>Age</th>
                                <th>Date/Time of Arrest</th>
                                <th class="no-print">Actions</th>
                              </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($profiles as $profile): 
                                $arrestInfo = formatArrestDate($profile['date_time_place_of_arrest'] ?? '');
                                
                                // Highlight search term in name and alias
                                $display_name = $profile['full_name'];
                                $display_alias = $profile['alias'] ?: '—';
                                
                                if (!empty($searchQuery)) {
                                    $display_name = preg_replace('/(' . preg_quote($searchQuery, '/') . ')/i', '<span class="highlight">$1</span>', $display_name);
                                    $display_alias = preg_replace('/(' . preg_quote($searchQuery, '/') . ')/i', '<span class="highlight">$1</span>', $display_alias);
                                }
                            ?>
                              <tr>
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                <td class="checkbox-column" style="display: none;">
                                    <input type="checkbox" class="profile-checkbox select-checkbox" value="<?php echo $profile['id']; ?>" onclick="updateSelectionCount()">
                                  </td>
                                <?php endif; ?>
                                  <td><?php echo $display_name; ?></td>
                                  <td><?php echo $display_alias; ?></td>
                                  <td><?php echo $profile['age']; ?></td>
                                  <td>
                                    <?php if ($arrestInfo): ?>
                                        <span class="arrest-date-badge">
                                            <i class="fas fa-calendar-check"></i> <?php echo $arrestInfo['full']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="no-arrest">No arrest record</span>
                                    <?php endif; ?>
                                  </td>
                                <td class="no-print">
                                    <div class="action-btns">
                                        <a href="view_profile.php?id=<?php echo $profile['id']; ?>&return_to=barangay&barangay=<?php echo urlencode($selectedBarangay); ?>" 
                                           class="btn-icon view" 
                                           title="View Profile">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_profile.php?id=<?php echo $profile['id']; ?>&return_to=barangay&barangay=<?php echo urlencode($selectedBarangay); ?>" 
                                           class="btn-icon edit" 
                                           title="Edit Profile">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="certificate.php?id=<?php echo $profile['id']; ?>&barangay=<?php echo urlencode($selectedBarangay); ?>" 
                                           class="btn-icon certificate" 
                                           title="Generate Certificate of Completion"
                                           target="_blank">
                                            <i class="fas fa-certificate"></i>
                                        </a>
                                    </div>
                                  </td>
                              </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4 text-muted">
                    <small>
                        <i class="fas fa-database"></i> 
                        Showing <strong><?php echo count($profiles); ?></strong> profiles from <strong><?php echo htmlspecialchars($selectedBarangay); ?></strong>
                        <?php if (!empty($selectedYear) || !empty($selectedMonth) || !empty($searchQuery)): ?>
                            (filtered by 
                            <?php if (!empty($searchQuery)): ?>search: "<strong><?php echo htmlspecialchars($searchQuery); ?></strong>"<?php endif; ?>
                            <?php if (!empty($selectedYear)): ?> year <?php echo $selectedYear; ?><?php endif; ?>
                            <?php if (!empty($selectedMonth)): ?> month of <?php echo $allMonths[$selectedMonth]; ?><?php endif; ?>)
                        <?php endif; ?>
                    </small>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open fa-3x" style="color: #cbd5e1; margin-bottom: 15px;"></i>
                    <h4 style="color: #475569;">No Profiles Found</h4>
                    <p style="color: #64748b;">
                        <?php if (!empty($searchQuery)): ?>
                            No profiles found in <?php echo htmlspecialchars($selectedBarangay); ?> matching "<strong><?php echo htmlspecialchars($searchQuery); ?></strong>".
                        <?php else: ?>
                            No profiles found in <?php echo htmlspecialchars($selectedBarangay); ?> matching your filters.
                        <?php endif; ?>
                    </p>
                    <a href="profile_form.php?barangay=<?php echo urlencode($selectedBarangay); ?>" class="btn-add-header" style="display: inline-block; text-decoration: none; margin-top: 10px;">
                        <i class="fas fa-plus-circle"></i> Add New Profile
                    </a>
                    <a href="barangay_profiles.php?barangay=<?php echo urlencode($selectedBarangay); ?>" class="btn-filter" style="display: inline-block; text-decoration: none; margin-top: 10px;">
                        <i class="fas fa-redo"></i> Clear All Filters
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Report Footer -->
        <div class="report-footer">
            <div>Generated on: <?php echo $currentDate; ?></div>
            <div>Generated by: <?php echo $generatedBy; ?></div>
            <div class="signature">
                <div>_________________________</div>
                <div>_________________________</div>
            </div>
        </div>
    </div>

    <div class="footer no-print">
        <div class="container">
            <p>PNP Biographical Profiling System &copy; <?php echo date('Y'); ?> - Manolo Fortich Police Station</p>
        </div>
    </div>

    <script>
        let deleteMode = false;
        let selectedIds = [];
        
        // Toggle delete mode
        function toggleDeleteMode() {
            deleteMode = !deleteMode;
            const checkboxes = document.querySelectorAll('.profile-checkbox');
            const checkboxHeaders = document.querySelectorAll('.checkbox-column');
            const deleteButton = document.getElementById('deleteButtonHeader');
            
            if (deleteMode) {
                // Show checkboxes
                checkboxHeaders.forEach(header => header.style.display = 'table-cell');
                checkboxes.forEach(checkbox => {
                    checkbox.closest('td').style.display = 'table-cell';
                });
                deleteButton.innerHTML = '<i class="fas fa-times"></i> Cancel Delete';
                deleteButton.style.background = '#64748b';
            } else {
                // Hide checkboxes and cancel selection
                checkboxHeaders.forEach(header => header.style.display = 'none');
                checkboxes.forEach(checkbox => {
                    checkbox.closest('td').style.display = 'none';
                    checkbox.checked = false;
                });
                deleteButton.innerHTML = '<i class="fas fa-trash-alt"></i> Delete';
                deleteButton.style.background = '#ef4444';
                cancelSelection();
            }
        }
        
        // Function to update selection count and display
        function updateSelectionCount() {
            const checkboxes = document.querySelectorAll('.profile-checkbox');
            selectedIds = Array.from(checkboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);
            
            const count = selectedIds.length;
            const bulkDeleteBar = document.getElementById('bulkDeleteBar');
            const selectedCountDisplay = document.getElementById('selectedCountDisplay');
            const selectedCountBadge = document.getElementById('selectedCountBadge');
            const selectedIdsInput = document.getElementById('selectedIdsInput');
            
            if (bulkDeleteBar) {
                if (count > 0) {
                    bulkDeleteBar.classList.add('show');
                    if (selectedCountDisplay) selectedCountDisplay.textContent = count;
                    if (selectedCountBadge) selectedCountBadge.textContent = count;
                    if (selectedIdsInput) selectedIdsInput.value = selectedIds.join(',');
                } else {
                    bulkDeleteBar.classList.remove('show');
                    if (selectedIdsInput) selectedIdsInput.value = '';
                }
            }
            
            // Update select all checkbox
            const selectAllCheckbox = document.getElementById('selectAll');
            if (selectAllCheckbox) {
                const allChecked = checkboxes.length > 0 && Array.from(checkboxes).every(cb => cb.checked);
                selectAllCheckbox.checked = allChecked;
            }
        }
        
        // Toggle select all
        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.profile-checkbox');
            
            if (selectAllCheckbox) {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = selectAllCheckbox.checked;
                });
            }
            
            updateSelectionCount();
        }
        
        // Cancel selection
        function cancelSelection() {
            const checkboxes = document.querySelectorAll('.profile-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            
            const selectAllCheckbox = document.getElementById('selectAll');
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = false;
            }
            
            updateSelectionCount();
        }
        
        // Submit bulk delete
        function submitBulkDelete() {
            if (selectedIds.length === 0) {
                alert('Please select at least one profile to delete.');
                return false;
            }
            
            const message = selectedIds.length === 1 
                ? 'Are you sure you want to delete the selected profile? This action cannot be undone.'
                : `Are you sure you want to delete ${selectedIds.length} profiles? This action cannot be undone.`;
            
            if (confirm(message)) {
                document.getElementById('bulkDeleteForm').submit();
            }
        }
        
        // Auto-submit form when dropdown changes
        document.querySelector('select[name="year"]')?.addEventListener('change', function() {
            this.form.submit();
        });
        
        document.querySelector('select[name="month"]')?.addEventListener('change', function() {
            this.form.submit();
        });
        
        // Auto-hide alert messages after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-message');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Hide checkboxes by default
            const checkboxes = document.querySelectorAll('.profile-checkbox');
            const checkboxHeaders = document.querySelectorAll('.checkbox-column');
            checkboxHeaders.forEach(header => header.style.display = 'none');
            checkboxes.forEach(checkbox => {
                checkbox.closest('td').style.display = 'none';
            });
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>