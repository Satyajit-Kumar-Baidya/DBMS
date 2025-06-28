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
$patients = [];
$errors = [];

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

    // Instead of using only the appointments table, build the patient list from appointments.txt
    $appointmentPatientUserIds = [];
    if (($handle = fopen('../appointments.txt', 'r')) !== false) {
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < 7) continue;
            list($doc_id, $user_id, $date, $time, $reason, $status, $created_at) = $data;
            if ($doc_id == $doctorId) {
                $appointmentPatientUserIds[$user_id] = true;
            }
        }
        fclose($handle);
    }
    $patients = [];
    if (!empty($appointmentPatientUserIds)) {
        $userIds = array_keys($appointmentPatientUserIds);
        $in = str_repeat('?,', count($userIds) - 1) . '?';
        $stmt = $pdo->prepare("SELECT p.id as patient_id, u.first_name, u.last_name, u.email, p.date_of_birth, p.gender FROM patients p JOIN users u ON p.user_id = u.id WHERE p.user_id IN ($in)");
        $stmt->execute($userIds);
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Build a set of patient_ids who have received prescriptions from this doctor
    $prescribedPatientIds = [];
    if (($handle = fopen('../prescriptions.txt', 'r')) !== false) {
        while (($data = fgetcsv($handle)) !== false) {
            // [patient_id, doctor_id, ...]
            if (count($data) < 2) continue;
            list($presc_patient_id, $presc_doctor_id) = $data;
            if ($presc_doctor_id == $doctorId) {
                $prescribedPatientIds[$presc_patient_id] = true;
            }
        }
        fclose($handle);
    }

} catch (PDOException $e) {
    $errors[] = "Database Error: " . $e->getMessage();
}

// Only show patients who have received a prescription from this doctor
$filteredPatients = [];
if (!empty($prescribedPatientIds)) {
    $prescribedIds = array_keys($prescribedPatientIds);
    $in = str_repeat('?,', count($prescribedIds) - 1) . '?';
    $stmt = $pdo->prepare("SELECT p.id as patient_id, u.first_name, u.last_name, u.email, p.date_of_birth, p.gender FROM patients p JOIN users u ON p.user_id = u.id WHERE p.id IN ($in)");
    $stmt->execute($prescribedIds);
    $filteredPatients = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle delete prescription request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_patient_id'])) {
    $deletePatientId = $_POST['delete_patient_id'];
    // Get user_id for this patient
    $stmt = $pdo->prepare("SELECT user_id FROM patients WHERE id = ?");
    $stmt->execute([$deletePatientId]);
    $userId = $stmt->fetchColumn();
    // Delete prescriptions for this patient by this doctor
    $lines = file('../prescriptions.txt');
    $newLines = [];
    foreach ($lines as $line) {
        $data = str_getcsv($line);
        if (count($data) < 2) continue;
        list($presc_patient_id, $presc_doctor_id) = $data;
        if (!($presc_patient_id == $deletePatientId && $presc_doctor_id == $doctorId)) {
            $newLines[] = $line;
        }
    }
    file_put_contents('../prescriptions.txt', $newLines);
    // Optionally, remove from appointments.txt as well
    $lines = file('../appointments.txt');
    $newLines = [];
    foreach ($lines as $line) {
        $data = str_getcsv($line);
        if (count($data) < 2) continue;
        list($doc_id, $pat_user_id) = $data;
        if (!($doc_id == $doctorId && $pat_user_id == $userId)) {
            $newLines[] = $line;
        }
    }
    file_put_contents('../appointments.txt', $newLines);
    // Refresh page
    header('Location: patients.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Patients - Healthcare System</title>
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
                    <a href="appointments.php"><i class="fas fa-calendar-check"></i> My Appointments</a>
                    <a href="patients.php" class="active"><i class="fas fa-procedures"></i> My Patients</a>
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
                    <h2>My Patients</h2>
                    <p>View the list of patients you have attended to.</p>
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

                <!-- Patients List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">My Patients List</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($filteredPatients)): ?>
                            <p class="text-center">No patients found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Patient ID</th>
                                            <th>Email</th>
                                            <th>Date of Birth</th>
                                            <th>Gender</th>
                                            <th>Prescription Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($filteredPatients as $patient): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['patient_id']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['email'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($patient['date_of_birth'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($patient['gender'] ?? 'N/A'); ?></td>
                                                <td><span class="badge bg-success">Done</span></td>
                                                <td>
                                                    <a href="prescriptions.php?patient_id=<?php echo $patient['patient_id']; ?>" class="btn btn-sm btn-primary">View Prescription</a>
                                                    <a href="medical-records.php?patient_id=<?php echo $patient['patient_id']; ?>" class="btn btn-sm btn-info">View Medical History</a>
                                                    <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this patient\'s prescriptions and appointments?');">
                                                        <input type="hidden" name="delete_patient_id" value="<?php echo $patient['patient_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                    </form>
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
</body>
</html> 