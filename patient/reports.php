<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../dbConnect.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    header("Location: ../index.php");
    exit();
}

$user = $_SESSION['user'];
$patientId = null;
$reports = [];
$errors = [];

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

    // Get all reports for the patient
    $stmt = $pdo->prepare("SELECT * FROM reports WHERE patient_id = ? ORDER BY created_at DESC");
    $stmt->execute([$patientId]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errors[] = "Database Error: " . $e->getMessage();
}

// Function to generate PDF report
function generatePDFReport($data) {
    // In a real application, you would use a PDF library like TCPDF or FPDF
    // For this example, we'll just return a dummy PDF path
    return 'reports/' . uniqid() . '.pdf';
}

// Function to download file
function downloadFile($filePath) {
    if (file_exists($filePath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
    return false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Reports - Healthcare System</title>
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
                    <h2>Medical Reports</h2>
                    <p>Download your medical reports, test results, and insurance documents.</p>
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

                <!-- Reports List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Available Reports</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($reports)): ?>
                            <p class="text-center">No reports available.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Report Type</th>
                                            <th>Description</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reports as $report): ?>
                                            <tr>
                                                <td>
                                                    <i class="fas fa-<?php 
                                                        echo $report['report_type'] === 'Test Result' ? 'flask' : 
                                                            ($report['report_type'] === 'Consultation' ? 'stethoscope' : 'file-medical'); 
                                                    ?>"></i>
                                                    <?php echo htmlspecialchars($report['report_type']); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($report['description'] ?? 'No description available'); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($report['created_at'])); ?></td>
                                                <td>
                                                    <a href="download_report.php?id=<?php echo $report['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-download"></i> Download
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Request New Report -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Request New Report</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="request_report.php">
                            <div class="mb-3">
                                <label for="report_type" class="form-label">Report Type</label>
                                <select class="form-select" id="report_type" name="report_type" required>
                                    <option value="">Select Report Type</option>
                                    <option value="Test Result">Test Result</option>
                                    <option value="Consultation">Consultation Summary</option>
                                    <option value="Insurance">Insurance Document</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Request Report</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../script.js"></script>
</body>
</html> 