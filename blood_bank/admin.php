<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Blood Bank</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="overlay"></div>
<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}
$is_admin = true;
$is_person = false;
$success = isset($_GET['success']) ? true : false;
?>
<div class="topnav">
    <a href="index.php">Home</a>
    <a href="donors.php">Donor List</a>
    <a href="search.php">Search Donor</a>
    <a href="admin.php" class="active">Admin Dashboard</a>
    <a href="persons_list.php">View Persons</a>
    <a href="logout.php">Logout</a>
</div>
    <h1>Admin Panel</h1>
    <h2>Add New Donor</h2>
    <?php if ($success) echo '<p style="color:#1976d2;font-weight:bold;">Donor added successfully!</p>'; ?>
    <form action="add_donor.php" method="post">
        <input type="text" name="name" placeholder="Name" required><br><br>
        <input type="text" name="address" placeholder="Address" required><br><br>
        <input type="email" name="email" placeholder="Email" required><br><br>
        <input type="text" name="phone" placeholder="Phone" required><br><br>
        <select name="blood_type" required>
            <option value="">Select Blood Type</option>
            <option value="A+">A+</option>
            <option value="A-">A-</option>
            <option value="B+">B+</option>
            <option value="B-">B-</option>
            <option value="AB+">AB+</option>
            <option value="AB-">AB-</option>
            <option value="O+">O+</option>
            <option value="O-">O-</option>
        </select><br><br>
        <input type="submit" value="Add Donor">
    </form>
</body>
</html> 