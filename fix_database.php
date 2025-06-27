<?php
require_once 'dbConnect.php';

echo "Fixing database structure...\n";

try {
    // Drop existing tables in reverse order of dependencies
    $pdo->exec("DROP TABLE IF EXISTS notifications");
    $pdo->exec("DROP TABLE IF EXISTS blog_posts");
    $pdo->exec("DROP TABLE IF EXISTS feedback");
    $pdo->exec("DROP TABLE IF EXISTS health_logs");
    $pdo->exec("DROP TABLE IF EXISTS prescriptions");
    $pdo->exec("DROP TABLE IF EXISTS medicines");
    $pdo->exec("DROP TABLE IF EXISTS payments");
    $pdo->exec("DROP TABLE IF EXISTS medical_records");
    $pdo->exec("DROP TABLE IF EXISTS appointments");
    $pdo->exec("DROP TABLE IF EXISTS doctors");
    $pdo->exec("DROP TABLE IF EXISTS patients");
    $pdo->exec("DROP TABLE IF EXISTS users");

    echo "Existing tables dropped.\n";

    // Create users table
    $pdo->exec("CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(255) NOT NULL,
        last_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'doctor', 'patient') NOT NULL DEFAULT 'patient',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Create patients table
    $pdo->exec("CREATE TABLE patients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        address TEXT,
        dob DATE,
        gender ENUM('Male', 'Female', 'Other'),
        emergency_contact VARCHAR(255),
        blood_group VARCHAR(10),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Create doctors table
    $pdo->exec("CREATE TABLE doctors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        specialization VARCHAR(255),
        qualification VARCHAR(255),
        experience INT,
        availability TEXT,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Create appointments table
    $pdo->exec("CREATE TABLE appointments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        doctor_id INT NOT NULL,
        appointment_date DATETIME NOT NULL,
        reason TEXT,
        status ENUM('Pending', 'Confirmed', 'Cancelled', 'Completed') NOT NULL DEFAULT 'Pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
        FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
    )");

    // Create medical_records table
    $pdo->exec("CREATE TABLE medical_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        doctor_id INT,
        record_date DATE NOT NULL,
        record_type VARCHAR(255),
        description TEXT,
        file_path VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
        FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL
    )");

    // Create medicines table
    $pdo->exec("CREATE TABLE medicines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        stock INT NOT NULL DEFAULT 0,
        price DECIMAL(10, 2),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Create prescriptions table
    $pdo->exec("CREATE TABLE prescriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        doctor_id INT NOT NULL,
        prescription_date DATE NOT NULL,
        medication VARCHAR(255) NOT NULL,
        dosage VARCHAR(255),
        instructions TEXT,
        status ENUM('active', 'filled', 'cancelled') NOT NULL DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
        FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
    )");

     // Create payments table
     $pdo->exec("CREATE TABLE payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        appointment_id INT,
        amount DECIMAL(10, 2) NOT NULL,
        payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        payment_method VARCHAR(255),
        transaction_id VARCHAR(255),
        status ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
        FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
    )");

    // Create health_logs table
    $pdo->exec("CREATE TABLE health_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        log_date DATE NOT NULL,
        log_type VARCHAR(255),
        log_value VARCHAR(255),
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
    )");

    // Create feedback table
    $pdo->exec("CREATE TABLE feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        feedback_type VARCHAR(255),
        subject VARCHAR(255),
        message TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Create blog_posts table
    $pdo->exec("CREATE TABLE blog_posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        author_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        content TEXT,
        published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Create notifications table
    $pdo->exec("CREATE TABLE notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN NOT NULL DEFAULT false,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");


    echo "Tables created successfully.\n";

    // Create test users first

    // Create a default admin user
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES ('Admin', 'User', 'admin@healthcare.com', ?, 'admin')");
    $stmt->execute([$adminPassword]);
    echo "Default admin user created.\n";

    // Create a test doctor
    $doctorPassword = password_hash('doctor123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES ('Test', 'Doctor', 'doctor@healthcare.com', ?, 'doctor')");
    $stmt->execute([$doctorPassword]);
    $newDoctorUserId = $pdo->lastInsertId();
    $stmt = $pdo->prepare("INSERT INTO doctors (user_id, specialization) VALUES (?, ?)");
    $stmt->execute([$newDoctorUserId, 'General Medicine']);
    echo "Test doctor created.\n";

    // Create a test patient
    $patientPassword = password_hash('patient123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES ('Test', 'Patient', 'patient@healthcare.com', ?, 'patient')");
    $stmt->execute([$patientPassword]);
    $newPatientUserId = $pdo->lastInsertId();
    $stmt = $pdo->prepare("INSERT INTO patients (user_id) VALUES (?)");
    $stmt->execute([$newPatientUserId]);
    echo "Test patient created.\n";

    // Fetch the actual patient and doctor IDs from the tables
    $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ? LIMIT 1");
    $stmt->execute([$newPatientUserId]);
    $patientId = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ? LIMIT 1");
    $stmt->execute([$newDoctorUserId]);
    $doctorId = $stmt->fetchColumn();

    // Now add some test appointments for the test patient, referencing the created users
    if ($patientId && $doctorId) {
        $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, reason, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$patientId, $doctorId, '2023-10-27 10:00:00', 'Routine checkup', 'Pending']);
        $stmt->execute([$patientId, $doctorId, '2023-10-20 14:30:00', 'Follow-up', 'Completed']);
        echo "Test appointments created.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 