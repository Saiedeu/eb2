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

// Redirect to login if not logged in
if (!$user->isLoggedIn()) {
    $_SESSION['return_url'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . SITE_URL . '/users/login.php');
    exit;
}

// Get current user data
$currentUser = $user->getCurrentUser();

// Get user transactions (example - adjust according to your database structure)
$transactions = [];
try {
    $db = Database::getInstance();
    $transactions = $db->getRows(
        "SELECT * FROM user_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5",
        [$currentUser['id']]
    );
} catch (Exception $e) {
    // Log error but don't show to user
    error_log("Error fetching transactions: " . $e->getMessage());
}

// Set page title
$pageTitle = 'Dashboard - ' . SITE_NAME;

// Include header
include $basePath . '/templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="new-exchange.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-exchange-alt me-1"></i> New Exchange
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Welcome Message -->
            <div class="alert alert-info">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle fa-2x"></i>
                    </div>
                    <div class="ms-3">
                        <h5 class="alert-heading mb-1">Welcome back, <?php echo htmlspecialchars($currentUser['name'] ?? 'User'); ?>!</h5>
                        <p class="mb-0">Your user ID: <strong><?php echo htmlspecialchars($currentUser['user_id']); ?></strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 small">Total Transactions</h6>
                            <h3 class="mb-0"><?php echo count($transactions); ?></h3>
                        </div>
                        <div class="icon-shape bg-white bg-opacity-25 rounded p-3">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="transactions.php" class="text-white-50 small text-decoration-none">
                        View all <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 small">Completed</h6>
                            <h3 class="mb-0"><?php 
                                echo count(array_filter($transactions, function($t) { 
                                    return $t['status'] === 'completed'; 
                                })); 
                            ?></h3>
                        </div>
                        <div class="icon-shape bg-white bg-opacity-25 rounded p-3">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-dark-50 small">Pending</h6>
                            <h3 class="mb-0"><?php 
                                echo count(array_filter($transactions, function($t) { 
                                    return $t['status'] === 'pending'; 
                                })); 
                            ?></h3>
                        </div>
                        <div class="icon-shape bg-dark bg-opacity-10 rounded p-3">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 small">Member Since</h6>
                            <h5 class="mb-0"><?php 
                                echo date('M Y', strtotime($currentUser['created_at'])); 
                            ?></h5>
                        </div>
                        <div class="icon-shape bg-white bg-opacity-25 rounded p-3">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Transactions -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Transactions</h5>
                    <a href="transactions.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($transactions)): ?>
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-exchange-alt fa-3x text-muted"></i>
                            </div>
                            <h5>No transactions yet</h5>
                            <p class="text-muted">Start by creating your first exchange</p>
                            <a href="new-exchange.php" class="btn btn-primary mt-2">
                                <i class="fas fa-plus me-1"></i> New Exchange
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Transaction ID</th>
                                        <th>Date</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $tx): ?>
                                        <tr>
                                            <td><?php echo substr($tx['transaction_id'], 0, 8) . '...'; ?></td>
                                            <td><?php echo date('M d, Y', strtotime($tx['created_at'])); ?></td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    <?php echo htmlspecialchars($tx['from_currency']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    <?php echo htmlspecialchars($tx['to_currency']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo number_format($tx['amount'], 8); ?>
                                                <?php echo htmlspecialchars($tx['from_currency']); ?>
                                            </td>
                                            <td>
                                                <?php 
                                                    $statusClass = [
                                                        'pending' => 'warning',
                                                        'completed' => 'success',
                                                        'cancelled' => 'danger'
                                                    ][$tx['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $statusClass; ?>">
                                                    <?php echo ucfirst($tx['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="transaction.php?id=<?php echo $tx['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="new-exchange.php" class="list-group-item list-group-item-action d-flex align-items-center">
                            <div class="icon-shape bg-primary bg-opacity-10 text-primary rounded p-2 me-3">
                                <i class="fas fa-exchange-alt"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">New Exchange</h6>
                                <p class="text-muted mb-0 small">Start a new currency exchange</p>
                            </div>
                            <div class="ms-auto">
                                <i class="fas fa-chevron-right text-muted"></i>
                            </div>
                        </a>
                        
                        <a href="profile.php" class="list-group-item list-group-item-action d-flex align-items-center">
                            <div class="icon-shape bg-success bg-opacity-10 text-success rounded p-2 me-3">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">My Profile</h6>
                                <p class="text-muted mb-0 small">Update your personal information</p>
                            </div>
                            <div class="ms-auto">
                                <i class="fas fa-chevron-right text-muted"></i>
                            </div>
                        </a>
                        
                        <a href="security.php" class="list-group-item list-group-item-action d-flex align-items-center">
                            <div class="icon-shape bg-warning bg-opacity-10 text-warning rounded p-2 me-3">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Security</h6>
                                <p class="text-muted mb-0 small">Change password and security settings</p>
                            </div>
                            <div class="ms-auto">
                                <i class="fas fa-chevron-right text-muted"></i>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Activity</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if (empty($transactions)): ?>
                            <div class="text-center py-5">
                                <div class="mb-2">
                                    <i class="fas fa-history fa-2x text-muted"></i>
                                </div>
                                <p class="text-muted mb-0">No recent activity</p>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($transactions, 0, 5) as $activity): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">
                                            <?php 
                                                echo $activity['status'] === 'completed' 
                                                    ? 'Exchange completed' 
                                                    : 'New exchange started';
                                            ?>
                                        </h6>
                                        <small class="text-muted">
                                            <?php echo time_elapsed_string($activity['created_at']); ?>
                                        </small>
                                    </div>
                                    <p class="mb-1">
                                        Exchanged 
                                        <strong>
                                            <?php echo number_format($activity['amount'], 8); ?> 
                                            <?php echo htmlspecialchars($activity['from_currency']); ?>
                                        </strong> 
                                        to 
                                        <strong>
                                            <?php echo number_format($activity['total'], 8); ?> 
                                            <?php echo htmlspecialchars($activity['to_currency']); ?>
                                        </strong>
                                    </p>
                                    <small class="text-muted">
                                        Transaction ID: <?php echo $activity['transaction_id']; ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// Helper function to format time elapsed
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

// Include footer
include $basePath . '/templates/footer.php';
?>
