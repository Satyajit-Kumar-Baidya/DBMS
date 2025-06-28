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

// Use a separate file for patient-side prescription deletions
$patientPrescriptionsFile = '../prescriptions_patient.txt';
$allPrescriptions = [];
if (($handle = fopen('../prescriptions.txt', 'r')) !== false) {
    while (($data = fgetcsv($handle)) !== false) {
        if (count($data) < 7) continue;
        list($pid, $doctor_id, $medication, $dosage, $instructions, $status, $prescription_date) = $data;
        if ($pid == $patientId) {
            $allPrescriptions[] = [
                'patient_id' => $pid,
                'doctor_id' => $doctor_id,
                'medication' => $medication,
                'dosage' => $dosage,
                'instructions' => $instructions,
                'status' => $status,
                'prescription_date' => $prescription_date
            ];
        }
    }
    fclose($handle);
}

// Handle delete prescription request (patient side only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_prescription_index'])) {
    $deleteIndex = intval($_POST['delete_prescription_index']);
    $lines = file_exists($patientPrescriptionsFile) ? file($patientPrescriptionsFile) : file('../prescriptions.txt');
    $patientLines = [];
    foreach ($lines as $i => $line) {
        $data = str_getcsv($line);
        if (count($data) < 7) continue;
        if ($data[0] == $patientId) {
            if ($i == $deleteIndex) continue; // skip this one
        }
        $patientLines[] = $line;
    }
    file_put_contents($patientPrescriptionsFile, $patientLines);
    header('Location: prescriptions.php');
    exit();
}

// Build a map of doctor_id => doctor name
$doctorNames = [];
if (!empty($allPrescriptions)) {
    $doctorIds = array_unique(array_map(function($presc) { return $presc['doctor_id']; }, $allPrescriptions));
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
    <title>Prescriptions - Healthcare System</title>
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
                    <a href="medical-records.php"><i class="fas fa-file-medical"></i> Medical Records</a>
                    <a href="prescriptions.php" class="active"><i class="fas fa-prescription"></i> Prescriptions</a>
                    <a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a>
                    <a href="health-log.php"><i class="fas fa-heartbeat"></i> Health Log</a>
                    <a href="../profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="welcome-section">
                    <h2>Prescriptions</h2>
                    <p>View your prescriptions.</p>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Prescriptions List</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($allPrescriptions)): ?>
                                    <div class="alert alert-info">No prescriptions found.</div>
                                <?php else: ?>
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Doctor</th>
                                                <th>Medication</th>
                                                <th>Dosage</th>
                                                <th>Instructions</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $loop_index = 0; foreach ($allPrescriptions as $prescription): ?>
                                                <tr>
                                                    <td><?php echo date('M d, Y', strtotime($prescription['prescription_date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($doctorNames[$prescription['doctor_id']] ?? $prescription['doctor_id']); ?></td>
                                                    <td><?php echo htmlspecialchars($prescription['medication']); ?></td>
                                                    <td><?php echo htmlspecialchars($prescription['dosage']); ?></td>
                                                    <td><?php echo nl2br(htmlspecialchars($prescription['instructions'] ?? 'N/A')); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $prescription['status'] === 'Active' ? 'success' : 'secondary'; ?>">
                                                            <?php echo htmlspecialchars($prescription['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#viewPrescriptionModal<?php echo $loop_index; ?>">
                                                            <i class="fas fa-eye"></i> View
                                                        </button>
                                                    </td>
                                                </tr>
                                                <!-- Modal for viewing prescription details -->
                                                <div class="modal fade" id="viewPrescriptionModal<?php echo $loop_index; ?>" tabindex="-1" aria-labelledby="viewPrescriptionModalLabel<?php echo $loop_index; ?>" aria-hidden="true">
                                                  <div class="modal-dialog">
                                                    <div class="modal-content">
                                                      <div class="modal-header">
                                                        <h5 class="modal-title" id="viewPrescriptionModalLabel<?php echo $loop_index; ?>">Prescription Details</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                      </div>
                                                      <div class="modal-body">
                                                        <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($prescription['prescription_date'])); ?></p>
                                                        <p><strong>Doctor:</strong> <?php echo htmlspecialchars($doctorNames[$prescription['doctor_id']] ?? $prescription['doctor_id']); ?></p>
                                                        <p><strong>Medication:</strong> <?php echo htmlspecialchars($prescription['medication']); ?></p>
                                                        <p><strong>Dosage:</strong> <?php echo htmlspecialchars($prescription['dosage']); ?></p>
                                                        <p><strong>Instructions:</strong> <?php echo nl2br(htmlspecialchars($prescription['instructions'] ?? 'N/A')); ?></p>
                                                        <p><strong>Status:</strong> <span class="badge bg-<?php echo $prescription['status'] === 'Active' ? 'success' : 'secondary'; ?>"><?php echo htmlspecialchars($prescription['status']); ?></span></p>
                                                      </div>
                                                      <div class="modal-footer">
                                                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this prescription?');">
                                                          <input type="hidden" name="delete_prescription_index" value="<?php echo $loop_index; ?>">
                                                          <button type="submit" class="btn btn-danger">Delete</button>
                                                        </form>
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                      </div>
                                                    </div>
                                                  </div>
                                                </div>
                                            <?php $loop_index++; endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../script.js"></script>
</body>
</html> 