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
$availability = [];

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

    // Handle add availability
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_availability'])) {
        $day = $_POST['day_of_week'] ?? '';
        $start = $_POST['start_time'] ?? '';
        $end = $_POST['end_time'] ?? '';
        if ($day && $start && $end) {
            $stmt = $pdo->prepare("INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
            $stmt->execute([$doctorId, $day, $start, $end]);
            $success = 'Availability slot added.';
        } else {
            $errors[] = 'All fields are required for availability.';
        }
    }
    // Handle delete availability
    if (isset($_GET['delete_avail'])) {
        $availId = (int)$_GET['delete_avail'];
        $stmt = $pdo->prepare("DELETE FROM doctor_availability WHERE id = ? AND doctor_id = ?");
        $stmt->execute([$availId, $doctorId]);
        $success = 'Availability slot deleted.';
    }

    // Fetch doctor's schedule/appointments for display (existing)
    $stmt = $pdo->prepare("SELECT a.*, p.user_id as patient_user_id, pu.first_name as patient_first_name, pu.last_name as patient_last_name FROM appointments a JOIN patients p ON a.patient_id = p.id JOIN users pu ON p.user_id = pu.id WHERE a.doctor_id = ? ORDER BY a.appointment_date ASC");
    $stmt->execute([$doctorId]);
    $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch doctor's availability
    $stmt = $pdo->prepare("SELECT * FROM doctor_availability WHERE doctor_id = ? ORDER BY FIELD(day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time");
    $stmt->execute([$doctorId]);
    $availability = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                        <h6>Add New Availability Slot</h6>
                        <form method="POST" class="row g-2 mb-4">
                            <input type="hidden" name="add_availability" value="1">
                            <div class="col-md-3">
                                <select class="form-select" name="day_of_week" required>
                                    <option value="">Day</option>
                                    <option>Monday</option>
                                    <option>Tuesday</option>
                                    <option>Wednesday</option>
                                    <option>Thursday</option>
                                    <option>Friday</option>
                                    <option>Saturday</option>
                                    <option>Sunday</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="time" class="form-control" name="start_time" required>
                            </div>
                            <div class="col-md-3">
                                <input type="time" class="form-control" name="end_time" required>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-success w-100">Add Slot</button>
                            </div>
                        </form>
                        <h6>Current Availability</h6>
                        <?php if (empty($availability)): ?>
                            <p class="text-center">No availability slots set.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Day</th>
                                            <th>Start Time</th>
                                            <th>End Time</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($availability as $slot): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($slot['day_of_week']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($slot['start_time'],0,5)); ?></td>
                                            <td><?php echo htmlspecialchars(substr($slot['end_time'],0,5)); ?></td>
                                            <td>
                                                <a href="?delete_avail=<?php echo $slot['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this slot?');">Delete</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
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