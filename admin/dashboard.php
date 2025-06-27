<?php
session_start();
require_once '../dbConnect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Get admin user info
$user = $_SESSION['user'];

// Fetch statistics
try {
    $stats = [
        'total_patients' => $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn(),
        'total_doctors' => $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn(),
        'total_appointments' => $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn(),
        'total_ambulances' => $pdo->query("SELECT COUNT(*) FROM ambulances")->fetchColumn()
    ];
} catch (PDOException $e) {
    $error = "Error fetching statistics: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Healthcare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            color: white;
            padding-top: 20px;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .main-content {
            padding: 20px;
        }
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            color: white;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .welcome-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <h3 class="text-center mb-4">Admin Panel</h3>
                <nav>
                    <a href="dashboard.php" class="active">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href="manage_doctors.php">
                        <i class="fas fa-user-md"></i> Doctors
                    </a>
                    <a href="manage_patients.php">
                        <i class="fas fa-procedures"></i> Patients
                    </a>
                    <a href="manage_appointments.php">
                        <i class="fas fa-calendar-check"></i> Appointments
                    </a>
                    <a href="manage_ambulances.php">
                        <i class="fas fa-ambulance"></i> Ambulances
                    </a>
                    <a href="reports.php">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                    <a href="settings.php">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                    <a href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="welcome-section">
                    <h2>Welcome, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>!</h2>
                    <p>Here's your healthcare system overview.</p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card bg-primary">
                            <i class="fas fa-procedures"></i>
                            <h3><?php echo $stats['total_patients'] ?? 0; ?></h3>
                            <p>Total Patients</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-success">
                            <i class="fas fa-user-md"></i>
                            <h3><?php echo $stats['total_doctors'] ?? 0; ?></h3>
                            <p>Total Doctors</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-info">
                            <i class="fas fa-calendar-check"></i>
                            <h3><?php echo $stats['total_appointments'] ?? 0; ?></h3>
                            <p>Total Appointments</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-warning">
                            <i class="fas fa-ambulance"></i>
                            <h3><?php echo $stats['total_ambulances'] ?? 0; ?></h3>
                            <p>Total Ambulances</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <a href="manage_doctors.php?action=add" class="btn btn-primary w-100">
                                            <i class="fas fa-user-md"></i> Add Doctor
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="manage_patients.php?action=add" class="btn btn-success w-100">
                                            <i class="fas fa-user-plus"></i> Add Patient
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="manage_appointments.php?action=add" class="btn btn-info w-100">
                                            <i class="fas fa-calendar-plus"></i> Schedule Appointment
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="manage_ambulances.php?action=add" class="btn btn-warning w-100">
                                            <i class="fas fa-ambulance"></i> Add Ambulance
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 