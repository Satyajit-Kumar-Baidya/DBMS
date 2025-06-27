<?php
session_start();
require_once '../dbConnect.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    header("Location: ../index.php");
    exit();
}

$user = $_SESSION['user'];
$patientId = null;

// Get patient ID
try {
    $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $patientId = $stmt->fetchColumn();

    if (!$patientId) {
        // If patient record not found, log out user
        session_destroy();
        header("Location: ../index.php");
        exit();
    }
} catch (PDOException $e) {
    // If database error, log out user
    session_destroy();
    header("Location: ../index.php");
    exit();
}

// Handle date filter
$filter_start = $_GET['filter_start'] ?? '';
$filter_end = $_GET['filter_end'] ?? '';
$filter_sql = '';
$filter_params = [$patientId];
if ($filter_start) {
    $filter_sql .= ' AND log_date >= ?';
    $filter_params[] = $filter_start;
}
if ($filter_end) {
    $filter_sql .= ' AND log_date <= ?';
    $filter_params[] = $filter_end;
}

// Fetch patient's health log data
$healthLog = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM health_logs WHERE patient_id = ? $filter_sql ORDER BY log_date DESC");
    $stmt->execute($filter_params);
    $healthLog = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle database error
    echo "Database Error: " . $e->getMessage();
}

