<?php
session_start();
if(isset($_SESSION['user_id'])) {
    if(isset($_SESSION['user']['role'])) {
        switch($_SESSION['user']['role']) {
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
                header("Location: index.php");
        }
    } else {
        header("Location: index.php");
    }
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Integrated Healthcare Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">IHMS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-primary me-2" href="index.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary" href="register.php">Sign Up</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header id="home" class="hero">
        <div class="design-element design-circle-1"></div>
        <div class="design-element design-circle-2"></div>
        <div class="design-element design-square"></div>
        <div class="design-element design-dots"></div>
        
        <div class="container h-100">
            <div class="row h-100 align-items-center">
                <div class="col-lg-7 text-center text-lg-start animate__animated animate__fadeInUp">
                    <h1 class="display-4 fw-bold text-gradient mb-4">Integrated Healthcare Management System</h1>
                    <p class="lead mb-4">Revolutionizing healthcare management with advanced technology and seamless integration. Experience the future of healthcare today.</p>
                    <div class="d-flex gap-3 justify-content-center justify-content-lg-start">
                        <a href="register.php" class="btn btn-primary btn-lg">Get Started</a>
                        <a href="#about" class="btn btn-outline-primary btn-lg">Learn More</a>
                    </div>
                </div>
                <div class="col-lg-5 d-none d-lg-block animate__animated animate__fadeInRight">
                    <div class="hero-image-wrapper">
                        <i class="bi bi-heart-pulse-fill text-primary display-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- About Section -->
    <section id="about" class="about-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center mb-5">
                    <h2 class="section-title">Why Choose Us?</h2>
                    <p class="section-subtitle">Transforming Healthcare Management with Innovation</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card">
                        <div class="icon-wrapper">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h3>Secure & Reliable</h3>
                        <p>Advanced security measures to protect sensitive medical data and ensure privacy compliance.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card">
                        <div class="icon-wrapper">
                            <i class="bi bi-lightning-charge"></i>
                        </div>
                        <h3>Fast & Efficient</h3>
                        <p>Streamlined processes for quick access to patient information and medical records.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card">
                        <div class="icon-wrapper">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <h3>Analytics</h3>
                        <p>Comprehensive reporting and analytics tools for informed decision-making.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card">
                        <div class="icon-wrapper">
                            <i class="bi bi-people"></i>
                        </div>
                        <h3>User-Friendly</h3>
                        <p>Intuitive interface designed for healthcare professionals and administrators.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center mb-5">
                    <h2 class="section-title">Key Features</h2>
                    <p class="section-subtitle">Comprehensive Healthcare Management Tools</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card h-100">
                        <div class="icon-wrapper">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <h3>Appointment Management</h3>
                        <p>Easy scheduling and management of appointments for both doctors and patients.</p>
                        <ul class="feature-list">
                            <li><i class="bi bi-check-circle"></i> Online Booking</li>
                            <li><i class="bi bi-check-circle"></i> Automated Reminders</li>
                            <li><i class="bi bi-check-circle"></i> Schedule Management</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card h-100">
                        <div class="icon-wrapper">
                            <i class="bi bi-file-medical"></i>
                        </div>
                        <h3>Medical Records</h3>
                        <p>Secure storage and easy access to patient medical records and history.</p>
                        <ul class="feature-list">
                            <li><i class="bi bi-check-circle"></i> Digital Records</li>
                            <li><i class="bi bi-check-circle"></i> Easy Access</li>
                            <li><i class="bi bi-check-circle"></i> Secure Storage</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card h-100">
                        <div class="icon-wrapper">
                            <i class="bi bi-clipboard2-pulse"></i>
                        </div>
                        <h3>Prescription Management</h3>
                        <p>Digital prescription system with medication tracking and history.</p>
                        <ul class="feature-list">
                            <li><i class="bi bi-check-circle"></i> Digital Prescriptions</li>
                            <li><i class="bi bi-check-circle"></i> Medication Tracking</li>
                            <li><i class="bi bi-check-circle"></i> History Management</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Blood Management Features -->
            <div class="row justify-content-center mt-5">
                <div class="col-lg-8 text-center mb-5">
                    <h2 class="section-title">Blood Bank Services</h2>
                    <p class="section-subtitle">Efficient Blood Donation and Request Management</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="feature-card h-100">
                        <div class="icon-wrapper">
                            <i class="bi bi-droplet-fill"></i>
                        </div>
                        <h3>Blood Donation Management</h3>
                        <p>Comprehensive blood donation and inventory management system.</p>
                        <ul class="feature-list">
                            <li><i class="bi bi-check-circle"></i> Blood Donation Scheduling</li>
                            <li><i class="bi bi-check-circle"></i> Donor Health Tracking</li>
                            <li><i class="bi bi-check-circle"></i> Blood Stock Management</li>
                            <li><i class="bi bi-check-circle"></i> Donation History</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="feature-card h-100">
                        <div class="icon-wrapper">
                            <i class="bi bi-heart-pulse"></i>
                        </div>
                        <h3>Emergency Blood Services</h3>
                        <p>Quick and efficient emergency blood request system.</p>
                        <ul class="feature-list">
                            <li><i class="bi bi-check-circle"></i> Emergency Blood Requests</li>
                            <li><i class="bi bi-check-circle"></i> Real-time Availability</li>
                            <li><i class="bi bi-check-circle"></i> Donor Notifications</li>
                            <li><i class="bi bi-check-circle"></i> Blood Type Matching</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row gy-4">
                <div class="col-lg-4">
                    <h4 class="text-gradient">IHMS</h4>
                    <p class="mb-4">Empowering healthcare professionals with cutting-edge management solutions.</p>
                </div>
                <div class="col-lg-4">
                    <h5>Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="#home">Home</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="#features">Features</a></li>
                        <li><a href="index.php">Login</a></li>
                        <li><a href="register.php">Sign Up</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h5>Contact</h5>
                    <ul class="footer-contact">
                        <li><i class="bi bi-envelope"></i> contact@ihms.com</li>
                        <li><i class="bi bi-telephone"></i> +1 234 567 890</li>
                        <li><i class="bi bi-geo-alt"></i> 123 Healthcare Street, Medical Center</li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0">&copy; 2024 IHMS. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <div class="social-links">
                        <a href="#"><i class="bi bi-facebook"></i></a>
                        <a href="#"><i class="bi bi-twitter"></i></a>
                        <a href="#"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 