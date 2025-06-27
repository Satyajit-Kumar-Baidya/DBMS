<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();
require_once '../dbConnect.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../index.php");
    exit();
}

// Handle appointment status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id']) && isset($_POST['status'])) {
    $appointment_id = $_POST['appointment_id'];
    $status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ? AND doctor_id = ?");
    $stmt->execute([$status, $appointment_id, $_SESSION['doctor_id']]);
    
    header("Location: manage_appointments.php");
    exit();
}

// Fetch all appointments for the doctor
$stmt = $pdo->prepare("
    SELECT 
        a.*,
        p.first_name as patient_first_name,
        p.last_name as patient_last_name,
        p.email as patient_email,
        p.phone as patient_phone
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    WHERE a.doctor_id = ?
    ORDER BY 
        CASE 
            WHEN a.status = 'pending' THEN 1
            WHEN a.status = 'accepted' AND a.appointment_date >= CURDATE() THEN 2
            ELSE 3
        END,
        a.appointment_date ASC,
        a.appointment_time ASC
");
$stmt->execute([$_SESSION['doctor_id']]);
$appointments = $stmt->fetchAll();

// Group appointments by status
$pending = array_filter($appointments, fn($apt) => $apt['status'] === 'pending');
$upcoming = array_filter($appointments, fn($apt) => $apt['status'] === 'accepted' && strtotime($apt['appointment_date']) >= strtotime('today'));
$past = array_filter($appointments, fn($apt) => $apt['status'] === 'accepted' && strtotime($apt['appointment_date']) < strtotime('today'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments - Healthcare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../styles.css">
    <style>
        .appointment-card {
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .appointment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .patient-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .patient-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .status-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
        }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0">Manage Appointments</h2>
                <p class="text-muted mb-0">View and manage your patient appointments</p>
            </div>
        </div>

        <!-- Appointment Tabs -->
        <ul class="nav nav-tabs" id="appointmentTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">
                    Pending Requests <span class="badge bg-warning text-dark ms-2"><?php echo count($pending); ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming" type="button" role="tab">
                    Upcoming <span class="badge bg-primary ms-2"><?php echo count($upcoming); ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="past-tab" data-bs-toggle="tab" data-bs-target="#past" type="button" role="tab">
                    Past Appointments <span class="badge bg-secondary ms-2"><?php echo count($past); ?></span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="appointmentTabsContent">
            <!-- Pending Appointments -->
            <div class="tab-pane fade show active" id="pending" role="tabpanel">
                <?php if (empty($pending)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-calendar-check text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3">No pending appointment requests</p>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($pending as $apt): ?>
                            <div class="col-12">
                                <div class="card appointment-card">
                                    <div class="card-body">
                                        <span class="status-badge badge bg-warning text-dark">Pending Action</span>
                                        
                                        <div class="patient-profile">
                                            <div class="patient-avatar">
                                                <i class="bi bi-person-circle"></i>
                                            </div>
                                            <div class="patient-info">
                                                <h5 class="mb-1"><?php echo htmlspecialchars($apt['patient_first_name'] . ' ' . $apt['patient_last_name']); ?></h5>
                                                <p class="mb-1">
                                                    <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($apt['patient_email']); ?> •
                                                    <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($apt['patient_phone']); ?>
                                                </p>
                                                <p class="mb-0 text-muted">
                                                    <i class="bi bi-calendar3"></i> <?php echo date('l, F j, Y', strtotime($apt['appointment_date'])); ?> at 
                                                    <i class="bi bi-clock"></i> <?php echo date('g:i A', strtotime($apt['appointment_time'])); ?>
                                                </p>
                                            </div>
                                        </div>

                                        <div class="mt-3">
                                            <p class="mb-2"><strong>Reason for Visit:</strong></p>
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($apt['reason'])); ?></p>
                                        </div>

                                        <div class="mt-3">
                                            <form method="POST" class="d-inline-block">
                                                <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                                <input type="hidden" name="status" value="accepted">
                                                <button type="submit" class="btn btn-success">
                                                    <i class="bi bi-check-circle"></i> Accept
                                                </button>
                                            </form>
                                            <form method="POST" class="d-inline-block ms-2">
                                                <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                                <input type="hidden" name="status" value="rejected">
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="bi bi-x-circle"></i> Reject
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Upcoming Appointments -->
            <div class="tab-pane fade" id="upcoming" role="tabpanel">
                <?php if (empty($upcoming)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-calendar text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3">No upcoming appointments</p>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($upcoming as $apt): ?>
                            <div class="col-12">
                                <div class="card appointment-card">
                                    <div class="card-body">
                                        <span class="status-badge badge bg-success">Confirmed</span>
                                        
                                        <div class="patient-profile">
                                            <div class="patient-avatar">
                                                <i class="bi bi-person-circle"></i>
                                            </div>
                                            <div class="patient-info">
                                                <h5 class="mb-1"><?php echo htmlspecialchars($apt['patient_first_name'] . ' ' . $apt['patient_last_name']); ?></h5>
                                                <p class="mb-1">
                                                    <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($apt['patient_email']); ?> •
                                                    <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($apt['patient_phone']); ?>
                                                </p>
                                                <p class="mb-0 text-muted">
                                                    <i class="bi bi-calendar3"></i> <?php echo date('l, F j, Y', strtotime($apt['appointment_date'])); ?> at 
                                                    <i class="bi bi-clock"></i> <?php echo date('g:i A', strtotime($apt['appointment_time'])); ?>
                                                </p>
                                            </div>
                                        </div>

                                        <div class="mt-3">
                                            <p class="mb-2"><strong>Reason for Visit:</strong></p>
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($apt['reason'])); ?></p>
                                        </div>

                                        <div class="mt-3">
                                            <a href="patient_records.php?patient_id=<?php echo $apt['patient_id']; ?>" class="btn btn-primary">
                                                <i class="bi bi-file-text"></i> View Medical Records
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Past Appointments -->
            <div class="tab-pane fade" id="past" role="tabpanel">
                <?php if (empty($past)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-clock-history text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3">No past appointments</p>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($past as $apt): ?>
                            <div class="col-12">
                                <div class="card appointment-card">
                                    <div class="card-body">
                                        <span class="status-badge badge bg-secondary">Completed</span>
                                        
                                        <div class="patient-profile">
                                            <div class="patient-avatar">
                                                <i class="bi bi-person-circle"></i>
                                            </div>
                                            <div class="patient-info">
                                                <h5 class="mb-1"><?php echo htmlspecialchars($apt['patient_first_name'] . ' ' . $apt['patient_last_name']); ?></h5>
                                                <p class="mb-1">
                                                    <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($apt['patient_email']); ?> •
                                                    <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($apt['patient_phone']); ?>
                                                </p>
                                                <p class="mb-0 text-muted">
                                                    <i class="bi bi-calendar3"></i> <?php echo date('l, F j, Y', strtotime($apt['appointment_date'])); ?> at 
                                                    <i class="bi bi-clock"></i> <?php echo date('g:i A', strtotime($apt['appointment_time'])); ?>
                                                </p>
                                            </div>
                                        </div>

                                        <div class="mt-3">
                                            <p class="mb-2"><strong>Reason for Visit:</strong></p>
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($apt['reason'])); ?></p>
                                        </div>

                                        <div class="mt-3">
                                            <a href="patient_records.php?patient_id=<?php echo $apt['patient_id']; ?>" class="btn btn-primary">
                                                <i class="bi bi-file-text"></i> View Medical Records
                                            </a>
                                            <a href="add_prescription.php?appointment_id=<?php echo $apt['id']; ?>" class="btn btn-success ms-2">
                                                <i class="bi bi-file-earmark-plus"></i> Add/Edit Prescription
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show active tab based on URL hash or default to pending
        document.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash || '#pending';
            const tab = new bootstrap.Tab(document.querySelector(`[data-bs-target="${hash}"]`));
            tab.show();
        });
    </script>
</body>
</html> 