<?php
session_start();
require_once 'config/database.php';

$error = '';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    
    // Validation
    if (empty($fullname) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'All fields are required';
    } 
    elseif (strlen($fullname) < 3) {
        $error = 'Full name must be at least 3 characters';
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    }
    elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    }
    elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    }
    else {
        try {
            $pdo = getPDOConnection();
            
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Email already registered';
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $stmt = $pdo->prepare("
                    INSERT INTO users (name, email, password, created_at, updated_at) 
                    VALUES (?, ?, ?, NOW(), NOW())
                ");
                
                if ($stmt->execute([$fullname, $email, $hashedPassword])) {
                    // Get the new user ID
                    $userId = $pdo->lastInsertId();
                    
                    // Set session
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_name'] = $fullname;
                    $_SESSION['user_email'] = $email;
                    
                    // Redirect to dashboard
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $error = 'Registration failed. Please try again';
                }
            }
            
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - FET | Food Expiry Tracker</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>

<!-- Background -->
<div class="auth-background">
    <div class="floating-shape shape-1"></div>
    <div class="floating-shape shape-2"></div>
    <div class="floating-shape shape-3"></div>
    <div class="floating-shape shape-4"></div>
</div>

<a href="index.php" class="btn-back-home">
    <i class="fas fa-arrow-left"></i> Back to Home
</a>

<div class="auth-container">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-lg-10">
                <div class="auth-card">
                    <div class="row g-0">

                        <!-- Left Illustration -->
                        <div class="col-lg-6 d-none d-lg-block">
                            <div class="auth-illustration register-illustration">
                                <div class="illustration-content">
                                    <div class="brand-logo"><h2>FET</h2></div>
                                    <h3 class="illustration-title">Start Your Journey!</h3>
                                    <p class="illustration-subtitle">
                                        Reduce food waste and manage inventory smarter.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Right Form -->
                        <div class="col-lg-6">
                            <div class="auth-form-wrapper">

                                <div class="auth-header">
                                    <h1 class="auth-title">Create Account</h1>
                                    <p class="auth-subtitle">Sign up to get started</p>
                                </div>

                                <?php if ($error): ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-circle me-2"></i>
                                        <?= htmlspecialchars($error); ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" class="auth-form">

                                    <!-- Full Name -->
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-user"></i> Full Name
                                        </label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            name="fullname"
                                            value="<?= isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : '' ?>"
                                            required
                                            minlength="3"
                                        >
                                    </div>

                                    <!-- Email -->
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-envelope"></i> Email
                                        </label>
                                        <input
                                            type="email"
                                            class="form-control"
                                            name="email"
                                            value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                                            required
                                        >
                                    </div>

                                    <!-- Password -->
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-lock"></i> Password
                                        </label>
                                        <input
                                            type="password"
                                            class="form-control"
                                            name="password"
                                            required
                                            minlength="6"
                                        >
                                    </div>

                                    <!-- Confirm -->
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-lock"></i> Confirm Password
                                        </label>
                                        <input
                                            type="password"
                                            class="form-control"
                                            name="confirmPassword"
                                            required
                                            minlength="6"
                                        >
                                    </div>

                                    <button type="submit" name="submit" class="btn btn-primary btn-auth">
                                        <i class="fas fa-user-plus"></i> Create Account
                                    </button>

                                    <div class="auth-footer mt-3">
                                        <p>Already have an account?
                                            <a href="login.php" class="auth-link">Sign In</a>
                                        </p>
                                    </div>

                                </form>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>