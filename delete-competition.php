<?php
session_start();
require_once 'config.php';

// Check if user is logged in and has permission (admin or organizer)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'organizer')) {
    header('Location: login.php');
    exit;
}

// Check if competition ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid competition ID";
    
    // Redirect based on user role
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin-competitions.php');
    } else {
        header('Location: organizer-competitions.php');
    }
    exit;
}

$competition_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch competition details first (for logging and permission check)
$competition_query = "SELECT * FROM competitions WHERE id = ?";
$stmt = $conn->prepare($competition_query);
$stmt->bind_param("i", $competition_id);
$stmt->execute();
$competition_result = $stmt->get_result();

if ($competition_result->num_rows === 0) {
    $_SESSION['error'] = "Competition not found";
    
    // Redirect based on user role
    if ($role === 'admin') {
        header('Location: admin-competitions.php');
    } else {
        header('Location: organizer-competitions.php');
    }
    exit;
}

$competition = $competition_result->fetch_assoc();

// Check if organizer is trying to delete someone else's competition
if ($role === 'organizer' && $competition['organizer_id'] != $user_id) {
    $_SESSION['error'] = "You don't have permission to delete this competition";
    header('Location: organizer-competitions.php');
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Delete competition participants first (foreign key constraint)
    $delete_participants_query = "DELETE FROM competition_participants WHERE competition_id = ?";
    $stmt = $conn->prepare($delete_participants_query);
    $stmt->bind_param("i", $competition_id);
    $stmt->execute();
    
    // Delete competition-related notifications
    $delete_notifications_query = "DELETE FROM notifications WHERE related_id = ? AND related_type = 'competition'";
    $stmt = $conn->prepare($delete_notifications_query);
    $stmt->bind_param("i", $competition_id);
    $stmt->execute();
    
    // Delete the competition
    $delete_competition_query = "DELETE FROM competitions WHERE id = ?";
    $stmt = $conn->prepare($delete_competition_query);
    $stmt->bind_param("i", $competition_id);
    $stmt->execute();
    
    // Log admin actions if an admin deleted it
    if ($role === 'admin') {
        $log_query = "INSERT INTO admin_logs (admin_id, action, details, created_at) VALUES (?, 'deleted_competition', ?, NOW())";
        $log_details = "Deleted competition ID: " . $competition_id . ", Title: " . $competition['title'];
        $stmt = $conn->prepare($log_query);
        $stmt->bind_param("is", $user_id, $log_details);
        $stmt->execute();
    }
    
    // Commit the transaction
    $conn->commit();
    
    $_SESSION['success'] = "Competition has been deleted successfully";
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    $_SESSION['error'] = "Failed to delete competition: " . $e->getMessage();
}

// Redirect based on user role
if ($role === 'admin') {
    header('Location: admin-competitions.php');
} else {
    header('Location: organizer-competitions.php');
}
exit;
?> 