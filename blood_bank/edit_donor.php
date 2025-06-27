<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}
if (!isset($_GET['index']) && !isset($_POST['index'])) {
    header('Location: donors.php');
    exit();
}
$index = isset($_GET['index']) ? intval($_GET['index']) : intval($_POST['index']);
$success = '';
$error = '';
$name = $address = $email = $phone = $blood_type = '';
if (file_exists('donors.txt')) {
    $lines = file('donors.txt');
    if (isset($lines[$index])) {
        $parts = explode('|', trim($lines[$index]));
        if (count($parts) === 5) {
            list($name, $address, $email, $phone, $blood_type) = $parts;
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = trim($_POST['name']);
    $new_address = trim($_POST['address']);
    $new_email = trim($_POST['email']);
    $new_phone = trim($_POST['phone']);
    $new_blood_type = trim($_POST['blood_type']);
    if ($new_name === '' || $new_address === '' || $new_email === '' || $new_phone === '' || $new_blood_type === '') {
        $error = 'All fields are required.';
    } else {
        $lines[$index] = "$new_name|$new_address|$new_email|$new_phone|$new_blood_type\n";
        file_put_contents('donors.txt', $lines);
        $success = 'Donor updated successfully!';
        $name = $new_name;
        $address = $new_address;
        $email = $new_email;
        $phone = $new_phone;
        $blood_type = $new_blood_type;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Donor - Blood Bank</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="overlay"></div>
<div class="topnav">
    <a href="index.php">Home</a>
    <a href="contact.php">Contact Us</a>
    <a href="donors.php" class="active">Donor List</a>
    <a href="search.php">Search Donor</a>
    <a href="admin.php">Admin Dashboard</a>
    <a href="persons_list.php">View Persons</a>
    <a href="logout.php">Logout</a>
</div>
<h1>Edit Donor</h1>
<div class="login-container" style="max-width:400px;">
    <?php if ($success) echo '<p style="color:#388e3c;font-weight:bold;">'.$success.'</p>'; ?>
    <?php if ($error) echo '<p class="error">'.$error.'</p>'; ?>
    <form method="post" action="edit_donor.php">
        <input type="hidden" name="index" value="<?php echo $index; ?>">
        <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" placeholder="Name" required><br><br>
        <input type="text" name="address" value="<?php echo htmlspecialchars($address); ?>" placeholder="Address" required><br><br>
        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Email" required><br><br>
        <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" placeholder="Phone" required><br><br>
        <select name="blood_type" required>
            <option value="">Select Blood Type</option>
            <option value="A+" <?php if($blood_type==='A+') echo 'selected'; ?>>A+</option>
            <option value="A-" <?php if($blood_type==='A-') echo 'selected'; ?>>A-</option>
            <option value="B+" <?php if($blood_type==='B+') echo 'selected'; ?>>B+</option>
            <option value="B-" <?php if($blood_type==='B-') echo 'selected'; ?>>B-</option>
            <option value="AB+" <?php if($blood_type==='AB+') echo 'selected'; ?>>AB+</option>
            <option value="AB-" <?php if($blood_type==='AB-') echo 'selected'; ?>>AB-</option>
            <option value="O+" <?php if($blood_type==='O+') echo 'selected'; ?>>O+</option>
            <option value="O-" <?php if($blood_type==='O-') echo 'selected'; ?>>O-</option>
        </select><br><br>
        <input type="submit" value="Update Donor">
    </form>
</div>
</body>
</html> 