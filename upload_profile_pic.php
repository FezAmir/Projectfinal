<?php
session_start();
require_once 'config/db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to upload a profile picture']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Determine the table based on user type
$table = ($user_type === 'student') ? 'students' : 'teachers';

// Log the request details
error_log("Upload request received - User ID: $user_id, Table: $table");

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    
    // Validate file
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload a JPEG, PNG, or GIF image.']);
        exit;
    }
    
    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'File is too large. Maximum size is 5MB.']);
        exit;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Error uploading file. Please try again.']);
        exit;
    }
    
    // Generate unique filename
    $filename = 'profile_' . $user_id . '_' . time() . '_' . $file['name'];
    $uploadDir = 'uploads/profile_pictures/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        error_log("Creating upload directory: " . $uploadDir);
        if (!mkdir($uploadDir, 0777, true)) {
            error_log("Failed to create directory: " . $uploadDir);
            echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
            exit;
        }
    }
    
    $destination = $uploadDir . $filename;
    
    error_log("Attempting to move file to: " . $destination);
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        error_log("File successfully moved to: " . $destination);
        
        // Update database
        $sql = "UPDATE $table SET profile_picture = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $destination, $user_id);
        
        if ($stmt->execute()) {
            error_log("Database updated successfully");
            // Update session data
            $_SESSION['profile_picture'] = $destination;
            
            echo json_encode(['success' => true, 'message' => 'Profile picture updated successfully', 'filepath' => $destination]);
        } else {
            error_log("Database update failed: " . $stmt->error);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        }
        
        $stmt->close();
    } else {
        $error = error_get_last();
        error_log("File upload failed. Error: " . ($error ? $error['message'] : 'Unknown error'));
        error_log("Upload error details: " . print_r($_FILES['profile_picture']['error'], true));
        echo json_encode(['success' => false, 'message' => 'Failed to save file. Please try again.']);
    }
} else {
    error_log("Invalid request - Method: " . $_SERVER['REQUEST_METHOD'] . ", Files: " . print_r($_FILES, true));
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

$conn->close(); 