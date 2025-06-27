<?php
session_start();
require_once 'dbConnect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$user = $_SESSION['user'];

// Initialize profile data with user session data
$profileData = $_SESSION['user'];

// Fetch additional profile data for patient/doctor
if ($user['role'] === 'patient') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM patients WHERE user_id = ? LIMIT 1");
        $stmt->execute([$user['id']]);
        $patientData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($patientData) {
            // Merge patient data, ensuring all expected keys are present
            $profileData = array_merge([
                'address' => '',
                'dob' => '',
                'gender' => '',
                'emergency_contact' => '',
                'blood_group' => '',
                'patient_id' => $patientData['id']
            ], $profileData, $patientData);
        }
    } catch (PDOException $e) {
         $errors[] = 'Database error fetching patient data: ' . $e->getMessage();
    }
} elseif ($user['role'] === 'doctor') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM doctors WHERE user_id = ? LIMIT 1");
        $stmt->execute([$user['id']]);
        $doctorData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($doctorData) {
             // Merge doctor data, ensuring all expected keys are present
            $profileData = array_merge([
                'specialization' => '',
                'qualification' => '',
                'experience' => '',
                'availability' => '',
                'hospital' => '',
                'location' => '',
                'consultation_fee' => '',
                'background' => '',
                'available_days' => ''
            ], $profileData, $doctorData);
        }
    } catch (PDOException $e) {
         $errors[] = 'Database error fetching doctor data: ' . $e->getMessage();
    }
}

