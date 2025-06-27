<?php
require_once 'db_connect.php';
session_start();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid donor ID";
    header("Location: donors.php");
    exit();
}

try {
    // First check if the user has permission to delete this donor
    $stmt = $pdo->prepare("SELECT bd.*, p.user_id 
                          FROM blood_donors bd 
                          LEFT JOIN patients p ON bd.patient_id = p.id 
                          WHERE bd.id = ?");
    $stmt->execute([$_GET['id']]);
    $donor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$donor) {
        $_SESSION['error'] = "Donor not found";
        header("Location: donors.php");
        exit();
    }

    // Check if the logged-in user is the owner of this donor record
    if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $donor['user_id']) {
        $_SESSION['error'] = "You don't have permission to delete this donor";
        header("Location: donors.php");
        exit();
    }

    // Delete the donor
    $stmt = $pdo->prepare("DELETE FROM blood_donors WHERE id = ?");
    $stmt->execute([$_GET['id']]);

    $_SESSION['success'] = "Donor deleted successfully";
} catch(PDOException $e) {
    $_SESSION['error'] = "Error deleting donor: " . $e->getMessage();
}

header("Location: donors.php");
exit(); 