<?php
require_once 'db_connect.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $stmt = $pdo->prepare("INSERT INTO blood_donors (first_name, last_name, blood_group, age, gender, phone, email, address) 
                              VALUES (:first_name, :last_name, :blood_group, :age, :gender, :phone, :email, :address)");
        
        $stmt->execute([
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'blood_group' => $_POST['blood_group'],
            'age' => $_POST['age'],
            'gender' => $_POST['gender'],
            'phone' => $_POST['phone'],
            'email' => $_POST['email'],
            'address' => $_POST['address']
        ]);

        // Update blood stock
        $stmt = $pdo->prepare("UPDATE blood_stock SET quantity = quantity + 1 WHERE blood_group = ?");
        $stmt->execute([$_POST['blood_group']]);

        $_SESSION['success'] = "Donor added successfully!";
        header("Location: donors.php");
        exit();
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error adding donor: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Blood Donor</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'navigation.php'; ?>
    
    <div class="container">
        <h2>Add New Blood Donor</h2>
        
        <?php
        if (isset($_SESSION['error'])) {
            echo "<div class='error'>" . $_SESSION['error'] . "</div>";
            unset($_SESSION['error']);
        }
        ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>First Name:</label>
                <input type="text" name="first_name" required>
            </div>

            <div class="form-group">
                <label>Last Name:</label>
                <input type="text" name="last_name" required>
            </div>

            <div class="form-group">
                <label>Blood Group:</label>
                <select name="blood_group" required>
                    <option value="A+">A+</option>
                    <option value="A-">A-</option>
                    <option value="B+">B+</option>
                    <option value="B-">B-</option>
                    <option value="AB+">AB+</option>
                    <option value="AB-">AB-</option>
                    <option value="O+">O+</option>
                    <option value="O-">O-</option>
                </select>
            </div>

            <div class="form-group">
                <label>Age:</label>
                <input type="number" name="age" required min="18" max="65">
            </div>

            <div class="form-group">
                <label>Gender:</label>
                <select name="gender" required>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label>Phone:</label>
                <input type="tel" name="phone" required>
            </div>

            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email">
            </div>

            <div class="form-group">
                <label>Address:</label>
                <textarea name="address" required></textarea>
            </div>

            <button type="submit" class="btn">Add Donor</button>
        </form>
    </div>
</body>
</html> 