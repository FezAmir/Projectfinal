<?php
require_once 'config.php';
require_once 'db.php';

// Display all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Setting up EasyComp Database</h1>";

try {
    // Read the SQL file
    $sql = file_get_contents('setup_database.sql');
    
    // Split into individual statements
    $statements = explode(';', $sql);
    
    $success = true;
    $total = 0;
    $completed = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $total++;
            try {
                if ($conn->query($statement)) {
                    $completed++;
                } else {
                    echo "<p style='color: red;'>Error executing statement: " . $conn->error . "</p>";
                    echo "<pre>" . htmlspecialchars($statement) . "</pre>";
                    $success = false;
                }
            } catch (mysqli_sql_exception $e) {
                echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
                echo "<pre>" . htmlspecialchars($statement) . "</pre>";
                $success = false;
            }
        }
    }
    
    if ($success) {
        echo "<p style='color: green;'>Database setup completed successfully! Executed $completed of $total statements.</p>";
        echo "<p>You can now <a href='index.php'>return to the homepage</a> and use the application.</p>";
    } else {
        echo "<p style='color: orange;'>Database setup completed with some errors. Executed $completed of $total statements.</p>";
        echo "<p>Some features might not work correctly. Please check the errors above.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>An error occurred: " . $e->getMessage() . "</p>";
}
?> 