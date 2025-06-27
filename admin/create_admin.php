<?php
require_once '../dbConnect.php';

try {
    // Delete existing admin user if exists
    $stmt = $pdo->prepare("DELETE FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute(['i.m.tanjamul@gmail.com']);
    
    // Create new admin user
    $stmt = $pdo->prepare("
        INSERT INTO users (
            first_name,
            last_name,
            email,
            password,
            role
        ) VALUES (?, ?, ?, ?, 'admin')
    ");

    // Hash the password
    $hashedPassword = password_hash('1234', PASSWORD_DEFAULT);

    $stmt->execute([
        'Tanjamul',
        'Islam',
        'i.m.tanjamul@gmail.com',
        $hashedPassword
    ]);

    echo "<div style='text-align: center; margin-top: 50px;'>";
    echo "<h3>Admin user created successfully!</h3>";
    echo "<p><strong>Email:</strong> i.m.tanjamul@gmail.com</p>";
    echo "<p><strong>Password:</strong> 1234</p>";
    echo "<p>Please change these credentials after first login.</p>";
    echo "<p><a href='../index.php'>Go to Login Page</a></p>";
    echo "</div>";
} catch (PDOException $e) {
    echo "<div style='text-align: center; margin-top: 50px; color: red;'>";
    echo "<h3>Error creating admin user:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?> 