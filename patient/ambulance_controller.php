<?php
require_once '../dbConnect.php';
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

class AmbulanceController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    private function getPatientId($userId) {
        $stmt = $this->pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }

    public function getAvailableAmbulances() {
        $stmt = $this->pdo->prepare("
            SELECT * FROM ambulances 
            WHERE status = 'available'
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAmbulanceById($id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM ambulances 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function bookAmbulance($ambulanceId, $userId, $pickupLocation, $destination, $bookingDate, $bookingTime, $patientContact, $emergencyType, $specialInstructions, $paymentMethod, $estimatedPrice) {
        try {
            $this->pdo->beginTransaction();

            // Get patient ID from user ID
            $patientId = $this->getPatientId($userId);
            if (!$patientId) {
                throw new Exception("Patient record not found");
            }

            // Insert booking
            $stmt = $this->pdo->prepare("
                INSERT INTO ambulance_bookings (
                    ambulance_id, 
                    patient_id, 
                    pickup_location, 
                    destination, 
                    booking_date, 
                    booking_time,
                    patient_contact,
                    emergency_type,
                    special_instructions,
                    payment_method,
                    estimated_price
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $ambulanceId,
                $patientId,
                $pickupLocation,
                $destination,
                $bookingDate,
                $bookingTime,
                $patientContact,
                $emergencyType,
                $specialInstructions,
                $paymentMethod,
                $estimatedPrice
            ]);

            // Update ambulance status
            $stmt = $this->pdo->prepare("
                UPDATE ambulances 
                SET status = 'busy' 
                WHERE id = ?
            ");
            $stmt->execute([$ambulanceId]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getPatientBookings($userId) {
        // Get patient ID from user ID
        $patientId = $this->getPatientId($userId);
        if (!$patientId) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT 
                ab.*,
                a.vehicle_type,
                a.vehicle_number,
                a.driver_name,
                a.driver_contact,
                a.price_per_km
            FROM ambulance_bookings ab
            JOIN ambulances a ON ab.ambulance_id = a.id
            WHERE ab.patient_id = ?
            ORDER BY ab.created_at DESC
        ");
        $stmt->execute([$patientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cancelBooking($bookingId, $userId) {
        try {
            $this->pdo->beginTransaction();

            // Get patient ID from user ID
            $patientId = $this->getPatientId($userId);
            if (!$patientId) {
                throw new Exception("Patient record not found");
            }

            // Get ambulance ID from booking
            $stmt = $this->pdo->prepare("
                SELECT ambulance_id 
                FROM ambulance_bookings 
                WHERE id = ? AND patient_id = ?
            ");
            $stmt->execute([$bookingId, $patientId]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                throw new Exception("Booking not found or unauthorized");
            }

            // Update booking status
            $stmt = $this->pdo->prepare("
                UPDATE ambulance_bookings 
                SET status = 'cancelled' 
                WHERE id = ?
            ");
            $stmt->execute([$bookingId]);

            // Update ambulance status back to available
            $stmt = $this->pdo->prepare("
                UPDATE ambulances 
                SET status = 'available' 
                WHERE id = ?
            ");
            $stmt->execute([$booking['ambulance_id']]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}

// Handle requests
$controller = new AmbulanceController($pdo);

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    switch ($action) {
        case 'list':
            $ambulances = $controller->getAvailableAmbulances();
            include 'ambulance_list.php';
            break;

        case 'book_form':
            if (!isset($_GET['id'])) {
                $_SESSION['error'] = "Invalid ambulance selection";
                header("Location: ambulance_controller.php?action=list");
                exit();
            }
            $ambulance = $controller->getAmbulanceById($_GET['id']);
            include 'ambulance_booking_form.php';
            break;

        case 'book':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    foreach (['ambulance_id','pickup_location','destination','booking_date','booking_time','patient_contact','emergency_type','payment_method'] as $field) {
                        if (empty($_POST[$field])) throw new Exception("All fields are required");
                    }
                    $controller->bookAmbulance(
                        $_POST['ambulance_id'], $_SESSION['user']['id'], $_POST['pickup_location'], $_POST['destination'],
                        $_POST['booking_date'], $_POST['booking_time'], $_POST['patient_contact'], $_POST['emergency_type'],
                        $_POST['special_instructions'] ?? '', $_POST['payment_method'], $_POST['estimated_price'] ?? null
                    );
                    $_SESSION['success'] = "Ambulance booked successfully!";
                    header("Location: ambulance_controller.php?action=my_bookings");
                    exit();
                } catch (Exception $e) {
                    $_SESSION['error'] = $e->getMessage();
                    header("Location: ambulance_controller.php?action=list");
                    exit();
                }
            }
            break;

        case 'my_bookings':
            $bookings = $controller->getPatientBookings($_SESSION['user']['id']);
            include 'my_ambulance_bookings.php';
            break;

        case 'cancel':
            if (!isset($_GET['id'])) {
                $_SESSION['error'] = "Invalid booking ID";
                header("Location: ambulance_controller.php?action=my_bookings");
                exit();
            }

            try {
                $controller->cancelBooking($_GET['id'], $_SESSION['user']['id']);
                $_SESSION['success'] = "Booking cancelled successfully";
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
            }

            header("Location: patient_dashboard.php");
            exit();
            break;

        default:
            header("Location: ../dashboard.php");
            exit();
    }
} else {
    header("Location: ../dashboard.php");
    exit();
}
?> 