// Handle profile updates (if form is submitted)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    // Basic validation
    $errors = [];
    if (empty($first_name)) $errors[] = 'First name is required.';
    if (empty($last_name)) $errors[] = 'Last name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';

    if (empty($errors)) {
        try {
            // Update users table
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $email, $user['id']]);

            // Update patient/doctor specific table if needed
            if ($user['role'] === 'patient') {
                $address = $_POST['address'] ?? '';
                $dob = $_POST['dob'] ?? '';
                $gender = $_POST['gender'] ?? '';
                $emergency_contact = $_POST['emergency_contact'] ?? '';
                $blood_group = $_POST['blood_group'] ?? '';
                
                // Check if patient record exists before updating
                $stmtCheck = $pdo->prepare("SELECT id FROM patients WHERE user_id = ? LIMIT 1");
                $stmtCheck->execute([$user['id']]);
                if ($stmtCheck->fetchColumn()) {
                    $stmt = $pdo->prepare("UPDATE patients SET address = ?, dob = ?, gender = ?, emergency_contact = ?, blood_group = ? WHERE user_id = ?");
                    $stmt->execute([$address, $dob, $gender, $emergency_contact, $blood_group, $user['id']]);
                } else {
                     // If patient record doesn't exist, create it
                     $stmtInsert = $pdo->prepare("INSERT INTO patients (user_id, address, dob, gender, emergency_contact, blood_group) VALUES (?, ?, ?, ?, ?, ?)");
                     $stmtInsert->execute([$user['id'], $address, $dob, $gender, $emergency_contact, $blood_group]);
                }

            } elseif ($user['role'] === 'doctor') {
                 $specialization = $_POST['specialization'] ?? '';
                 $qualification = $_POST['qualification'] ?? '';
                 $experience = $_POST['experience'] ?? '';
                 $availability = $_POST['availability'] ?? '';
                 $hospital = $_POST['hospital'] ?? '';
                 $location = $_POST['location'] ?? '';
                 $consultation_fee = $_POST['consultation_fee'] ?? '';
                 $background = $_POST['background'] ?? '';
                 $available_days = $_POST['available_days'] ?? '';

                 // Check if doctor record exists before updating
                 $stmtCheck = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ? LIMIT 1");
                 $stmtCheck->execute([$user['id']]);
                 if ($stmtCheck->fetchColumn()) {
                    $stmt = $pdo->prepare("UPDATE doctors SET specialization = ?, qualification = ?, experience = ?, availability = ?, hospital = ?, location = ?, consultation_fee = ?, background = ?, available_days = ? WHERE user_id = ?");
                    $stmt->execute([$specialization, $qualification, $experience, $availability, $hospital, $location, $consultation_fee, $background, $available_days, $user['id']]);
                 } else {
                     // If doctor record doesn't exist, create it
                     $stmtInsert = $pdo->prepare("INSERT INTO doctors (user_id, specialization, qualification, experience, availability, hospital, location, consultation_fee, background, available_days) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                     $stmtInsert->execute([$user['id'], $specialization, $qualification, $experience, $availability, $hospital, $location, $consultation_fee, $background, $available_days]);
                 }
            }

            // Refresh session data after update
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $_SESSION['user'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Re-fetch and merge profile data to include updated patient/doctor info
            $profileData = $_SESSION['user'];
            if ($user['role'] === 'patient') {
                $stmt = $pdo->prepare("SELECT * FROM patients WHERE user_id = ? LIMIT 1");
                $stmt->execute([$user['id']]);
                $patientData = $stmt->fetch(PDO::FETCH_ASSOC);
                 if ($patientData) {
                    $profileData = array_merge([
                        'address' => '',
                        'dob' => '',
                        'gender' => '',
                        'emergency_contact' => '',
                        'blood_group' => '',
                        'patient_id' => $patientData['id']
                    ], $profileData, $patientData);
                }
            } elseif ($user['role'] === 'doctor') {
                $stmt = $pdo->prepare("SELECT * FROM doctors WHERE user_id = ? LIMIT 1");
                $stmt->execute([$user['id']]);
                $doctorData = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($doctorData) {
                    $profileData = array_merge([
                        'specialization' => '',
                        'qualification' => '',
                        'experience' => '',
                        'availability' => '',
                        'hospital' => '',
                        'location' => '',
                        'consultation_fee' => '',
                        'background' => '',
                        'available_days' => ''
                    ], $profileData, $doctorData);
                }
            }

            $success_message = 'Profile updated successfully.';

        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    } else {
         $error_message = 'Please fix the following errors: ' . implode(', ', $errors);
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Healthcare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <h3 class="text-center mb-4">Healthcare</h3>
                <nav>
                    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    <?php if ($user['role'] === 'admin'): ?>
                        <a href="admin/users.php"><i class="fas fa-users"></i> User Management</a>
                        <a href="admin/doctors.php"><i class="fas fa-user-md"></i> Doctors</a>
                        <a href="admin/patients.php"><i class="fas fa-procedures"></i> Patients</a>
                        <a href="admin/appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a>
                        <a href="admin/medicines.php"><i class="fas fa-pills"></i> Medicines</a>
                        <a href="admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                    <?php elseif ($user['role'] === 'doctor'): ?>
                        <a href="doctor/appointments.php"><i class="fas fa-calendar-check"></i> My Appointments</a>
                        <a href="doctor/patients.php"><i class="fas fa-procedures"></i> My Patients</a>
                        <a href="doctor/prescriptions.php"><i class="fas fa-prescription"></i> Prescriptions</a>
                        <a href="doctor/medical-records.php"><i class="fas fa-file-medical"></i> Medical Records</a>
                    <?php else: // patient ?>
                        <a href="patient/appointments.php"><i class="fas fa-calendar-check"></i> My Appointments</a>
                        <a href="patient/medical-records.php"><i class="fas fa-file-medical"></i> Medical Records</a>
                        <a href="patient/prescriptions.php"><i class="fas fa-prescription"></i> Prescriptions</a>
                        <a href="patient/payments.php"><i class="fas fa-credit-card"></i> Payments</a>
                        <a href="patient/health-log.php"><i class="fas fa-heartbeat"></i> Health Log</a>
                    <?php endif; ?>
                    <a href="profile.php" class="active"><i class="fas fa-user"></i> Profile</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="welcome-section">
                    <h2>Welcome, <?php echo htmlspecialchars($profileData['first_name']); ?>!</h2>
                    <?php if ($user['role'] === 'patient' && isset($profileData['id'])): ?>
                        <p class="mb-0">Your Patient ID: <strong><?php echo htmlspecialchars($profileData['id']); ?></strong></p>
                        <small class="text-muted">Keep this ID handy - doctors will use it to access your medical records.</small>
                    <?php endif; ?>
                </div>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Profile Information</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($user['role'] === 'patient' && isset($profileData['id'])): ?>
                            <div class="alert alert-info mb-4">
                                <strong>Your Patient ID:</strong> <?php echo htmlspecialchars($profileData['id']); ?>
                                <br>
                                <small class="text-muted">This ID is required when doctors need to access your medical records.</small>
                            </div>
                        <?php endif; ?>

                        <form action="profile.php" method="POST">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($profileData['first_name'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($profileData['last_name'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($profileData['email'] ?? ''); ?>" required>
                            </div>

                            <?php if ($user['role'] === 'patient'): ?>
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($profileData['address'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="dob" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($profileData['dob'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select class="form-select" id="gender" name="gender">
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?php echo ((($profileData['gender'] ?? '') === 'Male')) ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo ((($profileData['gender'] ?? '') === 'Female')) ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo ((($profileData['gender'] ?? '') === 'Other')) ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="emergency_contact" class="form-label">Emergency Contact</label>
                                    <input type="text" class="form-control" id="emergency_contact" name="emergency_contact" value="<?php echo htmlspecialchars($profileData['emergency_contact'] ?? ''); ?>">
                                </div>
                                 <div class="mb-3">
                                    <label for="blood_group" class="form-label">Blood Group</label>
                                    <input type="text" class="form-control" id="blood_group" name="blood_group" value="<?php echo htmlspecialchars($profileData['blood_group'] ?? ''); ?>">
                                </div>
                            <?php elseif ($user['role'] === 'doctor'): ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="specialization" class="form-label">Specialization</label>
                                            <input type="text" class="form-control" id="specialization" name="specialization" value="<?php echo htmlspecialchars($profileData['specialization'] ?? ''); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="qualification" class="form-label">Qualifications</label>
                                            <input type="text" class="form-control" id="qualification" name="qualification" value="<?php echo htmlspecialchars($profileData['qualification'] ?? ''); ?>" required placeholder="e.g. MBBS, MD, MS">
                                        </div>
                                        <div class="mb-3">
                                            <label for="experience" class="form-label">Years of Experience</label>
                                            <input type="number" class="form-control" id="experience" name="experience" value="<?php echo htmlspecialchars($profileData['experience'] ?? ''); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="hospital" class="form-label">Hospital/Clinic Name</label>
                                            <input type="text" class="form-control" id="hospital" name="hospital" value="<?php echo htmlspecialchars($profileData['hospital'] ?? ''); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="location" class="form-label">Location</label>
                                            <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($profileData['location'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="consultation_fee" class="form-label">Consultation Fee</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control" id="consultation_fee" name="consultation_fee" value="<?php echo htmlspecialchars($profileData['consultation_fee'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="available_days" class="form-label">Available Days</label>
                                            <input type="text" class="form-control" id="available_days" name="available_days" value="<?php echo htmlspecialchars($profileData['available_days'] ?? ''); ?>" placeholder="e.g. Monday-Friday" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="availability" class="form-label">Available Hours</label>
                                            <input type="text" class="form-control" id="availability" name="availability" value="<?php echo htmlspecialchars($profileData['availability'] ?? ''); ?>" placeholder="e.g. 9:00 AM - 5:00 PM" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="background" class="form-label">Professional Background</label>
                                            <textarea class="form-control" id="background" name="background" rows="4"><?php echo htmlspecialchars($profileData['background'] ?? ''); ?></textarea>
                                            <small class="text-muted">Share your professional experience, specialties, and achievements.</small>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html> 