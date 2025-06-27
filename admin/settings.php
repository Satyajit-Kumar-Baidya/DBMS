<?php
session_start();
require_once '../dbConnect.php';

// Restrict to admin only
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$admin = $_SESSION['user'];
$success = $error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    try {
        $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, email=? WHERE id=?");
        $stmt->execute([$first_name, $last_name, $email, $admin['id']]);
        $success = 'Profile updated successfully!';
        $_SESSION['user']['first_name'] = $first_name;
        $_SESSION['user']['last_name'] = $last_name;
        $_SESSION['user']['email'] = $email;
    } catch (PDOException $e) {
        $error = 'Error updating profile.';
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    if ($new !== $confirm) {
        $error = 'New passwords do not match!';
    } else {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id=?");
        $stmt->execute([$admin['id']]);
        $hash = $stmt->fetchColumn();
        if (!password_verify($current, $hash)) {
            $error = 'Current password is incorrect!';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $admin['id']]);
            $success = 'Password changed successfully!';
        }
    }
}

// Handle notification preferences (dummy, for UI only)
$notifications = isset($_POST['notifications']) ? $_POST['notifications'] : ['email','sms'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2 class="mb-4">Admin Settings</h2>
        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">Profile Information</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($admin['first_name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($admin['last_name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-header">Change Password</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="new_password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-warning">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">Notification Preferences</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="notifications[]" value="email" id="notifEmail" <?php if(in_array('email',$notifications)) echo 'checked'; ?>>
                                <label class="form-check-label" for="notifEmail">Email Notifications</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="notifications[]" value="sms" id="notifSMS" <?php if(in_array('sms',$notifications)) echo 'checked'; ?>>
                                <label class="form-check-label" for="notifSMS">SMS Notifications</label>
                            </div>
                            <button type="submit" class="btn btn-info mt-2">Save Preferences</button>
                        </form>
                        <div class="alert alert-info mt-3">(Preferences are for demo only and not saved in DB.)</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 