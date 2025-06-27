<?php
require_once 'db_connect.php';
session_start();

// Fetch all donors
try {
    $stmt = $pdo->query("SELECT * FROM blood_donors ORDER BY created_at DESC");
    $donors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Error fetching donors: " . $e->getMessage();
    $donors = [];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Blood Donors List</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'navigation.php'; ?>
    
    <div class="container">
        <h2>Blood Donors List</h2>
        
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

        <a href="add_donor.php" class="btn">Add New Donor</a>

        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Blood Group</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Donation Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($donors as $donor): ?>
                <tr>
                    <td><?php echo htmlspecialchars($donor['first_name'] . ' ' . $donor['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($donor['blood_group']); ?></td>
                    <td><?php echo htmlspecialchars($donor['age']); ?></td>
                    <td><?php echo htmlspecialchars($donor['gender']); ?></td>
                    <td><?php echo htmlspecialchars($donor['phone']); ?></td>
                    <td><?php echo htmlspecialchars($donor['email']); ?></td>
                    <td><?php echo htmlspecialchars($donor['address']); ?></td>
                    <td><?php echo date('Y-m-d', strtotime($donor['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html> 