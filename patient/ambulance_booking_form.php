<?php
include_once '../includes/header.php';
require_once '../includes/patient_navbar.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

// Redirect if no ambulance selected
if (!isset($ambulance)) {
    header("Location: ambulance_controller.php?action=list");
    exit();
}
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Book Ambulance</h4>
                </div>
                <div class="card-body">
                    <div class="selected-ambulance mb-4">
                        <h5>Selected Ambulance Details</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Vehicle Type:</strong> <?php echo htmlspecialchars($ambulance['vehicle_type']); ?></p>
                                <p><strong>Vehicle Number:</strong> <?php echo htmlspecialchars($ambulance['vehicle_number']); ?></p>
                                <p><strong>Driver Name:</strong> <?php echo htmlspecialchars($ambulance['driver_name']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Driver Contact:</strong> <?php echo htmlspecialchars($ambulance['driver_contact']); ?></p>
                                <p><strong>Current Location:</strong> <?php echo htmlspecialchars($ambulance['location']); ?></p>
                                <p><strong>Price per KM:</strong> $<?php echo number_format($ambulance['price_per_km'], 2); ?></p>
                            </div>
                        </div>
                    </div>

                    <form action="ambulance_controller.php?action=book" method="POST" id="bookingForm">
                        <input type="hidden" name="ambulance_id" value="<?php echo $ambulance['id']; ?>">
                        
                        <div class="mb-3">
                            <label for="pickup_location" class="form-label">Pickup Location</label>
                            <input type="text" class="form-control" id="pickup_location" name="pickup_location" required>
                        </div>

                        <div class="mb-3">
                            <label for="destination" class="form-label">Destination</label>
                            <input type="text" class="form-control" id="destination" name="destination" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="booking_date" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="booking_date" name="booking_date" 
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="booking_time" class="form-label">Time</label>
                                    <input type="time" class="form-control" id="booking_time" name="booking_time" required>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Confirm Booking</button>
                            <a href="ambulance_controller.php?action=list" class="btn btn-outline-secondary">Back to List</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    const date = document.getElementById('booking_date').value;
    const time = document.getElementById('booking_time').value;
    
    const selectedDateTime = new Date(date + ' ' + time);
    const now = new Date();
    
    if (selectedDateTime < now) {
        e.preventDefault();
        alert('Please select a future date and time.');
    }
});
</script>

<?php include_once '../includes/footer.php'; ?> 