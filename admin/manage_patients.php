<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../dbConnect.php';

// Restrict to admin only
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$_POST['email']]);
                if ($stmt->fetch()) {
                    echo '<script>alert("Email already exists. Please use a different email."); window.location.href="manage_patients.php";</script>';
                    exit();
                }
                // Create user
                $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, 'patient')");
                $stmt->execute([
                    $_POST['first_name'],
                    $_POST['last_name'],
                    $_POST['email'],
                    password_hash($_POST['password'], PASSWORD_DEFAULT)
                ]);
                $user_id = $pdo->lastInsertId();
                // Create patient
                $stmt = $pdo->prepare("INSERT INTO patients (user_id, date_of_birth, gender, blood_group, address, phone) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $user_id,
                    $_POST['date_of_birth'],
                    $_POST['gender'],
                    $_POST['blood_group'],
                    $_POST['address'],
                    $_POST['phone']
                ]);
                break;
            case 'edit':
                $stmt = $pdo->prepare("UPDATE patients SET date_of_birth = ?, gender = ?, blood_group = ?, address = ?, phone = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['date_of_birth'],
                    $_POST['gender'],
                    $_POST['blood_group'],
                    $_POST['address'],
                    $_POST['phone'],
                    $_POST['id']
                ]);
                break;
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM patients WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                break;
        }
        header("Location: manage_patients.php");
        exit();
    }
}

// Fetch all patients with user info
$stmt = $pdo->query("SELECT p.id, p.date_of_birth, p.gender, p.blood_group, u.first_name, u.last_name, u.email FROM patients p JOIN users u ON p.user_id = u.id ORDER BY p.id DESC");
$patients = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Patient Management</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
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
                                <th>Blood Group</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($patients as $patient): ?>
                            <tr>
                                <td><?php echo $patient['id']; ?></td>
                                <td><?php echo $patient['first_name'] . ' ' . $patient['last_name']; ?></td>
                                <td><?php echo $patient['date_of_birth']; ?></td>
                                <td><?php echo $patient['gender']; ?></td>
                                <td><?php echo $patient['email']; ?></td>
                                <td><?php echo $patient['blood_group']; ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $patient['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                    <!-- Edit button/modal can be added here -->
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
                    <form action="manage_patients.php" method="POST">
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
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" required>
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
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Show Add Patient modal if ?action=add is present
    if (window.location.search.includes('action=add')) {
        var addModal = new bootstrap.Modal(document.getElementById('addPatientModal'));
        window.addEventListener('DOMContentLoaded', function() {
            addModal.show();
        });
    }
    </script>
</body>
</html> 