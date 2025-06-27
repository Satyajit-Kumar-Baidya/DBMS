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
                $stmt = $pdo->prepare("INSERT INTO doctors (first_name, last_name, specialization, email, phone) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['first_name'],
                    $_POST['last_name'],
                    $_POST['specialization'],
                    $_POST['email'],
                    $_POST['phone']
                ]);
                break;

            case 'edit':
                $stmt = $pdo->prepare("UPDATE doctors SET first_name = ?, last_name = ?, specialization = ?, email = ?, phone = ? WHERE doctor_id = ?");
                $stmt->execute([
                    $_POST['first_name'],
                    $_POST['last_name'],
                    $_POST['specialization'],
                    $_POST['email'],
                    $_POST['phone'],
                    $_POST['doctor_id']
                ]);
                break;

            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM doctors WHERE doctor_id = ?");
                $stmt->execute([$_POST['doctor_id']]);
                break;
        }
        header("Location: doctors.php");
        exit();
    }
}

// Fetch all doctors
$stmt = $pdo->query("SELECT * FROM doctors ORDER BY created_at DESC");
$doctors = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Management - Healthcare System</title>
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
                        <a class="nav-link active" href="doctors.php">Doctors</a>
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
        <!-- Add Patient Search Form -->
        <div class="card mb-4">
            <div class="card-body">
                <h4>Search Patient Records</h4>
                <form action="view_patient_history.php" method="GET" class="row g-3">
                    <div class="col-md-6">
                        <label for="patient_id" class="form-label">Patient ID</label>
                        <input type="number" class="form-control" id="patient_id" name="patient_id" required placeholder="Enter patient ID">
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">Search Patient History</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Doctor Management</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDoctorModal">
                Add New Doctor
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
                                <th>Specialization</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($doctors as $doctor): ?>
                            <tr>
                                <td><?php echo $doctor['doctor_id']; ?></td>
                                <td><?php echo $doctor['first_name'] . ' ' . $doctor['last_name']; ?></td>
                                <td><?php echo $doctor['specialization']; ?></td>
                                <td><?php echo $doctor['email']; ?></td>
                                <td><?php echo $doctor['phone']; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editDoctor(<?php echo htmlspecialchars(json_encode($doctor)); ?>)">Edit</button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteDoctor(<?php echo $doctor['doctor_id']; ?>)">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Doctor Modal -->
    <div class="modal fade" id="addDoctorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Doctor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="doctors.php" method="POST">
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
                            <label class="form-label">Specialization</label>
                            <input type="text" class="form-control" name="specialization" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Doctor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Doctor Modal -->
    <div class="modal fade" id="editDoctorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Doctor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="doctors.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="doctor_id" id="edit_doctor_id">
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" id="edit_first_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" id="edit_last_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Specialization</label>
                            <input type="text" class="form-control" name="specialization" id="edit_specialization" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" id="edit_phone" required>
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

    <!-- Delete Doctor Form -->
    <form id="deleteDoctorForm" action="doctors.php" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="doctor_id" id="delete_doctor_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editDoctor(doctor) {
            document.getElementById('edit_doctor_id').value = doctor.doctor_id;
            document.getElementById('edit_first_name').value = doctor.first_name;
            document.getElementById('edit_last_name').value = doctor.last_name;
            document.getElementById('edit_specialization').value = doctor.specialization;
            document.getElementById('edit_email').value = doctor.email;
            document.getElementById('edit_phone').value = doctor.phone;
            
            new bootstrap.Modal(document.getElementById('editDoctorModal')).show();
        }

        function deleteDoctor(doctorId) {
            if (confirm('Are you sure you want to delete this doctor?')) {
                document.getElementById('delete_doctor_id').value = doctorId;
                document.getElementById('deleteDoctorForm').submit();
            }
        }
    </script>
</body>
</html> 