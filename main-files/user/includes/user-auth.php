<?php
/**
 * Exchange Bridge - User Authentication System
 * 
 * @package     ExchangeBridge
 * @author      Saieed Rahman
 * @copyright   SidMan Solutions 2025
 * @version     1.0.0
 */

// Prevent direct access
if (!defined('ALLOW_ACCESS')) {
    header("HTTP/1.1 403 Forbidden");
    exit("Direct access forbidden");
}

require_once __DIR__ . '/mailer.php';

class UserAuth {
    private $db;
    private $mailer;
    private static $instance = null;
    
    private function __construct() {
        $this->db = Database::getInstance();
        $this->mailer = EmailService::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new UserAuth();
        }
        return self::$instance;
    }
    
    /**
     * Generate unique user ID starting from 100001
     */
    private function generateUserId() {
        $lastUserId = $this->db->getValue(
            "SELECT user_id FROM registered_users ORDER BY id DESC LIMIT 1"
        );
        
        if ($lastUserId) {
            $nextId = intval($lastUserId) + 1;
        } else {
            $nextId = 100001; // Starting number
        }
        
        return (string) $nextId;
    }
    
    /**
     * Register new user with email verification
     */
    public function register($email, $password = null, $name = null, $googleId = null) {
        try {
            // Check if email already exists
            $existingUser = $this->db->getRow(
                "SELECT id, email_verified, status FROM registered_users WHERE email = ?",
                [$email]
            );
            
            if ($existingUser) {
                if ($existingUser['email_verified'] == 0) {
                    return [
                        'success' => false,
                        'message' => 'Email already registered but not verified. Please check your email for verification link.',
                        'needs_verification' => true
                    ];
                }
                return ['success' => false, 'message' => 'Email already registered'];
            }
            
            $userId = $this->generateUserId();
            $loginMethod = $googleId ? 'google' : 'email';
            $emailVerificationToken = bin2hex(random_bytes(32));
            $emailVerificationExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            $userData = [
                'user_id' => $userId,
                'email' => $email,
                'name' => $name,
                'google_id' => $googleId,
                'login_method' => $loginMethod,
                'email_verification_token' => $emailVerificationToken,
                'email_verification_expires' => $emailVerificationExpires,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            if ($password) {
                $userData['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            
            $newUserId = $this->db->insert('registered_users', $userData);
            
            if ($newUserId) {
                // Send verification email for email registration
                if (!$googleId && getSetting('email_verification_required', 'yes') === 'yes') {
                    $this->sendVerificationEmail($email, $emailVerificationToken, $name);
                    
                    return [
                        'success' => true,
                        'message' => 'Registration successful! Please check your email for verification link.',
                        'user_id' => $userId,
                        'needs_verification' => true
                    ];
                } else {
                    // Google users are automatically verified
                    if ($googleId) {
                        $this->db->update('registered_users', 
                            ['email_verified' => 1], 
                            'id = ?', 
                            [$newUserId]
                        );
                    }
                    
                    return [
                        'success' => true,
                        'message' => 'Registration successful!',
                        'user_id' => $userId,
                        'needs_verification' => false
                    ];
                }
            }
            
            return ['success' => false, 'message' => 'Registration failed'];
            
        } catch (Exception $e) {
            error_log("User registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed due to server error'];
        }
    }
    
    /**
     * Login user
     */
    public function login($email, $password = null, $googleId = null) {
        try {
            $whereClause = "email = ? AND status = 'active'";
            $params = [$email];
            
            if ($googleId) {
                $whereClause .= " AND (google_id = ? OR login_method IN ('google', 'both'))";
                $params[] = $googleId;
            }
            
            $user = $this->db->getRow(
                "SELECT * FROM registered_users WHERE $whereClause",
                $params
            );
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            // Check if email is verified
            if (!$user['email_verified']) {
                return [
                    'success' => false, 
                    'message' => 'Please verify your email before logging in',
                    'needs_verification' => true
                ];
            }
            
            // Verify password for email login
            if (!$googleId && $password) {
                if (!password_verify($password, $user['password'])) {
                    return ['success' => false, 'message' => 'Invalid credentials'];
                }
            }
            
            // Update last login
            $this->db->update('registered_users', 
                ['last_login' => date('Y-m-d H:i:s')], 
                'id = ?', 
                [$user['id']]
            );
            
            // Create session
            $sessionToken = $this->createSession($user['id']);
            
            // Set session variables
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_uid'] = $user['user_id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['session_token'] = $sessionToken;
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'user_id' => $user['user_id'],
                    'email' => $user['email'],
                    'name' => $user['name']
                ]
            ];
            
        } catch (Exception $e) {
            error_log("User login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed due to server error'];
        }
    }
    
    /**
     * Verify email
     */
    public function verifyEmail($token) {
        try {
            $user = $this->db->getRow(
                "SELECT id, email, name FROM registered_users 
                 WHERE email_verification_token = ? 
                 AND email_verification_expires > NOW()
                 AND email_verified = 0",
                [$token]
            );
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid or expired verification token'];
            }
            
            $updated = $this->db->update('registered_users', [
                'email_verified' => 1,
                'email_verification_token' => null,
                'email_verification_expires' => null
            ], 'id = ?', [$user['id']]);
            
            if ($updated) {
                // Send welcome email
                $this->sendWelcomeEmail($user['email'], $user['name']);
                
                return [
                    'success' => true,
                    'message' => 'Email verified successfully! You can now login.'
                ];
            }
            
            return ['success' => false, 'message' => 'Verification failed'];
            
        } catch (Exception $e) {
            error_log("Email verification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Verification failed due to server error'];
        }
    }
    
    /**
     * Resend verification email
     */
    public function resendVerification($email) {
        try {
            $user = $this->db->getRow(
                "SELECT id, name, email_verified FROM registered_users WHERE email = ?",
                [$email]
            );
            
            if (!$user) {
                return ['success' => false, 'message' => 'Email not found'];
            }
            
            if ($user['email_verified']) {
                return ['success' => false, 'message' => 'Email already verified'];
            }
            
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            $this->db->update('registered_users', [
                'email_verification_token' => $token,
                'email_verification_expires' => $expires
            ], 'id = ?', [$user['id']]);
            
            $this->sendVerificationEmail($email, $token, $user['name']);
            
            return [
                'success' => true,
                'message' => 'Verification email sent successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Resend verification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send verification email'];
        }
    }
    
    /**
     * Create user session
     */
    private function createSession($userId) {
        $sessionToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $this->db->insert('user_sessions', [
            'user_id' => $userId,
            'session_token' => $sessionToken,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'expires_at' => $expiresAt
        ]);
        
        return $sessionToken;
    }
    
    /**
     * Validate session
     */
    public function validateSession($sessionToken) {
        try {
            $session = $this->db->getRow(
                "SELECT s.*, u.* FROM user_sessions s
                 JOIN registered_users u ON s.user_id = u.id
                 WHERE s.session_token = ? AND s.expires_at > NOW() AND u.status = 'active'",
                [$sessionToken]
            );
            
            return $session ? $session : false;
            
        } catch (Exception $e) {
            error_log("Session validation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
            return false;
        }
        
        if (!isset($_SESSION['session_token'])) {
            return false;
        }
        
        $session = $this->validateSession($_SESSION['session_token']);
        return $session !== false;
    }
    
    /**
     * Get current user
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $this->db->getRow(
            "SELECT * FROM registered_users WHERE id = ?",
            [$_SESSION['user_id']]
        );
    }
    
    /**
     * Logout user
     */
    public function logout() {
        if (isset($_SESSION['session_token'])) {
            $this->db->delete('user_sessions', 'session_token = ?', [$_SESSION['session_token']]);
        }
        
        // Clear user session variables
        unset($_SESSION['user_logged_in']);
        unset($_SESSION['user_id']);
        unset($_SESSION['user_uid']);
        unset($_SESSION['user_email']);
        unset($_SESSION['user_name']);
        unset($_SESSION['session_token']);
        
        return true;
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $data) {
        try {
            $allowedFields = ['name', 'phone'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            if (empty($updateData)) {
                return ['success' => false, 'message' => 'No valid fields to update'];
            }
            
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            
            $updated = $this->db->update('registered_users', $updateData, 'id = ?', [$userId]);
            
            if ($updated) {
                // Update session variables if name was changed
                if (isset($updateData['name'])) {
                    $_SESSION['user_name'] = $updateData['name'];
                }
                
                return ['success' => true, 'message' => 'Profile updated successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to update profile'];
            
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Update failed due to server error'];
        }
    }
    
    /**
     * Change password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            $user = $this->db->getRow(
                "SELECT password FROM registered_users WHERE id = ?",
                [$userId]
            );
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            if (!password_verify($currentPassword, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $updated = $this->db->update('registered_users', [
                'password' => $hashedPassword,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$userId]);
            
            if ($updated) {
                return ['success' => true, 'message' => 'Password changed successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to change password'];
            
        } catch (Exception $e) {
            error_log("Password change error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Password change failed due to server error'];
        }
    }
    
    /**
     * Request password reset
     */
    public function requestPasswordReset($email) {
        try {
            $user = $this->db->getRow(
                "SELECT id, name FROM registered_users WHERE email = ? AND status = 'active'",
                [$email]
            );
            
            if (!$user) {
                return ['success' => false, 'message' => 'Email not found'];
            }
            
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $this->db->insert('password_resets', [
                'user_id' => $user['id'],
                'token' => $token,
                'expires_at' => $expiresAt
            ]);
            
            $this->sendPasswordResetEmail($email, $token, $user['name']);
            
            return [
                'success' => true,
                'message' => 'Password reset link sent to your email'
            ];
            
        } catch (Exception $e) {
            error_log("Password reset request error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send password reset email'];
        }
    }
    
    /**
     * Reset password
     */
    public function resetPassword($token, $newPassword) {
        try {
            $resetRequest = $this->db->getRow(
                "SELECT pr.*, u.email FROM password_resets pr
                 JOIN registered_users u ON pr.user_id = u.id
                 WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used = 0",
                [$token]
            );
            
            if (!$resetRequest) {
                return ['success' => false, 'message' => 'Invalid or expired reset token'];
            }
            
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $this->db->update('registered_users', [
                'password' => $hashedPassword,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$resetRequest['user_id']]);
            
            // Mark reset token as used
            $this->db->update('password_resets', [
                'used' => 1
            ], 'token = ?', [$token]);
            
            return [
                'success' => true,
                'message' => 'Password reset successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Password reset failed due to server error'];
        }
    }
    
    /**
     * Get user transaction history
     */
    public function getUserTransactions($userId, $limit = 20, $offset = 0) {
        try {
            return $this->db->getRows(
                "SELECT e.*, 
                    fc.name as from_currency_name, fc.display_name as from_display_name,
                    tc.name as to_currency_name, tc.display_name as to_display_name
                 FROM exchanges e
                 JOIN currencies fc ON e.from_currency = fc.code
                 JOIN currencies tc ON e.to_currency = tc.code
                 WHERE e.registered_user_id = ?
                 ORDER BY e.created_at DESC
                 LIMIT ? OFFSET ?",
                [$userId, $limit, $offset]
            );
        } catch (Exception $e) {
            error_log("Get user transactions error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Count user transactions
     */
    public function countUserTransactions($userId) {
        try {
            return $this->db->getValue(
                "SELECT COUNT(*) FROM exchanges WHERE registered_user_id = ?",
                [$userId]
            );
        } catch (Exception $e) {
            error_log("Count user transactions error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Send verification email
     */
    private function sendVerificationEmail($email, $token, $name = null) {
        $verificationUrl = SITE_URL . "/users/verify-email.php?token=" . $token;
        $siteName = getSetting('site_name', 'Exchange Bridge');
        
        $subject = "Verify Your Email - $siteName";
        $message = $this->mailer->getEmailTemplate('verification', [
            'name' => $name ?: 'User',
            'site_name' => $siteName,
            'verification_url' => $verificationUrl,
            'support_email' => getSetting('contact_email', 'support@exchangebridge.com')
        ]);
        
        return $this->mailer->sendEmail($email, $subject, $message);
    }
    
    /**
     * Send welcome email
     */
    private function sendWelcomeEmail($email, $name = null) {
        $siteName = getSetting('site_name', 'Exchange Bridge');
        $loginUrl = SITE_URL . "/users/login.php";
        
        $subject = "Welcome to $siteName!";
        $message = $this->mailer->getEmailTemplate('welcome', [
            'name' => $name ?: 'User',
            'site_name' => $siteName,
            'login_url' => $loginUrl,
            'dashboard_url' => SITE_URL . "/users/dashboard.php",
            'support_email' => getSetting('contact_email', 'support@exchangebridge.com')
        ]);
        
        return $this->mailer->sendEmail($email, $subject, $message);
    }
    
    /**
     * Send password reset email
     */
    private function sendPasswordResetEmail($email, $token, $name = null) {
        $resetUrl = SITE_URL . "/users/reset-password.php?token=" . $token;
        $siteName = getSetting('site_name', 'Exchange Bridge');
        
        $subject = "Reset Your Password - $siteName";
        $message = $this->mailer->getEmailTemplate('password-reset', [
            'name' => $name ?: 'User',
            'site_name' => $siteName,
            'reset_url' => $resetUrl,
            'support_email' => getSetting('contact_email', 'support@exchangebridge.com')
        ]);
        
        return $this->mailer->sendEmail($email, $subject, $message);
    }
    
    /**
     * Clean expired sessions and tokens
     */
    public function cleanupExpiredData() {
        try {
            // Delete expired sessions
            $this->db->query("DELETE FROM user_sessions WHERE expires_at < NOW()");
            
            // Delete expired password reset tokens
            $this->db->query("DELETE FROM password_resets WHERE expires_at < NOW()");
            
            // Delete expired email verification tokens
            $this->db->update('registered_users', [
                'email_verification_token' => null,
                'email_verification_expires' => null
            ], 'email_verification_expires < NOW()');
            
            return true;
        } catch (Exception $e) {
            error_log("Cleanup expired data error: " . $e->getMessage());
            return false;
        }
    }
}