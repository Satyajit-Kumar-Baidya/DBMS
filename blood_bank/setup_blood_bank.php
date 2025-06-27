<?php
require_once 'db_connect.php';

try {
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

    echo "Blood bank tables created successfully!";
} catch(PDOException $e) {
    echo "Error creating blood bank tables: " . $e->getMessage();
}
?> 