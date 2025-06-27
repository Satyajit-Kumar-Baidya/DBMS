<?php
session_start();
require_once '../dbConnect.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    header("Location: ../index.php");
    exit();
}

$user = $_SESSION['user'];
$patientId = null;
$payments = [];
$errors = [];
$success = '';

// Get patient ID
try {
    $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user['id']]);
    $patientId = $stmt->fetchColumn();

    if (!$patientId) {
        session_destroy();
        header("Location: ../index.php");
        exit();
    }

    // Handle payment submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['make_payment'])) {
        $billId = $_POST['bill_id'] ?? '';
        $amount = $_POST['amount'] ?? 0;
        $paymentMethod = $_POST['payment_method'] ?? '';
        $dueDate = $_POST['due_date'] ?? '';

        // Basic validation
        if (empty($billId)) $errors[] = 'Bill ID is required.';
        if ($amount <= 0) $errors[] = 'Amount must be greater than 0.';
        if (empty($paymentMethod)) $errors[] = 'Payment method is required.';
        if (empty($dueDate)) $errors[] = 'Due date is required.';

        if (empty($errors)) {
            try {
                // In a real application, you would integrate with a payment gateway here
                // For this example, we'll simulate a successful payment
                $stmt = $pdo->prepare("INSERT INTO payments (patient_id, bill_id, amount, payment_method, payment_status, due_date, payment_date) VALUES (?, ?, ?, ?, 'Completed', ?, NOW())");
                $stmt->execute([$patientId, $billId, $amount, $paymentMethod, $dueDate]);
                
                $success = 'Payment processed successfully!';
                
                // Send notification
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'Payment', ?)");
                $stmt->execute([$user['id'], "Payment of $amount for bill $billId has been processed successfully."]);
                
            } catch (PDOException $e) {
                $errors[] = "Database Error: " . $e->getMessage();
            }
        }
    }

    // Get payment history
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE patient_id = ? ORDER BY created_at DESC");
    $stmt->execute([$patientId]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errors[] = "Database Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Healthcare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <h3 class="text-center mb-4">Healthcare</h3>
                <nav>
                    <a href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="appointments.php"><i class="fas fa-calendar-check"></i> My Appointments</a>
                    <a href="medical_history.php"><i class="fas fa-history"></i> Medical History</a>
                    <a href="prescriptions.php"><i class="fas fa-prescription"></i> Prescriptions</a>
                    <a href="payments.php" class="active"><i class="fas fa-credit-card"></i> Payments</a>
                    <a href="health-log.php"><i class="fas fa-heartbeat"></i> Health Log</a>
                    <a href="../profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="welcome-section">
                    <h2>Payments</h2>
                    <p>View your payment history and make new payments.</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <!-- Make Payment Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Make a Payment</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <div class="col-md-6">
                                <label for="bill_id" class="form-label">Bill ID</label>
                                <input type="text" class="form-control" id="bill_id" name="bill_id" required>
                            </div>
                            <div class="col-md-6">
                                <label for="amount" class="form-label">Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="payment_method" class="form-label">Payment Method</label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <option value="">Select Payment Method</option>
                                    <option value="Card">Credit/Debit Card</option>
                                    <option value="Mobile Banking">Mobile Banking</option>
                                    <option value="Online Wallet">Online Wallet</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="make_payment" class="btn btn-primary">Process Payment</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Payment History -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Payment History</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($payments)): ?>
                            <p class="text-center">No payment history found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Bill ID</th>
                                            <th>Amount</th>
                                            <th>Payment Method</th>
                                            <th>Status</th>
                                            <th>Due Date</th>
                                            <th>Payment Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payments as $payment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($payment['bill_id']); ?></td>
                                                <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $payment['payment_status'] === 'Completed' ? 'success' : ($payment['payment_status'] === 'Pending' ? 'warning' : 'danger'); ?>">
                                                        <?php echo htmlspecialchars($payment['payment_status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($payment['due_date'])); ?></td>
                                                <td><?php echo $payment['payment_date'] ? date('M d, Y', strtotime($payment['payment_date'])) : 'N/A'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../script.js"></script>
</body>
</html> 