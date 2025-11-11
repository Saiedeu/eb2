<?php
// Prevent direct access
if (!defined('ALLOW_ACCESS')) {
    header("HTTP/1.1 403 Forbidden");
    exit("Direct access forbidden");
}

class User {
    private $db;
    private $user = null;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->checkSession();
    }
    
    /**
     * Register a new user
     */
    public function register($email, $password, $name = null, $phone = null) {
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        // Validate password
        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters long");
        }
        
        // Check if email already exists
        $existing = $this->db->getRow("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            throw new Exception("Email already registered");
        }
        
        // Generate verification token
        $verificationToken = bin2hex(random_bytes(32));
        $verificationExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Hash password
        $hashedPassword = $this->hashPassword($password);
        
        // Start transaction
        $this->db->beginTransaction();
        
        try {
            // Insert user
            $userId = $this->db->insert('users', [
                'email' => $email,
                'password' => $hashedPassword,
                'name' => $name,
                'phone' => $phone,
                'verification_token' => $verificationToken,
                'verification_expires' => $verificationExpires,
                'status' => 'pending',
                'last_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Generate user_id using the trigger
            $user = $this->db->getRow("SELECT user_id FROM users WHERE id = ?", [$userId]);
            
            // Send verification email
            $this->sendVerificationEmail($email, $verificationToken, $user['user_id']);
            
            $this->db->commit();
            
            return $user['user_id'];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Login user with email and password
     */
    public function login($email, $password, $remember = false) {
        $user = $this->db->getRow(
            "SELECT * FROM users WHERE email = ? AND status IN ('active', 'pending')", 
            [$email]
        );
        
        if (!$user || !$this->verifyPassword($password, $user['password'])) {
            $this->logFailedLoginAttempt($email);
            throw new Exception("Invalid email or password");
        }
        
        if ($user['status'] === 'pending') {
            throw new Exception("Please verify your email address first");
        }
        
        if ($user['status'] === 'suspended') {
            throw new Exception("Your account has been suspended. Please contact support.");
        }
        
        // Update last login
        $this->db->update('users', [
            'last_login' => date('Y-m-d H:i:s'),
            'last_ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ], 'id = ?', [$user['id']]);
        
        // Set session
        $this->setSession($user);
        
        // Set remember me cookie if requested
        if ($remember) {
            $this->setRememberMe($user['id']);
        }
        
        return true;
    }
    
    /**
     * Login with Google OAuth
     */
    public function loginWithGoogle($googleUser) {
        // Check if user exists with this Google ID
        $user = $this->db->getRow(
            "SELECT * FROM users WHERE google_id = ?", 
            [$googleUser->id]
        );
        
        if (!$user) {
            // Check if email exists but Google ID is not linked
            $user = $this->db->getRow(
                "SELECT * FROM users WHERE email = ? AND google_id IS NULL", 
                [$googleUser->email]
            );
            
            if ($user) {
                // Link Google account to existing user
                $this->db->update('users', [
                    'google_id' => $googleUser->id,
                    'status' => 'active',
                    'email_verified' => 1,
                    'last_login' => date('Y-m-d H:i:s'),
                    'last_ip' => $_SERVER['REMOTE_ADDR'] ?? null
                ], 'id = ?', [$user['id']]);
                
                $user = $this->db->getRow("SELECT * FROM users WHERE id = ?", [$user['id']]);
            } else {
                // Create new user
                $userId = $this->db->insert('users', [
                    'email' => $googleUser->email,
                    'name' => $googleUser->name,
                    'google_id' => $googleUser->id,
                    'status' => 'active',
                    'email_verified' => 1,
                    'last_login' => date('Y-m-d H:i:s'),
                    'last_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                $user = $this->db->getRow("SELECT * FROM users WHERE id = ?", [$userId]);
            }
        } else {
            // Update last login for existing user
            $this->db->update('users', [
                'last_login' => date('Y-m-d H:i:s'),
                'last_ip' => $_SERVER['REMOTE_ADDR'] ?? null
            ], 'id = ?', [$user['id']]);
            
            $user = $this->db->getRow("SELECT * FROM users WHERE id = ?", [$user['id']]);
        }
        
        // Set session
        $this->setSession($user);
        
        return true;
    }
    
    /**
     * Verify email with token
     */
    public function verifyEmail($token) {
        $user = $this->db->getRow(
            "SELECT * FROM users WHERE verification_token = ? AND status = 'pending' AND verification_expires > NOW()", 
            [$token]
        );
        
        if (!$user) {
            throw new Exception("Invalid or expired verification token");
        }
        
        $this->db->update('users', [
            'status' => 'active',
            'email_verified' => 1,
            'verification_token' => null,
            'verification_expires' => null
        ], 'id = ?', [$user['id']]);
        
        return true;
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail($email) {
        $user = $this->db->getRow("SELECT id, name FROM users WHERE email = ?", [$email]);
        
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $this->db->insert('password_resets', [
                'email' => $email,
                'token' => $token,
                'expires_at' => $expires
            ]);
            
            // Send email with reset link
            $resetLink = SITE_URL . "/users/reset-password.php?token=" . urlencode($token);
            $subject = "Password Reset Request";
            $message = "Hello " . htmlspecialchars($user['name'] ?? 'User') . ",\n\n";
            $message .= "You have requested to reset your password. Click the link below to set a new password:\n";
            $message .= $resetLink . "\n\n";
            $message .= "If you did not request this, please ignore this email.\n";
            $message .= "This link will expire in 1 hour.\n\n";
            $message .= "Regards,\n" . SITE_NAME;
            
            $this->sendEmail($email, $subject, $message);
        }
        
        // Always return true to prevent email enumeration
        return true;
    }
    
    /**
     * Reset password with token
     */
    public function resetPassword($token, $newPassword) {
        $reset = $this->db->getRow(
            "SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()", 
            [$token]
        );
        
        if (!$reset) {
            throw new Exception("Invalid or expired reset token");
        }
        
        $user = $this->db->getRow("SELECT * FROM users WHERE email = ?", [$reset['email']]);
        
        if (!$user) {
            throw new Exception("User not found");
        }
        
        // Update password
        $hashedPassword = $this->hashPassword($newPassword);
        
        $this->db->update('users', [
            'password' => $hashedPassword,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$user['id']]);
        
        // Delete used token
        $this->db->delete('password_resets', 'token = ?', [$token]);
        
        return true;
    }
    
    /**
     * Get current user data
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        if ($this->user === null) {
            $this->user = $this->db->getRow(
                "SELECT id, user_id, email, name, phone, status, email_verified, created_at, last_login " .
                "FROM users WHERE id = ?", 
                [$_SESSION['user_id']]
            );
        }
        
        return $this->user;
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Logout user
     */
    public function logout() {
        // Delete remember me token if exists
        if (isset($_COOKIE['remember_token'])) {
            $this->deleteRememberToken($_COOKIE['remember_token']);
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
        
        // Unset session variables
        $_SESSION = [];
        
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
        
        // Clear user data
        $this->user = null;
        
        return true;
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $data) {
        $allowedFields = ['name', 'phone'];
        $updateData = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updateData[$key] = $value;
            }
        }
        
        if (empty($updateData)) {
            throw new Exception("No valid fields to update");
        }
        
        $updateData['updated_at'] = date('Y-m-d H:i:s');
        
        $this->db->update('users', $updateData, 'id = ?', [$userId]);
        
        // Clear cached user data
        $this->user = null;
        
        return true;
    }
    
    /**
     * Change password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        $user = $this->db->getRow("SELECT password FROM users WHERE id = ?", [$userId]);
        
        if (!$user) {
            throw new Exception("User not found");
        }
        
        if (!$this->verifyPassword($currentPassword, $user['password'])) {
            throw new Exception("Current password is incorrect");
        }
        
        $hashedPassword = $this->hashPassword($newPassword);
        
        $this->db->update('users', [
            'password' => $hashedPassword,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$userId]);
        
        return true;
    }
    
    /**
     * Get user transactions
     */
    public function getTransactions($userId, $limit = 10, $offset = 0) {
        return $this->db->getRows(
            "SELECT * FROM user_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?",
            [$userId, $limit, $offset]
        );
    }
    
    /**
     * Count user transactions
     */
    public function countTransactions($userId) {
        return $this->db->getValue(
            "SELECT COUNT(*) FROM user_transactions WHERE user_id = ?",
            [$userId]
        );
    }
    
    // ===== PRIVATE METHODS =====
    
    private function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    private function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    private function setSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['last_activity'] = time();
    }
    
    private function setRememberMe($userId) {
        $token = bin2hex(random_bytes(32));
        $expires = time() + (30 * 24 * 60 * 60); // 30 days
        
        // Store token in database
        $this->db->insert('user_sessions', [
            'user_id' => $userId,
            'session_id' => $token,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'expires_at' => date('Y-m-d H:i:s', $expires)
        ]);
        
        // Set cookie
        setcookie(
            'remember_token',
            $token,
            $expires,
            '/',
            '',
            isset($_SERVER['HTTPS']),
            true
        );
    }
    
    private function deleteRememberToken($token) {
        $this->db->delete('user_sessions', 'session_id = ?', [$token]);
    }
    
    private function checkSession() {
        if ($this->isLoggedIn()) {
            // Check session hijacking
            if ($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '') || 
                $_SESSION['ip_address'] !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
                $this->logout();
                throw new Exception("Session validation failed");
            }
            
            // Check session timeout (30 minutes)
            if (time() - $_SESSION['last_activity'] > 1800) {
                $this->logout();
                throw new Exception("Session expired");
            }
            
            // Update last activity
            $_SESSION['last_activity'] = time();
            
        } elseif (isset($_COOKIE['remember_token'])) {
            // Try to login with remember me token
            $session = $this->db->getRow(
                "SELECT u.* FROM users u " .
                "JOIN user_sessions s ON u.id = s.user_id " .
                "WHERE s.session_id = ? AND s.expires_at > NOW()",
                [$_COOKIE['remember_token']]
            );
            
            if ($session) {
                $this->setSession($session);
                $this->user = $session;
            } else {
                // Invalid token, delete cookie
                setcookie('remember_token', '', time() - 3600, '/');
            }
        }
    }
    
    private function sendVerificationEmail($email, $token, $userId) {
        $verificationLink = SITE_URL . "/users/verify-email.php?token=" . urlencode($token);
        $subject = "Verify Your Email Address";
        $message = "Thank you for registering!\n\n";
        $message .= "Your user ID is: " . $userId . "\n\n";
        $message .= "Please click the following link to verify your email address:\n";
        $message .= $verificationLink . "\n\n";
        $message .= "If you did not create an account, please ignore this email.\n\n";
        $message .= "Regards,\n" . SITE_NAME;
        
        return $this->sendEmail($email, $subject, $message);
    }
    
    private function sendEmail($to, $subject, $message) {
        // Get mail settings from database
        $mailSettings = $this->db->getRow("SELECT * FROM mail_settings LIMIT 1");
        
        if (!$mailSettings) {
            error_log("Mail settings not configured");
            return false;
        }
        
        // Create a new PHPMailer instance
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $mailSettings['smtp_host'];
            $mail->SMTPAuth = (bool)$mailSettings['smtp_auth'];
            $mail->Username = $mailSettings['smtp_username'];
            $mail->Password = $mailSettings['smtp_password'];
            $mail->SMTPSecure = $mailSettings['smtp_secure'];
            $mail->Port = $mailSettings['smtp_port'];
            
            // Recipients
            $mail->setFrom($mailSettings['from_email'], $mailSettings['from_name']);
            $mail->addAddress($to);
            
            if (!empty($mailSettings['reply_to'])) {
                $mail->addReplyTo($mailSettings['reply_to']);
            }
            
            // Content
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $message;
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
    
    private function logFailedLoginAttempt($email) {
        // Log failed login attempts for rate limiting
        // You can implement this based on your requirements
        error_log("Failed login attempt for email: " . $email . " from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    }
}
