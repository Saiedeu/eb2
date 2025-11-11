<?php
/**
 * Resend Verification Email Page
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

// Redirect if already logged in
if ($userAuth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';
$email = $_GET['email'] ?? '';

// Handle form submission
if ($_POST) {
    // Rate limiting
    if (!$security->checkRateLimit('resend_verification_' . $security->getClientIp(), 3, 3600)) {
        $error = 'Too many verification requests. Please try again in an hour.';
    } else {
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $error = 'Please enter your email address';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address';
        } else {
            $result = $userAuth->resendVerification($email);
            
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Include header
$pageTitle = 'Resend Verification - ' . getSetting('site_name', 'Exchange Bridge');
require_once '../includes/header.php';
?>

<main class="flex-grow container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <div class="w-16 h-16 mx-auto mb-4 bg-orange-500 rounded-full flex items-center justify-center">
                    <i class="fas fa-redo text-white text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Resend Verification</h1>
                <p class="text-gray-600 dark:text-gray-400">
                    Didn't receive the verification email? We can send it again.
                </p>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                </div>
                
                <div class="text-center">
                    <div class="mb-6">
                        <div class="w-20 h-20 mx-auto mb-4 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-envelope text-green-600 text-3xl"></i>
                        </div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Verification Email Sent!</h2>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">
                            We've sent a new verification email to <strong><?php echo htmlspecialchars($email); ?></strong>. 
                            Please check your inbox and follow the instructions.
                        </p>
                    </div>
                    
                    <div class="space-y-3">
                        <a href="login.php" 
                           class="block w-full bg-primary hover:bg-primary-dark text-white font-medium py-2 px-4 rounded-md transition duration-200">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Back to Login
                        </a>
                        <a href="../index.php" 
                           class="block w-full bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md transition duration-200">
                            <i class="fas fa-home mr-2"></i>
                            Go to Homepage
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <form method="POST" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Email Address
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($email); ?>"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-base"
                               placeholder="Enter your email address"
                               required>
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-primary hover:bg-primary-dark text-white font-medium py-2 px-4 rounded-md transition duration-200">
                        <i class="fas fa-redo mr-2"></i>
                        Resend Verification Email
                    </button>
                </form>
                
                <div class="mt-8 text-center space-y-2">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <a href="login.php" class="text-primary hover:text-primary-dark font-medium">
                            <i class="fas fa-arrow-left mr-1"></i>
                            Back to Login
                        </a>
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Don't have an account? 
                        <a href="register.php" class="text-primary hover:text-primary-dark font-medium">
                            Sign up here
                        </a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>