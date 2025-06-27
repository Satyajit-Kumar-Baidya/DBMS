<?php
session_start();
require_once 'dbConnect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $stmt = $pdo->prepare("INSERT INTO prescriptions (patient_id, doctor_id, medication_name, dosage, frequency, duration, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['patient_id'],
                    $_POST['doctor_id'],
                    $_POST['medication_name'],
                    $_POST['dosage'],
                    $_POST['frequency'],
                    $_POST['duration'],
                    $_POST['notes']
                ]);
                break;

            case 'edit':
                $stmt = $pdo->prepare("UPDATE prescriptions SET patient_id = ?, doctor_id = ?, medication_name = ?, dosage = ?, frequency = ?, duration = ?, notes = ? WHERE prescription_id = ?");
                $stmt->execute([
                    $_POST['patient_id'],
                    $_POST['doctor_id'],
                    $_POST['medication_name'],
                    $_POST['dosage'],
                    $_POST['frequency'],
                    $_POST['duration'],
                    $_POST['notes'],
                    $_POST['prescription_id']
                ]);
                break;

            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM prescriptions WHERE prescription_id = ?");
                $stmt->execute([$_POST['prescription_id']]);
                break;
        }
        header("Location: prescriptions.php");
        exit();
    }
}

// Fetch all prescriptions with patient and doctor details
$stmt = $pdo->query("
    SELECT p.*, 
           pt.first_name as patient_first_name, pt.last_name as patient_last_name,
           d.first_name as doctor_first_name, d.last_name as doctor_last_name
    FROM prescriptions p
    JOIN patients pt ON p.patient_id = pt.patient_id
    JOIN doctors d ON p.doctor_id = d.doctor_id
    ORDER BY p.prescribed_date DESC
");
$prescriptions = $stmt->fetchAll();

// Fetch all patients for dropdown
$stmt = $pdo->query("SELECT patient_id, first_name, last_name FROM patients ORDER BY first_name, last_name");
$patients = $stmt->fetchAll();

// Fetch all doctors for dropdown
$stmt = $pdo->query("SELECT doctor_id, first_name, last_name, specialization FROM doctors ORDER BY first_name, last_name");
$doctors = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription Management - Healthcare System</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Healthcare System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="patients.php">Patients</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="doctors.php">Doctors</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="appointments.php">Appointments</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="medical_records.php">Medical Records</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="prescriptions.php">Prescriptions</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Prescription Management</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPrescriptionModal">
                Add New Prescription
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
                                <th>Medication</th>
                                <th>Dosage</th>
                                <th>Frequency</th>
                                <th>Duration</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prescriptions as $prescription): ?>
                            <tr>
                                <td><?php echo $prescription['prescription_id']; ?></td>
                                <td><?php echo $prescription['patient_first_name'] . ' ' . $prescription['patient_last_name']; ?></td>
                                <td><?php echo $prescription['doctor_first_name'] . ' ' . $prescription['doctor_last_name']; ?></td>
                                <td><?php echo $prescription['medication_name']; ?></td>
                                <td><?php echo $prescription['dosage']; ?></td>
                                <td><?php echo $prescription['frequency']; ?></td>
                                <td><?php echo $prescription['duration']; ?></td>
                                <td><?php echo $prescription['prescribed_date']; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editPrescription(<?php echo htmlspecialchars(json_encode($prescription)); ?>)">Edit</button>
                                    <button class="btn btn-sm btn-danger" onclick="deletePrescription(<?php echo $prescription['prescription_id']; ?>)">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Prescription Modal -->
    <div class="modal fade" id="addPrescriptionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Prescription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="prescriptions.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Patient</label>
                            <select class="form-select" name="patient_id" required>
                                <option value="">Select Patient</option>
                                <?php foreach ($patients as $patient): ?>
                                <option value="<?php echo $patient['patient_id']; ?>">
                                    <?php echo $patient['first_name'] . ' ' . $patient['last_name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Doctor</label>
                            <select class="form-select" name="doctor_id" required>
                                <option value="">Select Doctor</option>
                                <?php foreach ($doctors as $doctor): ?>
                                <option value="<?php echo $doctor['doctor_id']; ?>">
                                    <?php echo $doctor['first_name'] . ' ' . $doctor['last_name'] . ' (' . $doctor['specialization'] . ')'; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Medication Name</label>
                            <input type="text" class="form-control" name="medication_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dosage</label>
                            <input type="text" class="form-control" name="dosage" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Frequency</label>
                            <input type="text" class="form-control" name="frequency" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Duration</label>
                            <input type="text" class="form-control" name="duration" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Prescription</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Prescription Modal -->
    <div class="modal fade" id="editPrescriptionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Prescription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="prescriptions.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="prescription_id" id="edit_prescription_id">
                        <div class="mb-3">
                            <label class="form-label">Patient</label>
                            <select class="form-select" name="patient_id" id="edit_patient_id" required>
                                <?php foreach ($patients as $patient): ?>
                                <option value="<?php echo $patient['patient_id']; ?>">
                                    <?php echo $patient['first_name'] . ' ' . $patient['last_name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Doctor</label>
                            <select class="form-select" name="doctor_id" id="edit_doctor_id" required>
                                <?php foreach ($doctors as $doctor): ?>
                                <option value="<?php echo $doctor['doctor_id']; ?>">
                                    <?php echo $doctor['first_name'] . ' ' . $doctor['last_name'] . ' (' . $doctor['specialization'] . ')'; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Medication Name</label>
                            <input type="text" class="form-control" name="medication_name" id="edit_medication_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dosage</label>
                            <input type="text" class="form-control" name="dosage" id="edit_dosage" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Frequency</label>
                            <input type="text" class="form-control" name="frequency" id="edit_frequency" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Duration</label>
                            <input type="text" class="form-control" name="duration" id="edit_duration" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" id="edit_notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Prescription Form -->
    <form id="deletePrescriptionForm" action="prescriptions.php" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="prescription_id" id="delete_prescription_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editPrescription(prescription) {
            document.getElementById('edit_prescription_id').value = prescription.prescription_id;
            document.getElementById('edit_patient_id').value = prescription.patient_id;
            document.getElementById('edit_doctor_id').value = prescription.doctor_id;
            document.getElementById('edit_medication_name').value = prescription.medication_name;
            document.getElementById('edit_dosage').value = prescription.dosage;
            document.getElementById('edit_frequency').value = prescription.frequency;
            document.getElementById('edit_duration').value = prescription.duration;
            document.getElementById('edit_notes').value = prescription.notes;
            
            new bootstrap.Modal(document.getElementById('editPrescriptionModal')).show();
        }

        function deletePrescription(prescriptionId) {
            if (confirm('Are you sure you want to delete this prescription?')) {
                document.getElementById('delete_prescription_id').value = prescriptionId;
                document.getElementById('deletePrescriptionForm').submit();
            }
        }
    </script>
</body>
</html> 