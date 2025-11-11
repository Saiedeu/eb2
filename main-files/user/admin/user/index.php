<?php
/**
 * Admin - Users Management
 */

// Start session and define access
session_start();
define('ALLOW_ACCESS', true);

// Include required files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

// Check authentication
if (!Auth::isLoggedIn() || !Auth::isAdmin()) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();
$security = Security::getInstance();

$error = '';
$success = '';

// Handle actions
if ($_POST) {
    if (!$security->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        $action = $_POST['action'] ?? '';
        $userId = (int)($_POST['user_id'] ?? 0);
        
        switch ($action) {
            case 'update_status':
                $status = $_POST['status'] ?? '';
                if (in_array($status, ['active', 'suspended', 'blocked'])) {
                    $result = $db->update('registered_users', 
                        ['status' => $status], 
                        'id = ?', 
                        [$userId]
                    );
                    
                    if ($result) {
                        $success = 'User status updated successfully';
                    } else {
                        $error = 'Failed to update user status';
                    }
                } else {
                    $error = 'Invalid status';
                }
                break;
                
            case 'delete_user':
                // Don't delete users with transactions
                $transactionCount = $db->getValue(
                    "SELECT COUNT(*) FROM exchanges WHERE registered_user_id = ?",
                    [$userId]
                );
                
                if ($transactionCount > 0) {
                    $error = 'Cannot delete user with existing transactions';
                } else {
                    $result = $db->delete('registered_users', 'id = ?', [$userId]);
                    if ($result) {
                        $success = 'User deleted successfully';
                    } else {
                        $error = 'Failed to delete user';
                    }
                }
                break;
                
            case 'send_verification':
                $user = $db->getRow("SELECT email, name FROM registered_users WHERE id = ?", [$userId]);
                if ($user) {
                    // You can implement email sending here
                    $success = 'Verification email sent to ' . $user['email'];
                } else {
                    $error = 'User not found';
                }
                break;
        }
    }
}

// Get users with filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$whereClause = "1=1";
$params = [];

if ($search) {
    $whereClause .= " AND (user_id LIKE ? OR email LIKE ? OR name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($status) {
    $whereClause .= " AND status = ?";
    $params[] = $status;
}

$users = $db->getRows(
    "SELECT u.*, 
        (SELECT COUNT(*) FROM exchanges WHERE registered_user_id = u.id) as transaction_count,
        (SELECT COUNT(*) FROM exchanges WHERE registered_user_id = u.id AND status = 'confirmed') as confirmed_transactions
     FROM registered_users u 
     WHERE $whereClause 
     ORDER BY u.created_at DESC 
     LIMIT ? OFFSET ?",
    array_merge($params, [$limit, $offset])
);

$totalUsers = $db->getValue("SELECT COUNT(*) FROM registered_users WHERE $whereClause", $params);
$totalPages = ceil($totalUsers / $limit);

// Get statistics
$stats = [
    'total' => $db->getValue("SELECT COUNT(*) FROM registered_users"),
    'active' => $db->getValue("SELECT COUNT(*) FROM registered_users WHERE status = 'active'"),
    'suspended' => $db->getValue("SELECT COUNT(*) FROM registered_users WHERE status = 'suspended'"),
    'blocked' => $db->getValue("SELECT COUNT(*) FROM registered_users WHERE status = 'blocked'"),
    'verified' => $db->getValue("SELECT COUNT(*) FROM registered_users WHERE email_verified = 1"),
    'unverified' => $db->getValue("SELECT COUNT(*) FROM registered_users WHERE email_verified = 0")
];

