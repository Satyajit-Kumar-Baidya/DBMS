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
$medicalRecords = [];
$patients = [];
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

    // Handle adding a new medical record
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_medical_record'])) {
        $patientId = $_POST['patient_id'] ?? '';
        $visitDate = $_POST['consultation_date'] ?? '';
        $diagnosis = $_POST['diagnosis'] ?? '';
        $treatmentNotes = $_POST['treatment_notes'] ?? '';

        // Basic validation
        if (empty($patientId)) $errors[] = 'Patient is required.';
        if (empty($visitDate)) $errors[] = 'Consultation date is required.';
        if (empty($diagnosis)) $errors[] = 'Diagnosis is required.';

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO medical_history (patient_id, doctor_id, visit_date, diagnosis, treatment_notes) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$patientId, $doctorId, $visitDate, $diagnosis, $treatmentNotes]);

                $success = 'Medical record added successfully!';
                // Clear form fields after successful submission (optional)
                $_POST = []; 

            } catch (PDOException $e) {
                $errors[] = "Database Error: " . $e->getMessage();
            }
        }
    }

    // Add patient search by ID for adding a new record
    $searchedPatient = null;
    if (isset($_POST['search_patient_id'])) {
        $searchId = trim($_POST['search_patient_id']);
        if ($searchId !== '') {
            $stmt = $pdo->prepare("SELECT p.id, u.first_name, u.last_name FROM patients p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
            $stmt->execute([$searchId]);
            $searchedPatient = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$searchedPatient) {
                $errors[] = 'No patient found with that ID.';
            }
        }
    }

    // Fetch medical records associated with this doctor (placeholder)
    $stmt = $pdo->prepare("SELECT mh.*, u.first_name as patient_first_name, u.last_name as patient_last_name 
                          FROM medical_history mh 
                          JOIN patients p ON mh.patient_id = p.id 
                          JOIN users u ON p.user_id = u.id 
                          WHERE mh.doctor_id = ? ORDER BY mh.visit_date DESC");
    $stmt->execute([$doctorId]);
    $medicalRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch list of patients for the medical record form (patients associated with this doctor)
     $stmtPatients = $pdo->prepare("SELECT DISTINCT p.id as patient_id, u.first_name, u.last_name 
                          FROM patients p
                          JOIN users u ON p.user_id = u.id
                          JOIN appointments a ON p.id = a.patient_id WHERE a.doctor_id = ?
                          UNION
                          SELECT DISTINCT p.id as patient_id, u.first_name, u.last_name 
                          FROM patients p
                          JOIN users u ON p.user_id = u.id
                          JOIN medical_history mh ON p.id = mh.patient_id WHERE mh.doctor_id = ?");
    $stmtPatients->execute([$doctorId, $doctorId]);
    $patients = $stmtPatients->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all prescriptions written by this doctor for all patients
    $allPrescriptions = [];
    if (($handle = fopen('../prescriptions.txt', 'r')) !== false) {
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < 7) continue;
            list($pid, $doc_id, $medication, $dosage, $instructions, $status, $prescription_date) = $data;
            if ($doc_id != $doctorId) continue;
            $allPrescriptions[$pid][] = [
                'medication' => $medication,
                'dosage' => $dosage,
                'instructions' => $instructions,
                'status' => $status,
                'prescription_date' => $prescription_date
            ];
        }
        fclose($handle);
    }

    // Handle deleting a medical record
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_medical_record_id'])) {
        $deleteId = intval($_POST['delete_medical_record_id']);
        try {
            $stmt = $pdo->prepare("DELETE FROM medical_history WHERE id = ? AND doctor_id = ?");
            $stmt->execute([$deleteId, $doctorId]);
            $success = 'Medical record deleted successfully!';
            // Redirect to avoid resubmission
            header('Location: medical-records.php');
            exit();
        } catch (PDOException $e) {
            $errors[] = "Database Error: " . $e->getMessage();
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
    <title>Doctor Medical Records - Healthcare System</title>
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
                    <a href="prescriptions.php"><i class="fas fa-prescription"></i> Prescriptions</a>
                    <a href="medical-records.php" class="active"><i class="fas fa-file-medical"></i> Medical Records</a>
                    <a href="schedule.php"><i class="fas fa-calendar-alt"></i> My Schedule</a>
                    <a href="../profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="welcome-section">
                    <h2>Medical Records</h2>
                    <p>View and add medical records for your patients.</p>
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

                 <!-- Add New Medical Record Form -->
                 <?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Add New Medical Record</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="medical-records.php?action=add">
                                <div class="mb-3 row align-items-end">
                                    <div class="col-md-6">
                                        <label for="search_patient_id" class="form-label">Enter Patient ID</label>
                                        <input type="number" class="form-control" id="search_patient_id" name="search_patient_id" value="<?php echo htmlspecialchars($_POST['search_patient_id'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-info" name="search_patient">Search</button>
                                    </div>
                                </div>
                            </form>
                            <?php if ($searchedPatient): ?>
                            <form method="POST" action="medical-records.php?action=add">
                                <input type="hidden" name="add_medical_record" value="1">
                                <input type="hidden" name="patient_id" value="<?php echo $searchedPatient['id']; ?>">
                                <div class="mb-3">
                                    <label class="form-label">Patient Name</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($searchedPatient['first_name'] . ' ' . $searchedPatient['last_name']); ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label for="consultation_date" class="form-label">Consultation Date</label>
                                    <input type="datetime-local" class="form-control" id="consultation_date" name="consultation_date" required>
                                </div>
                                <div class="mb-3">
                                    <label for="diagnosis" class="form-label">Diagnosis</label>
                                    <textarea class="form-control" id="diagnosis" name="diagnosis" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="treatment_notes" class="form-label">Treatment Notes</label>
                                    <textarea class="form-control" id="treatment_notes" name="treatment_notes" rows="3"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary mt-3">Submit Medical Record</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Medical Records List -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Medical Records</h5>
                        <a href="medical-records.php?action=add" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add New Record
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($medicalRecords)): ?>
                            <p class="text-center">No medical records found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Patient</th>
                                            <th>Date</th>
                                            <th>Diagnosis</th>
                                            <th>Treatment Notes</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $loop_index = 0; foreach ($medicalRecords as $record): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($record['patient_first_name'] . ' ' . $record['patient_last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($record['visit_date']); ?></td>
                                                <td><?php echo htmlspecialchars($record['diagnosis']); ?></td>
                                                <td><?php echo nl2br(htmlspecialchars($record['treatment_notes'] ?? 'N/A')); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#viewMedicalRecordModal<?php echo $loop_index; ?>">View Details</button>
                                                    <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this medical record?');">
                                                        <input type="hidden" name="delete_medical_record_id" value="<?php echo $record['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <!-- Modal for viewing medical record details and prescriptions -->
                                            <div class="modal fade" id="viewMedicalRecordModal<?php echo $loop_index; ?>" tabindex="-1" aria-labelledby="viewMedicalRecordModalLabel<?php echo $loop_index; ?>" aria-hidden="true">
                                              <div class="modal-dialog">
                                                <div class="modal-content">
                                                  <div class="modal-header">
                                                    <h5 class="modal-title" id="viewMedicalRecordModalLabel<?php echo $loop_index; ?>">Medical Record Details</h5>
                                                  </div>
                                                  <div class="modal-body">
                                                    <p><strong>Patient:</strong> <?php echo htmlspecialchars($record['patient_first_name'] . ' ' . $record['patient_last_name']); ?></p>
                                                    <p><strong>Date:</strong> <?php echo htmlspecialchars($record['visit_date']); ?></p>
                                                    <p><strong>Diagnosis:</strong> <?php echo htmlspecialchars($record['diagnosis']); ?></p>
                                                    <p><strong>Treatment Notes:</strong> <?php echo nl2br(htmlspecialchars($record['treatment_notes'] ?? 'N/A')); ?></p>
                                                    <?php $prescriptions = $allPrescriptions[$record['patient_id']] ?? []; ?>
                                                    <?php if (!empty($prescriptions)): ?>
                                                    <hr>
                                                    <h6>Prescriptions Given</h6>
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>Date</th>
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
                                                                <td><?php echo htmlspecialchars($presc['medication']); ?></td>
                                                                <td><?php echo htmlspecialchars($presc['dosage']); ?></td>
                                                                <td><?php echo nl2br(htmlspecialchars($presc['instructions'] ?? 'N/A')); ?></td>
                                                                <td><span class="badge bg-<?php echo $presc['status'] === 'Active' ? 'success' : 'secondary'; ?>"><?php echo htmlspecialchars($presc['status']); ?></span></td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                    <?php endif; ?>
                                                  </div>
                                                </div>
                                              </div>
                                            </div>
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
</body>
</html> 