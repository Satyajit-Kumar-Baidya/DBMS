<?php
session_start();
if (!isset($_SESSION['person_logged_in']) || $_SESSION['person_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}
$name = $_SESSION['person_username'];
$email = $phone = $address = $passkey = '';
$success = '';
$error = '';
if (isset($_POST['delete_account'])) {
    // Delete this person from persons.txt
    $new_lines = [];
    if (file_exists('persons.txt')) {
        $lines = file('persons.txt');
        foreach ($lines as $line) {
            $parts = explode('|', trim($line));
            if (count($parts) === 5 && $parts[0] !== $name) {
                $new_lines[] = $line;
            }
        }
        file_put_contents('persons.txt', $new_lines);
    }
    session_destroy();
    header('Location: login.php?deleted=1');
    exit();
}
if (file_exists('persons.txt')) {
    $lines = file('persons.txt');
    foreach ($lines as $line) {
        $parts = explode('|', trim($line));
        if (count($parts) === 5 && $parts[0] === $name) {
            list($_, $email, $phone, $address, $passkey) = $parts;
            break;
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_account'])) {
    $new_email = trim($_POST['email']);
    $new_phone = trim($_POST['phone']);
    $new_address = trim($_POST['address']);
    $new_passkey = trim($_POST['passkey']);
    if ($new_email === '' || $new_phone === '' || $new_address === '' || $new_passkey === '') {
        $error = 'All fields are required.';
    } else {
        $new_lines = [];
        if (file_exists('persons.txt')) {
            $lines = file('persons.txt');
            foreach ($lines as $line) {
                $parts = explode('|', trim($line));
                if (count($parts) === 5 && $parts[0] === $name) {
                    $new_lines[] = "$name|$new_email|$new_phone|$new_address|$new_passkey\n";
                } else {
                    $new_lines[] = $line;
                }
            }
            file_put_contents('persons.txt', $new_lines);
            $success = 'Profile updated successfully!';
            $email = $new_email;
            $phone = $new_phone;
            $address = $new_address;
            $passkey = $new_passkey;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Blood Bank</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="overlay"></div>
<div class="topnav">
    <a href="index.php">Home</a>
    <a href="contact.php">Contact Us</a>
    <a href="donors.php">Donor List</a>
    <a href="search.php">Search Donor</a>
    <a href="request.php">Request Blood</a>
    <a href="profile.php" class="active">My Profile</a>
    <a href="logout.php">Logout</a>
</div>
<h1>My Profile</h1>
<div class="login-container" style="max-width:400px;">
    <?php if ($success) echo '<p style="color:#1976d2;font-weight:bold;">'.$success.'</p>'; ?>
    <?php if ($error) echo '<p class="error">'.$error.'</p>'; ?>
    <form method="post" action="profile.php">
        <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" readonly><br><br>
        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Email" required><br><br>
        <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" placeholder="Phone" required><br><br>
        <input type="text" name="address" value="<?php echo htmlspecialchars($address); ?>" placeholder="Address" required><br><br>
        <input type="password" name="passkey" value="<?php echo htmlspecialchars($passkey); ?>" placeholder="Passkey" required><br><br>
        <input type="submit" value="Update Profile">
    </form>
    <form method="post" action="profile.php" onsubmit="return confirm('Are you sure you want to delete your account? This cannot be undone.');" style="margin-top:20px;">
        <input type="hidden" name="delete_account" value="1">
        <input type="submit" value="Delete My Account" style="background:#e53935;">
    </form>
</div>
</body>
</html> 