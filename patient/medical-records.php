<?php
session_start();
require_once '../dbConnect.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    header("Location: ../index.php");
    exit();
}

$user = $_SESSION['user'];
$patientId = null;

// Get patient ID
try {
    $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $patientId = $stmt->fetchColumn();

    if (!$patientId) {
        // If patient record not found, log out user
        session_destroy();
        header("Location: ../index.php");
        exit();
    }
} catch (PDOException $e) {
    // If database error, log out user
    session_destroy();
    header("Location: ../index.php");
    exit();
}

// Fetch patient's medical records from medical_history
$medicalRecords = [];
try {
    $stmt = $pdo->prepare("SELECT mh.*, d.id as doctor_id, u.first_name as doctor_first_name, u.last_name as doctor_last_name FROM medical_history mh JOIN doctors d ON mh.doctor_id = d.id JOIN users u ON d.user_id = u.id WHERE mh.patient_id = ? ORDER BY mh.visit_date DESC");
    $stmt->execute([$patientId]);
    $medicalRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}

// Fetch patient's prescriptions from prescriptions.txt
$prescriptions = [];
if (($handle = fopen('../prescriptions.txt', 'r')) !== false) {
    while (($data = fgetcsv($handle)) !== false) {
        if (count($data) < 7) continue;
        list($pid, $doctor_id, $medication, $dosage, $instructions, $status, $prescription_date) = $data;
        if ($pid != $patientId) continue;
        $prescriptions[] = [
            'doctor_id' => $doctor_id,
            'medication' => $medication,
            'dosage' => $dosage,
            'instructions' => $instructions,
            'status' => $status,
            'prescription_date' => $prescription_date
        ];
    }
    fclose($handle);
}
// Build a map of doctor_id => doctor name
$doctorNames = [];
if (!empty($prescriptions)) {
    $doctorIds = array_unique(array_map(function($p) { return $p['doctor_id']; }, $prescriptions));
    if (!empty($doctorIds)) {
        $in = str_repeat('?,', count($doctorIds) - 1) . '?';
        $stmt = $pdo->prepare("SELECT d.id, u.first_name, u.last_name FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.id IN ($in)");
        $stmt->execute($doctorIds);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $doctorNames[$row['id']] = $row['first_name'] . ' ' . $row['last_name'];
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records - Healthcare System</title>
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
                    <a href="medical-records.php" class="active"><i class="fas fa-file-medical"></i> Medical Records</a>
                    <a href="prescriptions.php"><i class="fas fa-prescription"></i> Prescriptions</a>
                    <a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a>
                    <a href="health-log.php"><i class="fas fa-heartbeat"></i> Health Log</a>
                    <a href="../profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="welcome-section">
                    <h2>Medical Records</h2>
                    <p>View your medical records.</p>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Medical Records List</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($medicalRecords)): ?>
                                    <div class="alert alert-info">No medical records found.</div>
                                <?php else: ?>
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Doctor</th>
                                                <th>Diagnosis</th>
                                                <th>Treatment</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($medicalRecords as $record): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($record['visit_date']); ?></td>
                                                    <td>Dr. <?php echo htmlspecialchars($record['doctor_first_name'] . ' ' . $record['doctor_last_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($record['diagnosis']); ?></td>
                                                    <td><?php echo htmlspecialchars($record['treatment'] ?? $record['treatment_notes'] ?? ''); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($prescriptions)): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Prescriptions History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Doctor</th>
                                        <th>Medication</th>
                                        <th>Dosage</th>
                                        <th>Instructions</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($prescriptions as $presc): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($presc['prescription_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($doctorNames[$presc['doctor_id']] ?? $presc['doctor_id']); ?></td>
                                            <td><?php echo htmlspecialchars($presc['medication']); ?></td>
                                            <td><?php echo htmlspecialchars($presc['dosage']); ?></td>
                                            <td><?php echo nl2br(htmlspecialchars($presc['instructions'] ?? 'N/A')); ?></td>
                                            <td><span class="badge bg-<?php echo $presc['status'] === 'Active' ? 'success' : 'secondary'; ?>"><?php echo htmlspecialchars($presc['status']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../script.js"></script>
</body>
</html> 