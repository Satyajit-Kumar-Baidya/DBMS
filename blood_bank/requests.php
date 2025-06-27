<?php
require_once 'db_connect.php';
session_start();

// Fetch all blood requests
try {
    $stmt = $pdo->query("SELECT br.*, p.user_id 
                         FROM blood_requests br 
                         LEFT JOIN patients p ON br.patient_id = p.id 
                         ORDER BY br.urgency DESC, br.created_at DESC");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Error fetching requests: " . $e->getMessage();
    $requests = [];
}

// Handle request status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    try {
        $stmt = $pdo->prepare("UPDATE blood_requests SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], $_POST['request_id']]);

        // If request is approved, update blood stock
        if ($_POST['status'] === 'approved') {
            $request_id = $_POST['request_id'];
            
            // Get request details
            $stmt = $pdo->prepare("SELECT blood_group, units_needed FROM blood_requests WHERE id = ?");
            $stmt->execute([$request_id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Update blood stock
            $stmt = $pdo->prepare("UPDATE blood_stock SET quantity = quantity - ? WHERE blood_group = ?");
            $stmt->execute([$request['units_needed'], $request['blood_group']]);
        }

        $_SESSION['success'] = "Request status updated successfully!";
        header("Location: requests.php");
        exit();
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error updating request: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requests - Blood Bank</title>
    <link rel="stylesheet" href="style.css">
</head>
<?php
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}
$is_admin = true;
$is_person = false;
?>
<body>
<div class="overlay"></div>
<div class="topnav">
    <a href="index.php">Home</a>
    <a href="contact.php">Contact Us</a>
    <a href="donors.php">Donor List</a>
    <a href="search.php">Search Donor</a>
    <a href="admin.php">Admin Dashboard</a>
    <a href="persons_list.php">View Persons</a>
    <a href="logout.php">Logout</a>
</div>
    <h1>Blood Requests</h1>
    <?php include 'navigation.php'; ?>
    
    <div class="container">
        <h2>Blood Requests</h2>
        
        <?php
        if (isset($_SESSION['success'])) {
            echo "<div class='success'>" . $_SESSION['success'] . "</div>";
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo "<div class='error'>" . $_SESSION['error'] . "</div>";
            unset($_SESSION['error']);
        }
        ?>

        <a href="add_request.php" class="btn">Add New Request</a>

        <table class="table">
            <thead>
                <tr>
                    <th>Requester</th>
                    <th>Blood Group</th>
                    <th>Units Needed</th>
                    <th>Urgency</th>
                    <th>Hospital</th>
                    <th>Contact</th>
                    <th>Request Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                <tr class="urgency-<?php echo htmlspecialchars($request['urgency']); ?>">
                    <td><?php echo htmlspecialchars($request['requester_name']); ?></td>
                    <td><?php echo htmlspecialchars($request['blood_group']); ?></td>
                    <td><?php echo htmlspecialchars($request['units_needed']); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($request['urgency'])); ?></td>
                    <td><?php echo htmlspecialchars($request['hospital_name']); ?></td>
                    <td>
                        Phone: <?php echo htmlspecialchars($request['contact_phone']); ?><br>
                        Email: <?php echo htmlspecialchars($request['contact_email']); ?>
                    </td>
                    <td><?php echo htmlspecialchars($request['request_date']); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($request['status'])); ?></td>
                    <td>
                        <?php if ($request['status'] === 'pending' && isset($_SESSION['user_id'])): ?>
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                            <select name="status" onchange="this.form.submit();" class="small-input">
                                <option value="">Update Status</option>
                                <option value="approved">Approve</option>
                                <option value="completed">Complete</option>
                                <option value="cancelled">Cancel</option>
                            </select>
                            <input type="hidden" name="update_status" value="1">
                        </form>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $request['user_id']): ?>
                        <a href="delete_request.php?id=<?php echo $request['id']; ?>" 
                           class="btn-small btn-danger" 
                           onclick="return confirm('Are you sure you want to delete this request?')">Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html> 