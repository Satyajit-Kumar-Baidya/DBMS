<?php
session_start();
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_doctor = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'doctor';

if (!$is_admin && !$is_doctor) {
    header('Location: login.php');
    exit();
}

$delete_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_name'])) {
    $delete_name = $_POST['delete_name'];
    $new_lines = [];
    if (file_exists('persons.txt')) {
        $lines = file('persons.txt');
        foreach ($lines as $line) {
            $parts = explode('|', trim($line));
            if (count($parts) === 5 && $parts[0] !== $delete_name) {
                $new_lines[] = $line;
            }
        }
        file_put_contents('persons.txt', $new_lines);
        $delete_success = 'Person deleted successfully!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Requests - Blood Bank</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .requests-section {
            margin-top: 30px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .requests-section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            color: #333;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f9f9f9;
        }
        .delete-btn {
            background: #e53935;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        .delete-btn:hover {
            background: #c62828;
        }
        .success-message {
            color: #1976d2;
            font-weight: bold;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
<div class="overlay"></div>
<?php include 'navigation.php'; ?>

<div class="container">
    <h1>Blood Requests</h1>
    <?php if ($delete_success) echo '<p class="success-message">'.$delete_success.'</p>'; ?>
    
    <div class="requests-section">
        <table>
            <tr>
                <th>Requester Name</th>
                <th>Requester Email</th>
                <th>Requester Phone</th>
                <th>Donor Name</th>
                <th>Blood Type</th>
                <th>Donor Email</th>
                <th>Request Date</th>
            </tr>
            <?php
            if (file_exists('requests.txt')) {
                $lines = file('requests.txt');
                foreach ($lines as $line) {
                    $parts = explode('|', trim($line));
                    if (count($parts) >= 6) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($parts[3]) . '</td>'; // Requester Name
                        echo '<td>' . htmlspecialchars($parts[4]) . '</td>'; // Requester Email
                        echo '<td>' . htmlspecialchars($parts[5]) . '</td>'; // Requester Phone
                        echo '<td>' . htmlspecialchars($parts[0]) . '</td>'; // Donor Name
                        echo '<td>' . htmlspecialchars($parts[1]) . '</td>'; // Blood Type
                        echo '<td>' . htmlspecialchars($parts[2]) . '</td>'; // Donor Email
                        echo '<td>' . (isset($parts[6]) ? htmlspecialchars($parts[6]) : 'N/A') . '</td>'; // Request Date
                        echo '</tr>';
                    }
                }
            }
            ?>
        </table>
    </div>
</div>

</body>
</html> 