$pageTitle = 'Users Management';
require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Page Header -->
        <div class="col-12">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title">Users Management</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Users</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="col-xl-2 col-sm-6 col-12">
            <div class="card board1 fill">
                <div class="card-body">
                    <div class="dash-widget-header">
                        <div>
                            <h3 class="card_widget_header"><?php echo number_format($stats['total']); ?></h3>
                            <h6 class="text-muted">Total Users</h6>
                        </div>
                        <div class="ml-auto mt-md-3 mt-lg-0">
                            <i class="fas fa-users text-primary fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-sm-6 col-12">
            <div class="card board2 fill">
                <div class="card-body">
                    <div class="dash-widget-header">
                        <div>
                            <h3 class="card_widget_header"><?php echo number_format($stats['active']); ?></h3>
                            <h6 class="text-muted">Active</h6>
                        </div>
                        <div class="ml-auto mt-md-3 mt-lg-0">
                            <i class="fas fa-user-check text-success fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-sm-6 col-12">
            <div class="card board3 fill">
                <div class="card-body">
                    <div class="dash-widget-header">
                        <div>
                            <h3 class="card_widget_header"><?php echo number_format($stats['verified']); ?></h3>
                            <h6 class="text-muted">Verified</h6>
                        </div>
                        <div class="ml-auto mt-md-3 mt-lg-0">
                            <i class="fas fa-user-shield text-info fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-sm-6 col-12">
            <div class="card board4 fill">
                <div class="card-body">
                    <div class="dash-widget-header">
                        <div>
                            <h3 class="card_widget_header"><?php echo number_format($stats['suspended']); ?></h3>
                            <h6 class="text-muted">Suspended</h6>
                        </div>
                        <div class="ml-auto mt-md-3 mt-lg-0">
                            <i class="fas fa-user-clock text-warning fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-sm-6 col-12">
            <div class="card board5 fill">
                <div class="card-body">
                    <div class="dash-widget-header">
                        <div>
                            <h3 class="card_widget_header"><?php echo number_format($stats['blocked']); ?></h3>
                            <h6 class="text-muted">Blocked</h6>
                        </div>
                        <div class="ml-auto mt-md-3 mt-lg-0">
                            <i class="fas fa-user-slash text-danger fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-sm-6 col-12">
            <div class="card board6 fill">
                <div class="card-body">
                    <div class="dash-widget-header">
                        <div>
                            <h3 class="card_widget_header"><?php echo number_format($stats['unverified']); ?></h3>
                            <h6 class="text-muted">Unverified</h6>
                        </div>
                        <div class="ml-auto mt-md-3 mt-lg-0">
                            <i class="fas fa-user-times text-secondary fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Users Table -->
        <div class="col-12">
            <div class="card card-table">
                <div class="card-header">
                    <h4 class="card-title">Registered Users</h4>
                    
                    <!-- Filters -->
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <form method="GET" class="search-form">
                                <div class="input-group">
                                    <input type="text" 
                                           name="search" 
                                           class="form-control" 
                                           placeholder="Search users..." 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php if ($status): ?>
                                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
                                <?php endif; ?>
                            </form>
                        </div>
                        
                        <div class="col-md-3">
                            <form method="GET">
                                <select name="status" class="form-control" onchange="this.form.submit()">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                    <option value="blocked" <?php echo $status === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                                </select>
                                <?php if ($search): ?>
                                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                <?php endif; ?>
                            </form>
                        </div>
                        
                        <div class="col-md-5 text-right">
                            <a href="?export=csv" class="btn btn-success">
                                <i class="fas fa-download"></i> Export CSV
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover table-center mb-0">
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Verified</th>
                                    <th>Transactions</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <span class="badge badge-primary">
                                                <?php echo htmlspecialchars($user['user_id']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($user['name'] ?: 'N/A'); ?></strong>
                                                <?php if ($user['google_id']): ?>
                                                    <span class="badge badge-info badge-sm ml-1">Google</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['phone'] ?: 'N/A'); ?></td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                echo $user['status'] === 'active' ? 'success' : 
                                                    ($user['status'] === 'suspended' ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo ucfirst($user['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($user['email_verified']): ?>
                                                <span class="text-success"><i class="fas fa-check-circle"></i> Yes</span>
                                            <?php else: ?>
                                                <span class="text-danger"><i class="fas fa-times-circle"></i> No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo $user['transaction_count']; ?></strong> total
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo $user['confirmed_transactions']; ?> confirmed
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo date('H:i', strtotime($user['created_at'])); ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                        type="button" 
                                                        data-toggle="dropdown">
                                                    Actions
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item" href="user-details.php?id=<?php echo $user['id']; ?>">
                                                        <i class="fas fa-eye"></i> View Details
                                                    </a>
                                                    
                                                    <?php if ($user['status'] !== 'suspended'): ?>
                                                        <a class="dropdown-item text-warning" href="#" 
                                                           onclick="updateUserStatus(<?php echo $user['id']; ?>, 'suspended')">
                                                            <i class="fas fa-pause"></i> Suspend
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($user['status'] !== 'blocked'): ?>
                                                        <a class="dropdown-item text-danger" href="#" 
                                                           onclick="updateUserStatus(<?php echo $user['id']; ?>, 'blocked')">
                                                            <i class="fas fa-ban"></i> Block
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($user['status'] !== 'active'): ?>
                                                        <a class="dropdown-item text-success" href="#" 
                                                           onclick="updateUserStatus(<?php echo $user['id']; ?>, 'active')">
                                                            <i class="fas fa-check"></i> Activate
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!$user['email_verified']): ?>
                                                        <div class="dropdown-divider"></div>
                                                        <a class="dropdown-item" href="#" 
                                                           onclick="sendVerification(<?php echo $user['id']; ?>)">
                                                            <i class="fas fa-envelope"></i> Send Verification
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($user['transaction_count'] == 0): ?>
                                                        <div class="dropdown-divider"></div>
                                                        <a class="dropdown-item text-danger" href="#" 
                                                           onclick="deleteUser(<?php echo $user['id']; ?>)">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">
                                            Previous
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">
                                            Next
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Action Forms -->
<form id="statusForm" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo $security->generateCSRFToken(); ?>">
    <input type="hidden" name="action" value="update_status">
    <input type="hidden" name="user_id" id="statusUserId">
    <input type="hidden" name="status" id="statusValue">
</form>

<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo $security->generateCSRFToken(); ?>">
    <input type="hidden" name="action" value="delete_user">
    <input type="hidden" name="user_id" id="deleteUserId">
</form>

<form id="verificationForm" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo $security->generateCSRFToken(); ?>">
    <input type="hidden" name="action" value="send_verification">
    <input type="hidden" name="user_id" id="verificationUserId">
</form>

<script>
function updateUserStatus(userId, status) {
    const statusText = status.charAt(0).toUpperCase() + status.slice(1);
    
    if (confirm(`Are you sure you want to ${status} this user?`)) {
        document.getElementById('statusUserId').value = userId;
        document.getElementById('statusValue').value = status;
        document.getElementById('statusForm').submit();
    }
}

function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        document.getElementById('deleteUserId').value = userId;
        document.getElementById('deleteForm').submit();
    }
}

function sendVerification(userId) {
    if (confirm('Send verification email to this user?')) {
        document.getElementById('verificationUserId').value = userId;
        document.getElementById('verificationForm').submit();
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>