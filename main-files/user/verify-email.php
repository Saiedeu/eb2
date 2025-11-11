<?php
/**
 * Email Verification Page
 */

// Start session and define access
session_start();
define('ALLOW_ACCESS', true);

// Include required files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/user-auth.php';

$userAuth = UserAuth::getInstance();

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = 'Invalid verification link';
} else {
    $result = $userAuth->verifyEmail($token);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// Include header
$pageTitle = 'Email Verification - ' . getSetting('site_name', 'Exchange Bridge');
require_once '../includes/header.php';
?>

<main class="flex-grow container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <div class="w-16 h-16 mx-auto mb-4 bg-primary rounded-full flex items-center justify-center">
                    <i class="fas fa-envelope-open text-white text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Email Verification</h1>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                </div>
                
                <div class="text-center">
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        If you're having trouble with email verification, you can:
                    </p>
                    <div class="space-y-3">
                        <a href="resend-verification.php" 
                           class="block w-full bg-primary hover:bg-primary-dark text-white font-medium py-2 px-4 rounded-md transition duration-200">
                            <i class="fas fa-redo mr-2"></i>
                            Resend Verification Email
                        </a>
                        <a href="login.php" 
                           class="block w-full bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md transition duration-200">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Login
                        </a>
                    </div>
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
                            <i class="fas fa-check text-green-600 text-3xl"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Verification Successful!</h2>
                        <p class="text-gray-600 dark:text-gray-400">
                            Your email has been verified. You can now login to your account.
                        </p>
                    </div>
                    
                    <div class="space-y-3">
                        <a href="login.php" 
                           class="block w-full bg-primary hover:bg-primary-dark text-white font-medium py-2 px-4 rounded-md transition duration-200">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Login to Your Account
                        </a>
                        <a href="../index.php" 
                           class="block w-full bg-secondary hover:bg-secondary-dark text-white font-medium py-2 px-4 rounded-md transition duration-200">
                            <i class="fas fa-exchange-alt mr-2"></i>
                            Start Exchange
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>