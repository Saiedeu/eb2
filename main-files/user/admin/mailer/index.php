<?php
/**
 * Admin - PHPMailer Settings
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

// Handle form submission
if ($_POST) {
    if (!$security->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_smtp') {
            $settings = [
                'smtp_enabled' => $_POST['smtp_enabled'] ?? 'no',
                'smtp_host' => trim($_POST['smtp_host'] ?? ''),
                'smtp_port' => (int)($_POST['smtp_port'] ?? 587),
                'smtp_username' => trim($_POST['smtp_username'] ?? ''),
                'smtp_password' => $_POST['smtp_password'] ?? '',
                'smtp_encryption' => $_POST['smtp_encryption'] ?? 'tls',
                'smtp_from_email' => trim($_POST['smtp_from_email'] ?? ''),
                'smtp_from_name' => trim($_POST['smtp_from_name'] ?? ''),
            ];
            
            $allUpdated = true;
            foreach ($settings as $key => $value) {
                if (!updateSetting($key, $value)) {
                    $allUpdated = false;
                }
            }
            
            if ($allUpdated) {
                $success = 'SMTP settings updated successfully';
            } else {
                $error = 'Some settings could not be updated';
            }
        }
        
        if ($action === 'update_google_oauth') {
            $settings = [
                'google_oauth_enabled' => $_POST['google_oauth_enabled'] ?? 'no',
                'google_oauth_client_id' => trim($_POST['google_oauth_client_id'] ?? ''),
                'google_oauth_client_secret' => trim($_POST['google_oauth_client_secret'] ?? ''),
                'google_oauth_redirect_uri' => trim($_POST['google_oauth_redirect_uri'] ?? ''),
            ];
            
            $allUpdated = true;
            foreach ($settings as $key => $value) {
                if (!updateSetting($key, $value)) {
                    $allUpdated = false;
                }
            }
            
            if ($allUpdated) {
                $success = 'Google OAuth settings updated successfully';
            } else {
                $error = 'Some settings could not be updated';
            }
        }
        
        if ($action === 'update_email_settings') {
            $settings = [
                'email_verification_required' => $_POST['email_verification_required'] ?? 'yes',
                'user_registration_enabled' => $_POST['user_registration_enabled'] ?? 'yes',
            ];
            
            $allUpdated = true;
            foreach ($settings as $key => $value) {
                if (!updateSetting($key, $value)) {
                    $allUpdated = false;
                }
            }
            
            if ($allUpdated) {
                $success = 'Email settings updated successfully';
            } else {
                $error = 'Some settings could not be updated';
            }
        }
        
        if ($action === 'test_email') {
            require_once '../includes/mailer.php';
            $emailService = EmailService::getInstance();
            
            $testEmail = trim($_POST['test_email'] ?? '');
            if (empty($testEmail)) {
                $error = 'Please enter a test email address';
            } else {
                $result = $emailService->testEmailConfig($testEmail);
                if ($result['success']) {
                    $success = 'Test email sent successfully to ' . $testEmail;
                } else {
                    $error = 'Test email failed: ' . $result['message'];
                }
            }
        }
    }
}

// Get current settings
$smtpSettings = [
    'smtp_enabled' => getSetting('smtp_enabled', 'yes'),
    'smtp_host' => getSetting('smtp_host', 'smtp.gmail.com'),
    'smtp_port' => getSetting('smtp_port', '587'),
    'smtp_username' => getSetting('smtp_username', ''),
    'smtp_password' => getSetting('smtp_password', ''),
    'smtp_encryption' => getSetting('smtp_encryption', 'tls'),
    'smtp_from_email' => getSetting('smtp_from_email', ''),
    'smtp_from_name' => getSetting('smtp_from_name', getSetting('site_name', 'Exchange Bridge')),
];

$googleOAuthSettings = [
    'google_oauth_enabled' => getSetting('google_oauth_enabled', 'no'),
    'google_oauth_client_id' => getSetting('google_oauth_client_id', ''),
    'google_oauth_client_secret' => getSetting('google_oauth_client_secret', ''),
    'google_oauth_redirect_uri' => getSetting('google_oauth_redirect_uri', SITE_URL . '/users/google-callback.php'),
];

$emailSettings = [
    'email_verification_required' => getSetting('email_verification_required', 'yes'),
    'user_registration_enabled' => getSetting('user_registration_enabled', 'yes'),
];

$pageTitle = 'PHPMailer Settings';
require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Page Header -->
        <div class="col-12">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title">PHPMailer Settings</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">PHPMailer Settings</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <!-- SMTP Configuration -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="card-title">SMTP Configuration</h4>
                    <p class="text-muted mb-0">Configure SMTP settings for sending emails</p>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $security->generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="update_smtp">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Enable SMTP</label>
                                    <select name="smtp_enabled" class="form-control">
                                        <option value="yes" <?php echo $smtpSettings['smtp_enabled'] === 'yes' ? 'selected' : ''; ?>>Yes</option>
                                        <option value="no" <?php echo $smtpSettings['smtp_enabled'] === 'no' ? 'selected' : ''; ?>>No (Use PHP mail)</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>SMTP Host</label>
                                    <input type="text" 
                                           name="smtp_host" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($smtpSettings['smtp_host']); ?>"
                                           placeholder="smtp.gmail.com">
                                    <small class="text-muted">Gmail: smtp.gmail.com, Outlook: smtp-mail.outlook.com</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>SMTP Port</label>
                                    <input type="number" 
                                           name="smtp_port" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($smtpSettings['smtp_port']); ?>"
                                           placeholder="587">
                                    <small class="text-muted">TLS: 587, SSL: 465</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Encryption</label>
                                    <select name="smtp_encryption" class="form-control">
                                        <option value="tls" <?php echo $smtpSettings['smtp_encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                        <option value="ssl" <?php echo $smtpSettings['smtp_encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>SMTP Username (Email)</label>
                                    <input type="email" 
                                           name="smtp_username" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($smtpSettings['smtp_username']); ?>"
                                           placeholder="your-email@gmail.com">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>SMTP Password (App Password)</label>
                                    <input type="password" 
                                           name="smtp_password" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($smtpSettings['smtp_password']); ?>"
                                           placeholder="App Password">
                                    <small class="text-muted">For Gmail, use App Password instead of regular password</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>From Email</label>
                                    <input type="email" 
                                           name="smtp_from_email" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($smtpSettings['smtp_from_email']); ?>"
                                           placeholder="noreply@yourdomain.com">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>From Name</label>
                                    <input type="text" 
                                           name="smtp_from_name" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($smtpSettings['smtp_from_name']); ?>"
                                           placeholder="Exchange Bridge">
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update SMTP Settings
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Test Email -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="card-title">Test Email Configuration</h4>
                    <p class="text-muted mb-0">Send a test email to verify your configuration</p>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $security->generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="test_email">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Test Email Address</label>
                                    <input type="email" 
                                           name="test_email" 
                                           class="form-control" 
                                           placeholder="test@example.com"
                                           required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-info d-block">
                                        <i class="fas fa-paper-plane"></i> Send Test Email
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Google OAuth Configuration -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="card-title">Google OAuth Configuration</h4>
                    <p class="text-muted mb-0">Configure Google Sign-In for users</p>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Setup Instructions:</h6>
                        <ol class="mb-0">
                            <li>Go to <a href="https://console.developers.google.com/" target="_blank">Google Cloud Console</a></li>
                            <li>Create a new project or select existing one</li>
                            <li>Enable Google+ API</li>
                            <li>Create OAuth 2.0 credentials</li>
                            <li>Add <code><?php echo SITE_URL; ?>/users/google-callback.php</code> to authorized redirect URIs</li>
                        </ol>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $security->generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="update_google_oauth">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Enable Google OAuth</label>
                                    <select name="google_oauth_enabled" class="form-control">
                                        <option value="yes" <?php echo $googleOAuthSettings['google_oauth_enabled'] === 'yes' ? 'selected' : ''; ?>>Yes</option>
                                        <option value="no" <?php echo $googleOAuthSettings['google_oauth_enabled'] === 'no' ? 'selected' : ''; ?>>No</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Redirect URI</label>
                                    <input type="url" 
                                           name="google_oauth_redirect_uri" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($googleOAuthSettings['google_oauth_redirect_uri']); ?>"
                                           readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Client ID</label>
                                    <input type="text" 
                                           name="google_oauth_client_id" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($googleOAuthSettings['google_oauth_client_id']); ?>"
                                           placeholder="xxx.apps.googleusercontent.com">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Client Secret</label>
                                    <input type="password" 
                                           name="google_oauth_client_secret" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($googleOAuthSettings['google_oauth_client_secret']); ?>"
                                           placeholder="GOCSPX-...">
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Google OAuth Settings
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Email Settings -->
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Email & Registration Settings</h4>
                    <p class="text-muted mb-0">Configure email verification and registration options</p>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $security->generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="update_email_settings">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Require Email Verification</label>
                                    <select name="email_verification_required" class="form-control">
                                        <option value="yes" <?php echo $emailSettings['email_verification_required'] === 'yes' ? 'selected' : ''; ?>>Yes</option>
                                        <option value="no" <?php echo $emailSettings['email_verification_required'] === 'no' ? 'selected' : ''; ?>>No</option>
                                    </select>
                                    <small class="text-muted">Users must verify their email before they can login</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Allow User Registration</label>
                                    <select name="user_registration_enabled" class="form-control">
                                        <option value="yes" <?php echo $emailSettings['user_registration_enabled'] === 'yes' ? 'selected' : ''; ?>>Yes</option>
                                        <option value="no" <?php echo $emailSettings['user_registration_enabled'] === 'no' ? 'selected' : ''; ?>>No</option>
                                    </select>
                                    <small class="text-muted">Allow new users to register accounts</small>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Email Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>