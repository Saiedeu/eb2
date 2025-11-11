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

// Process registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    try {
        // Get form data
        $name = trim($_POST['name'] ?? '');
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $agreeTerms = isset($_POST['agree_terms']);
        
        // Validate input
        if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
            throw new Exception('All fields are required');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address');
        }
        
        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters long');
        }
        
        if ($password !== $confirmPassword) {
            throw new Exception('Passwords do not match');
        }
        
        if (!$agreeTerms) {
            throw new Exception('You must agree to the terms and conditions');
        }
        
        // Register the user
        $userId = $user->register($email, $password, $name, $phone);
        
        // Redirect to login page with success message
        header('Location: login.php?registered=1');
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Set page title
$pageTitle = 'Create Account - ' . SITE_NAME;

// Include header
include $basePath . '/templates/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h2 class="h4">Create an Account</h2>
                        <p class="text-muted">Join Exchange Bridge to get started</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-grid gap-2 mb-4">
                                <a href="<?php echo SITE_URL; ?>/users/google-auth.php?action=register" class="btn btn-outline-danger">
                                    <i class="fab fa-google me-2"></i> Sign up with Google
                                </a>
                            </div>
                            
                            <div class="text-center mb-4">
                                <span class="text-muted">OR</span>
                            </div>
                        </div>
                    </div>
                    
                    <form method="post" action="" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                       required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                       required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       required minlength="8">
                                <div class="form-text">At least 8 characters</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       required minlength="8">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number (Optional)</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                            <div class="form-text">For transaction notifications</div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="agree_terms" name="agree_terms" 
                                   <?php echo isset($_POST['agree_terms']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="agree_terms">
                                I agree to the <a href="<?php echo SITE_URL; ?>/terms.php" target="_blank">Terms of Service</a> 
                                and <a href="<?php echo SITE_URL; ?>/privacy.php" target="_blank">Privacy Policy</a>
                            </label>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="register" class="btn btn-primary py-2">
                                Create Account
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="card-footer text-center py-3">
                    Already have an account? <a href="login.php" class="text-decoration-none">Sign in</a>
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

<script>
// Client-side password validation
const password = document.getElementById('password');
const confirmPassword = document.getElementById('confirm_password');
const form = document.querySelector('form');

function validatePassword() {
    if (password.value !== confirmPassword.value) {
        confirmPassword.setCustomValidity("Passwords don't match");
    } else {
        confirmPassword.setCustomValidity('');
    }
}

password.onchange = validatePassword;
confirmPassword.onkeyup = validatePassword;

// Form submission
form.addEventListener('submit', function(event) {
    if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    form.classList.add('was-validated');
}, false);
</script>

<?php 
// Include footer
include $basePath . '/templates/footer.php';
?>
