<?php
require_once 'dbConnect.php';

try {
    // Create test user
    $email = 'test@example.com';
    $password = password_hash('test123', PASSWORD_BCRYPT);
    $first_name = 'Test';
    $last_name = 'User';
    
    $stmt = $pdo->prepare("INSERT INTO users (email, password, first_name, last_name) VALUES (?, ?, ?, ?)");
    $stmt->execute([$email, $password, $first_name, $last_name]);
    
    echo "Test user created successfully!\n";
    echo "Email: test@example.com\n";
    echo "Password: test123\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 