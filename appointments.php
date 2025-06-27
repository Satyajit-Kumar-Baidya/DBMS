<?php
session_start();
require_once 'dbConnect.php';

// Check if user is logged in

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Combine date and time for database insertion/update from the form
        $appointment_datetime = null;
        if (isset($_POST['appointment_date']) && isset($_POST['appointment_time'])) {
            $appointment_datetime = $_POST['appointment_date'] . ' ' . $_POST['appointment_time'] . ':00';
        }

        switch ($_POST['action']) {
            case 'add':
                // The schema in dbConnect.php uses DATETIME for appointment_date
                // It also sets a default status of 'pending'
                $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, status, reason) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['patient_id'],
                    $_POST['doctor_id'],
                    $appointment_datetime,
                    'Scheduled', // Keeping original logic's status 'Scheduled'
                    $_POST['reason']
                ]);
                break;

            case 'edit':
                // Use the primary key 'id' for the WHERE clause as per dbConnect.php
                $stmt = $pdo->prepare("UPDATE appointments SET patient_id = ?, doctor_id = ?, appointment_date = ?, status = ?, reason = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['patient_id'],
                    $_POST['doctor_id'],
                    $appointment_datetime,
                    $_POST['status'],
                    $_POST['reason'],
                    $_POST['appointment_id']
                ]);
                break;

            case 'delete':
                // Use the primary key 'id' for the WHERE clause
                $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
                $stmt->execute([$_POST['appointment_id']]);
                break;
        }
        header("Location: appointments.php");
        exit();
    }
}

// CORRECTED QUERY: Fetch all appointments joining with users table for names
$stmt = $pdo->query("
    SELECT a.*, a.id as appointment_id,
           pu.first_name as patient_first_name, pu.last_name as patient_last_name,
           du.first_name as doctor_first_name, du.last_name as doctor_last_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN users pu ON p.user_id = pu.id
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users du ON d.user_id = du.id
    ORDER BY a.appointment_date DESC
");
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// CORRECTED QUERY: Fetch all patients for dropdown
$stmt = $pdo->query("SELECT p.id, u.first_name, u.last_name FROM patients p JOIN users u ON p.user_id = u.id ORDER BY u.first_name, u.last_name");
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// CORRECTED QUERY: Fetch all doctors for dropdown
$stmt = $pdo->query("SELECT d.id, u.first_name, u.last_name, d.specialization FROM doctors d JOIN users u ON d.user_id = u.id ORDER BY u.first_name, u.last_name");
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Management - Healthcare System</title>
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
                        <a class="nav-link active" href="appointments.php">Appointments</a>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Appointment Management</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAppointmentModal">
                Schedule New Appointment
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Reason</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $appointment): ?>
                            <?php
                                // Split DATETIME into Date and Time for display
                                $appointment_dt = new DateTime($appointment['appointment_date']);
                                $display_date = $appointment_dt->format('Y-m-d');
                                $display_time = $appointment_dt->format('H:i');
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appointment['appointment_id']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?></td>
                                <td><?php echo htmlspecialchars($display_date); ?></td>
                                <td><?php echo htmlspecialchars($display_time); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $appointment['status'] === 'Scheduled' ? 'primary' : 
                                            ($appointment['status'] === 'Completed' ? 'success' : 
                                            ($appointment['status'] === 'pending' ? 'warning' : 'danger')); 
                                    ?>">
                                        <?php echo htmlspecialchars($appointment['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars(substr($appointment['reason'], 0, 30)); ?>...</td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick='editAppointment(<?php echo json_encode($appointment, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'>Edit</button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteAppointment(<?php echo htmlspecialchars($appointment['appointment_id']); ?>)">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addAppointmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Schedule New Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>