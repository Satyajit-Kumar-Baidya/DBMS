<?php
require_once 'dbConnect.php';

session_start();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $role = $_POST['role'] ?? 'patient'; // Get role from form, default to patient
    $created_at = date('Y-m-d H:i:s');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    if(empty($first_name)){
        $errors['first_name'] = 'First name is required';
    }
    if(empty($last_name)){
        $errors['last_name'] = 'Last name is required';
    }
    if(empty($password)){
        $errors['password'] = 'Password is required';
    }

    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match';
    }

    if (!in_array($role, ['patient', 'doctor'])) {
        $errors['role'] = 'Invalid role selected.';
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        $errors['user_exist'] = 'Email is already registered';
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header('Location: register.php');
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (email, password, first_name, last_name, role, created_at) VALUES (:email, :password, :first_name, :last_name, :role, :created_at)");
    $stmt->execute([
        'email' => $email, 
        'password' => $hashedPassword, 
        'first_name' => $first_name,
        'last_name' => $last_name,
        'role' => $role,
        'created_at' => $created_at
    ]);

    $userId = $pdo->lastInsertId();

    if ($role === 'patient') {
        $stmt = $pdo->prepare("INSERT INTO patients (user_id) VALUES (:user_id)");
        $stmt->execute(['user_id' => $userId]);
        
        // Get the patient ID to show in success message
        $patientId = $pdo->lastInsertId();
        $_SESSION['registration_success'] = "Registration successful! Your Patient ID is: " . $patientId . ". Please keep this ID safe as doctors will use it to access your medical records.";
    } elseif ($role === 'doctor') {
        $stmt = $pdo->prepare("INSERT INTO doctors (user_id) VALUES (:user_id)");
        $stmt->execute(['user_id' => $userId]);
    }

    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signin'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }

    if (empty($password)) {
        $errors['password'] = 'Password cannot be empty';
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header('Location: index.php');
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Set session data
            $_SESSION['user'] = [
                'id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'role' => $user['role'],
                'created_at' => $user['created_at']
            ];
            $_SESSION['user_id'] = $user['id'];

            // Set patient_id in session if user is a patient
            if ($user['role'] === 'patient') {
                $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ? LIMIT 1");
                $stmt->execute([$user['id']]);
                $patientId = $stmt->fetchColumn();
                if ($patientId) {
                    $_SESSION['patient_id'] = $patientId;
                }
            }

            // Redirect based on role
            switch ($user['role']) {
                case 'admin':
                    header('Location: admin/dashboard.php');
                    break;
                case 'doctor':
                    header('Location: doctor/doctor_dashboard.php');
                    break;
                case 'patient':
                    header('Location: patient/patient_dashboard.php');
                    break;
                default:
                    // If invalid role, destroy session and redirect to login
                    session_destroy();
                    $errors['login'] = 'Invalid user role';
                    $_SESSION['errors'] = $errors;
                    header('Location: index.php');
            }
            exit();
        } else {
            $errors['login'] = 'Invalid email or password';
            $_SESSION['errors'] = $errors;
            header('Location: index.php');
            exit();
        }
    } catch (PDOException $e) {
        $errors['login'] = 'Database error occurred';
        $_SESSION['errors'] = $errors;
        header('Location: index.php');
        exit();
    }
}
