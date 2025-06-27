<?php
session_start();
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_person = isset($_SESSION['person_logged_in']) && $_SESSION['person_logged_in'] === true;
$is_patient = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'patient';

if (!$is_person && !$is_patient) {
    header('Location: login.php');
    exit();
}

// Get user info based on type
$user_name = '';
$user_email = '';
$user_phone = '';

if ($is_person && file_exists('persons.txt')) {
    $username = $_SESSION['person_username'];
    $lines = file('persons.txt');
    foreach ($lines as $line) {
        $parts = explode('|', trim($line));
        if (count($parts) === 5 && $parts[0] === $username) {
            $user_name = $parts[0];
            $user_email = $parts[1];
            $user_phone = $parts[2];
            break;
        }
    }
} elseif ($is_patient && isset($_SESSION['user'])) {
    $user_name = $_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name'];
    $user_email = $_SESSION['user']['email'];
    $user_phone = $_SESSION['user']['phone'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Blood - Blood Bank</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .request-form {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .request-form input {
            width: 100%;
            padding: 8px;
            margin: 8px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .request-form input[readonly] {
            background: #f5f5f5;
        }
        .submit-btn {
            background: #e53935;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }
        .submit-btn:hover {
            background: #c62828;
        }
        .success-message {
            color: green;
            padding: 10px;
            margin: 10px 0;
            background: #e8f5e9;
            border-radius: 4px;
        }
        .my-requests {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .my-requests table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .my-requests th, .my-requests td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .my-requests th {
            background-color: #f5f5f5;
            font-weight: bold;
            color: #333;
        }
        .my-requests tr:hover {
            background-color: #f9f9f9;
        }
        .delete-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .delete-btn:hover {
            background: #c82333;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }
    </style>
</head>
<body>
<div class="overlay"></div>
<?php include 'navigation.php'; ?>

<div class="request-form">
    <h1>Request Blood</h1>
    <?php if (isset($_GET['requested'])): ?>
        <div class="success-message">Blood request sent successfully!</div>
    <?php endif; ?>

    <?php
    $donor_name = isset($_GET['name']) ? htmlspecialchars($_GET['name']) : '';
    $donor_blood = isset($_GET['blood_type']) ? htmlspecialchars($_GET['blood_type']) : '';
    $donor_email = isset($_GET['email']) ? htmlspecialchars($_GET['email']) : '';

    // Get user's requests
    $user_requests = [];
    if (file_exists('requests.txt')) {
        $lines = file('requests.txt');
        foreach ($lines as $line) {
            $parts = explode('|', trim($line));
            if (count($parts) >= 6) {
                $requester_email = $parts[4]; // Email is at index 4
                if ($requester_email === $user_email) {
                    $user_requests[] = $parts;
                }
            }
        }
    }
    ?>

    <?php if (!empty($user_requests)): ?>
    <div class="my-requests">
        <h2>My Blood Requests</h2>
        <table>
            <tr>
                <th>Donor Name</th>
                <th>Blood Type</th>
                <th>Donor Email</th>
                <th>Request Date</th>
                <th>Action</th>
            </tr>
            <?php foreach ($user_requests as $index => $request): ?>
            <tr>
                <td><?php echo htmlspecialchars($request[0]); ?></td>
                <td><?php echo htmlspecialchars($request[1]); ?></td>
                <td><?php echo htmlspecialchars($request[2]); ?></td>
                <td><?php echo isset($request[6]) ? htmlspecialchars($request[6]) : 'N/A'; ?></td>
                <td>
                    <form action="delete_request.php" method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this request?');">
                        <input type="hidden" name="request_index" value="<?php echo $index; ?>">
                        <button type="submit" class="delete-btn">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>

    <?php if ($donor_name && $donor_blood): ?>
    <div class="request-form">
        <h2>New Blood Request</h2>
        <form action="add_request.php" method="post">
            <h3>Donor Info</h3>
            <input type="text" name="donor_name" value="<?php echo $donor_name; ?>" readonly placeholder="Donor Name">
            <input type="text" name="donor_blood" value="<?php echo $donor_blood; ?>" readonly placeholder="Blood Type">
            <input type="hidden" name="donor_email" value="<?php echo $donor_email; ?>">
            
            <h3>Your Info</h3>
            <input type="text" name="requester_name" placeholder="Your Name" value="<?php echo htmlspecialchars($user_name); ?>" required>
            <input type="email" name="requester_email" placeholder="Your Email" value="<?php echo htmlspecialchars($user_email); ?>" required>
            <input type="text" name="requester_phone" placeholder="Your Phone" value="<?php echo htmlspecialchars($user_phone); ?>" required>
            
            <button type="submit" class="submit-btn">Send Request</button>
        </form>
    </div>
    <?php endif; ?>
</div>

</body>
</html> 