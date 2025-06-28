<?php
session_start();
require_once '../dbConnect.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'patient') {
    header("Location: ../index.php");
    exit();
}

// Get the patient ID for the logged-in user
$stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->execute([$_SESSION['user']['id']]);
$patient_id = $stmt->fetchColumn();

$appointments = [];
if (($handle = fopen('../appointments.txt', 'r')) !== false) {
    while (($data = fgetcsv($handle)) !== false) {
        if (count($data) < 7) continue;
        list($doctor_id, $pid, $date, $time, $reason, $status, $created_at) = $data;
        if ($pid != $patient_id) continue;
        $apt = [
            'doctor_id' => $doctor_id,
            'patient_id' => $pid,
            'appointment_date' => $date,
            'appointment_time' => $time,
            'reason' => $reason,
            'status' => $status,
            'created_at' => $created_at
        ];
        $appointments[] = $apt;
    }
    fclose($handle);
}

// Build a map of doctor_id => doctor name
$doctorNames = [];
if (!empty($appointments)) {
    $doctorIds = array_unique(array_map(function($apt) { return $apt['doctor_id']; }, $appointments));
    if (!empty($doctorIds)) {
        $in = str_repeat('?,', count($doctorIds) - 1) . '?';
        $stmt = $pdo->prepare("SELECT d.id, u.first_name, u.last_name FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.id IN ($in)");
        $stmt->execute($doctorIds);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $doctorNames[$row['id']] = $row['first_name'] . ' ' . $row['last_name'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - Healthcare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0">My Appointments</h2>
                <p class="text-muted mb-0">View and manage your appointments</p>
            </div>
            <a href="patient_dashboard.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
        <div class="card">
            <div class="card-body">
                <?php if (empty($appointments)): ?>
                    <div class="alert alert-info">You don't have any appointments yet.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Doctor</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $apt): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($doctorNames[$apt['doctor_id']] ?? $apt['doctor_id']); ?></td>
                                        <td><?php echo htmlspecialchars($apt['appointment_date']); ?></td>
                                        <td><?php echo htmlspecialchars($apt['appointment_time']); ?></td>
                                        <td><?php echo htmlspecialchars($apt['reason']); ?></td>
                                        <td><?php echo htmlspecialchars($apt['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 