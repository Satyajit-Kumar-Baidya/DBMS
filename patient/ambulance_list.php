<?php
session_start();
// include_once '../includes/header.php';
// require_once '../includes/patient_navbar.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Available Ambulances</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card { box-shadow: 0 2px 10px rgba(0,0,0,0.08); border: none; }
        .card-title { font-weight: bold; }
        .container { max-width: 900px; }
        .btn-primary, .btn-outline-primary { border-radius: 20px; }
        h2 { margin-bottom: 30px; }
    </style>
</head>
<body>
<a href="patient_dashboard.php" class="btn btn-secondary position-absolute top-0 end-0 m-4">Dashboard</a>
<div class="container mt-5">
    <h2 class="text-center">Available Ambulances</h2>
    <?php if (empty($ambulances)): ?>
        <div class="alert alert-info text-center">No ambulances are available at the moment. Please try again later.</div>
    <?php else: ?>
        <div class="row">
        <?php foreach ($ambulances as $ambulance): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title text-primary"><?php echo htmlspecialchars($ambulance['vehicle_type']); ?></h5>
                        <ul class="list-unstyled mb-3">
                            <li><strong>Vehicle Number:</strong> <?php echo htmlspecialchars($ambulance['vehicle_number']); ?></li>
                            <li><strong>Driver:</strong> <?php echo htmlspecialchars($ambulance['driver_name']); ?></li>
                            <li><strong>Contact:</strong> <?php echo htmlspecialchars($ambulance['driver_contact']); ?></li>
                            <li><strong>Location:</strong> <?php echo htmlspecialchars($ambulance['location']); ?></li>
                            <li><strong>Price per KM:</strong> $<?php echo number_format($ambulance['price_per_km'], 2); ?></li>
                        </ul>
                        <a href="ambulance_controller.php?action=book_form&id=<?php echo $ambulance['id']; ?>" class="btn btn-primary w-100 mb-2">Book Now</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="text-center mt-4">
        <a href="ambulance_controller.php?action=my_bookings" class="btn btn-outline-primary">View My Bookings</a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 