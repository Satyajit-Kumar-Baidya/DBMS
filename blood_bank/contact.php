<?php
session_start();
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_person = isset($_SESSION['person_logged_in']) && $_SESSION['person_logged_in'] === true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Blood Bank</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="overlay"></div>
<div class="topnav">
    <a href="index.php">Home</a>
    <a href="contact.php" class="active">Contact Us</a>
    <a href="donors.php">Donor List</a>
    <a href="search.php">Search Donor</a>
    <?php if ($is_admin): ?>
        <a href="admin.php">Admin Dashboard</a>
        <a href="logout.php">Logout</a>
    <?php elseif ($is_person): ?>
        <a href="request.php">Request Blood</a>
        <a href="logout.php">Logout</a>
    <?php else: ?>
        <a href="login.php">Login</a>
    <?php endif; ?>
</div>
<h1>Contact Us</h1>
<form style="background:rgba(255,255,255,0.95);display:inline-block;padding:30px 40px;border-radius:10px;margin-top:30px;box-shadow:0 4px 24px #b71c1c33;" method="post" action="#">
    <input type="text" name="name" placeholder="Your Name" required><br><br>
    <input type="email" name="email" placeholder="Your Email" required><br><br>
    <textarea name="message" placeholder="Your Message" rows="5" style="width:250px;resize:none;" required></textarea><br><br>
    <input type="submit" value="Send">
</form>
</body>
</html> 