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
    
    if ($_SESSION['role'] == 'admin') {
        try {
            $delete_siblings = "DELETE FROM siblings WHERE profile_id = :profile_id";
            $stmt_siblings = $db->prepare($delete_siblings);
            $stmt_siblings->bindParam(':profile_id', $delete_id);
            $stmt_siblings->execute();
            
            $delete_query = "DELETE FROM biographical_profiles WHERE id = :id";
            $stmt_delete = $db->prepare($delete_query);
            $stmt_delete->bindParam(':id', $delete_id);
            $stmt_delete->execute();
            
            $_SESSION['success_message'] = "Profile deleted successfully!";
            
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
    if ($_SESSION['role'] == 'admin') {
        $selected_ids = explode(',', $_POST['selected_ids']);
        $deleted_count = 0;
        $error_count = 0;
        
        try {
            $db->beginTransaction();
            
            foreach ($selected_ids as $id) {
                $delete_siblings = "DELETE FROM siblings WHERE profile_id = :profile_id";
                $stmt_siblings = $db->prepare($delete_siblings);
                $stmt_siblings->bindParam(':profile_id', $id);
                $stmt_siblings->execute();
                
                $delete_query = "DELETE FROM biographical_profiles WHERE id = :id";
                $stmt_delete = $db->prepare($delete_query);
                $stmt_delete->bindParam(':id', $id);
                
                if ($stmt_delete->execute()) {
                    $deleted_count++;
                } else {
                    $error_count++;
                }
            }
            
            $db->commit();
            
            if ($deleted_count > 0) {
                $_SESSION['success_message'] = "Successfully deleted $deleted_count profile(s).";
            }
            if ($error_count > 0) {
                $_SESSION['error_message'] = "Failed to delete $error_count profile(s).";
            }
            
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

// Get profiles for this specific barangay
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

// Create array of all months
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

// Create array of all years
$allYears = [];
for ($y = 2016; $y <= 2026; $y++) {
    $allYears[] = $y;
}
$allYears = array_reverse($allYears);

// Function to format date
function formatArrestDate($dateTimePlace) {
    if (empty($dateTimePlace)) {
        return null;
    }
    
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?php echo htmlspecialchars($selectedBarangay); ?> - PNP Biographical Profiling System</title>
    
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

        .app-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #0a2f4d 0%, #123b5e 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar-header {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-logo {
            width: 70px;
            height: 70px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            border: 2px solid #c9a959;
        }

        .sidebar-logo i {
            font-size: 32px;
            color: #c9a959;
        }

        .sidebar-header h3 {
            font-size: 18px;
            margin: 0 0 5px;
            font-weight: 600;
        }

        .sidebar-header p {
            font-size: 12px;
            color: #b0c4de;
            margin: 0;
        }

        .user-info-sidebar {
            background: rgba(255,255,255,0.1);
            margin: 20px;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
        }

        .user-avatar-sidebar {
            width: 60px;
            height: 60px;
            background: #c9a959;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            color: #0a2f4d;
            font-weight: 600;
            font-size: 24px;
        }

        .user-name-sidebar {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .user-rank-sidebar {
            font-size: 12px;
            color: #b0c4de;
        }

        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav li {
            margin: 5px 15px;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: #e0e0e0;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .sidebar-nav a i {
            width: 20px;
            font-size: 16px;
        }

        .sidebar-nav a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .sidebar-nav a.active {
            background: #c9a959;
            color: #0a2f4d;
        }

        .sidebar-nav a.active i {
            color: #0a2f4d;
        }

        .main-content-wrapper {
            flex: 1;
            margin-left: 280px;
            transition: margin-left 0.3s ease;
        }

        .top-navbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: #0a2f4d;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .menu-toggle:hover {
            background: #f1f5f9;
        }

        .page-title {
            font-size: 20px;
            font-weight: 600;
            color: #0a2f4d;
            margin: 0;
        }

        .top-user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .top-user-avatar {
            width: 40px;
            height: 40px;
            background: #c9a959;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0a2f4d;
            font-weight: 600;
            font-size: 16px;
        }

        .top-user-name {
            font-weight: 500;
            font-size: 14px;
            color: #1e293b;
        }

        .top-user-rank {
            font-size: 12px;
            color: #64748b;
        }

        .main-content {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-title-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-title-section i {
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

        .highlight {
            background: #fef3c7;
            padding: 0 2px;
            border-radius: 3px;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content-wrapper {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .top-user-info {
                display: none;
            }
            
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
            
            .main-content {
                padding: 20px;
            }
            
            .title-text h2 {
                font-size: 20px;
            }
            
            .page-title-section i {
                font-size: 20px;
                padding: 8px;
            }
        }

        @media print {
            .sidebar,
            .top-navbar,
            .filter-section,
            .search-section,
            .bulk-delete-bar,
            .action-btns,
            .checkbox-column,
            .footer,
            .no-print {
                display: none !important;
            }

            .main-content-wrapper {
                margin-left: 0 !important;
            }

            @page {
                size: A4;
                margin: 1.5cm;
            }

            body {
                background: white;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>
<div class="app-wrapper">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h3>PNP Profiling System</h3>
            <p>Manolo Fortich Police Station</p>
        </div>
        
        <div class="user-info-sidebar">
            <div class="user-avatar-sidebar">
                <?php echo substr($_SESSION['full_name'], 0, 1); ?>
            </div>
            <div class="user-name-sidebar"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
            <div class="user-rank-sidebar"><?php echo htmlspecialchars($_SESSION['rank']); ?> • <?php echo htmlspecialchars($_SESSION['unit']); ?></div>
        </div>
        
        <ul class="sidebar-nav">
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="barangays.php"><i class="fas fa-map-marker-alt"></i> Barangays</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
            <?php if ($_SESSION['role'] == 'admin'): ?>
            <li><a href="users.php"><i class="fas fa-users-cog"></i> Accounts</a></li>
            <?php endif; ?>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content Wrapper -->
    <div class="main-content-wrapper">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h2 class="page-title">Barangay Profiles</h2>
            <div class="top-user-info">
                <div>
                    <div class="top-user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                    <div class="top-user-rank"><?php echo htmlspecialchars($_SESSION['rank']); ?></div>
                </div>
                <div class="top-user-avatar">
                    <?php echo substr($_SESSION['full_name'], 0, 1); ?>
                </div>
            </div>
        </div>

        <div class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-title-section">
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

            <!-- Filter Section -->
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
                                <option value="<?php echo $year; ?>" <?php echo $selectedYear == $year ? 'selected' : ''; ?>><?php echo $year; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-calendar-alt"></i> Arrest Month</label>
                        <select name="month" class="filter-select">
                            <option value="">All Months</option>
                            <?php foreach ($allMonths as $monthNum => $monthName): ?>
                                <option value="<?php echo $monthNum; ?>" <?php echo $selectedMonth == $monthNum ? 'selected' : ''; ?>><?php echo $monthName; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Apply Filters</button>
                        <a href="barangay_profiles.php?barangay=<?php echo urlencode($selectedBarangay); ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="btn-reset"><i class="fas fa-redo"></i> Reset Filters</a>
                    </div>
                </form>
                
                <?php if (!empty($selectedYear) || !empty($selectedMonth)): ?>
                <div class="active-filter-badge">
                    <i class="fas fa-info-circle"></i>
                    <span>
                        <strong>Active Filters:</strong>
                        <?php if (!empty($selectedYear)): ?>
                            <span class="filter-tag"><i class="fas fa-calendar"></i> Year: <?php echo $selectedYear; ?></span>
                        <?php endif; ?>
                        <?php if (!empty($selectedMonth)): ?>
                            <span class="filter-tag"><i class="fas fa-calendar-alt"></i> Month: <?php echo $allMonths[$selectedMonth]; ?></span>
                        <?php endif; ?>
                        <span style="margin-left: 10px; color: #0a2f4d;"><i class="fas fa-users"></i> <?php echo count($profiles); ?> records found</span>
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
                                               class="btn-icon view" title="View Profile">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($_SESSION['role'] == 'admin'): ?>
                                            <a href="edit_profile.php?id=<?php echo $profile['id']; ?>&return_to=barangay&barangay=<?php echo urlencode($selectedBarangay); ?>" 
                                               class="btn-icon edit" title="Edit Profile">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php endif; ?>
                                            <a href="certificate.php?id=<?php echo $profile['id']; ?>&barangay=<?php echo urlencode($selectedBarangay); ?>" 
                                               class="btn-icon certificate" title="Generate Certificate" target="_blank">
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

        

<script>
    // Sidebar Toggle for Mobile
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');

    menuToggle.addEventListener('click', function() {
        sidebar.classList.toggle('open');
    });

    document.addEventListener('click', function(event) {
        const isClickInsideSidebar = sidebar.contains(event.target);
        const isClickOnToggle = menuToggle.contains(event.target);
        
        if (!isClickInsideSidebar && !isClickOnToggle && window.innerWidth <= 768 && sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
        }
    });

    let deleteMode = false;
    let selectedIds = [];
    
    function toggleDeleteMode() {
        deleteMode = !deleteMode;
        const checkboxes = document.querySelectorAll('.profile-checkbox');
        const checkboxHeaders = document.querySelectorAll('.checkbox-column');
        const deleteButton = document.getElementById('deleteButtonHeader');
        
        if (deleteMode) {
            checkboxHeaders.forEach(header => header.style.display = 'table-cell');
            checkboxes.forEach(checkbox => {
                checkbox.closest('td').style.display = 'table-cell';
            });
            deleteButton.innerHTML = '<i class="fas fa-times"></i> Cancel Delete';
            deleteButton.style.background = '#64748b';
        } else {
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
    
    function updateSelectionCount() {
        const checkboxes = document.querySelectorAll('.profile-checkbox');
        selectedIds = Array.from(checkboxes).filter(cb => cb.checked).map(cb => cb.value);
        
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
        
        const selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            const allChecked = checkboxes.length > 0 && Array.from(checkboxes).every(cb => cb.checked);
            selectAllCheckbox.checked = allChecked;
        }
    }
    
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
    
    document.querySelector('select[name="year"]')?.addEventListener('change', function() {
        this.form.submit();
    });
    
    document.querySelector('select[name="month"]')?.addEventListener('change', function() {
        this.form.submit();
    });
    
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert-message');
        alerts.forEach(alert => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
    
    document.addEventListener('DOMContentLoaded', function() {
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