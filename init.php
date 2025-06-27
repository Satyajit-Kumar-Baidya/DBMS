<?php
require_once 'dbConnect.php';

// Function to create necessary directories
function createDirectories() {
    $directories = [
        'uploads/medical_records',
        'uploads/prescriptions',
        'uploads/test_reports',
        'uploads/profile_pictures'
    ];

    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}

// Function to create database tables
function createTables() {
    global $pdo;
    
    try {
        // Read and execute the SQL file
        $sql = file_get_contents('healthcare_tables.sql');
        $pdo->exec($sql);
        echo "Database tables created successfully!<br>";
    } catch (PDOException $e) {
        die("Error creating tables: " . $e->getMessage());
    }
}

// Function to create default admin user
function createDefaultAdmin() {
    global $pdo;
    
    try {
        // Check if admin exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = 'admin@healthcare.com'");
        $stmt->execute();
        
        if (!$stmt->fetch()) {
            $password = password_hash('admin123', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(['Admin', 'User', 'admin@healthcare.com', $password, 'admin']);
            echo "Default admin user created successfully!<br>";
        }
    } catch (PDOException $e) {
        die("Error creating admin user: " . $e->getMessage());
    }
}

// Function to create test data
function createTestData() {
    global $pdo;
    
    try {
        // Create test doctor
        $password = password_hash('doctor123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['John', 'Doe', 'doctor@healthcare.com', $password, 'doctor']);
        $doctorId = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("INSERT INTO doctors (user_id, specialization, qualification, experience) VALUES (?, ?, ?, ?)");
        $stmt->execute([$doctorId, 'General Medicine', 'MBBS, MD', 10]);
        
        // Create test patient
        $password = password_hash('patient123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['Jane', 'Smith', 'patient@healthcare.com', $password, 'patient']);
        $patientId = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("INSERT INTO patients (user_id, address, dob, gender) VALUES (?, ?, ?, ?)");
        $stmt->execute([$patientId, '123 Main St', '1990-01-01', 'female']);
        
        echo "Test data created successfully!<br>";
    } catch (PDOException $e) {
        die("Error creating test data: " . $e->getMessage());
    }
}

// Execute initialization
try {
    createDirectories();
    createTables();
    createDefaultAdmin();
    createTestData();
    echo "Initialization completed successfully!";
} catch (Exception $e) {
    die("Initialization failed: " . $e->getMessage());
} 