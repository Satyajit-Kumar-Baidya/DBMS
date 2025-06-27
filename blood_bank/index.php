<?php
session_start();
require_once 'db_connect.php';

$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_person = isset($_SESSION['person_logged_in']) && $_SESSION['person_logged_in'] === true;
$is_doctor = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'doctor';
$is_patient = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'patient';

// Get total donors count from database
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM blood_donors");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_donors = $result['total'];
} catch(PDOException $e) {
    $total_donors = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Bank Management System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="overlay"></div>
<?php include 'navigation.php'; ?>
<div style="max-width:700px;margin:60px auto 0 auto;background:rgba(255,255,255,0.97);padding:48px 36px 36px 36px;border-radius:18px;box-shadow:0 8px 32px #00897b22;border:2px solid #e0e0e0;display:flex;flex-direction:column;align-items:center;font-family:'Segoe UI',Arial,sans-serif;">
    <div style="display:flex;align-items:center;gap:18px;margin-bottom:18px;">
        <span style="font-size:44px;color:#e53935;vertical-align:middle;">&#128293;</span>
        <h1 style="margin:0;font-size:2.5rem;font-weight:700;color:#00695c;letter-spacing:1px;">Welcome to the Blood Bank Management System</h1>
    </div>
    <p style="font-size:20px;line-height:1.7;color:#333;margin:0 0 18px 0;text-align:center;max-width:600px;">Our mission is to connect blood donors with those in need, making the process simple, secure, and accessible for everyone. Whether you are a donor, a patient, or an administrator, our platform is here to help save lives.</p>
    <div style="margin:32px 0 24px 0;display:flex;align-items:center;gap:12px;">
        <span style="font-size:26px;color:#388e3c;font-weight:600;">Donate Blood, Save Lives!</span>
        <span style="font-size:28px;color:#e53935;">&#10084;&#65039;</span>
    </div>
    <div style="margin:24px 0 24px 0;padding:18px 32px;background:#f1f8e9;border-radius:10px;box-shadow:0 2px 8px #b2dfdb55;display:inline-block;">
        <span style="font-size:22px;color:#00695c;font-weight:500;">Total Registered Donors: </span>
        <span style="font-size:22px;font-weight:bold;letter-spacing:1px;">[<?php echo $total_donors > 0 ? $total_donors : 'Coming Soon'; ?>]</span>
    </div>
    <ul style="text-align:left;max-width:500px;margin:30px auto 30px auto;font-size:17px;line-height:1.8;color:#444;padding-left:24px;">
        <li><b>Donors:</b> Register with the admin to be listed and help save lives.</li>
        <li><b>Persons:</b> Sign up, search for donors, and send blood requests easily.</li>
        <li><b>Admins:</b> Manage donor records and view all requests and registered persons.</li>
    </ul>
</div>
<div style="max-width:700px;margin:40px auto 60px auto;background:rgba(255,255,255,0.98);padding:32px 24px 28px 24px;border-radius:16px;box-shadow:0 4px 18px #00897b22;border:2px solid #e0e0e0;">
    <h2 style="color:#00695c;margin-top:0;text-align:center;font-size:2rem;font-weight:600;letter-spacing:1px;">Blood Group Compatibility Chart</h2>
    <p style="text-align:center;color:#444;font-size:16px;margin-bottom:24px;">See which blood groups can donate or receive blood from each other.</p>
    <table style="width:100%;border-collapse:collapse;font-size:1rem;text-align:center;">
        <tr style="background:#f1f8e9;">
            <th style="padding:10px 6px;border:1px solid #b2dfdb;">Blood Group</th>
            <th style="padding:10px 6px;border:1px solid #b2dfdb;">Can Donate To</th>
            <th style="padding:10px 6px;border:1px solid #b2dfdb;">Can Receive From</th>
        </tr>
        <tr>
            <td style="padding:8px;border:1px solid #b2dfdb;font-weight:bold;color:#e53935;">O-</td>
            <td style="padding:8px;border:1px solid #b2dfdb;">All groups</td>
            <td style="padding:8px;border:1px solid #b2dfdb;">O-</td>
        </tr>
        <tr style="background:#f9fbe7;">
            <td style="padding:8px;border:1px solid #b2dfdb;font-weight:bold;color:#e53935;">O+</td>
            <td style="padding:8px;border:1px solid #b2dfdb;">O+, A+, B+, AB+</td>
            <td style="padding:8px;border:1px solid #b2dfdb;">O-, O+</td>
        </tr>
        <tr>
            <td style="padding:8px;border:1px solid #b2dfdb;font-weight:bold;color:#1976d2;">A-</td>
            <td style="padding:8px;border:1px solid #b2dfdb;">A-, A+, AB-, AB+</td>
            <td style="padding:8px;border:1px solid #b2dfdb;">O-, A-</td>
        </tr>
        <tr style="background:#f9fbe7;">
            <td style="padding:8px;border:1px solid #b2dfdb;font-weight:bold;color:#1976d2;">A+</td>
            <td style="padding:8px;border:1px solid #b2dfdb;">A+, AB+</td>
            <td style="padding:8px;border:1px solid #b2dfdb;">O-, O+, A-, A+</td>
        </tr>
        <tr>
            <td style="padding:8px;border:1px solid #b2dfdb;font-weight:bold;color:#388e3c;">B-</td>
            <td style="padding:8px;border:1px solid #b2dfdb;">B-, B+, AB-, AB+</td>
            <td style="padding:8px;border:1px solid #b2dfdb;">O-, B-</td>
        </tr>
        <tr style="background:#f9fbe7;">
            <td style="padding:8px;border:1px solid #b2dfdb;font-weight:bold;color:#388e3c;">B+</td>
            <td style="padding:8px;border:1px solid #b2dfdb;">B+, AB+</td>
            <td style="padding:8px;border:1px solid #b2dfdb;">O-, O+, B-, B+</td>
        </tr>
        <tr>
            <td style="padding:8px;border:1px solid #b2dfdb;font-weight:bold;color:#ab47bc;">AB-</td>
            <td style="padding:8px;border:1px solid #b2dfdb;">AB-, AB+</td>
            <td style="padding:8px;border:1px solid #b2dfdb;">O-, A-, B-, AB-</td>
        </tr>
        <tr style="background:#f9fbe7;">
            <td style="padding:8px;border:1px solid #b2dfdb;font-weight:bold;color:#ab47bc;">AB+</td>
            <td style="padding:8px;border:1px solid #b2dfdb;">AB+</td>
            <td style="padding:8px;border:1px solid #b2dfdb;">All groups</td>
        </tr>
    </table>
    <div style="margin-top:18px;text-align:center;color:#888;font-size:14px;">Universal Donor: <b>O-</b> &nbsp;|&nbsp; Universal Recipient: <b>AB+</b></div>
</div>
</body>
</html> 