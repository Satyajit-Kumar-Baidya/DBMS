<?php

session_start();
require_once 'dbConnect.php';

// Check if user is already logged in
if (isset($_SESSION['user'])) {
    header('Location: home.php');
    exit();
}

// Get any errors from previous submission
$errors = isset($_SESSION['errors']) ? $_SESSION['errors'] : [];
unset($_SESSION['errors']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Healthcare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .password-container {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            user-select: none;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="text-center mb-4">Register</h2>
                        
                        <?php if (isset($errors['user_exist'])): ?>
                            <div class="alert alert-danger"><?php echo $errors['user_exist']; ?></div>
                        <?php endif; ?>

                        <form action="user-account.php" method="POST">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>" 
                                       id="first_name" name="first_name" required>
                                <?php if (isset($errors['first_name'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['first_name']; ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>" 
                                       id="last_name" name="last_name" required>
                                <?php if (isset($errors['last_name'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['last_name']; ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                       id="email" name="email" required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="password-container">
                                    <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                           id="password" name="password" required>
                                    <span class="password-toggle" onclick="togglePassword()">👁️</span>
                                </div>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <div class="password-container">
                                    <input type="password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                           id="confirm_password" name="confirm_password" required>
                                    <span class="password-toggle" onclick="toggleConfirmPassword()">👁️</span>
                                </div>
                                <?php if (isset($errors['confirm_password'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['confirm_password']; ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="role" class="form-label">Registering as:</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="patient">Patient</option>
                                    <option value="doctor">Doctor</option>
                                </select>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name="signup" class="btn btn-primary">Register</button>
                            </div>
                        </form>

                        <div class="text-center mt-3">
                            Already have an account? <a href="index.php">Login here</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
        }

        function toggleConfirmPassword() {
            const confirmPasswordInput = document.getElementById('confirm_password');
            confirmPasswordInput.type = confirmPasswordInput.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>

</html>