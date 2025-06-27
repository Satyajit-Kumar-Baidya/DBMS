<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();
require_once '../dbConnect.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    header("Location: ../index.php");
    exit();
}

$user = $_SESSION['user'];

// Get search parameters
$search = $_GET['search'] ?? '';
$specialization = $_GET['specialization'] ?? '';
$location = $_GET['location'] ?? '';

// Build the query
$query = "
    SELECT d.*, u.first_name, u.last_name, u.email,
           COUNT(DISTINCT a.id) as total_patients
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    LEFT JOIN appointments a ON d.id = a.doctor_id
    WHERE d.status = 'active'
";
$params = [];

if ($search) {
    $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR d.specialization LIKE ? OR d.hospital LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if ($specialization) {
    $query .= " AND d.specialization = ?";
    $params[] = $specialization;
}

if ($location) {
    $query .= " AND d.location = ?";
    $params[] = $location;
}

$query .= " GROUP BY d.id ORDER BY d.experience DESC, total_patients DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get unique specializations and locations for filters
    $stmt = $pdo->query("SELECT DISTINCT specialization FROM doctors ORDER BY specialization");
    $specializations = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->query("SELECT DISTINCT location FROM doctors ORDER BY location");
    $locations = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Doctors - Healthcare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        .doctor-card {
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }
        .doctor-card:hover {
            transform: translateY(-5px);
        }
        .doctor-avatar {
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
            border-radius: 50%;
            overflow: hidden;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .doctor-avatar i {
            font-size: 4rem;
            color: #adb5bd;
        }
        .qualification-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            margin: 0.25rem;
            background-color: #e9ecef;
            border-radius: 20px;
            font-size: 0.875rem;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
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
                    <a href="../dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a>
                    <a href="appointments.php"><i class="bi bi-calendar-check"></i> My Appointments</a>
                    <a href="browse_doctors.php" class="active"><i class="bi bi-search"></i> Find Doctors</a>
                    <a href="medical_history.php"><i class="bi bi-file-medical"></i> Medical History</a>
                    <a href="../profile.php"><i class="bi bi-person"></i> Profile</a>
                    <a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="welcome-section">
                    <h2>Find a Doctor</h2>
                    <p>Browse through our list of qualified healthcare professionals.</p>
                </div>

                <!-- Search and Filter Section -->
                <div class="filter-section">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Search</label>
                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, specialization...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Specialization</label>
                            <select class="form-select" name="specialization">
                                <option value="">All Specializations</option>
                                <?php foreach ($specializations as $spec): ?>
                                    <option value="<?php echo htmlspecialchars($spec); ?>" <?php echo $specialization === $spec ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($spec); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Location</label>
                            <select class="form-select" name="location">
                                <option value="">All Locations</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo $location === $loc ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($loc); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">Search</button>
                        </div>
                    </form>
                </div>

                <!-- Doctors List -->
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php elseif (empty($doctors)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-search" style="font-size: 3rem; color: #6c757d;"></i>
                        <p class="text-muted mt-3">No doctors found matching your criteria</p>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($doctors as $doctor): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card doctor-card h-100">
                                    <div class="card-body text-center">
                                        <div class="doctor-avatar">
                                            <i class="bi bi-person-circle"></i>
                                        </div>
                                        <h4 class="mb-2">Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></h4>
                                        <p class="text-primary mb-3"><?php echo htmlspecialchars($doctor['specialization']); ?></p>
                                        
                                        <div class="mb-3">
                                            <span class="badge bg-primary">
                                                <i class="bi bi-star-fill"></i> <?php echo $doctor['experience']; ?>+ Years Experience
                                            </span>
                                            <span class="badge bg-info">
                                                <i class="bi bi-people-fill"></i> <?php echo $doctor['total_patients']; ?> Patients
                                            </span>
                                        </div>

                                        <div class="mb-3 text-start">
                                            <p class="mb-1"><i class="bi bi-hospital"></i> <?php echo htmlspecialchars($doctor['hospital']); ?></p>
                                            <p class="mb-1"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($doctor['location']); ?></p>
                                            <p class="mb-1"><i class="bi bi-currency-dollar"></i> Consultation Fee: $<?php echo htmlspecialchars($doctor['consultation_fee']); ?></p>
                                        </div>

                                        <div class="d-grid gap-2">
                                            <button type="button" class="btn btn-outline-primary" onclick="viewDoctorProfile(<?php echo htmlspecialchars(json_encode($doctor)); ?>)">
                                                View Profile
                                            </button>
                                            <a href="book_appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-primary">
                                                Book Appointment
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

    <!-- Doctor Profile Modal -->
    <div class="modal fade" id="doctorProfileModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Doctor Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="doctor-avatar mb-3">
                                <i class="bi bi-person-circle"></i>
                            </div>
                            <h4 id="modalDoctorName"></h4>
                            <p id="modalSpecialization" class="text-primary"></p>
                        </div>
                        <div class="col-md-8">
                            <div class="mb-4">
                                <h5>Professional Background</h5>
                                <p id="modalBackground"></p>
                            </div>
                            <div class="mb-4">
                                <h5>Qualifications</h5>
                                <div id="modalQualifications"></div>
                            </div>
                            <div class="mb-4">
                                <h5>Practice Information</h5>
                                <p><strong>Hospital/Clinic:</strong> <span id="modalHospital"></span></p>
                                <p><strong>Location:</strong> <span id="modalLocation"></span></p>
                                <p><strong>Available Days:</strong> <span id="modalAvailableDays"></span></p>
                                <p><strong>Consultation Hours:</strong> <span id="modalAvailability"></span></p>
                                <p><strong>Consultation Fee:</strong> $<span id="modalFee"></span></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="#" id="modalBookAppointment" class="btn btn-primary">Book Appointment</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewDoctorProfile(doctor) {
            document.getElementById('modalDoctorName').textContent = 'Dr. ' + doctor.first_name + ' ' + doctor.last_name;
            document.getElementById('modalSpecialization').textContent = doctor.specialization;
            document.getElementById('modalBackground').textContent = doctor.background || 'No background information available.';
            
            // Format qualifications
            const qualificationsDiv = document.getElementById('modalQualifications');
            qualificationsDiv.innerHTML = '';
            if (doctor.qualification) {
                doctor.qualification.split(',').forEach(qual => {
                    const badge = document.createElement('span');
                    badge.className = 'qualification-badge';
                    badge.textContent = qual.trim();
                    qualificationsDiv.appendChild(badge);
                });
            } else {
                qualificationsDiv.textContent = 'No qualification information available.';
            }

            document.getElementById('modalHospital').textContent = doctor.hospital;
            document.getElementById('modalLocation').textContent = doctor.location;
            document.getElementById('modalAvailableDays').textContent = doctor.available_days;
            document.getElementById('modalAvailability').textContent = doctor.availability;
            document.getElementById('modalFee').textContent = doctor.consultation_fee;
            
            document.getElementById('modalBookAppointment').href = 'book_appointment.php?doctor_id=' + doctor.id;

            new bootstrap.Modal(document.getElementById('doctorProfileModal')).show();
        }
    </script>
</body>
</html> 