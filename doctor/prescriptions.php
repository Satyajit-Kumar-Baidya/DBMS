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
$prescriptions = [];
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

    // Handle writing a new prescription
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['write_prescription'])) {
        $patientId = $_POST['patient_id'] ?? '';
        $medication = $_POST['medication'] ?? '';
        $dosage = $_POST['dosage'] ?? '';
        $instructions = $_POST['instructions'] ?? '';
        $status = 'Active';
        $prescription_date = date('Y-m-d');
        if (!empty($patientId) && !empty($medication)) {
            $line = implode(",", [
                $patientId,
                $doctorId,
                str_replace(["\n", "\r", ","], [" ", " ", ";"], $medication),
                str_replace(["\n", "\r", ","], [" ", " ", ";"], $dosage),
                str_replace(["\n", "\r", ","], [" ", " ", ";"], $instructions),
                $status,
                $prescription_date
            ]) . "\n";
            file_put_contents('../prescriptions.txt', $line, FILE_APPEND);
            // Mark the earliest pending appointment as completed for this doctor and patient
            $stmt = $pdo->prepare("SELECT user_id FROM patients WHERE id = ?");
            $stmt->execute([$patientId]);
            $userId = $stmt->fetchColumn();
            $lines = file('../appointments.txt');
            foreach ($lines as $i => $line) {
                $data = str_getcsv($line);
                if (count($data) < 7) continue;
                list($doc_id, $pat_user_id, $date, $time, $reason, $apt_status, $created_at) = $data;
                if ($doc_id == $doctorId && $pat_user_id == $userId && strtolower($apt_status) !== 'completed') {
                    $data[5] = 'completed';
                    $lines[$i] = implode(',', $data) . "\n";
                    break;
                }
            }
            file_put_contents('../appointments.txt', $lines);
            $success = 'Prescription written successfully!';
            $_POST = [];
        } else {
            $errors[] = 'Patient and medication are required.';
        }
    }

    // Handle delete prescription request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_prescription_index'])) {
        $deleteIndex = intval($_POST['delete_prescription_index']);
        $lines = file('../prescriptions.txt');
        if (isset($lines[$deleteIndex])) {
            unset($lines[$deleteIndex]);
            file_put_contents('../prescriptions.txt', $lines);
        }
        header('Location: prescriptions.php');
        exit();
    }

    // Fetch doctor's prescriptions from prescriptions.txt
    $prescriptions = [];
    if (($handle = fopen('../prescriptions.txt', 'r')) !== false) {
        while (($data = fgetcsv($handle)) !== false) {
            // [patient_id, doctor_id, medication, dosage, instructions, status, prescription_date]
            if (count($data) < 7) continue;
            list($patient_id, $doc_id, $medication, $dosage, $instructions, $status, $prescription_date) = $data;
            if ($doc_id != $doctorId) continue;
            $prescriptions[] = [
                'patient_id' => $patient_id,
                'doctor_id' => $doc_id,
                'medication' => $medication,
                'dosage' => $dosage,
                'instructions' => $instructions,
                'status' => $status,
                'prescription_date' => $prescription_date
            ];
        }
        fclose($handle);
    }

    // Build a set of user_ids for patients who have had appointments with this doctor (from appointments.txt)
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
    // Map user_ids to patient_ids
    $patients = [];
    if (!empty($appointmentPatientUserIds)) {
        $userIds = array_keys($appointmentPatientUserIds);
        $in = str_repeat('?,', count($userIds) - 1) . '?';
        $stmt = $pdo->prepare("SELECT p.id as patient_id, u.first_name, u.last_name FROM patients p JOIN users u ON p.user_id = u.id WHERE p.user_id IN ($in)");
        $stmt->execute($userIds);
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Build a map of patient_id => user_id for patients who have had appointments with this doctor
    $patientIdToUserId = [];
    if (!empty($patients)) {
        foreach ($patients as $patient) {
            $patientIdToUserId[$patient['patient_id']] = null; // will fill below
        }
        // Get user_ids for these patient_ids
        $in = str_repeat('?,', count($patientIdToUserId) - 1) . '?';
        $stmt = $pdo->prepare("SELECT id, user_id FROM patients WHERE id IN ($in)");
        $stmt->execute(array_keys($patientIdToUserId));
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $patientIdToUserId[$row['id']] = $row['user_id'];
        }
    }

} catch (PDOException $e) {
    $errors[] = "Database Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Prescriptions - Healthcare System</title>
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
                    <a href="patients.php"><i class="fas fa-procedures"></i> My Patients</a>
                    <a href="prescriptions.php" class="active"><i class="fas fa-prescription"></i> Prescriptions</a>
                    <a href="medical-records.php"><i class="fas fa-file-medical"></i> Medical Records</a>
                    <a href="schedule.php"><i class="fas fa-calendar-alt"></i> My Schedule</a>
                    <a href="../profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="welcome-section">
                    <h2>Prescriptions</h2>
                    <p>View and write prescriptions.</p>
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

                <!-- Write New Prescription Form -->
                 <?php if (isset($_GET['action']) && $_GET['action'] === 'write'): ?>
                    <?php
                    // Show appointment history for selected patient
                    $selectedPatientId = $_POST['patient_id'] ?? $_GET['patient_id'] ?? '';
                    if (!empty($selectedPatientId)) {
                        // Map patient_id to user_id
                        $stmt = $pdo->prepare("SELECT user_id FROM patients WHERE id = ?");
                        $stmt->execute([$selectedPatientId]);
                        $userId = $stmt->fetchColumn();
                        $appointments = [];
                        if (($handle = fopen('../appointments.txt', 'r')) !== false) {
                            while (($data = fgetcsv($handle)) !== false) {
                                if (count($data) < 7) continue;
                                list($doc_id, $pat_user_id, $date, $time, $reason, $status, $created_at) = $data;
                                if ($doc_id == $doctorId && $pat_user_id == $userId) {
                                    $appointments[] = [
                                        'date' => $date,
                                        'time' => $time,
                                        'reason' => $reason,
                                        'status' => $status
                                    ];
                                }
                            }
                            fclose($handle);
                        }
                        if (!empty($appointments)) {
                            echo '<h5>Appointment History for this Patient:</h5>';
                            echo '<table class="table table-sm"><thead><tr><th>Date</th><th>Time</th><th>Reason</th><th>Status</th></tr></thead><tbody>';
                            foreach ($appointments as $apt) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($apt['date']) . '</td>';
                                echo '<td>' . htmlspecialchars($apt['time']) . '</td>';
                                echo '<td>' . htmlspecialchars($apt['reason']) . '</td>';
                                echo '<td>' . htmlspecialchars($apt['status']) . '</td>';
                                echo '</tr>';
                            }
                            echo '</tbody></table>';
                        }
                    }
                    ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Write New Prescription</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="prescriptions.php?action=write">
                                <input type="hidden" name="write_prescription" value="1">
                                <?php
                                // Debug information
                                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                                    echo '<div class="alert alert-info">';
                                    echo 'POST Data: ';
                                    print_r($_POST);
                                    echo '</div>';
                                }
                                ?>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="patient_id_search" class="form-label">Search Patient by ID</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="patient_id_search" placeholder="Enter Patient ID">
                                            <button type="button" class="btn btn-primary" onclick="searchPatient()">Search</button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="patient_id" class="form-label">Select Patient</label>
                                        <select class="form-select" id="patient_id" name="patient_id" required>
                                            <option value="">-- Select a Patient --</option>
                                            <?php foreach ($patients as $patient): ?>
                                                <option value="<?php echo htmlspecialchars($patient['patient_id']); ?>" 
                                                        data-id="<?php echo htmlspecialchars($patient['patient_id']); ?>">
                                                    <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?> 
                                                    (ID: <?php echo htmlspecialchars($patient['patient_id']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="medication" class="form-label">Medication</label>
                                    <input type="text" class="form-control" id="medication" name="medication" required>
                                </div>
                                <div class="mb-3">
                                    <label for="dosage" class="form-label">Dosage</label>
                                    <input type="text" class="form-control" id="dosage" name="dosage" required placeholder="e.g., 500mg twice daily">
                                </div>
                                <div class="mb-3">
                                    <label for="instructions" class="form-label">Instructions</label>
                                    <textarea class="form-control" id="instructions" name="instructions" rows="3" placeholder="Special instructions for the patient"></textarea>
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">Save Prescription</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Prescriptions List -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">My Prescriptions</h5>
                         <a href="prescriptions.php?action=write" class="btn btn-primary btn-sm">
                             <i class="fas fa-plus"></i> Write New Prescription
                         </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($prescriptions)): ?>
                            <p class="text-center">No prescriptions found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Patient</th>
                                            <th>Medication</th>
                                            <th>Dosage</th>
                                            <th>Instructions</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $loop_index = 0; foreach ($prescriptions as $prescription): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($prescription['patient_id']); ?></td>
                                                <td><?php echo nl2br(htmlspecialchars($prescription['medication'])); ?></td>
                                                <td><?php echo nl2br(htmlspecialchars($prescription['dosage'])); ?></td>
                                                <td><?php echo nl2br(htmlspecialchars($prescription['instructions'] ?? 'N/A')); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $prescription['status'] === 'Active' ? 'success' : ($prescription['status'] === 'Completed' ? 'secondary' : 'danger'); ?>">
                                                        <?php echo htmlspecialchars($prescription['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="#" class="btn btn-sm btn-primary">View Details</a>
                                                    <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this prescription?');">
                                                        <input type="hidden" name="delete_prescription_index" value="<?php echo $loop_index; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php $loop_index++; endforeach; ?>
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
    <script>
    function searchPatient() {
        const searchId = document.getElementById('patient_id_search').value;
        const select = document.getElementById('patient_id');
        const options = select.options;
        let found = false;
        for (let i = 0; i < options.length; i++) {
            const optionId = options[i].getAttribute('data-id');
            if (optionId === searchId) {
                select.selectedIndex = i;
                found = true;
                break;
            }
        }
        // Also check if searchId matches a user_id
        if (!found) {
            <?php
            // Output a JS object mapping patient_id to user_id
            echo 'const patientIdToUserId = ' . json_encode($patientIdToUserId) . ";\n";
            ?>
            for (let i = 0; i < options.length; i++) {
                const patientId = options[i].getAttribute('data-id');
                if (patientIdToUserId[patientId] && patientIdToUserId[patientId].toString() === searchId) {
                    select.selectedIndex = i;
                    found = true;
                    break;
                }
            }
        }
        if (!found) {
            alert('Patient not found with ID: ' + searchId + '. You can only write prescriptions for patients who have had appointments with you.');
        }
    }
    </script>
</body>
</html> 