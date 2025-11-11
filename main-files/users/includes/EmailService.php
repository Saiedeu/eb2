<?php
// Prevent direct access
if (!defined('ALLOW_ACCESS')) {
    header("HTTP/1.1 403 Forbidden");
    exit("Direct access forbidden");
}

class EmailService {
    private $db;
    private $mailer;
    private $settings;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->mailer = new PHPMailer\PHPMailer\PHPMailer(true);
        $this->loadSettings();
    }
    
    /**
     * Load email settings from database
     */
    private function loadSettings() {
        $this->settings = $this->db->getRow("SELECT * FROM mail_settings LIMIT 1");
        
        if (!$this->settings) {
            // Default settings if not configured
            $this->settings = [
                'smtp_host' => 'smtp.gmail.com',
                'smtp_port' => 587,
                'smtp_secure' => 'tls',
                'smtp_auth' => 1,
                'smtp_username' => '',
                'smtp_password' => '',
                'from_email' => 'noreply@example.com',
                'from_name' => 'Exchange Bridge',
                'reply_to' => 'support@example.com'
            ];
            
            // Save default settings
            $this->db->insert('mail_settings', $this->settings);
        }
        
        // Configure PHPMailer
        $this->configureMailer();
    }
    
    /**
     * Update email settings
     */
    public function updateSettings($settings) {
        $allowedFields = [
            'smtp_host', 'smtp_port', 'smtp_secure', 'smtp_auth',
            'smtp_username', 'smtp_password', 'from_email', 'from_name', 'reply_to'
        ];
        
        $updateData = [];
        
        foreach ($settings as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updateData[$key] = $value;
            }
        }
        
        if (empty($updateData)) {
            throw new Exception("No valid fields to update");
        }
        
        $updateData['updated_at'] = date('Y-m-d H:i:s');
        
        $this->db->update('mail_settings', $updateData, '1=1');
        
        // Reload settings
        $this->loadSettings();
        
        return true;
    }
    
    /**
     * Configure PHPMailer with current settings
     */
    private function configureMailer() {
        // Server settings
        $this->mailer->isSMTP();
        $this->mailer->Host = $this->settings['smtp_host'];
        $this->mailer->SMTPAuth = (bool)$this->settings['smtp_auth'];
        $this->mailer->Username = $this->settings['smtp_username'];
        $this->mailer->Password = $this->settings['smtp_password'];
        $this->mailer->SMTPSecure = $this->settings['smtp_secure'];
        $this->mailer->Port = (int)$this->settings['smtp_port'];
        
        // Default from address
        $this->mailer->setFrom(
            $this->settings['from_email'],
            $this->settings['from_name']
        );
        
        // Default reply-to
        if (!empty($this->settings['reply_to'])) {
            $this->mailer->addReplyTo($this->settings['reply_to']);
        }
        
        // Default charset
        $this->mailer->CharSet = 'UTF-8';
        
        // Debug mode
        $this->mailer->SMTPDebug = defined('DEBUG_MODE') && DEBUG_MODE ? 2 : 0;
    }
    
    /**
     * Send an email
     */
    public function send($to, $subject, $body, $isHtml = false, $attachments = []) {
        try {
            // Reset all addresses and attachments
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Recipients
            if (is_array($to)) {
                foreach ($to as $email => $name) {
                    $this->mailer->addAddress($email, $name);
                }
            } else {
                $this->mailer->addAddress($to);
            }
            
            // Add attachments
            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    if (is_array($attachment)) {
                        $this->mailer->addAttachment(
                            $attachment['path'],
                            $attachment['name'] ?? basename($attachment['path'])
                        );
                    } else {
                        $this->mailer->addAttachment($attachment);
                    }
                }
            }
            
            // Content
            $this->mailer->isHTML($isHtml);
            $this->mailer->Subject = $subject;
            
            if ($isHtml) {
                $this->mailer->Body = $body;
                $this->mailer->AltBody = $this->htmlToText($body);
            } else {
                $this->mailer->Body = $body;
            }
            
            // Send the email
            $result = $this->mailer->send();
            
            // Log the email
            $this->logEmail($to, $subject, $result);
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            
            // Log the failed attempt
            $this->logEmail($to, $subject, false, $e->getMessage());
            
            throw new Exception("Failed to send email: " . $e->getMessage());
        }
    }
    
    /**
     * Send a verification email
     */
    public function sendVerificationEmail($email, $name, $verificationLink) {
        $subject = "Verify Your Email Address";
        
        // HTML version
        $html = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <title>Email Verification</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; background-color: #f9f9f9; }
                    .button {
                        display: inline-block; padding: 10px 20px; background-color: #4CAF50; 
                        color: white; text-decoration: none; border-radius: 4px; margin: 20px 0;
                    }
                    .footer { margin-top: 20px; font-size: 12px; color: #777; text-align: center; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Welcome to " . SITE_NAME . "</h1>
                    </div>
                    <div class='content'>
                        <p>Hello " . htmlspecialchars($name) . ",</p>
                        <p>Thank you for registering. Please verify your email address by clicking the button below:</p>
                        <p style='text-align: center;'>
                            <a href='" . htmlspecialchars($verificationLink) . "' class='button'>Verify Email</a>
                        </p>
                        <p>Or copy and paste this link into your browser:</p>
                        <p><small>" . htmlspecialchars($verificationLink) . "</small></p>
                        <p>If you did not create an account, please ignore this email.</p>
                    </div>
                    <div class='footer'>
                        <p>This email was sent to " . htmlspecialchars($email) . ".</p>
                        <p>&copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        // Plain text version
        $text = "Welcome to " . SITE_NAME . "\n\n";
        $text .= "Hello " . $name . ",\n\n";
        $text .= "Thank you for registering. Please verify your email address by visiting the following link:\n\n";
        $text .= $verificationLink . "\n\n";
        $text .= "If you did not create an account, please ignore this email.\n\n";
        $text .= "Regards,\n";
        $text .= SITE_NAME . " Team\n";
        
        try {
            // Reset all addresses and attachments
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Recipients
            $this->mailer->addAddress($email, $name);
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $html;
            $this->mailer->AltBody = $text;
            
            // Send the email
            $result = $this->mailer->send();
            
            // Log the email
            $this->logEmail($email, $subject, $result, 'verification');
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Verification email sending failed: " . $e->getMessage());
            $this->logEmail($email, $subject, false, $e->getMessage(), 'verification');
            throw new Exception("Failed to send verification email");
        }
    }
    
    /**
     * Send a password reset email
     */
    public function sendPasswordResetEmail($email, $name, $resetLink) {
        $subject = "Password Reset Request";
        
        // HTML version
        $html = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <title>Password Reset</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #2196F3; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; background-color: #f9f9f9; }
                    .button {
                        display: inline-block; padding: 10px 20px; background-color: #2196F3; 
                        color: white; text-decoration: none; border-radius: 4px; margin: 20px 0;
                    }
                    .footer { margin-top: 20px; font-size: 12px; color: #777; text-align: center; }
                    .code { 
                        font-family: monospace; font-size: 24px; letter-spacing: 5px; 
                        padding: 10px; background: #f0f0f0; text-align: center; margin: 20px 0;
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Password Reset</h1>
                    </div>
                    <div class='content'>
                        <p>Hello " . htmlspecialchars($name) . ",</p>
                        <p>We received a request to reset your password. Click the button below to reset it:</p>
                        <p style='text-align: center;'>
                            <a href='" . htmlspecialchars($resetLink) . "' class='button'>Reset Password</a>
                        </p>
                        <p>Or copy and paste this link into your browser:</p>
                        <p><small>" . htmlspecialchars($resetLink) . "</small></p>
                        <p>This link will expire in 1 hour.</p>
                        <p>If you did not request a password reset, please ignore this email or contact support if you have questions.</p>
                    </div>
                    <div class='footer'>
                        <p>This email was sent to " . htmlspecialchars($email) . ".</p>
                        <p>&copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        // Plain text version
        $text = "Password Reset Request\n\n";
        $text .= "Hello " . $name . ",\n\n";
        $text .= "We received a request to reset your password. Please visit the following link to reset your password:\n\n";
        $text .= $resetLink . "\n\n";
        $text .= "This link will expire in 1 hour.\n\n";
        $text .= "If you did not request a password reset, please ignore this email or contact support if you have questions.\n\n";
        $text .= "Regards,\n";
        $text .= SITE_NAME . " Team\n";
        
        try {
            // Reset all addresses and attachments
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Recipients
            $this->mailer->addAddress($email, $name);
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $html;
            $this->mailer->AltBody = $text;
            
            // Send the email
            $result = $this->mailer->send();
            
            // Log the email
            $this->logEmail($email, $subject, $result, 'password_reset');
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Password reset email sending failed: " . $e->getMessage());
            $this->logEmail($email, $subject, false, $e->getMessage(), 'password_reset');
            throw new Exception("Failed to send password reset email");
        }
    }
    
    /**
     * Log email sending activity
     */
    private function logEmail($to, $subject, $success, $error = null, $type = 'general') {
        try {
            $this->db->insert('email_logs', [
                'to_email' => is_array($to) ? implode(', ', array_keys($to)) : $to,
                'subject' => $subject,
                'type' => $type,
                'status' => $success ? 'sent' : 'failed',
                'error_message' => $error,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            // Don't throw exception if logging fails
            error_log("Failed to log email: " . $e->getMessage());
        }
    }
    
    /**
     * Convert HTML to plain text
     */
    private function htmlToText($html) {
        // Basic HTML to text conversion
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        return $text;
    }
    
    /**
     * Get the PHPMailer instance for advanced usage
     */
    public function getMailer() {
        return $this->mailer;
    }
    
    /**
     * Test email settings
     */
    public function testConnection() {
        try {
            $this->mailer->smtpConnect();
            $this->mailer->smtpClose();
            return true;
        } catch (Exception $e) {
            throw new Exception("SMTP connection failed: " . $e->getMessage());
        }
    }
}
