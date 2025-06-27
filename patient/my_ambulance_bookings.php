<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Ambulance Bookings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .container { max-width: 900px; }
        .card { box-shadow: 0 2px 10px rgba(0,0,0,0.08); border: none; }
        h2 { margin-bottom: 30px; }
    </style>
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center">My Ambulance Bookings</h2>
    <?php if (empty($bookings)): ?>
        <div class="alert alert-info text-center">You haven't made any ambulance bookings yet.</div>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-primary">
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Ambulance</th>
                            <th>Pickup</th>
                            <th>Destination</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($b['booking_date']); ?></td>
                            <td><?php echo htmlspecialchars($b['booking_time']); ?></td>
                            <td><?php echo htmlspecialchars($b['vehicle_number']); ?></td>
                            <td><?php echo htmlspecialchars($b['pickup_location']); ?></td>
                            <td><?php echo htmlspecialchars($b['destination']); ?></td>
                            <td><?php echo htmlspecialchars($b['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
    <div class="text-center mt-4">
        <a href="ambulance_controller.php?action=list" class="btn btn-primary">Book an Ambulance</a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 