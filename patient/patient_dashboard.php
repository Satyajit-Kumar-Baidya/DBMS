<?php
session_start();
require_once '../dbConnect.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'patient') {
    header("Location: ../index.php");
    exit();
}

$user = $_SESSION['user'];

// Get patient statistics
try {
    // Get patient ID
    $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user['id']]);
    $patientId = $stmt->fetchColumn();

    if ($patientId) {
        // Count upcoming appointments from appointments.txt
        $upcomingAppointments = 0;
        $today = date('Y-m-d');
        if (($handle = fopen('../appointments.txt', 'r')) !== false) {
            while (($data = fgetcsv($handle)) !== false) {
                if (count($data) < 7) continue;
                list($doctor_id, $pid, $date, $time, $reason, $status, $created_at) = $data;
                if ($pid == $patientId && strtolower($status) !== 'completed' && $date >= $today) {
                    $upcomingAppointments++;
                }
            }
            fclose($handle);
        }

        // Get medical records count for patient id 8 (or the logged-in patient)
        $medicalRecordsCount = 0;
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM medical_records WHERE patient_id = ?");
        $stmt->execute([$patientId]);
        $medicalRecordsCount = $stmt->fetchColumn();

        // Count active prescriptions from prescriptions.txt
        $activePrescriptions = 0;
        if (($handle = fopen('../prescriptions.txt', 'r')) !== false) {
            while (($data = fgetcsv($handle)) !== false) {
                if (count($data) < 7) continue;
                list($pid, $doctor_id, $medication, $dosage, $instructions, $status, $prescription_date) = $data;
                if ($pid == $patientId && strtolower($status) === 'active') {
                    $activePrescriptions++;
                }
            }
            fclose($handle);
        }

        // Get recent appointment
        $stmt = $pdo->prepare("
           SELECT 
    a.*, 
    u.first_name AS doctor_fname, 
    u.last_name AS doctor_lname, 
    d.specialization 
FROM 
    appointments a 
JOIN 
    doctors d ON a.doctor_id = d.id 
JOIN 
    users u ON d.user_id = u.id 
WHERE 
    a.patient_id = ? 
    AND a.appointment_date >= CURDATE() 
ORDER BY 
    a.appointment_date ASC 
LIMIT 1;
        ");
        $stmt->execute([$patientId]);
        $nextAppointment = $stmt->fetch();
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - Healthcare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --light-bg: #f8f9fa;
        }
        
        body {
            background-color: var(--light-bg);
        }
        
        .sidebar {
            min-height: 100vh;
            background-color: var(--primary-color);
            color: white;
            padding-top: 20px;
        }
        
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            display: block;
            transition: all 0.3s ease;
            border-radius: 5px;
            margin: 2px 10px;
        }
        
        .sidebar a:hover, .sidebar a.active {
            background-color: var(--secondary-color);
            transform: translateX(5px);
        }
        
        .main-content {
            padding: 20px;
        }
        
        .welcome-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: var(--accent-color);
        }
        
        .quick-actions .btn {
            padding: 15px;
            margin-bottom: 15px;
            text-align: left;
            font-weight: 500;
        }
        
        .quick-actions .btn i {
            margin-right: 10px;
        }
        
        .appointment-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }

        .table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
        }

        .btn-view {
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 5px;
        }

        .btn-view:hover {
            background-color: var(--secondary-color);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <h3 class="text-center mb-4">Healthcare</h3>
                <nav>
                    <a href="patient_dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="appointments.php"><i class="fas fa-calendar-check"></i> My Appointments</a>
                    <a href="medical-records.php"><i class="fas fa-file-medical"></i> Medical Records</a>
                    <a href="prescriptions.php"><i class="fas fa-prescription"></i> Prescriptions</a>
                    <a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a>
                    <a href="health-log.php"><i class="fas fa-heartbeat"></i> Health Log</a>
                    <a href="../chatbot/index.html"><i class="fas fa-robot"></i> ChatBot</a>
                    <a href="../profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="welcome-section">
                    <h2>Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h2>
                    <p>Manage your healthcare journey from your personalized dashboard.</p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="stats-card text-center">
                            <i class="fas fa-calendar-check"></i>
                            <h3><?php echo $upcomingAppointments ?? 0; ?></h3>
                            <p class="text-muted">Upcoming Appointments</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card text-center">
                            <i class="fas fa-file-medical"></i>
                            <h3><?php echo $medicalRecordsCount ?? 0; ?></h3>
                            <p class="text-muted">Medical Records</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card text-center">
                            <i class="fas fa-prescription"></i>
                            <h3><?php echo $activePrescriptions ?? 0; ?></h3>
                            <p class="text-muted">Active Prescriptions</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mt-4">
                    <div class="col-12">
                        <h4>Quick Actions</h4>
                        <div class="quick-actions">
                            <a href="book_appointment.php" class="btn btn-primary w-100 mb-2">
                                <i class="fas fa-calendar-plus"></i> Book New Appointment
                            </a>
                            <a href="ambulance_controller.php?action=list" class="btn btn-danger w-100 mb-2">
                                <i class="fas fa-ambulance"></i> Book Ambulance
                            </a>
                            <a href="medical-records.php" class="btn btn-info w-100 mb-2">
                                <i class="fas fa-file-medical"></i> View Medical Records
                            </a>
                            <a href="prescriptions.php" class="btn btn-success w-100 mb-2">
                                <i class="fas fa-prescription"></i> View Prescriptions
                            </a>
                            <a href="../blood_bank/index.php" class="btn btn-danger w-100">
                                <i class="fas fa-tint"></i> Blood Bank
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Next Appointment -->
                <?php if (isset($nextAppointment) && $nextAppointment): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="appointment-card">
                            <h4>Next Appointment</h4>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="mb-1">
                                        <strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($nextAppointment['doctor_fname'] . ' ' . $nextAppointment['doctor_lname']); ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Specialty:</strong> <?php echo htmlspecialchars($nextAppointment['specialty']); ?>
                                    </p>
                                    <p class="mb-0">
                                        <strong>Date & Time:</strong> <?php echo date('F j, Y', strtotime($nextAppointment['appointment_date'])); ?>
                                    </p>
                                </div>
                                <a href="view_appointment.php?id=<?php echo $nextAppointment['id']; ?>" class="btn btn-view">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 