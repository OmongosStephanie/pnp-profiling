<?php
// restore_profile.php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : 0;

if ($id > 0) {
    try {
        // Update profile status to active
        $query = "UPDATE biographical_profiles SET status = 'active' WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $_SESSION['success_message'] = "Profile restored successfully!";
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error restoring profile: " . $e->getMessage();
    }
}

header("Location: archive.php");
exit();
?>