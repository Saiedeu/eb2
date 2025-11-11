<?php
/**
 * User Registration Page
 */

// Start session and define access
session_start();
define('ALLOW_ACCESS', true);

// Include required files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/user-auth.php';
require_once '../includes/security.php';

$userAuth = UserAuth::getInstance();
$security = Security::getInstance();

// Check if registration is enabled
if (getSetting('user_registration_enabled', 'yes') !== 'yes') {
    header('Location: login.php');
    exit();
}

// Redirect if already logged in
if ($userAuth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

// Handle form submission
if ($_POST) {
    // Rate limiting
    if (!$security->checkRateLimit('user_register_' . $security->getClientIp(), 3, 3600)) {
        $error = 'Too many registration attempts. Please try again in an hour.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $terms = $_POST['terms'] ?? '';
        
        // Validation
        if (empty($email) || empty($password) || empty($confirmPassword)) {
            $error = 'Please fill in all required fields';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long';
        } elseif (!$terms) {
            $error = 'Please accept the terms and conditions';
        } else {
            $result = $userAuth->register($email, $password, $name);
            
            if ($result['success']) {
                if ($result['needs_verification']) {
                    $success = $result['message'];
                } else {
                    header('Location: login.php?registered=1');
                    exit();
                }
            } else {
                $error = $result['message'];
                
                // Show resend verification option if needed
                if (isset($result['needs_verification']) && $result['needs_verification']) {
                    $showResendVerification = true;
                }
            }
        }
    }
}

// Include header
$pageTitle = 'Sign Up - ' . getSetting('site_name', 'Exchange Bridge');
require_once '../includes/header.php';
?>

<main class="flex-grow container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Create Account</h1>
                <p class="text-gray-600 dark:text-gray-400">Join us and save your transaction history</p>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                    
                    <?php if (isset($showResendVerification) && $showResendVerification): ?>
                        <div class="mt-2">
                            <a href="resend-verification.php?email=<?php echo urlencode($email); ?>" 
                               class="text-blue-600 hover:text-blue-800 underline">
                                Resend verification email
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Full Name (Optional)
                    </label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="<?php echo htmlspecialchars($name ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-base">
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Email Address *
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="<?php echo htmlspecialchars($email ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-base"
                           required>
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Password *
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-base"
                           required>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        At least 8 characters long
                    </p>
                </div>
                
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Confirm Password *
                    </label>
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-base"
                           required>
                </div>
                
                <div class="flex items-start">
                    <input type="checkbox" 
                           id="terms" 
                           name="terms" 
                           value="1"
                           class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded mt-1"
                           required>
                    <label for="terms" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                        I agree to the 
                        <a href="../terms.php" target="_blank" class="text-primary hover:text-primary-dark">
                            Terms and Conditions
                        </a> 
                        and 
                        <a href="../privacy.php" target="_blank" class="text-primary hover:text-primary-dark">
                            Privacy Policy
                        </a>
                    </label>
                </div>
                
                <button type="submit" 
                        class="w-full bg-primary hover:bg-primary-dark text-white font-medium py-2 px-4 rounded-md transition duration-200">
                    <i class="fas fa-user-plus mr-2"></i>
                    Create Account
                </button>
            </form>
            
            <!-- Google Signup -->
            <?php if (getSetting('google_oauth_enabled', 'no') === 'yes'): ?>
            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white dark:bg-gray-800 text-gray-500">Or sign up with</span>
                    </div>
                </div>
                
                <div class="mt-6">
                    <a href="google-auth.php?action=signup" 
                       class="w-full flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition duration-200">
                        <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Sign up with Google
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="mt-8 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Already have an account? 
                    <a href="login.php" class="text-primary hover:text-primary-dark font-medium">
                        Login here
                    </a>
                </p>
            </div>
        </div>
    </div>
</main>

<script>
// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

// Real-time password strength indicator
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const strength = calculatePasswordStrength(password);
    
    // You can add a visual password strength indicator here
    console.log('Password strength:', strength);
});

function calculatePasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    
    return strength;
}
</script>

<?php require_once '../includes/footer.php'; ?>