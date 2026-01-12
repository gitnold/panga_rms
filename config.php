<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'php_user');
define('DB_PASS', 'Php_pass@2026');
define('DB_NAME', 'panga_rms');

// Create connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Start session
session_start();
?>
