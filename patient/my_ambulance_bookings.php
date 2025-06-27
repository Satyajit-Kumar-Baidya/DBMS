<?php
include_once '../includes/header.php';
require_once '../includes/patient_navbar.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}
?>

<div class="container mt-5">
    <div class="row mb-4">
        <div class="col">
            <h2>My Ambulance Bookings</h2>
            <p class="text-muted">View and manage your ambulance bookings</p>
        </div>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (empty($bookings)): ?>
        <div class="alert alert-info">
            <p class="mb-0">You haven't made any ambulance bookings yet.</p>
        </div>
        <a href="ambulance_controller.php?action=list" class="btn btn-primary">
            <i class="fas fa-ambulance"></i> Book an Ambulance
        </a>
    <?php else: ?>
        <div class="row">
            <?php foreach ($bookings as $booking): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Booking #<?php echo $booking['id']; ?></h5>
                                <span class="badge <?php 
                                    echo ($booking['status'] === 'pending') ? 'bg-warning' : 
                                        (($booking['status'] === 'confirmed') ? 'bg-success' : 
                                        (($booking['status'] === 'completed') ? 'bg-info' : 'bg-danger')); 
                                ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6>Ambulance Details</h6>
                                <p class="mb-1">
                                    <i class="fas fa-ambulance"></i>
                                    <?php echo htmlspecialchars($booking['vehicle_type']); ?> - 
                                    <?php echo htmlspecialchars($booking['vehicle_number']); ?>
                                </p>
                                <p class="mb-1">
                                    <i class="fas fa-user"></i>
                                    Driver: <?php echo htmlspecialchars($booking['driver_name']); ?>
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-phone"></i>
                                    Contact: <?php echo htmlspecialchars($booking['driver_contact']); ?>
                                </p>
                            </div>

                            <div class="mb-3">
                                <h6>Booking Details</h6>
                                <p class="mb-1">
                                    <i class="fas fa-map-marker-alt"></i>
                                    From: <?php echo htmlspecialchars($booking['pickup_location']); ?>
                                </p>
                                <p class="mb-1">
                                    <i class="fas fa-map-marker"></i>
                                    To: <?php echo htmlspecialchars($booking['destination']); ?>
                                </p>
                                <p class="mb-1">
                                    <i class="fas fa-calendar"></i>
                                    Date: <?php echo date('F j, Y', strtotime($booking['booking_date'])); ?>
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-clock"></i>
                                    Time: <?php echo date('g:i A', strtotime($booking['booking_time'])); ?>
                                </p>
                            </div>

                            <?php if ($booking['status'] === 'pending' || $booking['status'] === 'confirmed'): ?>
                                <div class="d-grid">
                                    <button type="button" 
                                            class="btn btn-danger"
                                            onclick="confirmCancel(<?php echo $booking['id']; ?>)">
                                        <i class="fas fa-times"></i> Cancel Booking
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-4">
            <a href="ambulance_controller.php?action=list" class="btn btn-primary">
                <i class="fas fa-plus"></i> Book Another Ambulance
            </a>
            <a href="../dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
function confirmCancel(bookingId) {
    if (confirm('Are you sure you want to cancel this booking?')) {
        window.location.href = `ambulance_controller.php?action=cancel&id=${bookingId}`;
    }
}
</script>

<?php include_once '../includes/footer.php'; ?> 