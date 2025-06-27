<?php
require_once 'db_connect.php';
session_start();

// Fetch current blood stock
try {
    $stmt = $pdo->query("SELECT * FROM blood_stock ORDER BY blood_group");
    $blood_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If no records exist, initialize with all blood groups
    if (empty($blood_stock)) {
        $blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        foreach ($blood_groups as $group) {
            $stmt = $pdo->prepare("INSERT INTO blood_stock (blood_group, quantity) VALUES (?, 0)");
            $stmt->execute([$group]);
        }
        $stmt = $pdo->query("SELECT * FROM blood_stock ORDER BY blood_group");
        $blood_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch(PDOException $e) {
    $_SESSION['error'] = "Error fetching blood stock: " . $e->getMessage();
    $blood_stock = [];
}

// Handle stock update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    try {
        foreach ($_POST['quantity'] as $id => $quantity) {
            $stmt = $pdo->prepare("UPDATE blood_stock SET quantity = ? WHERE id = ?");
            $stmt->execute([$quantity, $id]);
        }
        $_SESSION['success'] = "Blood stock updated successfully!";
        header("Location: blood_stock.php");
        exit();
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error updating blood stock: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Blood Stock Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'navigation.php'; ?>
    
    <div class="container">
        <h2>Blood Stock Management</h2>
        
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

        <form method="POST" action="">
            <table class="table">
                <thead>
                    <tr>
                        <th>Blood Group</th>
                        <th>Available Units</th>
                        <th>Last Updated</th>
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <th>Update Quantity</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($blood_stock as $stock): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($stock['blood_group']); ?></td>
                        <td><?php echo htmlspecialchars($stock['quantity']); ?></td>
                        <td><?php echo htmlspecialchars($stock['last_updated']); ?></td>
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <td>
                            <input type="number" name="quantity[<?php echo $stock['id']; ?>]" 
                                   value="<?php echo htmlspecialchars($stock['quantity']); ?>" 
                                   min="0" class="small-input">
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (isset($_SESSION['user_id'])): ?>
            <button type="submit" name="update_stock" class="btn">Update Stock</button>
            <?php endif; ?>
        </form>
    </div>
</body>
</html> 