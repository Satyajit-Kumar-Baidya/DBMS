<?php
require_once 'dbConnect.php';

try {
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE 'healthcare_db'");
    if ($stmt->rowCount() == 0) {
        echo "Database 'healthcare_db' does not exist!\n";
        exit;
    }
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        echo "Table 'users' does not exist!\n";
        exit;
    }
    
    // Show table structure
    $stmt = $pdo->query("SHOW COLUMNS FROM users");
    echo "Users table structure:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    
    // Check if there are any users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    echo "\nNumber of users in database: " . $userCount . "\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 