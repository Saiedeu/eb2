<?php
// Set the base path
$basePath = realpath(__DIR__ . '/..');

// Include configuration and autoloader
require_once $basePath . '/config/config.php';
require_once $basePath . '/includes/autoloader.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize the User class
$user = new User();

// Redirect to dashboard if already logged in
if ($user->isLoggedIn()) {
    header('Location: ' . SITE_URL . '/users/dashboard.php');
    exit;
}

// Initialize error and success messages
$error = '';
$success = '';

// Check for success messages from other pages
if (isset($_GET['registered'])) {
    $success = 'Registration successful! Please check your email to verify your account.';
}

if (isset($_GET['verified'])) {
    $success = 'Email verified successfully! You can now log in.';
}

if (isset($_GET['reset'])) {
    $success = 'Your password has been reset. Please log in with your new password.';
}

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    try {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        if (empty($email) || empty($password)) {
            throw new Exception('Please enter both email and password');
        }
        
        if ($user->login($email, $password, $remember)) {
            // Redirect to dashboard or return URL
            $returnUrl = $_GET['return_url'] ?? 'dashboard.php';
            header('Location: ' . SITE_URL . '/users/' . $returnUrl);
            exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Set page title
$pageTitle = 'Login - ' . SITE_NAME;

// Include header
include $basePath . '/templates/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h2 class="h4">Sign In</h2>
                        <p class="text-muted">Access your Exchange Bridge account</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="" novalidate>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                   required autofocus>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <label for="password" class="form-label">Password</label>
                                <a href="forgot-password.php" class="text-decoration-none small">Forgot password?</a>
                            </div>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        
                        <button type="submit" name="login" class="btn btn-primary w-100 py-2 mb-3">
                            Sign In
                        </button>
                        
                        <div class="text-center mb-3">
                            <span class="text-muted">Or sign in with</span>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="<?php echo SITE_URL; ?>/users/google-auth.php?action=login" class="btn btn-outline-danger">
                                <i class="fab fa-google me-2"></i> Google
                            </a>
                        </div>
                    </form>
                </div>
                
                <div class="card-footer text-center py-3">
                    Don't have an account? <a href="register.php" class="text-decoration-none">Sign up</a>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <a href="<?php echo SITE_URL; ?>" class="text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
</div>

<?php 
// Include footer
include $basePath . '/templates/footer.php';
?>
