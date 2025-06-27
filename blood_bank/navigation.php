<?php
session_start();
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_person = isset($_SESSION['person_logged_in']) && $_SESSION['person_logged_in'] === true;
$is_doctor = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'doctor';
$is_patient = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'patient';

// Give doctors admin-like access
if ($is_doctor) {
    $is_admin = true;
}

$can_edit = $is_admin || $is_doctor;

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-logo">Blood Bank Management</a>
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="donors.php" class="nav-link <?php echo $current_page == 'donors.php' ? 'active' : ''; ?>">
                    Donors List
                </a>
            </li>
            <li class="nav-item">
                <a href="blood_stock.php" class="nav-link <?php echo $current_page == 'blood_stock.php' ? 'active' : ''; ?>">
                    Blood Stock
                </a>
            </li>
            <li class="nav-item">
                <a href="add_donor.php" class="nav-link <?php echo $current_page == 'add_donor.php' ? 'active' : ''; ?>">
                    Add Donor
                </a>
            </li>
            <li class="nav-item">
                <a href="add_request.php" class="nav-link <?php echo $current_page == 'add_request.php' ? 'active' : ''; ?>">
                    Request Blood
                </a>
            </li>
            <?php if (isset($_SESSION['user_id'])): ?>
            <li class="nav-item">
                <a href="../logout.php" class="nav-link">Logout</a>
            </li>
            <?php else: ?>
            <li class="nav-item">
                <a href="../login.php" class="nav-link">Login</a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</nav> 