<?php
/**
 * User Login Page
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

// Handle form submission
if ($_POST) {
    // Rate limiting
    if (!$security->checkRateLimit('user_login_' . $security->getClientIp(), 5, 900)) {
        $error = 'Too many login attempts. Please try again in 15 minutes.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = 'Please fill in all fields';
        } else {
            $result = $userAuth->login($email, $password);
            
            if ($result['success']) {
                header('Location: dashboard.php');
                exit();
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
$pageTitle = 'Login - ' . getSetting('site_name', 'Exchange Bridge');
require_once '../includes/header.php';
?>

<main class="flex-grow container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Welcome Back</h1>
                <p class="text-gray-600 dark:text-gray-400">Login to your account</p>
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
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Email Address
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
                        Password
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-base"
                           required>
                </div>
                
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="remember" 
                               name="remember" 
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                            Remember me
                        </label>
                    </div>
                    <a href="forgot-password.php" class="text-sm text-primary hover:text-primary-dark">
                        Forgot password?
                    </a>
                </div>
                
                <button type="submit" 
                        class="w-full bg-primary hover:bg-primary-dark text-white font-medium py-2 px-4 rounded-md transition duration-200">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Login
                </button>
            </form>
            
            <!-- Google Login -->
            <?php if (getSetting('google_oauth_enabled', 'no') === 'yes'): ?>
            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white dark:bg-gray-800 text-gray-500">Or continue with</span>
                    </div>
                </div>
                
                <div class="mt-6">
                    <a href="google-auth.php" 
                       class="w-full flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition duration-200">
                        <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Continue with Google
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="mt-8 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Don't have an account? 
                    <a href="register.php" class="text-primary hover:text-primary-dark font-medium">
                        Sign up here
                    </a>
                </p>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>