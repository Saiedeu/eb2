<?php
/**
 * User Dashboard
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

// Check if user is logged in
if (!$userAuth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$currentUser = $userAuth->getCurrentUser();
$error = '';
$success = '';

// Handle profile update
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    if ($security->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $updateData = [
            'name' => trim($_POST['name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? '')
        ];
        
        $result = $userAuth->updateProfile($currentUser['id'], $updateData);
        
        if ($result['success']) {
            $success = $result['message'];
            // Refresh user data
            $currentUser = $userAuth->getCurrentUser();
        } else {
            $error = $result['message'];
        }
    } else {
        $error = 'Invalid request';
    }
}

// Handle password change
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if ($security->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'Please fill in all password fields';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match';
        } elseif (strlen($newPassword) < 8) {
            $error = 'New password must be at least 8 characters long';
        } else {
            $result = $userAuth->changePassword($currentUser['id'], $currentPassword, $newPassword);
            
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    } else {
        $error = 'Invalid request';
    }
}

// Get user transaction history
$page = (int)($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

$transactions = $userAuth->getUserTransactions($currentUser['id'], $limit, $offset);
$totalTransactions = $userAuth->countUserTransactions($currentUser['id']);
$totalPages = ceil($totalTransactions / $limit);

// Include header
$pageTitle = 'Dashboard - ' . getSetting('site_name', 'Exchange Bridge');
require_once '../includes/header.php';
?>

<main class="flex-grow container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Dashboard</h1>
        <p class="text-gray-600 dark:text-gray-400">Welcome back, <?php echo htmlspecialchars($currentUser['name'] ?: 'User'); ?>!</p>
    </div>
    
    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <!-- User Info Card -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Account Information</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">User ID</label>
                        <p class="text-lg font-mono text-gray-900 dark:text-white"><?php echo htmlspecialchars($currentUser['user_id']); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                        <p class="text-lg text-gray-900 dark:text-white"><?php echo htmlspecialchars($currentUser['email']); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Member Since</label>
                        <p class="text-lg text-gray-900 dark:text-white"><?php echo date('M d, Y', strtotime($currentUser['created_at'])); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Total Transactions</label>
                        <p class="text-lg font-bold text-primary"><?php echo $totalTransactions; ?></p>
                    </div>
                </div>
                
                <!-- Update Profile Form -->
                <form method="POST" class="border-t pt-6">
                    <input type="hidden" name="action" value="update_profile">
                    <input type="hidden" name="csrf_token" value="<?php echo $security->generateCSRFToken(); ?>">
                    
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Update Profile</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Full Name
                            </label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   value="<?php echo htmlspecialchars($currentUser['name'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-base">
                        </div>
                        
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Phone Number
                            </label>
                            <input type="tel" 
                                   id="phone" 
                                   name="phone" 
                                   value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-base">
                        </div>
                    </div>
                    
                    <button type="submit" 
                            class="bg-primary hover:bg-primary-dark text-white font-medium py-2 px-6 rounded-md transition duration-200">
                        <i class="fas fa-save mr-2"></i>
                        Update Profile
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Quick Actions</h3>
                
                <div class="space-y-3">
                    <a href="../index.php" 
                       class="block w-full bg-primary hover:bg-primary-dark text-white text-center py-3 px-4 rounded-md transition duration-200">
                        <i class="fas fa-exchange-alt mr-2"></i>
                        New Exchange
                    </a>
                    
                    <a href="../track.php" 
                       class="block w-full bg-secondary hover:bg-secondary-dark text-white text-center py-3 px-4 rounded-md transition duration-200">
                        <i class="fas fa-search mr-2"></i>
                        Track Transaction
                    </a>
                    
                    <button onclick="showPasswordModal()" 
                            class="block w-full bg-gray-600 hover:bg-gray-700 text-white text-center py-3 px-4 rounded-md transition duration-200">
                        <i class="fas fa-key mr-2"></i>
                        Change Password
                    </button>
                </div>
            </div>
            
            <!-- Account Status -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Account Status</h3>
                
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Email Verified</span>
                        <span class="<?php echo $currentUser['email_verified'] ? 'text-green-600' : 'text-red-600'; ?>">
                            <i class="fas fa-<?php echo $currentUser['email_verified'] ? 'check-circle' : 'times-circle'; ?>"></i>
                        </span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Account Status</span>
                        <span class="<?php echo $currentUser['status'] === 'active' ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo ucfirst($currentUser['status']); ?>
                        </span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Last Login</span>
                        <span class="text-sm text-gray-900 dark:text-white">
                            <?php echo $currentUser['last_login'] ? date('M d, Y', strtotime($currentUser['last_login'])) : 'Never'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Transaction History -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Transaction History</h2>
        </div>
        
        <?php if (empty($transactions)): ?>
            <div class="p-8 text-center">
                <div class="text-gray-400 mb-4">
                    <i class="fas fa-history text-4xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-500 dark:text-gray-400 mb-2">No transactions yet</h3>
                <p class="text-gray-400 mb-4">Your transaction history will appear here</p>
                <a href="../index.php" 
                   class="inline-block bg-primary hover:bg-primary-dark text-white py-2 px-6 rounded-md transition duration-200">
                    Start Your First Exchange
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Reference ID
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Exchange
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Amount
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Date
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($transactions as $transaction): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($transaction['reference_id']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($transaction['from_currency_name']); ?> → 
                                        <?php echo htmlspecialchars($transaction['to_currency_name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <?php echo number_format($transaction['send_amount'], 2); ?> <?php echo htmlspecialchars($transaction['from_currency']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        ↓ <?php echo number_format($transaction['receive_amount'], 2); ?> <?php echo htmlspecialchars($transaction['to_currency']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                        <?php 
                                        switch($transaction['status']) {
                                            case 'confirmed':
                                                echo 'bg-green-100 text-green-800';
                                                break;
                                            case 'pending':
                                                echo 'bg-yellow-100 text-yellow-800';
                                                break;
                                            case 'cancelled':
                                                echo 'bg-red-100 text-red-800';
                                                break;
                                            default:
                                                echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo ucfirst($transaction['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <?php echo date('M d, Y H:i', strtotime($transaction['created_at'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $limit, $totalTransactions); ?> of <?php echo $totalTransactions; ?> transactions
                        </div>
                        
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo ($page - 1); ?>" 
                                   class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-100 dark:hover:bg-gray-700">
                                    Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>" 
                                   class="px-3 py-1 border rounded text-sm <?php echo $i === $page ? 'bg-primary text-white border-primary' : 'border-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo ($page + 1); ?>" 
                                   class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-100 dark:hover:bg-gray-700">
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<!-- Password Change Modal -->
<div id="passwordModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Change Password</h3>
                    <button onclick="hidePasswordModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <input type="hidden" name="csrf_token" value="<?php echo $security->generateCSRFToken(); ?>">
                    
                    <div class="space-y-4">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Current Password
                            </label>
                            <input type="password" 
                                   id="current_password" 
                                   name="current_password" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-base"
                                   required>
                        </div>
                        
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                New Password
                            </label>
                            <input type="password" 
                                   id="new_password" 
                                   name="new_password" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-base"
                                   required>
                        </div>
                        
                        <div>
                            <label for="confirm_new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Confirm New Password
                            </label>
                            <input type="password" 
                                   id="confirm_new_password" 
                                   name="confirm_password" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-base"
                                   required>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" 
                                onclick="hidePasswordModal()" 
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-md">
                            Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showPasswordModal() {
    document.getElementById('passwordModal').classList.remove('hidden');
}

function hidePasswordModal() {
    document.getElementById('passwordModal').classList.add('hidden');
    // Clear form
    document.querySelector('#passwordModal form').reset();
}

// Password confirmation validation
document.getElementById('confirm_new_password').addEventListener('input', function() {
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