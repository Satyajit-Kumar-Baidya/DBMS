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
                $stmt = $pdo->prepare("INSERT INTO medical_records (patient_id, doctor_id, diagnosis, treatment, notes) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['patient_id'],
                    $_POST['doctor_id'],
                    $_POST['diagnosis'],
                    $_POST['treatment'],
                    $_POST['notes']
                ]);
                break;

            case 'edit':
                $stmt = $pdo->prepare("UPDATE medical_records SET patient_id = ?, doctor_id = ?, diagnosis = ?, treatment = ?, notes = ? WHERE record_id = ?");
                $stmt->execute([
                    $_POST['patient_id'],
                    $_POST['doctor_id'],
                    $_POST['diagnosis'],
                    $_POST['treatment'],
                    $_POST['notes'],
                    $_POST['record_id']
                ]);
                break;

            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM medical_records WHERE record_id = ?");
                $stmt->execute([$_POST['record_id']]);
                break;
        }
        header("Location: medical_records.php");
        exit();
    }
}

// Fetch all medical records with patient and doctor details
$stmt = $pdo->query("
    SELECT mr.*, 
           p.first_name as patient_first_name, p.last_name as patient_last_name,
           d.first_name as doctor_first_name, d.last_name as doctor_last_name
    FROM medical_records mr
    JOIN patients p ON mr.patient_id = p.patient_id
    JOIN doctors d ON mr.doctor_id = d.doctor_id
    ORDER BY mr.record_date DESC
");
$records = $stmt->fetchAll();

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
    <title>Medical Records Management - Healthcare System</title>
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
                        <a class="nav-link active" href="medical_records.php">Medical Records</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="prescriptions.php">Prescriptions</a>
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
            <h2>Medical Records Management</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRecordModal">
                Add New Medical Record
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
                                <th>Diagnosis</th>
                                <th>Treatment</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                            <tr>
                                <td><?php echo $record['record_id']; ?></td>
                                <td><?php echo $record['patient_first_name'] . ' ' . $record['patient_last_name']; ?></td>
                                <td><?php echo $record['doctor_first_name'] . ' ' . $record['doctor_last_name']; ?></td>
                                <td><?php echo substr($record['diagnosis'], 0, 30) . '...'; ?></td>
                                <td><?php echo substr($record['treatment'], 0, 30) . '...'; ?></td>
                                <td><?php echo $record['record_date']; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editRecord(<?php echo htmlspecialchars(json_encode($record)); ?>)">Edit</button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteRecord(<?php echo $record['record_id']; ?>)">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Medical Record Modal -->
    <div class="modal fade" id="addRecordModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Medical Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="medical_records.php" method="POST">
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
                            <label class="form-label">Diagnosis</label>
                            <textarea class="form-control" name="diagnosis" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Treatment</label>
                            <textarea class="form-control" name="treatment" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Medical Record Modal -->
    <div class="modal fade" id="editRecordModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Medical Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="medical_records.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="record_id" id="edit_record_id">
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
                            <label class="form-label">Diagnosis</label>
                            <textarea class="form-control" name="diagnosis" id="edit_diagnosis" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Treatment</label>
                            <textarea class="form-control" name="treatment" id="edit_treatment" rows="3" required></textarea>
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

    <!-- Delete Medical Record Form -->
    <form id="deleteRecordForm" action="medical_records.php" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="record_id" id="delete_record_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editRecord(record) {
            document.getElementById('edit_record_id').value = record.record_id;
            document.getElementById('edit_patient_id').value = record.patient_id;
            document.getElementById('edit_doctor_id').value = record.doctor_id;
            document.getElementById('edit_diagnosis').value = record.diagnosis;
            document.getElementById('edit_treatment').value = record.treatment;
            document.getElementById('edit_notes').value = record.notes;
            
            new bootstrap.Modal(document.getElementById('editRecordModal')).show();
        }

        function deleteRecord(recordId) {
            if (confirm('Are you sure you want to delete this medical record?')) {
                document.getElementById('delete_record_id').value = recordId;
                document.getElementById('deleteRecordForm').submit();
            }
        }
    </script>
</body>
</html> 