<?php
require_once '../dbConnect.php';
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Sample ambulance data
$ambulances = [
    [
        'vehicle_number' => 'AMB-001',
        'vehicle_type' => 'Basic Life Support',
        'driver_name' => 'John Smith',
        'driver_contact' => '+1234567890',
        'location' => 'Main Hospital',
        'price_per_km' => 10.00
    ],
    [
        'vehicle_number' => 'AMB-002',
        'vehicle_type' => 'Advanced Life Support',
        'driver_name' => 'Mike Johnson',
        'driver_contact' => '+1234567891',
        'location' => 'City Center',
        'price_per_km' => 15.00
    ],
    [
        'vehicle_number' => 'AMB-003',
        'vehicle_type' => 'Patient Transport',
        'driver_name' => 'David Wilson',
        'driver_contact' => '+1234567892',
        'location' => 'North Branch',
        'price_per_km' => 8.00
    ]
];

try {
    $stmt = $pdo->prepare("
        INSERT INTO ambulances (
            vehicle_number, 
            vehicle_type, 
            driver_name, 
            driver_contact, 
            location, 
            price_per_km,
            status
        ) VALUES (?, ?, ?, ?, ?, ?, 'available')
    ");

    foreach ($ambulances as $ambulance) {
        $stmt->execute([
            $ambulance['vehicle_number'],
            $ambulance['vehicle_type'],
            $ambulance['driver_name'],
            $ambulance['driver_contact'],
            $ambulance['location'],
            $ambulance['price_per_km']
        ]);
    }

    echo "Sample ambulances added successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 