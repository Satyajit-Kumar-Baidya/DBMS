<?php
require_once '../dbConnect.php';
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO ambulances (
                            vehicle_number, 
                            vehicle_type, 
                            driver_name, 
                            driver_contact, 
                            location, 
                            price_per_km,
                            status
                        ) VALUES (?, ?, ?, ?, ?, ?, 'available')
                    ");

                    $stmt->execute([
                        $_POST['vehicle_number'],
                        $_POST['vehicle_type'],
                        $_POST['driver_name'],
                        $_POST['driver_contact'],
                        $_POST['location'],
                        $_POST['price_per_km']
                    ]);

                    $_SESSION['success'] = "Ambulance added successfully!";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Error adding ambulance: " . $e->getMessage();
                }
                break;

            case 'update':
                try {
                    $stmt = $pdo->prepare("
                        UPDATE ambulances 
                        SET vehicle_number = ?,
                            vehicle_type = ?,
                            driver_name = ?,
                            driver_contact = ?,
                            location = ?,
                            price_per_km = ?,
                            status = ?
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $_POST['vehicle_number'],
                        $_POST['vehicle_type'],
                        $_POST['driver_name'],
                        $_POST['driver_contact'],
                        $_POST['location'],
                        $_POST['price_per_km'],
                        $_POST['status'],
                        $_POST['id']
                    ]);

                    $_SESSION['success'] = "Ambulance updated successfully!";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Error updating ambulance: " . $e->getMessage();
                }
                break;

            case 'delete':
                try {
                    $stmt = $pdo->prepare("DELETE FROM ambulances WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $_SESSION['success'] = "Ambulance deleted successfully!";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Error deleting ambulance: " . $e->getMessage();
                }
                break;
        }
    }
    header("Location: manage_ambulances.php");
    exit();
}

// Get all ambulances
$stmt = $pdo->query("SELECT * FROM ambulances ORDER BY created_at DESC");
$ambulances = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Ambulances - Healthcare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row mb-4">
            <div class="col">
                <h2>Manage Ambulances</h2>
                <p class="text-muted">Add, edit, and manage ambulances in the system</p>
            </div>
            <div class="col text-end">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAmbulanceModal">
                    <i class="fas fa-plus"></i> Add New Ambulance
                </button>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Vehicle Number</th>
                        <th>Type</th>
                        <th>Driver</th>
                        <th>Contact</th>
                        <th>Location</th>
                        <th>Price/KM</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ambulances as $ambulance): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ambulance['vehicle_number']); ?></td>
                            <td><?php echo htmlspecialchars($ambulance['vehicle_type']); ?></td>
                            <td><?php echo htmlspecialchars($ambulance['driver_name']); ?></td>
                            <td><?php echo htmlspecialchars($ambulance['driver_contact']); ?></td>
                            <td><?php echo htmlspecialchars($ambulance['location']); ?></td>
                            <td>$<?php echo number_format($ambulance['price_per_km'], 2); ?></td>
                            <td>
                                <span class="badge <?php 
                                    echo ($ambulance['status'] === 'available') ? 'bg-success' : 
                                        (($ambulance['status'] === 'busy') ? 'bg-warning' : 'bg-danger'); 
                                ?>">
                                    <?php echo ucfirst($ambulance['status']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary edit-btn" 
                                        data-ambulance='<?php echo json_encode($ambulance); ?>'
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editAmbulanceModal">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-btn"
                                        data-id="<?php echo $ambulance['id']; ?>"
                                        data-vehicle="<?php echo htmlspecialchars($ambulance['vehicle_number']); ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteAmbulanceModal">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Ambulance Modal -->
    <div class="modal fade" id="addAmbulanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Ambulance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="manage_ambulances.php" method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Vehicle Number</label>
                            <input type="text" class="form-control" name="vehicle_number" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Vehicle Type</label>
                            <input type="text" class="form-control" name="vehicle_type" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Driver Name</label>
                            <input type="text" class="form-control" name="driver_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Driver Contact</label>
                            <input type="text" class="form-control" name="driver_contact" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" name="location" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price per KM</label>
                            <input type="number" step="0.01" class="form-control" name="price_per_km" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Ambulance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Ambulance Modal -->
    <div class="modal fade" id="editAmbulanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Ambulance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="manage_ambulances.php" method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Vehicle Number</label>
                            <input type="text" class="form-control" name="vehicle_number" id="edit_vehicle_number" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Vehicle Type</label>
                            <input type="text" class="form-control" name="vehicle_type" id="edit_vehicle_type" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Driver Name</label>
                            <input type="text" class="form-control" name="driver_name" id="edit_driver_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Driver Contact</label>
                            <input type="text" class="form-control" name="driver_contact" id="edit_driver_contact" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" name="location" id="edit_location" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price per KM</label>
                            <input type="number" step="0.01" class="form-control" name="price_per_km" id="edit_price_per_km" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="available">Available</option>
                                <option value="busy">Busy</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Ambulance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Ambulance Modal -->
    <div class="modal fade" id="deleteAmbulanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Ambulance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="manage_ambulances.php" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                    <div class="modal-body">
                        <p>Are you sure you want to delete ambulance <strong id="delete_vehicle"></strong>?</p>
                        <p class="text-danger">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Ambulance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle edit button clicks
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const ambulance = JSON.parse(this.dataset.ambulance);
                document.getElementById('edit_id').value = ambulance.id;
                document.getElementById('edit_vehicle_number').value = ambulance.vehicle_number;
                document.getElementById('edit_vehicle_type').value = ambulance.vehicle_type;
                document.getElementById('edit_driver_name').value = ambulance.driver_name;
                document.getElementById('edit_driver_contact').value = ambulance.driver_contact;
                document.getElementById('edit_location').value = ambulance.location;
                document.getElementById('edit_price_per_km').value = ambulance.price_per_km;
                document.getElementById('edit_status').value = ambulance.status;
            });
        });

        // Handle delete button clicks
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('delete_id').value = this.dataset.id;
                document.getElementById('delete_vehicle').textContent = this.dataset.vehicle;
            });
        });
    </script>
</body>
</html> 