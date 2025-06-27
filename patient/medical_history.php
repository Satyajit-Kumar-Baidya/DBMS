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
$medicalHistory = [];
$errors = [];

// Get patient ID
try {
    $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user['id']]);
    $patientId = $stmt->fetchColumn();

    if (!$patientId) {
        session_destroy();
        header("Location: ../index.php");
        exit();
    }

    // Get filter parameters
    $startDate = $_GET['start_date'] ?? '';
    $endDate = $_GET['end_date'] ?? '';
    $doctorId = $_GET['doctor_id'] ?? '';
    $testType = $_GET['test_type'] ?? '';

    // Build query
    $query = "SELECT mh.*, 
                     d.specialization,
                     u.first_name as doctor_first_name, 
                     u.last_name as doctor_last_name,
                     p.medication_details,
                     tr.test_type,
                     tr.result_file_path
              FROM medical_history mh
              JOIN doctors d ON mh.doctor_id = d.id
              JOIN users u ON d.user_id = u.id
              LEFT JOIN prescriptions p ON mh.prescription_id = p.id
              LEFT JOIN test_results tr ON mh.test_result_id = tr.id
              WHERE mh.patient_id = ?";

    $params = [$patientId];

    if ($startDate) {
        $query .= " AND mh.consultation_date >= ?";
        $params[] = $startDate;
    }
    if ($endDate) {
        $query .= " AND mh.consultation_date <= ?";
        $params[] = $endDate;
    }
    if ($doctorId) {
        $query .= " AND mh.doctor_id = ?";
        $params[] = $doctorId;
    }
    if ($testType) {
        $query .= " AND tr.test_type = ?";
        $params[] = $testType;
    }

    $query .= " ORDER BY mh.consultation_date DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $medicalHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get list of doctors for filter
    $stmt = $pdo->query("SELECT d.id, u.first_name, u.last_name FROM doctors d JOIN users u ON d.user_id = u.id ORDER BY u.last_name");
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get list of test types for filter
    $stmt = $pdo->query("SELECT DISTINCT test_type FROM test_results ORDER BY test_type");
    $testTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    $errors[] = "Database Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical History - Healthcare System</title>
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
                    <a href="medical_history.php" class="active"><i class="fas fa-history"></i> Medical History</a>
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
                    <h2>Medical History</h2>
                    <p>View your complete medical history, including prescriptions, treatments, and test results.</p>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Filter Records</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="doctor_id" class="form-label">Doctor</label>
                                <select class="form-select" id="doctor_id" name="doctor_id">
                                    <option value="">All Doctors</option>
                                    <?php foreach ($doctors as $doctor): ?>
                                        <option value="<?php echo $doctor['id']; ?>" <?php echo $doctorId == $doctor['id'] ? 'selected' : ''; ?>>
                                            Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="test_type" class="form-label">Test Type</label>
                                <select class="form-select" id="test_type" name="test_type">
                                    <option value="">All Tests</option>
                                    <?php foreach ($testTypes as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $testType == $type ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <a href="medical_history.php" class="btn btn-secondary">Clear Filters</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Medical History Records -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Medical Records</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($medicalHistory)): ?>
                            <p class="text-center">No medical records found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Doctor</th>
                                            <th>Specialization</th>
                                            <th>Diagnosis</th>
                                            <th>Treatment</th>
                                            <th>Test Results</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($medicalHistory as $record): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($record['consultation_date'])); ?></td>
                                                <td>Dr. <?php echo htmlspecialchars($record['doctor_first_name'] . ' ' . $record['doctor_last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($record['specialization']); ?></td>
                                                <td><?php echo htmlspecialchars($record['diagnosis'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($record['treatment_notes'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php if ($record['test_type']): ?>
                                                        <?php echo htmlspecialchars($record['test_type']); ?>
                                                    <?php else: ?>
                                                        N/A
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($record['prescription_id']): ?>
                                                        <a href="download_prescription.php?id=<?php echo $record['prescription_id']; ?>" class="btn btn-sm btn-info">
                                                            <i class="fas fa-download"></i> Prescription
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($record['result_file_path']): ?>
                                                        <a href="download_test_result.php?id=<?php echo $record['test_result_id']; ?>" class="btn btn-sm btn-success">
                                                            <i class="fas fa-download"></i> Test Result
                                                        </a>
                                                    <?php endif; ?>
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