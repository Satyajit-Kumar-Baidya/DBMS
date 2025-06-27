<?php
session_start();
require_once '../dbConnect.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    header("Location: ../index.php");
    exit();
}

$user = $_SESSION['user'];
$doctorId = null;
$schedule = [];
$errors = [];
$success = '';

// Get doctor ID
try {
    $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user['id']]);
    $doctorId = $stmt->fetchColumn();

    if (!$doctorId) {
        session_destroy();
        header("Location: ../index.php");
        exit();
    }

    // Fetch doctor's schedule/appointments for display (placeholder)
    // This could be customized to show a calendar view or a list of upcoming slots
     $stmt = $pdo->prepare("SELECT a.*, p.user_id as patient_user_id, pu.first_name as patient_first_name, pu.last_name as patient_last_name FROM appointments a JOIN patients p ON a.patient_id = p.id JOIN users pu ON p.user_id = pu.id WHERE a.doctor_id = ? ORDER BY a.appointment_date ASC");
    $stmt->execute([$doctorId]);
    $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errors[] = "Database Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Schedule - Healthcare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <!-- Add a calendar library like FullCalendar here if needed -->
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <h3 class="text-center mb-4">Healthcare</h3>
                <nav>
                    <a href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="appointments.php"><i class="fas fa-calendar-check"></i> My Appointments</a>
                    <a href="patients.php"><i class="fas fa-procedures"></i> My Patients</a>
                    <a href="prescriptions.php"><i class="fas fa-prescription"></i> Prescriptions</a>
                    <a href="medical-records.php"><i class="fas fa-file-medical"></i> Medical Records</a>
                    <a href="schedule.php" class="active"><i class="fas fa-calendar-alt"></i> My Schedule</a>
                    <a href="../profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="welcome-section">
                    <h2>My Schedule</h2>
                    <p>View and manage your availability and upcoming appointments.</p>
                </div>

                 <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <!-- Schedule Display (Placeholder) -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Upcoming Schedule</h5>
                    </div>
                    <div class="card-body">
                         <?php if (empty($schedule)): ?>
                            <p class="text-center">No upcoming appointments in your schedule.</p>
                        <?php else: ?>
                             <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Patient</th>
                                            <th>Date & Time</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($schedule as $appointment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?></td>
                                                <td><?php echo date('M d, Y H:i', strtotime($appointment['appointment_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($appointment['reason'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $appointment['status'] === 'Confirmed' ? 'success' : 
                                                            ($appointment['status'] === 'Pending' ? 'warning' : 
                                                            ($appointment['status'] === 'Cancelled' ? 'danger' : 'secondary')); 
                                                    ?>">
                                                        <?php echo htmlspecialchars($appointment['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <!-- Action buttons (example) -->
                                                    <a href="#" class="btn btn-sm btn-primary">View Details</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                 <!-- Add/Manage Availability (Placeholder) -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Manage Availability</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-center">Availability management features would go here.</p>
                        <!-- Example: Form to add available time slots -->
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../script.js"></script>
    <!-- Add calendar script initialization here if using a calendar library -->
</body>
</html> 