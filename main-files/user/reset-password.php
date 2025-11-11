<?php
/**
 * Reset Password Page
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
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: forgot-password.php');
    exit();
}

// Handle form submission
if ($_POST) {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($newPassword) || empty($confirmPassword)) {
        $error = 'Please fill in all fields';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($newPassword) < 8) {
        $error = 'Password must be at least 8 characters long';
    } else {
        $result = $userAuth->resetPassword($token, $newPassword);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

// Include header
$pageTitle = 'Reset Password - ' . getSetting('site_name', 'Exchange Bridge');
require_once '../includes/header.php';
?>

<main class="flex-grow container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <div class="w-16 h-16 mx-auto mb-4 bg-blue-500 rounded-full flex items-center justify-center">
                    <i class="fas fa-lock text-white text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Reset Password</h1>
                <p class="text-gray-600 dark:text-gray-400">Enter your new password below</p>
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
                            <i class="fas fa-check text-green-600 text-3xl"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Password Reset Successful!</h2>
                        <p class="text-gray-600 dark:text-gray-400">
                            Your password has been reset successfully. You can now login with your new password.
                        </p>
                    </div>
                    
                    <a href="login.php" 
                       class="block w-full bg-primary hover:bg-primary-dark text-white font-medium py-2 px-4 rounded-md transition duration-200">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Login to Your Account
                    </a>
                </div>
            <?php else: ?>
                <form method="POST" class="space-y-6">
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            New Password
                        </label>
                        <input type="password" 
                               id="new_password" 
                               name="new_password" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-base"
                               required>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            At least 8 characters long
                        </p>
                    </div>
                    
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Confirm New Password
                        </label>
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-base"
                               required>
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-primary hover:bg-primary-dark text-white font-medium py-2 px-4 rounded-md transition duration-200">
                        <i class="fas fa-save mr-2"></i>
                        Reset Password
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>