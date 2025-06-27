<?php
session_start();
require_once '../dbConnect.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'doctor') {
    header("Location: ../index.php");
    exit();
}

$user = $_SESSION['user'];

// Fetch doctor ID
$stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ? LIMIT 1");
$stmt->execute([$user['id']]);
$doctorId = $stmt->fetchColumn();

// Count patients seen based on prescriptions written by this doctor
$patientsSeen = [];
$totalPrescriptions = 0;
if (($handle = fopen('../prescriptions.txt', 'r')) !== false) {
    while (($data = fgetcsv($handle)) !== false) {
        if (count($data) < 2) continue;
        list($pat_id, $doc_id) = $data;
        if ($doc_id == $doctorId) {
            $patientsSeen[$pat_id] = true;
            $totalPrescriptions++;
        }
    }
    fclose($handle);
}

// Count total patients seen (unique patient IDs from appointments.txt)
$totalAppointments = 0;
$upcomingAppointments = 0;
$today = date('Y-m-d');
if (($handle = fopen('../appointments.txt', 'r')) !== false) {
    while (($data = fgetcsv($handle)) !== false) {
        if (count($data) < 7) continue;
        list($doc_id, $pat_id, $date, $time, $reason, $status, $created_at) = $data;
        if ($doc_id == $doctorId) {
            $totalAppointments++;
            if (strtolower($status) !== 'completed' && $date >= $today) {
                $upcomingAppointments++;
            }
        }
    }
    fclose($handle);
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
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #2c3e50;
            color: white;
            padding-top: 20px;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
        }
        .sidebar a:hover {
            background-color: #34495e;
        }
        .main-content {
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <h3 class="text-center mb-4">Doctor Panel</h3>
                <nav>
                    <a href="doctor_dashboard.php" class="active">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href="appointments.php">
                        <i class="fas fa-calendar-check"></i> My Appointments
                    </a>
                    <a href="patients.php">
                        <i class="fas fa-user-injured"></i> My Patients
                    </a>
                    <a href="prescriptions.php">
                        <i class="fas fa-prescription"></i> Prescriptions
                    </a>
                    <a href="schedule.php">
                        <i class="fas fa-clock"></i> My Schedule
                    </a>
                    <a href="../blood_bank/index.php">
                        <i class="fas fa-tint"></i> Blood Bank
                    </a>
                    <a href="../profile.php">
                        <i class="fas fa-user-md"></i> My Profile
                    </a>
                    <a href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="alert alert-info">
                    <h2>Welcome Dr. <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>!</h2>
                    <p>This is your doctor dashboard. You can manage your appointments, patients, and prescriptions from here.</p>
                </div>

                <!-- Content will be added here -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center shadow-sm">
                            <div class="card-body">
                                <i class="fas fa-users fa-2x mb-2 text-primary"></i>
                                <h4><?php echo count($patientsSeen); ?></h4>
                                <p class="text-muted mb-0">Patients Seen</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center shadow-sm">
                            <div class="card-body">
                                <i class="fas fa-calendar-check fa-2x mb-2 text-success"></i>
                                <h4><?php echo $totalAppointments; ?></h4>
                                <p class="text-muted mb-0">Total Appointments</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center shadow-sm">
                            <div class="card-body">
                                <i class="fas fa-calendar-day fa-2x mb-2 text-info"></i>
                                <h4><?php echo $upcomingAppointments; ?></h4>
                                <p class="text-muted mb-0">Upcoming Appointments</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center shadow-sm">
                            <div class="card-body">
                                <i class="fas fa-prescription-bottle-alt fa-2x mb-2 text-warning"></i>
                                <h4><?php echo $totalPrescriptions; ?></h4>
                                <p class="text-muted mb-0">Prescriptions Written</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 