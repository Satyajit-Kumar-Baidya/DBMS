<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}
if (!isset($ambulance)) {
    header("Location: ambulance_controller.php?action=list");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Ambulance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .container { max-width: 600px; }
        .card { box-shadow: 0 2px 10px rgba(0,0,0,0.08); border: none; }
        .form-label { font-weight: 500; }
        h4 { margin-bottom: 30px; }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="card">
        <div class="card-body">
            <h4 class="text-center text-primary">Book Ambulance</h4>
            <form action="ambulance_controller.php?action=book" method="POST" id="bookingForm">
                <input type="hidden" name="ambulance_id" value="<?php echo $ambulance['id']; ?>">
                <div class="mb-3">
                    <label class="form-label">Pickup Location</label>
                    <select class="form-select" name="pickup_location" required>
                        <option value="">Select Location</option>
                        <option value="Hospital Main Gate">Hospital Main Gate</option>
                        <option value="Patient's Home">Patient's Home</option>
                        <option value="City Center">City Center</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Destination</label>
                    <input type="text" class="form-control" name="destination" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Patient Contact Number</label>
                    <input type="text" class="form-control" name="patient_contact" value="<?php echo htmlspecialchars($_SESSION['user']['phone'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Emergency Type</label>
                    <select class="form-select" name="emergency_type" required>
                        <option value="">Select Emergency Type</option>
                        <option value="Accident">Accident</option>
                        <option value="Cardiac">Cardiac</option>
                        <option value="Trauma">Trauma</option>
                        <option value="General">General</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Special Instructions</label>
                    <textarea class="form-control" name="special_instructions"></textarea>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" name="booking_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Time</label>
                        <input type="time" class="form-control" name="booking_time" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Estimated Price ($)</label>
                        <input type="number" class="form-control" name="estimated_price" step="0.01">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment Method</label>
                    <select class="form-select" name="payment_method" required>
                        <option value="">Select Payment Method</option>
                        <option value="Cash">Cash</option>
                        <option value="Card">Card</option>
                        <option value="Insurance">Insurance</option>
                    </select>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Confirm Booking</button>
                    <a href="ambulance_controller.php?action=list" class="btn btn-outline-secondary">Back to List</a>
                </div>
            </form>
            <div class="text-center mt-3">
                <a href="patient_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 