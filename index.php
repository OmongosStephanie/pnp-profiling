<?php
// index.php - Login Page
session_start();
require_once 'config/database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if database connection is successful
    if ($db) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        $query = "SELECT * FROM users WHERE username = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['rank'] = $user['rank'];
                $_SESSION['unit'] = $user['unit'];
                $_SESSION['role'] = $user['role'];
                
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid password!";
            }
        } else {
            $error = "Username not found!";
        }
    } else {
        $error = "Database connection failed. Please check if MySQL is running.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PNP Biographical Profiling System - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #0a2f4d 0%, #1a4b7a 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            overflow: hidden;
            width: 450px;
            max-width: 90%;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            background: #0a2f4d;
            color: white;
            padding: 30px;
            text-align: center;
            border-bottom: 5px solid #c9a959;
        }
        
        .login-header i {
            font-size: 50px;
            margin-bottom: 15px;
            color: #c9a959;
        }
        
        .login-header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .login-header h3 {
            margin: 5px 0 0;
            font-size: 16px;
            font-weight: normal;
            color: #e0e0e0;
        }
        
        .login-header p {
            margin: 10px 0 0;
            font-size: 14px;
            color: #ccc;
        }
        
        .login-form {
            padding: 40px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
            display: block;
        }
        
        .form-control {
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #0a2f4d;
            box-shadow: none;
        }
        
        .btn-login {
            background: #0a2f4d;
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            padding: 12px;
            width: 100%;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-login:hover {
            background: #c9a959;
            color: #0a2f4d;
        }
        
        .alert {
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .footer {
            background: #f5f5f5;
            padding: 15px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
        }
        
        .mysql-status {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-shield-alt"></i>
            <h2>Department of the Interior and Local Government</h2>
            <h3>PHILIPPINE NATIONAL POLICE</h3>
            <small>BUKIDNON POLICE PROVINCIAL OFFICE</small>
            <p>MANOLO FORTICH POLICE STATION</p>
        </div>
        <div class="login-form">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- MySQL Status Check -->
            <?php
            $test_connection = @fsockopen("localhost", 3306, $errno, $errstr, 1);
            if (!$test_connection):
            ?>
            <div class="mysql-status">
                <i class="fas fa-database"></i> 
                <strong>MySQL is not running!</strong> 
                <p class="mt-2 mb-0">Please start MySQL in XAMPP Control Panel.</p>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Username</label>
                    <input type="text" class="form-control" name="username" required placeholder="Enter username" value="admin">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" class="form-control" name="password" required placeholder="Enter password" value="admin123">
                </div>
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
        </div>
        <div class="footer">
            <small>Authorized Personnel Only | For Official Use Only</small><br>
        </div>
    </div>
</body>
</html>