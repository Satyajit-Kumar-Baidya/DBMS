<?php
session_start();
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_person = isset($_SESSION['person_logged_in']) && $_SESSION['person_logged_in'] === true;
$is_doctor = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'doctor';
$is_patient = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'patient';
$can_edit = $is_admin || $is_doctor;

$results = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['blood_type'])) {
    $search_blood = $_POST['blood_type'];
    if (file_exists('donors.txt')) {
        $lines = file('donors.txt');
        foreach ($lines as $line) {
            $parts = explode('|', trim($line));
            if (count($parts) === 5 && $parts[4] === $search_blood) {
                $results[] = $parts;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Donor - Blood Bank</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="overlay"></div>
<?php include 'navigation.php'; ?>
<h1>Search Donor</h1>
<form method="post" style="background:rgba(255,255,255,0.95);display:inline-block;padding:20px 30px;border-radius:10px;margin-top:30px;box-shadow:0 4px 24px #b71c1c33;">
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
    </select>
    <input type="submit" value="Search">
</form>
<?php if ($_SERVER['REQUEST_METHOD'] === 'POST') { ?>
    <h2>Results</h2>
    <table>
        <tr>
            <th>Name</th>
            <th>Address</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Blood Type</th>
            <th>Request</th>
        </tr>
        <?php foreach ($results as $donor) {
            echo '<tr>';
            foreach ($donor as $item) {
                echo '<td>' . htmlspecialchars($item) . '</td>';
            }
            echo '<td>';
            if ($is_person || $is_patient) {
                echo '<form action="request.php" method="get" style="margin:0;"><input type="hidden" name="name" value="'.htmlspecialchars($donor[0]).'">';
                echo '<input type="hidden" name="blood_type" value="'.htmlspecialchars($donor[4]).'">';
                echo '<input type="hidden" name="email" value="'.htmlspecialchars($donor[2]).'">';
                echo '<input type="submit" value="Request"></form>';
            } else {
                echo '<span style="color:#888;">Login to request</span>';
            }
            echo '</td>';
            echo '</tr>';
        } ?>
    </table>
    <?php if (empty($results)) echo '<p style="color:#d32f2f;font-weight:bold;">No donors found for this blood type.</p>'; ?>
<?php } ?>
</body>
</html> 