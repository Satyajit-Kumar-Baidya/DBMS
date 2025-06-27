<?php

session_start();
require_once 'dbConnect.php';

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['user']['role'])) {
    $role = $_SESSION['user']['role'];
    
    switch ($role) {
        case 'admin':
            header("Location: admin/dashboard.php");
            break;
        case 'doctor':
            header("Location: doctor/doctor_dashboard.php");
            break;
        case 'patient':
            header("Location: patient/patient_dashboard.php");
            break;
        default:
            // If invalid role, destroy session and reload login page
            session_destroy();
            header("Location: index.php");
    }
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, first_name, last_name, email, password, role 
            FROM users 
            WHERE email = ? 
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user'] = [
                'id' => $user['id'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'email' => $user['email'],
                'role' => $user['role']
            ];

            // Redirect based on role
            switch ($user['role']) {
                case 'admin':
                    header("Location: admin/dashboard.php");
                    break;
                case 'doctor':
                    header("Location: doctor/doctor_dashboard.php");
                    break;
                case 'patient':
                    header("Location: patient/patient_dashboard.php");
                    break;
                default:
                    $error = "Invalid user role";
                    session_destroy();
            }
            exit();
        } else {
            $error = "Invalid email or password";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>IHMS - Integrated Healthcare Management System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
    }
    .navbar-brand {
      font-size: 1.5rem;
      font-weight: bold;
    }
    .hero-section {
      padding: 100px 0;
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }
    .login-container {
      background: white;
      border-radius: 10px;
      padding: 30px;
      box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }
    .social-login {
      border-top: 1px solid #dee2e6;
      padding-top: 20px;
      margin-top: 20px;
    }
    .btn-google {
      background-color: #db4437;
      color: white;
    }
    .btn-facebook {
      background-color: #4267B2;
      color: white;
    }
  </style>
</head>

<body>
  <!-- Navigation -->
  <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
      <a class="navbar-brand" href="index.php">IHMS</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item">
            <a class="nav-link" href="index.php">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="about.php">About</a>
          </li>
        </ul>
        <div class="d-flex">
          <a href="register.php" class="btn btn-primary ms-2">Sign Up</a>
        </div>
      </div>
    </div>
  </nav>

  <!-- Hero Section with Login -->
  <div class="hero-section">
    <div class="container">
      <div class="row align-items-center">
        <!-- Left Column - Hero Text -->
        <div class="col-lg-6 mb-5 mb-lg-0">
          <h1 class="display-4 fw-bold mb-4">Integrated Healthcare Management System</h1>
          <p class="lead text-muted mb-4">
            Revolutionizing healthcare management with advanced technology and seamless integration. 
            Experience the future of healthcare today.
          </p>
        </div>

        <!-- Right Column - Login Form -->
        <div class="col-lg-5 offset-lg-1">
          <div class="login-container">
            <h2 class="text-center mb-4">Sign In</h2>
            
            <?php if (isset($error)): ?>
              <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
              </div>
            <?php endif; ?>

            <form method="POST" action="">
              <div class="mb-3">
                <div class="input-group">
                  <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                  <input type="email" class="form-control" name="email" placeholder="Email address" required>
                </div>
              </div>

              <div class="mb-3">
                <div class="input-group">
                  <span class="input-group-text"><i class="fas fa-lock"></i></span>
                  <input type="password" class="form-control" name="password" placeholder="Password" required>
                </div>
              </div>

              <div class="mb-3 text-end">
                <a href="recover-password.php" class="text-decoration-none">Recover Password</a>
              </div>

              <button type="submit" class="btn btn-primary w-100">Sign In</button>

              <div class="social-login text-center">
                <p class="text-muted">---------or---------</p>
                <div class="d-grid gap-2">
                  <button type="button" class="btn btn-google mb-2">
                    <i class="fab fa-google me-2"></i> Continue with Google
                  </button>
                  <button type="button" class="btn btn-facebook">
                    <i class="fab fa-facebook-f me-2"></i> Continue with Facebook
                  </button>
                </div>
              </div>

              <div class="text-center mt-3">
                <p class="mb-0">Don't have account yet? 
                  <a href="register.php" class="text-decoration-none">Sign Up</a>
                </p>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>