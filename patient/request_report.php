<?php
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
$success = '';

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

    // Handle report request submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $reportType = $_POST['report_type'] ?? '';
        $description = $_POST['description'] ?? '';

        // Basic validation
        if (empty($reportType)) $errors[] = 'Report type is required.';
        if (empty($description)) $errors[] = 'Description is required.';

        if (empty($errors)) {
            try {
                // Create reports directory if it doesn't exist
                $reportsDir = '../reports';
                if (!file_exists($reportsDir)) {
                    mkdir($reportsDir, 0777, true);
                }

                // Generate a unique filename
                $filename = uniqid() . '_' . strtolower(str_replace(' ', '_', $reportType)) . '.pdf';
                $filePath = $reportsDir . '/' . $filename;

                // In a real application, you would generate the PDF here
                // For this example, we'll create an empty file
                file_put_contents($filePath, '');

                // Insert report record into database
                $stmt = $pdo->prepare("INSERT INTO reports (patient_id, report_type, file_path, description) VALUES (?, ?, ?, ?)");
                $stmt->execute([$patientId, $reportType, $filePath, $description]);

                // Create notification for the request
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'Report', ?)");
                $stmt->execute([$user['id'], "Your request for a $reportType report has been received and is being processed."]);

                $success = 'Report request submitted successfully. You will be notified when it is ready.';
                
                // Redirect back to reports page with success message
                header("Location: reports.php?success=" . urlencode($success));
                exit();

            } catch (PDOException $e) {
                $errors[] = "Database Error: " . $e->getMessage();
            }
        }
    }

} catch (PDOException $e) {
    $errors[] = "Database Error: " . $e->getMessage();
}

// If there are errors, redirect back to reports page with errors
if (!empty($errors)) {
    header("Location: reports.php?error=" . urlencode(implode(', ', $errors)));
    exit();
} 