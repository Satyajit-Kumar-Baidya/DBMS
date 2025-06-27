<?php
session_start();
require_once 'dbConnect.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user']['role'] !== 'doctor') {
    header("Location: index.php");
    exit();
}

$errors = [];
$patient = null;
$medical_records = [];
$prescriptions = [];

// Check if patient ID is provided
if (isset($_GET['patient_id'])) {
    $patient_id = filter_var($_GET['patient_id'], FILTER_SANITIZE_NUMBER_INT);
    
    try {
        // Get patient details
        $stmt = $pdo->prepare("
            SELECT p.*, u.first_name, u.last_name, u.email 
            FROM patients p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$patient_id]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($patient) {
            // Get medical records
            $stmt = $pdo->prepare("
                SELECT mr.*, d.first_name as doctor_first_name, d.last_name as doctor_last_name 
                FROM medical_records mr
                LEFT JOIN doctors d ON mr.doctor_id = d.id
                WHERE mr.patient_id = ?
                ORDER BY mr.record_date DESC
            ");
            $stmt->execute([$patient_id]);
            $medical_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get prescriptions
            $stmt = $pdo->prepare("
                SELECT p.*, d.first_name as doctor_first_name, d.last_name as doctor_last_name 
                FROM prescriptions p
                LEFT JOIN doctors d ON p.doctor_id = d.id
                WHERE p.patient_id = ?
                ORDER BY p.prescription_date DESC
            ");
            $stmt->execute([$patient_id]);
            $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $errors[] = "Patient not found.";
        }
    } catch (PDOException $e) {
        $errors[] = "Database error: " . $e->getMessage();
    }
} else {
    $errors[] = "Patient ID is required.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient History - Healthcare System</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Healthcare System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="patients.php">Patients</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="doctors.php">Doctors</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="appointments.php">Appointments</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="medical_records.php">Medical Records</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="prescriptions.php">Prescriptions</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($patient): ?>
            <!-- Patient Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title mb-0">Patient Information</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Patient ID:</strong> <?php echo htmlspecialchars($patient['id']); ?></p>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($patient['email']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($patient['dob'] ?? 'N/A'); ?></p>
                            <p><strong>Gender:</strong> <?php echo htmlspecialchars($patient['gender'] ?? 'N/A'); ?></p>
                            <p><strong>Blood Group:</strong> <?php echo htmlspecialchars($patient['blood_group'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Medical Records -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title mb-0">Medical Records</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($medical_records)): ?>
                        <p class="text-muted">No medical records found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Record Type</th>
                                        <th>Description</th>
                                        <th>Doctor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($medical_records as $record): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($record['record_date']); ?></td>
                                            <td><?php echo htmlspecialchars($record['record_type']); ?></td>
                                            <td><?php echo htmlspecialchars($record['description']); ?></td>
                                            <td><?php echo htmlspecialchars($record['doctor_first_name'] . ' ' . $record['doctor_last_name']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Prescriptions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title mb-0">Prescriptions</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($prescriptions)): ?>
                        <p class="text-muted">No prescriptions found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Medication</th>
                                        <th>Dosage</th>
                                        <th>Instructions</th>
                                        <th>Doctor</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($prescriptions as $prescription): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($prescription['prescription_date']); ?></td>
                                            <td><?php echo htmlspecialchars($prescription['medication']); ?></td>
                                            <td><?php echo htmlspecialchars($prescription['dosage']); ?></td>
                                            <td><?php echo htmlspecialchars($prescription['instructions']); ?></td>
                                            <td><?php echo htmlspecialchars($prescription['doctor_first_name'] . ' ' . $prescription['doctor_last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($prescription['status']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 