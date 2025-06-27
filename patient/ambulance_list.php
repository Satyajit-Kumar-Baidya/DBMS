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
            <h2>Available Ambulances</h2>
            <p class="text-muted">Book an ambulance for emergency or scheduled transport</p>
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

    <div class="row">
        <?php if (empty($ambulances)): ?>
            <div class="col-12">
                <div class="alert alert-info">
                    No ambulances are available at the moment. Please try again later.
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($ambulances as $ambulance): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($ambulance['vehicle_type']); ?></h5>
                            <div class="mb-3">
                                <p class="mb-1">
                                    <i class="fas fa-ambulance"></i>
                                    Vehicle Number: <?php echo htmlspecialchars($ambulance['vehicle_number']); ?>
                                </p>
                                <p class="mb-1">
                                    <i class="fas fa-user"></i>
                                    Driver: <?php echo htmlspecialchars($ambulance['driver_name']); ?>
                                </p>
                                <p class="mb-1">
                                    <i class="fas fa-phone"></i>
                                    Contact: <?php echo htmlspecialchars($ambulance['driver_contact']); ?>
                                </p>
                                <p class="mb-1">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Current Location: <?php echo htmlspecialchars($ambulance['location']); ?>
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-dollar-sign"></i>
                                    Price per KM: $<?php echo number_format($ambulance['price_per_km'], 2); ?>
                                </p>
                            </div>
                            <a href="ambulance_controller.php?action=book_form&id=<?php echo $ambulance['id']; ?>" 
                               class="btn btn-primary w-100">
                                Book Now
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="row mt-4">
        <div class="col">
            <a href="ambulance_controller.php?action=my_bookings" class="btn btn-outline-primary">
                <i class="fas fa-history"></i> View My Bookings
            </a>
            <a href="../dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?> 