$error_message = '';
// Handle add new health log entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_health_log'])) {
    $log_date = $_POST['log_date'] ?? date('Y-m-d');
    $weight_kg = $_POST['weight_kg'] !== '' ? $_POST['weight_kg'] : null;
    $bp_sys = $_POST['blood_pressure_systolic'] !== '' ? $_POST['blood_pressure_systolic'] : null;
    $bp_dia = $_POST['blood_pressure_diastolic'] !== '' ? $_POST['blood_pressure_diastolic'] : null;
    $glucose = $_POST['glucose_mgdl'] !== '' ? $_POST['glucose_mgdl'] : null;
    $temp = $_POST['temperature_c'] !== '' ? $_POST['temperature_c'] : null;
    $hr = $_POST['heart_rate'] !== '' ? $_POST['heart_rate'] : null;
    $mood = $_POST['mood'] ?? null;
    $symptoms = $_POST['symptoms'] ?? null;
    $notes = $_POST['notes'] ?? null;
    try {
        $stmt = $pdo->prepare("INSERT INTO health_logs (patient_id, log_date, weight_kg, blood_pressure_systolic, blood_pressure_diastolic, glucose_mgdl, temperature_c, heart_rate, mood, symptoms, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$patientId, $log_date, $weight_kg, $bp_sys, $bp_dia, $glucose, $temp, $hr, $mood, $symptoms, $notes]);
        header("Location: health-log.php");
        exit();
    } catch (PDOException $e) {
        $error_message = 'Error adding health log entry: ' . $e->getMessage();
    }
}
// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM health_logs WHERE id = ? AND patient_id = ?");
    $stmt->execute([$delId, $patientId]);
    header("Location: health-log.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Log - Healthcare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <a href="medical-records.php"><i class="fas fa-file-medical"></i> Medical Records</a>
                    <a href="prescriptions.php"><i class="fas fa-prescription"></i> Prescriptions</a>
                    <a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a>
                    <a href="health-log.php" class="active"><i class="fas fa-heartbeat"></i> Health Log</a>
                    <a href="../profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="welcome-section">
                    <h2>Health Log</h2>
                    <p>Log and view your health data.</p>
                </div>

                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
                                <h5 class="mb-0">Health Trends</h5>
                                <form class="d-flex flex-wrap gap-2" method="get" action="">
                                    <input type="date" class="form-control form-control-sm" name="filter_start" value="<?php echo htmlspecialchars($filter_start); ?>" placeholder="Start date">
                                    <input type="date" class="form-control form-control-sm" name="filter_end" value="<?php echo htmlspecialchars($filter_end); ?>" placeholder="End date">
                                    <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                                    <a href="health-log.php" class="btn btn-sm btn-secondary">Clear</a>
                                </form>
                            </div>
                            <div class="card-body">
                                <canvas id="healthChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header"><h5 class="mb-0">Add Health Log Entry</h5></div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="add_health_log" value="1">
                                    <div class="mb-2">
                                        <label class="form-label">Date</label>
                                        <input type="date" class="form-control" name="log_date" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Weight (kg)</label>
                                        <input type="number" step="0.1" class="form-control" name="weight_kg">
                                    </div>
                                    <div class="mb-2 row">
                                        <div class="col">
                                            <label class="form-label">Blood Pressure Systolic</label>
                                            <input type="number" class="form-control" name="blood_pressure_systolic" placeholder="Systolic">
                                        </div>
                                        <div class="col">
                                            <label class="form-label">Diastolic</label>
                                            <input type="number" class="form-control" name="blood_pressure_diastolic" placeholder="Diastolic">
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Glucose (mg/dL)</label>
                                        <input type="number" step="0.1" class="form-control" name="glucose_mgdl">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Temperature (°C)</label>
                                        <input type="number" step="0.1" class="form-control" name="temperature_c">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Heart Rate (bpm)</label>
                                        <input type="number" class="form-control" name="heart_rate">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Mood</label>
                                        <input type="text" class="form-control" name="mood" placeholder="e.g. Happy, Sad, Anxious">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Symptoms</label>
                                        <textarea class="form-control" name="symptoms" rows="2" placeholder="Describe any symptoms"></textarea>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Notes</label>
                                        <textarea class="form-control" name="notes" rows="2" placeholder="Additional notes"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100">Add Entry</button>
                                </form>
                                <?php if (!empty($error_message)): ?>
                                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Health Log Entries</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($healthLog)): ?>
                                    <div class="alert alert-info">No health log entries found.</div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Weight</th>
                                                <th>BP</th>
                                                <th>Glucose</th>
                                                <th>Temp</th>
                                                <th>HR</th>
                                                <th>Mood</th>
                                                <th>Symptoms</th>
                                                <th>Notes</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($healthLog as $log): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($log['log_date']); ?></td>
                                                    <td><?php echo htmlspecialchars($log['weight_kg']); ?></td>
                                                    <td><?php echo htmlspecialchars($log['blood_pressure_systolic']); ?>/<?php echo htmlspecialchars($log['blood_pressure_diastolic']); ?></td>
                                                    <td><?php echo htmlspecialchars($log['glucose_mgdl']); ?></td>
                                                    <td><?php echo htmlspecialchars($log['temperature_c']); ?></td>
                                                    <td><?php echo htmlspecialchars($log['heart_rate']); ?></td>
                                                    <td><?php echo htmlspecialchars($log['mood']); ?></td>
                                                    <td><?php echo htmlspecialchars($log['symptoms']); ?></td>
                                                    <td><?php echo htmlspecialchars($log['notes']); ?></td>
                                                    <td><a href="?delete=<?php echo $log['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this entry?');">Delete</a></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../script.js"></script>
    <script>
    // Prepare data for chart
    const healthLog = <?php echo json_encode(array_reverse($healthLog)); ?>;
    const labels = healthLog.map(e => e.log_date);
    const weight = healthLog.map(e => e.weight_kg ? parseFloat(e.weight_kg) : null);
    const bpSys = healthLog.map(e => e.blood_pressure_systolic ? parseInt(e.blood_pressure_systolic) : null);
    const bpDia = healthLog.map(e => e.blood_pressure_diastolic ? parseInt(e.blood_pressure_diastolic) : null);
    const glucose = healthLog.map(e => e.glucose_mgdl ? parseFloat(e.glucose_mgdl) : null);
    const temp = healthLog.map(e => e.temperature_c ? parseFloat(e.temperature_c) : null);
    const hr = healthLog.map(e => e.heart_rate ? parseInt(e.heart_rate) : null);

    const ctx = document.getElementById('healthChart').getContext('2d');
    const healthChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Weight (kg)',
                    data: weight,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    yAxisID: 'y',
                    spanGaps: true
                },
                {
                    label: 'BP Systolic',
                    data: bpSys,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    yAxisID: 'y1',
                    spanGaps: true
                },
                {
                    label: 'BP Diastolic',
                    data: bpDia,
                    borderColor: 'rgba(255, 159, 64, 1)',
                    backgroundColor: 'rgba(255, 159, 64, 0.1)',
                    yAxisID: 'y1',
                    spanGaps: true
                },
                {
                    label: 'Glucose (mg/dL)',
                    data: glucose,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    yAxisID: 'y2',
                    spanGaps: true
                },
                {
                    label: 'Temperature (°C)',
                    data: temp,
                    borderColor: 'rgba(153, 102, 255, 1)',
                    backgroundColor: 'rgba(153, 102, 255, 0.1)',
                    yAxisID: 'y3',
                    spanGaps: true
                },
                {
                    label: 'Heart Rate (bpm)',
                    data: hr,
                    borderColor: 'rgba(255, 206, 86, 1)',
                    backgroundColor: 'rgba(255, 206, 86, 0.1)',
                    yAxisID: 'y4',
                    spanGaps: true
                }
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            stacked: false,
            plugins: {
                legend: { position: 'top' },
                title: { display: false }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: { display: true, text: 'Weight (kg)' }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    title: { display: true, text: 'Blood Pressure (mmHg)' }
                },
                y2: {
                    type: 'linear',
                    display: false,
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    title: { display: true, text: 'Glucose (mg/dL)' }
                },
                y3: {
                    type: 'linear',
                    display: false,
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    title: { display: true, text: 'Temperature (°C)' }
                },
                y4: {
                    type: 'linear',
                    display: false,
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    title: { display: true, text: 'Heart Rate (bpm)' }
                }
            }
        }
    });
    </script>
</body>
</html> 