<?php
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // First, connect without selecting a database
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create the database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS healthcare_db");
    
    // Select the database
    $pdo->exec("USE healthcare_db");
    
    // Create users table if it doesn't exist (since it's referenced by other tables)
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'doctor', 'patient') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create patients table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS patients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        date_of_birth DATE,
        gender VARCHAR(10),
        blood_group VARCHAR(5),
        address TEXT,
        phone VARCHAR(20),
        emergency_contact VARCHAR(20),
        medical_conditions TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");

    // Create blood bank tables
    
    // Create blood_stock table
    $pdo->exec("CREATE TABLE IF NOT EXISTS blood_stock (
        id INT PRIMARY KEY AUTO_INCREMENT,
        blood_group VARCHAR(5) NOT NULL,
        quantity INT NOT NULL DEFAULT 0,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Create blood_donors table
    $pdo->exec("CREATE TABLE IF NOT EXISTS blood_donors (
        id INT PRIMARY KEY AUTO_INCREMENT,
        patient_id INT,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        blood_group VARCHAR(5) NOT NULL,
        age INT NOT NULL,
        gender ENUM('male', 'female', 'other') NOT NULL,
        phone VARCHAR(20) NOT NULL,
        email VARCHAR(100),
        address TEXT NOT NULL,
        last_donation_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL
    )");

    // Create blood_requests table
    $pdo->exec("CREATE TABLE IF NOT EXISTS blood_requests (
        id INT PRIMARY KEY AUTO_INCREMENT,
        patient_id INT,
        requester_name VARCHAR(100) NOT NULL,
        blood_group VARCHAR(5) NOT NULL,
        units_needed INT NOT NULL,
        urgency ENUM('normal', 'urgent', 'critical') NOT NULL DEFAULT 'normal',
        hospital_name VARCHAR(200) NOT NULL,
        contact_phone VARCHAR(20) NOT NULL,
        contact_email VARCHAR(100),
        request_date DATE NOT NULL,
        status ENUM('pending', 'approved', 'completed', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL
    )");

    // Create blood_donations table
    $pdo->exec("CREATE TABLE IF NOT EXISTS blood_donations (
        id INT PRIMARY KEY AUTO_INCREMENT,
        donor_id INT NOT NULL,
        donation_date DATE NOT NULL,
        units INT NOT NULL DEFAULT 1,
        blood_group VARCHAR(5) NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (donor_id) REFERENCES blood_donors(id) ON DELETE CASCADE
    )");

    // Initialize blood_stock with all blood groups if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM blood_stock");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        $blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        $stmt = $pdo->prepare("INSERT INTO blood_stock (blood_group, quantity) VALUES (?, 0)");
        
        foreach ($blood_groups as $group) {
            $stmt->execute([$group]);
        }
    }

    echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; margin: 10px; border-radius: 5px;'>";
    echo "Database and tables created successfully!<br>";
    echo "You can now use the blood bank system.";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border-radius: 5px;'>";
    echo "Error: " . $e->getMessage();
    echo "</div>";
}
?> 