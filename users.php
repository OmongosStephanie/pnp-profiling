<?php
// users.php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Check if user is admin
if ($_SESSION['role'] != 'admin') {
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['change_password'])) {
        // Verify current password
        $query = "SELECT password FROM users WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($_POST['current_password'], $user['password'])) {
            if ($_POST['new_password'] == $_POST['confirm_password']) {
                $hashedPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                
                $updateQuery = "UPDATE users SET password = :password WHERE id = :id";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([
                    ':password' => $hashedPassword,
                    ':id' => $_SESSION['user_id']
                ]);
                
                $message = "Password changed successfully!";
            } else {
                $error = "New passwords do not match!";
            }
        } else {
            $error = "Current password is incorrect!";
        }
    }
    
    // Handle profile update
    if (isset($_POST['update_profile'])) {
        $query = "UPDATE users SET 
                  full_name = :full_name,
                  rank = :rank,
                  unit = :unit
                  WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':full_name' => $_POST['full_name'],
            ':rank' => $_POST['rank'],
            ':unit' => $_POST['unit'],
            ':id' => $_SESSION['user_id']
        ]);
        
        // Update session
        $_SESSION['full_name'] = $_POST['full_name'];
        $_SESSION['rank'] = $_POST['rank'];
        $_SESSION['unit'] = $_POST['unit'];
        
        $message = "Profile updated successfully!";
    }
}

// Get current user info
$query = "SELECT * FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - PNP Biographical Profiling System</title>
    
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
            font-family: 'Inter', sans-serif;
            background: #f4f7fb;
            color: #1e293b;
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

        .title-area .station {
            font-size: 14px;
            color: #c9a959;
            font-weight: 500;
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
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h2 {
            font-size: 24px;
            font-weight: 600;
            color: #0a2f4d;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header h2 i {
            color: #c9a959;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .settings-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #c9a959;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .card-header i {
            font-size: 20px;
            color: #c9a959;
        }

        .card-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #0a2f4d;
            margin: 0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .form-control, .form-select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            color: #1e293b;
            background: white;
        }

        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: #c9a959;
            box-shadow: 0 0 0 2px rgba(201,169,89,0.1);
        }

        .form-control:read-only {
            background: #f8fafc;
            cursor: not-allowed;
        }

        .btn-save {
            background: #0a2f4d;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
            justify-content: center;
        }

        .btn-save:hover {
            background: #123b5e;
        }

        .info-box {
            background: #f8fafc;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }

        .info-box p {
            margin: 5px 0;
            font-size: 13px;
            color: #64748b;
        }

        .info-box i {
            color: #c9a959;
            margin-right: 5px;
        }

        /* Alert */
        .alert-modern {
            background: white;
            border-left: 4px solid;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .alert-success {
            border-left-color: #28a745;
        }

        .alert-success i {
            color: #28a745;
        }

        .alert-danger {
            border-left-color: #dc3545;
        }

        .alert-danger i {
            color: #dc3545;
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

        @media (max-width: 768px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar-modern">
        <div class="navbar-container">
            <div class="navbar-header">
                <div class="logo-area">
                    <div class="pnp-logo">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="title-area">
                        <h1>PNP Biographical Profiling System</h1>
                        <div class="station">MANOLO FORTICH POLICE STATION</div>
                    </div>
                </div>
                <div class="user-area">
                    <div class="user-profile">
                        <div class="user-avatar"><?php echo substr($_SESSION['full_name'], 0, 1); ?></div>
                        <div class="user-info">
                            <div class="user-name"><?php echo $_SESSION['full_name']; ?></div>
                            <div class="user-rank"><?php echo $_SESSION['rank']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="nav-menu">
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="profile_form.php"><i class="fas fa-plus-circle"></i> New Profile</a></li>
                    <li><a href="profiles.php"><i class="fas fa-list"></i> View Profiles</a></li>
                    <li><a href="barangays.php"><i class="fas fa-map-marker-alt"></i> Barangays</a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                    <li><a href="users.php" class="active"><i class="fas fa-user-cog"></i> Account</a></li>
                    <li style="margin-left: auto;"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert-modern alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $message; ?></span>
                <button type="button" class="btn-close ms-auto" onclick="this.parentElement.remove()" style="background: none; border: none; cursor: pointer;">×</button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert-modern alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
                <button type="button" class="btn-close ms-auto" onclick="this.parentElement.remove()" style="background: none; border: none; cursor: pointer;">×</button>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="page-header">
            <h2><i class="fas fa-user-cog"></i> Account Settings</h2>
        </div>

        <div class="settings-grid">
            <!-- Profile Information -->
            <div class="settings-card">
                <div class="card-header">
                    <i class="fas fa-user"></i>
                    <h3>Profile Information</h3>
                </div>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Rank</label>
                        <select class="form-select" name="rank" required>
                            <option value="P/COL" <?php echo $user['rank'] == 'P/COL' ? 'selected' : ''; ?>>P/COL</option>
                            <option value="P/LT COL" <?php echo $user['rank'] == 'P/LT COL' ? 'selected' : ''; ?>>P/LT COL</option>
                            <option value="P/MAJ" <?php echo $user['rank'] == 'P/MAJ' ? 'selected' : ''; ?>>P/MAJ</option>
                            <option value="P/CPT" <?php echo $user['rank'] == 'P/CPT' ? 'selected' : ''; ?>>P/CPT</option>
                            <option value="P/LT" <?php echo $user['rank'] == 'P/LT' ? 'selected' : ''; ?>>P/LT</option>
                            <option value="P/MSG" <?php echo $user['rank'] == 'P/MSG' ? 'selected' : ''; ?>>P/MSG</option>
                            <option value="P/SSG" <?php echo $user['rank'] == 'P/SSG' ? 'selected' : ''; ?>>P/SSG</option>
                            <option value="P/SGT" <?php echo $user['rank'] == 'P/SGT' ? 'selected' : ''; ?>>P/SGT</option>
                            <option value="P/CPL" <?php echo $user['rank'] == 'P/CPL' ? 'selected' : ''; ?>>P/CPL</option>
                            <option value="P/Pat" <?php echo $user['rank'] == 'P/Pat' ? 'selected' : ''; ?>>P/Pat</option>
                            <option value="NUP" <?php echo $user['rank'] == 'NUP' ? 'selected' : ''; ?>>NUP</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Unit/Office</label>
                        <input type="text" class="form-control" name="unit" value="<?php echo htmlspecialchars($user['unit']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                        <small class="text-muted">Username cannot be changed</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" class="form-control" value="Administrator" readonly>
                        <small class="text-muted">You have full system access</small>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn-save">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="settings-card">
                <div class="card-header">
                    <i class="fas fa-lock"></i>
                    <h3>Change Password</h3>
                </div>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" class="form-control" name="new_password" required>
                        <small class="text-muted">Minimum 8 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn-save">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>
    <footer class="footer">
        <div class="container">
            <p>© <?php echo date('Y'); ?> Philippine National Police - Manolo Fortich Police Station</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>