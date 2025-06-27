<?php
session_start();
require_once '../dbConnect.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    header("Location: ../index.php");
    exit();
}

$user = $_SESSION['user'];
$doctorId = isset($user['id']) ? $user['id'] : (isset($_SESSION['doctor_id']) ? $_SESSION['doctor_id'] : '');
$appointments = [];
$errors = [];
$success = '';

// Get doctor ID
try {
    $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user['id']]);
    $doctorId = $stmt->fetchColumn();

    if (!$doctorId) {
        // If doctor record not found, log out user
        session_destroy();
        header("Location: ../index.php");
        exit();
    }

    // Fetch doctor's appointments (placeholder)
    $stmt = $pdo->prepare("SELECT a.*, p.user_id as patient_user_id, pu.first_name as patient_first_name, pu.last_name as patient_last_name FROM appointments a JOIN patients p ON a.patient_id = p.id JOIN users pu ON p.user_id = pu.id WHERE a.doctor_id = ? ORDER BY a.appointment_date DESC");
    $stmt->execute([$doctorId]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errors[] = "Database Error: " . $e->getMessage();
}

// Get doctor's appointments from appointments.txt
if (($handle = fopen('../appointments.txt', 'r')) !== false) {
    while (($data = fgetcsv($handle)) !== false) {
        // [doctor_id, patient_id, appointment_date, appointment_time, reason, status, created_at]
        if (count($data) < 7) continue;
        list($doc_id, $patient_id, $date, $time, $reason, $status, $created_at) = $data;
        if ($doc_id != $doctorId) continue;
        $appointments[] = [
            'doctor_id' => $doc_id,
            'patient_id' => $patient_id,
            'appointment_date' => $date,
            'appointment_time' => $time,
            'reason' => $reason,
            'status' => $status,
            'created_at' => $created_at
        ];
    }
    fclose($handle);
}

// Build a map of user_id => patient_id for all patients
$patientIdMap = [];
try {
    $stmt = $pdo->query("SELECT user_id, id FROM patients");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $patientIdMap[$row['user_id']] = $row['id'];
    }
} catch (PDOException $e) {
    // Ignore errors for now
}

