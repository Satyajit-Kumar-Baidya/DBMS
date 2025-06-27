<?php
session_start();
require_once '../dbConnect.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    header("Location: ../index.php");
    exit();
}

// Check if appointment ID is provided
if (!isset($_POST['appointment_id'])) {
    $_SESSION['error'] = "No appointment specified.";
    header("Location: appointments.php");
    exit();
}

$appointmentId = $_POST['appointment_id'];
$user = $_SESSION['user'];

try {
    // Get patient ID
    $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $patientId = $stmt->fetchColumn();

    if (!$patientId) {
        $_SESSION['error'] = "Patient record not found.";
        header("Location: appointments.php");
        exit();
    }

    // Check if appointment exists and belongs to the patient
    $stmt = $pdo->prepare("SELECT id, status FROM appointments WHERE id = ? AND patient_id = ?");
    $stmt->execute([$appointmentId, $patientId]);
    $appointment = $stmt->fetch();

    if (!$appointment) {
        $_SESSION['error'] = "Appointment not found.";
        header("Location: appointments.php");
        exit();
    }

    if ($appointment['status'] !== 'pending' && $appointment['status'] !== 'accepted') {
        $_SESSION['error'] = "This appointment cannot be cancelled.";
        header("Location: appointments.php");
        exit();
    }

    // Update appointment status to cancelled
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$appointmentId]);

    $_SESSION['success'] = "Appointment cancelled successfully.";
    header("Location: appointments.php");
    exit();

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: appointments.php");
    exit();
}
?> 