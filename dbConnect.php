<?php 
$host = "localhost";
$username = 'root';
$password = '';
$dbname = 'healthcare_db';

try {
    // First connect without database selected
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    
    // Connect to the database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create users table first (since other tables depend on it)
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'doctor', 'patient') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create patients table
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
        allergies TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");

    // Create doctors table
    $pdo->exec("CREATE TABLE IF NOT EXISTS doctors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        specialization VARCHAR(100),
        qualification TEXT,
        experience INT,
        hospital VARCHAR(255),
        location VARCHAR(255),
        consultation_fee DECIMAL(10,2),
        background TEXT,
        available_days VARCHAR(255),
        availability VARCHAR(100),
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");

    // Create appointments table
    $pdo->exec("CREATE TABLE IF NOT EXISTS appointments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        doctor_id INT NOT NULL,
        appointment_date DATETIME NOT NULL,
        status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
        reason TEXT,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id),
        FOREIGN KEY (doctor_id) REFERENCES doctors(id)
    )");

    // Create medical_history table
    $pdo->exec("CREATE TABLE IF NOT EXISTS medical_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        doctor_id INT NOT NULL,
        diagnosis TEXT,
        treatment TEXT,
        notes TEXT,
        visit_date DATE,
        next_visit_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id),
        FOREIGN KEY (doctor_id) REFERENCES doctors(id)
    )");

    // Create prescriptions table
    $pdo->exec("CREATE TABLE IF NOT EXISTS prescriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        doctor_id INT NOT NULL,
        medication TEXT NOT NULL,
        dosage TEXT,
        instructions TEXT,
        prescription_date DATE NOT NULL DEFAULT CURRENT_DATE,
        status VARCHAR(50) DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id),
        FOREIGN KEY (doctor_id) REFERENCES doctors(id)
    )");

    // Create ambulances table
    $pdo->exec("CREATE TABLE IF NOT EXISTS ambulances (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vehicle_number VARCHAR(20) NOT NULL UNIQUE,
        vehicle_type VARCHAR(50),
        driver_name VARCHAR(100),
        driver_contact VARCHAR(20),
        location VARCHAR(255),
        status ENUM('available', 'busy', 'maintenance') DEFAULT 'available',
        price_per_km DECIMAL(10,2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create ambulance_bookings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS ambulance_bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ambulance_id INT NOT NULL,
        patient_id INT NOT NULL,
        pickup_location VARCHAR(255) NOT NULL,
        destination VARCHAR(255) NOT NULL,
        booking_date DATE NOT NULL,
        booking_time TIME NOT NULL,
        status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ambulance_id) REFERENCES ambulances(id),
        FOREIGN KEY (patient_id) REFERENCES patients(id)
    )");

} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
