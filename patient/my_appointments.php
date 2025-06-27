<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();
require_once '../dbConnect.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../index.php");
    exit();
}

// Fetch all appointments with full details
$appointments = [];
$patient_id = $_SESSION['user']['id'];
if (($handle = fopen('../appointments.txt', 'r')) !== false) {
    while (($data = fgetcsv($handle)) !== false) {
        // [doctor_id, patient_id, appointment_date, appointment_time, reason, status, created_at]
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

echo '<div style="background: #ffe0e0; color: #b00; padding: 10px; margin: 10px 0;">DEBUG: Session user id: ' . htmlspecialchars($patient_id) . '<br>Appointments loaded:<br><pre>';
foreach ($appointments as $apt) {
    echo htmlspecialchars(json_encode($apt)) . "\n";
}
echo '</pre></div>';
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
        .doctor-profile-compact {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .doctor-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .doctor-avatar i {
            font-size: 30px;
            color: #6c757d;
        }
        .doctor-info {
            flex: 1;
        }
        .status-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
        }
        .appointment-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .tab-content {
            background: white;
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 0.25rem 0.25rem;
            padding: 1rem;
        }
        .nav-tabs .nav-link {
            font-weight: 500;
        }
        .nav-tabs .nav-link.active {
            border-bottom: 3px solid #0d6efd;
        }
        .appointment-date {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .prescription-section {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
        }
        .medicine-list {
            list-style: none;
            padding: 0;
        }
        .medicine-item {
            background: white;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            border: 1px solid #dee2e6;
        }
        .doctor-details-modal .modal-body {
            max-height: 80vh;
            overflow-y: auto;
        }
        .qualification-badge {
            background-color: #e9ecef;
            color: #495057;
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            display: inline-block;
        }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0">My Appointments</h2>
                <p class="text-muted mb-0">View and manage your appointments</p>
            </div>
            <a href="browse_doctors.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Book New Appointment
            </a>
        </div>

        <!-- Appointment Tabs -->
        <ul class="nav nav-tabs" id="appointmentTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">
                    Pending Requests <span class="badge bg-warning text-dark ms-2"><?php echo count($appointments); ?></span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="appointmentTabsContent">
            <!-- Pending Appointments -->
            <div class="tab-pane fade show active" id="pending" role="tabpanel">
                <?php if (empty($appointments)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-calendar-check text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3">No pending appointment requests</p>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($appointments as $apt): ?>
                            <div class="col-12">
                                <div class="card appointment-card">
                                    <div class="card-body">
                                        <span class="status-badge badge bg-warning text-dark">Pending Approval</span>
                                        
                                        <div class="doctor-profile-compact">
                                            <div class="doctor-avatar">
                                                <?php if ($apt['profile_image']): ?>
                                                    <img src="<?php echo htmlspecialchars($apt['profile_image']); ?>" alt="Doctor" class="img-fluid">
                                                <?php else: ?>
                                                    <i class="bi bi-person-circle"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="doctor-info">
                                                <h5 class="mb-1">Doctor ID: <?php echo htmlspecialchars($apt['doctor_id']); ?></h5>
                                                <p class="mb-1">Specialization: N/A</p>
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

                                        <div class="appointment-actions">
                                            <button type="button" class="btn btn-outline-primary" 
                                                    onclick="viewDoctorDetails(<?php echo htmlspecialchars(json_encode($apt)); ?>)">
                                                <i class="bi bi-info-circle"></i> Doctor Details
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" disabled>
                                                <i class="bi bi-clock-history"></i> Awaiting Response
                                            </button>
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

    <!-- Doctor Details Modal -->
    <div class="modal fade" id="doctorDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Doctor Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="doctor-avatar mb-3" style="width: 150px; height: 150px; margin: 0 auto;">
                                <i class="bi bi-person-circle"></i>
                            </div>
                            <h4 id="doctorName"></h4>
                            <p id="doctorSpecialization" class="text-primary"></p>
                            <div class="d-flex justify-content-center gap-2 mb-3">
                                <span class="badge bg-primary" id="doctorExperience"></span>
                                <span class="badge bg-info" id="doctorPatients"></span>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="doctor-info">
                                <h5>Professional Information</h5>
                                <div id="doctorQualifications" class="mb-3"></div>
                                <p><strong><i class="bi bi-hospital"></i> Hospital:</strong> <span id="doctorHospital"></span></p>
                                <p><strong><i class="bi bi-geo-alt"></i> Location:</strong> <span id="doctorLocation"></span></p>
                                <p><strong><i class="bi bi-currency-dollar"></i> Consultation Fee:</strong> $<span id="doctorFee"></span></p>
                                <p><strong><i class="bi bi-calendar-week"></i> Available Days:</strong> <span id="doctorDays"></span></p>
                                
                                <div class="mt-4">
                                    <h5>Background & Expertise</h5>
                                    <p id="doctorBackground"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="browse_doctors.php" class="btn btn-primary">
                        <i class="bi bi-calendar-plus"></i> Book New Appointment
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewDoctorDetails(doctor) {
            document.getElementById('doctorName').textContent = 'Dr. ' + doctor.doctor_first_name + ' ' + doctor.doctor_last_name;
            document.getElementById('doctorSpecialization').textContent = doctor.specialization;
            document.getElementById('doctorExperience').innerHTML = '<i class="bi bi-star-fill"></i> ' + doctor.experience + '+ Years Experience';
            document.getElementById('doctorHospital').textContent = doctor.hospital;
            document.getElementById('doctorLocation').textContent = doctor.location;
            document.getElementById('doctorFee').textContent = doctor.consultation_fee;
            document.getElementById('doctorDays').textContent = doctor.available_days;
            document.getElementById('doctorBackground').textContent = doctor.background;

            // Split qualifications and create badges
            const qualifications = doctor.qualification.split(',').map(q => q.trim());
            const qualificationsHtml = qualifications.map(q => 
                `<span class="qualification-badge">${q}</span>`
            ).join('');
            document.getElementById('doctorQualifications').innerHTML = qualificationsHtml;

            new bootstrap.Modal(document.getElementById('doctorDetailsModal')).show();
        }

        function printPrescription(appointmentId) {
            // Implement prescription printing functionality
            window.open(`print_prescription.php?id=${appointmentId}`, '_blank');
        }

        // Show active tab based on URL hash or default to pending
        document.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash || '#pending';
            const tab = new bootstrap.Tab(document.querySelector(`[data-bs-target="${hash}"]`));
            tab.show();
        });
    </script>
</body>
</html> 