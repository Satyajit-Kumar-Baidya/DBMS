<?php
require_once 'db_connect.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Check if there's enough blood stock
        $stmt = $pdo->prepare("SELECT quantity FROM blood_stock WHERE blood_group = ?");
        $stmt->execute([$_POST['blood_group']]);
        $stock = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($stock && $stock['quantity'] >= $_POST['units_needed']) {
            $stmt = $pdo->prepare("INSERT INTO blood_requests (requester_name, blood_group, units_needed, 
                                                             urgency, hospital_name, contact_phone, 
                                                             contact_email, request_date) 
                                  VALUES (:requester_name, :blood_group, :units_needed, 
                                          :urgency, :hospital_name, :contact_phone, 
                                          :contact_email, :request_date)");
            
            $stmt->execute([
                'requester_name' => $_POST['requester_name'],
                'blood_group' => $_POST['blood_group'],
                'units_needed' => $_POST['units_needed'],
                'urgency' => $_POST['urgency'],
                'hospital_name' => $_POST['hospital_name'],
                'contact_phone' => $_POST['contact_phone'],
                'contact_email' => $_POST['contact_email'],
                'request_date' => date('Y-m-d')
            ]);

            $_SESSION['success'] = "Blood request submitted successfully!";
            header("Location: requests.php");
            exit();
        } else {
            $_SESSION['error'] = "Sorry, not enough blood stock available for " . $_POST['blood_group'];
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error submitting request: " . $e->getMessage();
    }
}

// Get available blood groups and their quantities
try {
    $stmt = $pdo->query("SELECT blood_group, quantity FROM blood_stock ORDER BY blood_group");
    $blood_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total number of blood requests
    $stmt = $pdo->query("SELECT COUNT(*) as total_requests FROM blood_requests");
    $total_requests = $stmt->fetch(PDO::FETCH_ASSOC)['total_requests'];
} catch(PDOException $e) {
    $blood_stock = [];
    $total_requests = 0;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Request Blood</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'navigation.php'; ?>
    
    <div class="container">
        <h2>Request Blood</h2>
        <p class="total-requests">Total Blood Requests: <?php echo $total_requests; ?></p>
        
        <?php
        if (isset($_SESSION['error'])) {
            echo "<div class='error'>" . $_SESSION['error'] . "</div>";
            unset($_SESSION['error']);
        }
        ?>

        <!-- Show available blood stock -->
        <div class="blood-stock-info">
            <h3>Available Blood Stock:</h3>
            <table class="table">
                <tr>
                    <th>Blood Group</th>
                    <th>Available Units</th>
                </tr>
                <?php foreach ($blood_stock as $stock): ?>
                <tr>
                    <td><?php echo htmlspecialchars($stock['blood_group']); ?></td>
                    <td><?php echo htmlspecialchars($stock['quantity']); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label>Requester Name:</label>
                <input type="text" name="requester_name" required>
            </div>

            <div class="form-group">
                <label>Blood Group Needed:</label>
                <select name="blood_group" required>
                    <?php foreach ($blood_stock as $stock): ?>
                    <option value="<?php echo htmlspecialchars($stock['blood_group']); ?>">
                        <?php echo htmlspecialchars($stock['blood_group']); ?> 
                        (<?php echo htmlspecialchars($stock['quantity']); ?> units available)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Units Needed:</label>
                <input type="number" name="units_needed" required min="1">
            </div>

            <div class="form-group">
                <label>Urgency Level:</label>
                <select name="urgency" required>
                    <option value="normal">Normal</option>
                    <option value="urgent">Urgent</option>
                    <option value="critical">Critical</option>
                </select>
            </div>

            <div class="form-group">
                <label>Hospital Name:</label>
                <input type="text" name="hospital_name" required>
            </div>

            <div class="form-group">
                <label>Contact Phone:</label>
                <input type="tel" name="contact_phone" required>
            </div>

            <div class="form-group">
                <label>Contact Email:</label>
                <input type="email" name="contact_email">
            </div>

            <button type="submit" class="btn">Submit Request</button>
        </form>
    </div>
</body>
</html> 