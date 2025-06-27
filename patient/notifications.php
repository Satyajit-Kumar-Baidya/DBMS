<?php
session_start();
require_once '../dbConnect.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    header("Location: ../index.php");
    exit();
}

$user = $_SESSION['user'];
$notifications = [];
$errors = [];

try {
    // Get all notifications for the user
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user['id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark notifications as read if requested
    if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['notification_id'], $user['id']]);
        header("Location: notifications.php");
        exit();
    }

    // Delete notification if requested
    if (isset($_POST['delete']) && isset($_POST['notification_id'])) {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['notification_id'], $user['id']]);
        header("Location: notifications.php");
        exit();
    }

} catch (PDOException $e) {
    $errors[] = "Database Error: " . $e->getMessage();
}

// Function to send email notification (to be implemented with actual email service)
function sendEmailNotification($to, $subject, $message) {
    // In a real application, you would use a proper email service here
    // For example: PHPMailer, SendGrid, etc.
    return true;
}

// Function to send SMS notification (to be implemented with actual SMS service)
function sendSMSNotification($to, $message) {
    // In a real application, you would use a proper SMS service here
    // For example: Twilio, Nexmo, etc.
    return true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Healthcare System</title>
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
                    <a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a>
                    <a href="health-log.php"><i class="fas fa-heartbeat"></i> Health Log</a>
                    <a href="../profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="welcome-section">
                    <h2>Notifications</h2>
                    <p>View and manage your notifications and reminders.</p>
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

                <!-- Notifications List -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Your Notifications</h5>
                        <div>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#reminderModal">
                                <i class="fas fa-bell"></i> Set Reminder
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($notifications)): ?>
                            <p class="text-center">No notifications found.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="list-group-item list-group-item-action <?php echo $notification['is_read'] ? '' : 'list-group-item-primary'; ?>">
                                        <div class="d-flex w-100 justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">
                                                    <i class="fas fa-<?php 
                                                        echo $notification['type'] === 'Appointment' ? 'calendar' : 
                                                            ($notification['type'] === 'Prescription' ? 'prescription' : 
                                                            ($notification['type'] === 'Payment' ? 'credit-card' : 'bell')); 
                                                    ?>"></i>
                                                    <?php echo htmlspecialchars($notification['type']); ?> Notification
                                                </h6>
                                                <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                                <small class="text-muted">
                                                    <?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?>
                                                </small>
                                            </div>
                                            <div class="btn-group">
                                                <?php if (!$notification['is_read']): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                        <button type="submit" name="mark_read" class="btn btn-sm btn-outline-success">
                                                            <i class="fas fa-check"></i> Mark as Read
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                    <button type="submit" name="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this notification?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reminder Modal -->
    <div class="modal fade" id="reminderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Set Medication Reminder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="reminderForm">
                        <div class="mb-3">
                            <label for="medication" class="form-label">Medication Name</label>
                            <input type="text" class="form-control" id="medication" required>
                        </div>
                        <div class="mb-3">
                            <label for="reminder_time" class="form-label">Reminder Time</label>
                            <input type="time" class="form-control" id="reminder_time" required>
                        </div>
                        <div class="mb-3">
                            <label for="reminder_days" class="form-label">Days to Remind</label>
                            <select class="form-select" id="reminder_days" multiple required>
                                <option value="1">Monday</option>
                                <option value="2">Tuesday</option>
                                <option value="3">Wednesday</option>
                                <option value="4">Thursday</option>
                                <option value="5">Friday</option>
                                <option value="6">Saturday</option>
                                <option value="0">Sunday</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="reminder_method" class="form-label">Notification Method</label>
                            <select class="form-select" id="reminder_method" required>
                                <option value="email">Email</option>
                                <option value="sms">SMS</option>
                                <option value="both">Both</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveReminder()">Save Reminder</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../script.js"></script>
    <script>
        function saveReminder() {
            // In a real application, you would send this data to the server
            // to create a reminder in the database
            const reminder = {
                medication: document.getElementById('medication').value,
                time: document.getElementById('reminder_time').value,
                days: Array.from(document.getElementById('reminder_days').selectedOptions).map(option => option.value),
                method: document.getElementById('reminder_method').value
            };

            // For now, we'll just show an alert
            alert('Reminder set for ' + reminder.medication + ' at ' + reminder.time);
            
            // Close the modal
            bootstrap.Modal.getInstance(document.getElementById('reminderModal')).hide();
        }
    </script>
</body>
</html> 