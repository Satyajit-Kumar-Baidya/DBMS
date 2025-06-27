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
                $stmt = $pdo->prepare("INSERT INTO patients (first_name, last_name, date_of_birth, gender, email, phone, address, blood_group) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['first_name'],
                    $_POST['last_name'],
                    $_POST['date_of_birth'],
                    $_POST['gender'],
                    $_POST['email'],
                    $_POST['phone'],
                    $_POST['address'],
                    $_POST['blood_group']
                ]);
                break;

            case 'edit':
                $stmt = $pdo->prepare("UPDATE patients SET first_name = ?, last_name = ?, date_of_birth = ?, gender = ?, email = ?, phone = ?, address = ?, blood_group = ? WHERE patient_id = ?");
                $stmt->execute([
                    $_POST['first_name'],
                    $_POST['last_name'],
                    $_POST['date_of_birth'],
                    $_POST['gender'],
                    $_POST['email'],
                    $_POST['phone'],
                    $_POST['address'],
                    $_POST['blood_group'],
                    $_POST['patient_id']
                ]);
                break;

            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM patients WHERE patient_id = ?");
                $stmt->execute([$_POST['patient_id']]);
                break;
        }
        header("Location: patients.php");
        exit();
    }
}

// Fetch all patients
$stmt = $pdo->query("SELECT * FROM patients ORDER BY created_at DESC");
$patients = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Management - Healthcare System</title>
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
                        <a class="nav-link active" href="patients.php">Patients</a>
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
            <h2>Patient Management</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPatientModal">
                Add New Patient
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Date of Birth</th>
                                <th>Gender</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Blood Group</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($patients as $patient): ?>
                            <tr>
                                <td><?php echo $patient['patient_id']; ?></td>
                                <td><?php echo $patient['first_name'] . ' ' . $patient['last_name']; ?></td>
                                <td><?php echo $patient['date_of_birth']; ?></td>
                                <td><?php echo $patient['gender']; ?></td>
                                <td><?php echo $patient['email']; ?></td>
                                <td><?php echo $patient['phone']; ?></td>
                                <td><?php echo $patient['blood_group']; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editPatient(<?php echo htmlspecialchars(json_encode($patient)); ?>)">Edit</button>
                                    <button class="btn btn-sm btn-danger" onclick="deletePatient(<?php echo $patient['patient_id']; ?>)">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Patient Modal -->
    <div class="modal fade" id="addPatientModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Patient</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="patients.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" name="date_of_birth" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Blood Group</label>
                            <input type="text" class="form-control" name="blood_group">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Patient</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Patient Modal -->
    <div class="modal fade" id="editPatientModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Patient</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="patients.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="patient_id" id="edit_patient_id">
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" id="edit_first_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" id="edit_last_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" name="date_of_birth" id="edit_date_of_birth" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender" id="edit_gender" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" id="edit_phone" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" id="edit_address" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Blood Group</label>
                            <input type="text" class="form-control" name="blood_group" id="edit_blood_group">
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

    <!-- Delete Patient Form -->
    <form id="deletePatientForm" action="patients.php" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="patient_id" id="delete_patient_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editPatient(patient) {
            document.getElementById('edit_patient_id').value = patient.patient_id;
            document.getElementById('edit_first_name').value = patient.first_name;
            document.getElementById('edit_last_name').value = patient.last_name;
            document.getElementById('edit_date_of_birth').value = patient.date_of_birth;
            document.getElementById('edit_gender').value = patient.gender;
            document.getElementById('edit_email').value = patient.email;
            document.getElementById('edit_phone').value = patient.phone;
            document.getElementById('edit_address').value = patient.address;
            document.getElementById('edit_blood_group').value = patient.blood_group;
            
            new bootstrap.Modal(document.getElementById('editPatientModal')).show();
        }

        function deletePatient(patientId) {
            if (confirm('Are you sure you want to delete this patient?')) {
                document.getElementById('delete_patient_id').value = patientId;
                document.getElementById('deletePatientForm').submit();
            }
        }
    </script>
</body>
</html> 