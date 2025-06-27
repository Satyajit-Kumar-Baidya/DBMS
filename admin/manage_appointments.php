<?php
session_start();
require_once '../dbConnect.php';

// Restrict to admin only
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle add appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $patient_id = $_POST['patient_id'];
    $doctor_id = $_POST['doctor_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $reason = $_POST['reason'];
    $status = 'Pending';
    $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$patient_id, $doctor_id, $appointment_date, $appointment_time, $reason, $status]);
    header("Location: manage_appointments.php");
    exit();
}

// Fetch all appointments
$stmt = $pdo->query("SELECT a.*, p.first_name AS patient_first_name, p.last_name AS patient_last_name, d.first_name AS doctor_first_name, d.last_name AS doctor_last_name FROM appointments a JOIN patients p ON a.patient_id = p.id JOIN doctors d ON a.doctor_id = d.id ORDER BY a.appointment_date DESC, a.appointment_time DESC");
$appointments = $stmt->fetchAll();

// Fetch all patients and doctors for the form
$patients = $pdo->query("SELECT id, first_name, last_name FROM patients ORDER BY first_name, last_name")->fetchAll();
$doctors = $pdo->query("SELECT id, first_name, last_name FROM doctors ORDER BY first_name, last_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Appointments</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Appointment Management</h2>
            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#addAppointmentModal">
                Schedule Appointment
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
                                <th>Reason</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $apt): ?>
                            <tr>
                                <td><?php echo $apt['id']; ?></td>
                                <td><?php echo $apt['patient_first_name'] . ' ' . $apt['patient_last_name']; ?></td>
                                <td><?php echo $apt['doctor_first_name'] . ' ' . $apt['doctor_last_name']; ?></td>
                                <td><?php echo $apt['appointment_date']; ?></td>
                                <td><?php echo $apt['appointment_time']; ?></td>
                                <td><?php echo htmlspecialchars($apt['reason']); ?></td>
                                <td><?php echo $apt['status']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Add Appointment Modal -->
        <div class="modal fade" id="addAppointmentModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Schedule Appointment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="manage_appointments.php" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="add">
                            <div class="mb-3">
                                <label class="form-label">Patient</label>
                                <select class="form-select" name="patient_id" required>
                                    <option value="">Select Patient</option>
                                    <?php foreach ($patients as $pat): ?>
                                        <option value="<?php echo $pat['id']; ?>"><?php echo $pat['first_name'] . ' ' . $pat['last_name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Doctor</label>
                                <select class="form-select" name="doctor_id" required>
                                    <option value="">Select Doctor</option>
                                    <?php foreach ($doctors as $doc): ?>
                                        <option value="<?php echo $doc['id']; ?>"><?php echo $doc['first_name'] . ' ' . $doc['last_name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" class="form-control" name="appointment_date" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Time</label>
                                <input type="time" class="form-control" name="appointment_time" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reason</label>
                                <textarea class="form-control" name="reason" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-info">Schedule</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Show Add Appointment modal if ?action=add is present
    if (window.location.search.includes('action=add')) {
        var addModal = new bootstrap.Modal(document.getElementById('addAppointmentModal'));
        window.addEventListener('DOMContentLoaded', function() {
            addModal.show();
        });
    }
    </script>
</body>
</html> 