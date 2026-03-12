<?php
// delete_profile.php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : 0;

if ($id > 0) {
    try {
        // Start transaction
        $db->beginTransaction();
        
        // Delete siblings first (foreign key constraint)
        $query = "DELETE FROM siblings WHERE profile_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        // Delete profile
        $query = "DELETE FROM biographical_profiles WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $db->commit();
        
        $_SESSION['message'] = "Profile deleted successfully!";
        $_SESSION['message_type'] = "success";
        
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['message'] = "Error deleting profile: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
    }
}

header("Location: dashboard.php");
exit();
?>