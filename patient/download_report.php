<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../dbConnect.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    header("Location: ../index.php");
    exit();
}

$user = $_SESSION['user'];
$patientId = null;
$errors = [];

// Get patient ID
try {
    $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user['id']]);
    $patientId = $stmt->fetchColumn();

    if (!$patientId) {
        session_destroy();
        header("Location: ../index.php");
        exit();
    }

    // Check if report ID is provided
    if (!isset($_GET['id'])) {
        throw new Exception("Report ID is required.");
    }

    // Get report details
    $stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ? AND patient_id = ?");
    $stmt->execute([$_GET['id'], $patientId]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$report) {
        throw new Exception("Report not found or access denied.");
    }

    // Check if file exists
    if (!file_exists($report['file_path'])) {
        throw new Exception("Report file not found.");
    }

    // Set headers for file download
    header('Content-Description: File Transfer');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($report['file_path']) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($report['file_path']));

    // Read file and output to browser
    readfile($report['file_path']);
    exit;

} catch (Exception $e) {
    $errors[] = $e->getMessage();
    // Redirect back to reports page with error
    header("Location: reports.php?error=" . urlencode($e->getMessage()));
    exit();
} 