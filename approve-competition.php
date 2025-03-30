<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Check if competition ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid competition ID";
    header('Location: admin-competitions.php');
    exit;
}

$competition_id = $_GET['id'];
$admin_id = $_SESSION['user_id'];

// Fetch competition details
$competition_query = "SELECT c.*, o.username as organizer_name 
                     FROM competitions c 
                     JOIN organizers o ON c.organizer_id = o.id 
                     WHERE c.id = ?";
$stmt = $conn->prepare($competition_query);
$stmt->bind_param("i", $competition_id);
$stmt->execute();
$competition_result = $stmt->get_result();

if ($competition_result->num_rows === 0) {
    $_SESSION['error'] = "Competition not found";
    header('Location: admin-competitions.php');
    exit;
}

$competition = $competition_result->fetch_assoc();

// Update competition status to approved
$update_query = "UPDATE competitions SET status = 'approved', updated_at = NOW() WHERE id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("i", $competition_id);

if ($stmt->execute()) {
    // Add notification for the organizer
    $notification_query = "INSERT INTO notifications 
                          (user_id, user_type, message, related_id, related_type, created_at) 
                          VALUES (?, 'organizer', ?, ?, 'competition', NOW())";
    $notification_message = "Your competition \"" . $competition['title'] . "\" has been approved";
    $stmt = $conn->prepare($notification_query);
    $stmt->bind_param("isi", $competition['organizer_id'], $notification_message, $competition_id);
    $stmt->execute();
    
    // Log admin action
    $log_query = "INSERT INTO admin_logs 
                 (admin_id, action, details, created_at) 
                 VALUES (?, 'approved_competition', ?, NOW())";
    $log_details = "Approved competition ID: " . $competition_id . ", Title: " . $competition['title'];
    $stmt = $conn->prepare($log_query);
    $stmt->bind_param("is", $admin_id, $log_details);
    $stmt->execute();
    
    $_SESSION['success'] = "Competition has been approved successfully";
} else {
    $_SESSION['error'] = "Failed to approve competition: " . $conn->error;
}

// Redirect back to referring page or competitions list
if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'admin-') !== false) {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
} else {
    header('Location: admin-competitions.php');
}
exit;
?> 