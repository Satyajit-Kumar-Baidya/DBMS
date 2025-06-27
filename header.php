<?php
require_once 'includes/session.php';
require_once 'dbConnect.php';

checkSessionTimeout();
$isLoggedIn = isLoggedIn();
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Healthcare System</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php if ($isLoggedIn): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-hospital"></i> Healthcare System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (getUserRole() == 'admin' || getUserRole() == 'doctor'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="patients.php">
                            <i class="fas fa-users"></i> Patients
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (getUserRole() == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="doctors.php">
                            <i class="fas fa-user-md"></i> Doctors
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="appointments.php">
                            <i class="fas fa-calendar-check"></i> Appointments
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="chatbot.php">
                            <i class="fas fa-robot"></i> Chatbot
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="blood_bank/index.php">
                            <i class="fas fa-tint"></i> Blood Bank
                        </a>
                    </li>
                    
                    <?php if (getUserRole() == 'patient'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="medical_records.php">
                            <i class="fas fa-file-medical"></i> Medical Records
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="prescriptions.php">
                            <i class="fas fa-prescription"></i> Prescriptions
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($currentUser['first_name']); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    <div class="container mt-4">
</body>
</html> 