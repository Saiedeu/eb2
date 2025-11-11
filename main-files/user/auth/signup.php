<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../config/mail.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('../dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        setMessage('error', 'All fields are required');
    } elseif (!validateEmail($email)) {
        setMessage('error', 'Please enter a valid email address');
    } elseif (!validatePassword($password)) {
        setMessage('error', 'Password must be at least 6 characters long');
    } elseif ($password !== $confirm_password) {
        setMessage('error', 'Passwords do not match');
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            setMessage('error', 'Email already registered');
        } else {
            // Create user account
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $verification_token = generateToken();
            
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, verification_token, login_type) 
                VALUES (?, ?, ?, ?, 'email')
            ");
            
            if ($stmt->execute([$name, $email, $hashed_password, $verification_token])) {
                // Send verification email
                if (sendVerificationEmail($email, $name, $verification_token)) {
                    setMessage('success', 'Account created! Please check your email to verify your account.');
                    redirect('login.php');
                } else {
                    setMessage('error', 'Account created but failed to send verification email. Please contact support.');
                }
            } else {
                setMessage('error', 'Failed to create account. Please try again.');
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Exchange Bridge</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-form">
            <div class="form-header">
                <h2>Create Account</h2>
                <p>Join Exchange Bridge today</p>
            </div>
            
            <?php displayMessage(); ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required 
                           value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required 
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required 
                           minlength="6" placeholder="At least 6 characters">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Create Account</button>
            </form>
            
            <div class="divider">
                <span>or</span>
            </div>
            
            <a href="google-login.php" class="btn btn-google">
                <svg width="18" height="18" viewBox="0 0 24 24">
                    <path fill="#4285f4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34a853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#fbbc05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#ea4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Continue with Google
            </a>
            
            <div class="form-footer">
                <p>Already have an account? <a href="login.php">Sign in</a></p>
            </div>
        </div>
    </div>
</body>
</html>