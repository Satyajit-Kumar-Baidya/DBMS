<?php
session_start();
require_once '../dbConnect.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    header("Location: ../index.php");
    exit();
}

// Check if appointment ID is provided
if (!isset($_GET['id'])) {
    header("Location: appointments.php");
    exit();
}

$appointmentId = $_GET['id'];
$user = $_SESSION['user'];

// Get patient ID
try {
    $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $patientId = $stmt->fetchColumn();

    if (!$patientId) {
        header("Location: ../index.php");
        exit();
    }

    // Fetch appointment details
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            d.specialization,
            d.qualification,
            d.hospital,
            d.consultation_fee,
            u.first_name as doctor_first_name,
            u.last_name as doctor_last_name,
            u.email as doctor_email
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        JOIN users u ON d.user_id = u.id
        WHERE a.id = ? AND a.patient_id = ?
    ");
    $stmt->execute([$appointmentId, $patientId]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        header("Location: appointments.php");
        exit();
    }

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: appointments.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Appointment - Healthcare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --light-bg: #f8f9fa;
        }
        
        body {
            background-color: var(--light-bg);
        }
        
        .sidebar {
            min-height: 100vh;
            background-color: var(--primary-color);
            color: white;
            padding-top: 20px;
        }
        
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            display: block;
            transition: all 0.3s ease;
            border-radius: 5px;
            margin: 2px 10px;
        }
        
        .sidebar a:hover, .sidebar a.active {
            background-color: var(--secondary-color);
            transform: translateX(5px);
        }
        
        .main-content {
            padding: 20px;
        }
        
        .welcome-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .appointment-details {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .doctor-info {
            background: var(--light-bg);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-block;
        }

        .status-pending {
            background-color: #ffeeba;
            color: #856404;
        }

        .status-accepted {
            background-color: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-completed {
            background-color: #cce5ff;
            color: #004085;
        }

        .btn-cancel {
            background-color: #dc3545;
            color: white;
            border: none;
        }

        .btn-cancel:hover {
            background-color: #c82333;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <h3 class="text-center mb-4">Healthcare</h3>
                <nav>
                    <a href="patient_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="appointments.php" class="active"><i class="fas fa-calendar-check"></i> My Appointments</a>
                    <a href="medical-records.php"><i class="fas fa-file-medical"></i> Medical Records</a>
                    <a href="prescriptions.php"><i class="fas fa-prescription"></i> Prescriptions</a>
                    <a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a>
                    <a href="health-log.php"><i class="fas fa-heartbeat"></i> Health Log</a>
                    <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="welcome-section">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2>Appointment Details</h2>
                            <p class="mb-0">View your appointment information.</p>
                        </div>
                        <a href="appointments.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left"></i> Back to Appointments
                        </a>
                    </div>
                </div>

                <div class="appointment-details">
                    <div class="doctor-info">
                        <div class="row">
                            <div class="col-md-6">
                                <h4>Doctor Information</h4>
                                <p class="mb-1"><strong>Name:</strong> Dr. <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?></p>
                                <p class="mb-1"><strong>Specialization:</strong> <?php echo htmlspecialchars($appointment['specialization']); ?></p>
                                <p class="mb-1"><strong>Qualification:</strong> <?php echo htmlspecialchars($appointment['qualification']); ?></p>
                                <p class="mb-0"><strong>Hospital:</strong> <?php echo htmlspecialchars($appointment['hospital']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h4>Appointment Information</h4>
                                <p class="mb-1">
                                    <strong>Date:</strong> <?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?>
                                </p>
                                <p class="mb-1">
                                    <strong>Time:</strong> <?php echo date('g:i A', strtotime($appointment['appointment_date'])); ?>
                                </p>
                                <p class="mb-1">
                                    <strong>Consultation Fee:</strong> $<?php echo number_format($appointment['consultation_fee'], 2); ?>
                                </p>
                                <p class="mb-0">
                                    <strong>Status:</strong> 
                                    <span class="status-badge status-<?php echo strtolower($appointment['status']); ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <?php if ($appointment['status'] === 'pending' || $appointment['status'] === 'accepted'): ?>
                        <div class="text-end">
                            <button type="button" class="btn btn-cancel" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                <i class="fas fa-times"></i> Cancel Appointment
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel this appointment?</p>
                    <p class="text-muted small">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep it</button>
                    <form action="cancel_appointment.php" method="POST" class="d-inline">
                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                        <button type="submit" class="btn btn-danger">Yes, Cancel Appointment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 