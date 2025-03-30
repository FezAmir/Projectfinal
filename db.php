<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'competition_system');

// Create database connection with error handling
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        error_log("Connection failed: " . $conn->connect_error);
        $conn = null;
    } else {
        // Set charset to utf8mb4
        $conn->set_charset("utf8mb4");
    }
} catch (Exception $e) {
    error_log("Exception in database connection: " . $e->getMessage());
    $conn = null;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?> 