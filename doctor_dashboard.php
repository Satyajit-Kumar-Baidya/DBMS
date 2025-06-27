<?php
session_start();
require_once 'dbConnect.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    header("Location: index.php");
    exit();
}

$user = $_SESSION['user'];
$role = $user['role'];
$stats = [];

// Get doctor ID
$doctorId = null;
try {
    $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $doctorId = $stmt->fetchColumn();

    if (!$doctorId) {
         // If doctor record not found, log out user
         session_destroy();
         header("Location: index.php");
         exit();
    }

    // Doctor Statistics
    $stats = [
        'today_appointments' => $pdo->query("SELECT COUNT(*) FROM appointments WHERE doctor_id = $doctorId AND DATE(appointment_date) = CURDATE()")->fetchColumn(),
        'total_patients' => $pdo->query("SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE doctor_id = $doctorId")->fetchColumn(),
        'pending_prescriptions' => $pdo->query("SELECT COUNT(*) FROM prescriptions WHERE doctor_id = $doctorId")->fetchColumn()
    ];
} catch (PDOException $e) {
    // Handle database error
    echo "Database Error: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Healthcare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <h3 class="text-center mb-4">Healthcare</h3>
                <nav>
                    <a href="/Healthcare-Management/doctor_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="/Healthcare-Management/doctor/appointments.php"><i class="fas fa-calendar-check"></i> My Appointments</a>
                    <a href="/Healthcare-Management/doctor/patients.php"><i class="fas fa-procedures"></i> My Patients</a>
                    <a href="/Healthcare-Management/doctor/prescriptions.php"><i class="fas fa-prescription"></i> Prescriptions</a>
                    <a href="/Healthcare-Management/doctor/medical-records.php"><i class="fas fa-file-medical"></i> Medical Records</a>
                    <a href="/Healthcare-Management/profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="/Healthcare-Management/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="welcome-section">
                    <h2>Welcome, Dr. <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>!</h2>
                    <p>Here's your doctor dashboard overview.</p>
                </div>

                <div class="row">
                    <!-- Doctor Statistics -->
                    <div class="col-md-4">
                        <div class="stat-card bg-primary">
                            <i class="fas fa-calendar-check"></i>
                            <h3><?php echo $stats['today_appointments'] ?? 0; ?></h3>
                            <p>Today's Appointments</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card bg-success">
                            <i class="fas fa-procedures"></i>
                            <h3><?php echo $stats['total_patients'] ?? 0; ?></h3>
                            <p>Total Patients</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card bg-warning">
                            <i class="fas fa-prescription"></i>
                            <h3><?php echo $stats['pending_prescriptions'] ?? 0; ?></h3>
                            <p>Pending Prescriptions</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <a href="doctor/appointments.php?action=add" class="btn btn-primary btn-lg w-100">
                                            <i class="fas fa-calendar-plus"></i> Schedule Appointment
                                        </a>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <a href="doctor/prescriptions.php?action=add" class="btn btn-success btn-lg w-100">
                                            <i class="fas fa-prescription"></i> Write Prescription
                                        </a>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <a href="doctor/medical-records.php?action=add" class="btn btn-info btn-lg w-100">
                                            <i class="fas fa-file-medical"></i> Add Medical Record
                                        </a>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <a href="blood_bank/index.php" class="btn btn-danger btn-lg w-100">
                                            <i class="fas fa-tint"></i> Blood Bank
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html> 