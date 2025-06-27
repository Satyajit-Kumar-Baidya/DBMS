<?php
require_once 'db_connect.php';
session_start();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid request ID";
    header("Location: requests.php");
    exit();
}

try {
    // First check if the user has permission to delete this request
    $stmt = $pdo->prepare("SELECT br.*, p.user_id 
                          FROM blood_requests br 
                          LEFT JOIN patients p ON br.patient_id = p.id 
                          WHERE br.id = ?");
    $stmt->execute([$_GET['id']]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        $_SESSION['error'] = "Request not found";
        header("Location: requests.php");
        exit();
    }

    // Check if the logged-in user is the owner of this request
    if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $request['user_id']) {
        $_SESSION['error'] = "You don't have permission to delete this request";
        header("Location: requests.php");
        exit();
    }

    // If the request was approved, add the units back to blood stock
    if ($request['status'] === 'approved') {
        $stmt = $pdo->prepare("UPDATE blood_stock 
                              SET quantity = quantity + ? 
                              WHERE blood_group = ?");
        $stmt->execute([$request['units_needed'], $request['blood_group']]);
    }

    // Delete the request
    $stmt = $pdo->prepare("DELETE FROM blood_requests WHERE id = ?");
    $stmt->execute([$_GET['id']]);

    $_SESSION['success'] = "Request deleted successfully";
} catch(PDOException $e) {
    $_SESSION['error'] = "Error deleting request: " . $e->getMessage();
}

header("Location: requests.php");
exit(); 