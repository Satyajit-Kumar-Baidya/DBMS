<?php
require_once 'dbConnect.php';

echo "<h2>Testing Database Connection</h2>";

try {
    // Test database connection
    $pdo->query("SELECT 1");
    echo "Database connection successful!<br><br>";

    // Check if tables exist
    echo "<h3>Checking Tables:</h3>";
    $tables = ['users', 'doctors', 'patients', 'appointments'];
    foreach ($tables as $table) {
        $result = $pdo->query("SHOW TABLES LIKE '$table'");
        echo "$table table: " . ($result->rowCount() > 0 ? "Exists" : "Missing") . "<br>";
    }

    // Count records in each table
    echo "<br><h3>Record Counts:</h3>";
    foreach ($tables as $table) {
        $result = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $result->fetchColumn();
        echo "$table: $count records<br>";
    }

    // Show doctor details
    echo "<br><h3>Doctor Details:</h3>";
    $stmt = $pdo->query("
        SELECT 
            u.first_name, 
            u.last_name, 
            d.specialization, 
            d.qualification,
            d.experience,
            d.hospital,
            d.location,
            d.consultation_fee
        FROM doctors d 
        JOIN users u ON d.user_id = u.id
    ");
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($doctors) > 0) {
        foreach ($doctors as $doctor) {
            echo "Dr. {$doctor['first_name']} {$doctor['last_name']}<br>";
            echo "- Specialization: {$doctor['specialization']}<br>";
            echo "- Qualification: {$doctor['qualification']}<br>";
            echo "- Experience: {$doctor['experience']} years<br>";
            echo "- Hospital: {$doctor['hospital']}<br>";
            echo "- Location: {$doctor['location']}<br>";
            echo "- Consultation Fee: ${$doctor['consultation_fee']}<br><br>";
        }
    } else {
        echo "No doctors found in database.<br>";
    }

    // Show appointment details
    echo "<br><h3>Appointment Details:</h3>";
    $stmt = $pdo->query("
        SELECT 
            a.appointment_date,
            a.appointment_time,
            a.status,
            a.reason,
            u1.first_name as patient_first_name, 
            u1.last_name as patient_last_name,
            u2.first_name as doctor_first_name, 
            u2.last_name as doctor_last_name,
            d.specialization
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        JOIN users u1 ON p.user_id = u1.id
        JOIN doctors d ON a.doctor_id = d.id
        JOIN users u2 ON d.user_id = u2.id
        ORDER BY a.appointment_date, a.appointment_time
    ");
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($appointments) > 0) {
        foreach ($appointments as $apt) {
            echo "Date: {$apt['appointment_date']} at {$apt['appointment_time']}<br>";
            echo "Patient: {$apt['patient_first_name']} {$apt['patient_last_name']}<br>";
            echo "Doctor: Dr. {$apt['doctor_first_name']} {$apt['doctor_last_name']} ({$apt['specialization']})<br>";
            echo "Status: <strong>{$apt['status']}</strong><br>";
            echo "Reason: {$apt['reason']}<br><br>";
        }
    } else {
        echo "No appointments found in database.<br>";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 