// Separate appointments into upcoming and past (completed)
$upcomingAppointments = [];
$pastAppointments = [];
foreach ($appointments as $appointment) {
    if (strtolower($appointment['status']) === 'completed') {
        $pastAppointments[] = $appointment;
    } else {
        $upcomingAppointments[] = $appointment;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Appointments - Healthcare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <h3 class="text-center mb-4">Healthcare</h3>
                <nav>
                    <a href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="appointments.php" class="active"><i class="fas fa-calendar-check"></i> My Appointments</a>
                    <a href="patients.php"><i class="fas fa-procedures"></i> My Patients</a>
                    <a href="prescriptions.php"><i class="fas fa-prescription"></i> Prescriptions</a>
                    <a href="medical-records.php"><i class="fas fa-file-medical"></i> Medical Records</a>
                    <a href="schedule.php"><i class="fas fa-calendar-alt"></i> My Schedule</a>
                    <a href="../profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="welcome-section">
                    <h2>My Appointments</h2>
                    <p>View and manage your appointments.</p>
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

                <!-- Appointments List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Upcoming Appointments</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcomingAppointments)): ?>
                            <p class="text-center">No upcoming appointments found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Patient ID</th>
                                            <th>Date & Time</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $loop_index = 0; foreach ($upcomingAppointments as $appointment): ?>
                                            <tr>
                                                <td>
                                                    <?php 
                                                    $userId = $appointment['patient_id'];
                                                    echo isset($patientIdMap[$userId]) ? htmlspecialchars($patientIdMap[$userId]) : htmlspecialchars($userId); 
                                                    ?>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])) . ' ' . htmlspecialchars($appointment['appointment_time']); ?></td>
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
                                                    <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this appointment?');">
                                                        <input type="hidden" name="delete_appointment_index" value="<?php echo $loop_index; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                    </form>
                                                    <?php if ($appointment['status'] === 'Pending'): ?>
                                                        <a href="#" class="btn btn-sm btn-success">Confirm</a>
                                                        <button class="btn btn-sm btn-danger" onclick="showDeclineModal('<?php echo $appointment['doctor_id']; ?>','<?php echo $appointment['patient_id']; ?>','<?php echo $appointment['appointment_date']; ?>','<?php echo $appointment['appointment_time']; ?>')">Decline</button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php $loop_index++; endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Past Appointments Table -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Past Appointments</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pastAppointments)): ?>
                            <p class="text-center">No past appointments found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Patient ID</th>
                                            <th>Date & Time</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $past_loop_index = 0; foreach ($pastAppointments as $appointment): ?>
                                            <tr>
                                                <td>
                                                    <?php 
                                                    $userId = $appointment['patient_id'];
                                                    echo isset($patientIdMap[$userId]) ? htmlspecialchars($patientIdMap[$userId]) : htmlspecialchars($userId); 
                                                    ?>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])) . ' ' . htmlspecialchars($appointment['appointment_time']); ?></td>
                                                <td><?php echo htmlspecialchars($appointment['reason'] ?? 'N/A'); ?></td>
                                                <td><span class="badge bg-secondary">Completed</span></td>
                                                <td>
                                                    <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this past appointment from your view?');">
                                                        <input type="hidden" name="delete_past_appointment_index" value="<?php echo $past_loop_index; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php $past_loop_index++; endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="declineModal" tabindex="-1" aria-labelledby="declineModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="POST" id="declineForm">
            <div class="modal-header">
              <h5 class="modal-title" id="declineModalLabel">Decline Appointment</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="decline_doctor_id" id="decline_doctor_id">
              <input type="hidden" name="decline_patient_id" id="decline_patient_id">
              <input type="hidden" name="decline_date" id="decline_date">
              <input type="hidden" name="decline_time" id="decline_time">
              <div class="mb-3">
                <label for="decline_reason" class="form-label">Reason for Declining</label>
                <textarea class="form-control" name="decline_reason" id="decline_reason" rows="3" required></textarea>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-danger">Decline Appointment</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <script>
    function showDeclineModal(doctor_id, patient_id, date, time) {
      document.getElementById('decline_doctor_id').value = doctor_id;
      document.getElementById('decline_patient_id').value = patient_id;
      document.getElementById('decline_date').value = date;
      document.getElementById('decline_time').value = time;
      document.getElementById('decline_reason').value = '';
      var modal = new bootstrap.Modal(document.getElementById('declineModal'));
      modal.show();
    }
    </script>

    <?php
    // Handle decline form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['decline_reason'])) {
        $decline_doctor_id = $_POST['decline_doctor_id'];
        $decline_patient_id = $_POST['decline_patient_id'];
        $decline_date = $_POST['decline_date'];
        $decline_time = $_POST['decline_time'];
        $decline_reason = trim($_POST['decline_reason']);
        // Update appointments.txt
        $lines = file('../appointments.txt');
        foreach ($lines as $i => $line) {
            $data = str_getcsv($line);
            if (count($data) < 7) continue;
            list($doc_id, $pat_id, $date, $time, $reason, $status, $created_at) = $data;
            if ($doc_id == $decline_doctor_id && $pat_id == $decline_patient_id && $date == $decline_date && $time == $decline_time && strtolower($status) == 'pending') {
                $data[5] = 'declined';
                $lines[$i] = implode(',', $data) . "\n";
                break;
            }
        }
        file_put_contents('../appointments.txt', $lines);
        // Send email to patient (placeholder)
        // You would fetch the patient's email from the database or user session here
        // mail($patient_email, 'Appointment Declined', $decline_reason);
        $success = 'Appointment declined and patient notified.';
        // Optionally, reload page to reflect changes
        echo '<script>window.location.reload();</script>';
    }

    // Handle delete appointment request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_appointment_index'])) {
        $deleteIndex = intval($_POST['delete_appointment_index']);
        $lines = file('../appointments.txt');
        if (isset($lines[$deleteIndex])) {
            unset($lines[$deleteIndex]);
            file_put_contents('../appointments.txt', $lines);
        }
        header('Location: appointments.php');
        exit();
    }

    // Add handler for deleting past appointments from the doctor dashboard view only
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_past_appointment_index'])) {
        $deleteIndex = intval($_POST['delete_past_appointment_index']);
        // Remove the corresponding appointment from appointments.txt
        $lines = file('../appointments.txt');
        $completedCount = 0;
        foreach ($lines as $i => $line) {
            $data = str_getcsv($line);
            if (count($data) < 7) continue;
            if (strtolower($data[5]) === 'completed') {
                if ($completedCount === $deleteIndex) {
                    unset($lines[$i]);
                    break;
                }
                $completedCount++;
            }
        }
        file_put_contents('../appointments.txt', $lines);
        header('Location: appointments.php');
        exit();
    }

    // When rendering, skip hidden past appointments
    if (isset($_SESSION['doctor_hidden_past_appointments'])) {
        foreach ($_SESSION['doctor_hidden_past_appointments'] as $hiddenIndex) {
            unset($pastAppointments[$hiddenIndex]);
        }
    }
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../script.js"></script>
</body>
</html> 