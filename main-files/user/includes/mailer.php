<?php
/**
 * Exchange Bridge - Email Service with PHPMailer
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

// Include PHPMailer
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $db;
    private static $instance = null;
    
    private function __construct() {
        $this->db = Database::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new EmailService();
        }
        return self::$instance;
    }
    
    /**
     * Send email using PHPMailer
     */
    public function sendEmail($to, $subject, $body, $isHTML = true) {
        try {
            $mail = new PHPMailer(true);
            
            // Check if SMTP is enabled
            if (getSetting('smtp_enabled', 'yes') === 'yes') {
                // Server settings
                $mail->isSMTP();
                $mail->Host = getSetting('smtp_host', 'smtp.gmail.com');
                $mail->SMTPAuth = true;
                $mail->Username = getSetting('smtp_username', '');
                $mail->Password = getSetting('smtp_password', '');
                $mail->SMTPSecure = getSetting('smtp_encryption', 'tls') === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = (int)getSetting('smtp_port', '587');
                
                // For debugging (disable in production)
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
                }
            } else {
                // Use PHP's mail() function
                $mail->isMail();
            }
            
            // Recipients
            $fromEmail = getSetting('smtp_from_email', getSetting('contact_email', 'noreply@exchangebridge.com'));
            $fromName = getSetting('smtp_from_name', getSetting('site_name', 'Exchange Bridge'));
            
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            $mail->addReplyTo($fromEmail, $fromName);
            
            // Content
            $mail->isHTML($isHTML);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            if ($isHTML) {
                // Generate plain text version
                $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));
            }
            
            $result = $mail->send();
            
            if ($result) {
                $this->logEmail($to, $subject, 'sent');
                return ['success' => true, 'message' => 'Email sent successfully'];
            } else {
                $this->logEmail($to, $subject, 'failed', $mail->ErrorInfo);
                return ['success' => false, 'message' => 'Failed to send email: ' . $mail->ErrorInfo];
            }
            
        } catch (Exception $e) {
            $errorMessage = "Email error: " . $e->getMessage();
            error_log($errorMessage);
            $this->logEmail($to, $subject, 'error', $errorMessage);
            return ['success' => false, 'message' => $errorMessage];
        }
    }
    
    /**
     * Get email template
     */
    public function getEmailTemplate($template, $variables = []) {
        $templates = [
            'verification' => $this->getVerificationTemplate(),
            'welcome' => $this->getWelcomeTemplate(),
            'password-reset' => $this->getPasswordResetTemplate(),
            'transaction-notification' => $this->getTransactionNotificationTemplate()
        ];
        
        if (!isset($templates[$template])) {
            return 'Template not found';
        }
        
        $content = $templates[$template];
        
        // Replace variables
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        
        return $content;
    }
    
    /**
     * Email verification template
     */
    private function getVerificationTemplate() {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Email Verification</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #5D5CDE 0%, #4BB74B 100%); color: white; text-align: center; padding: 30px; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .button { display: inline-block; background: #5D5CDE; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>{{site_name}}</h1>
                    <p>Email Verification Required</p>
                </div>
                <div class="content">
                    <h2>Hello {{name}}!</h2>
                    <p>Thank you for registering with {{site_name}}. To complete your registration, please verify your email address by clicking the button below:</p>
                    
                    <center>
                        <a href="{{verification_url}}" class="button">Verify Email Address</a>
                    </center>
                    
                    <p>If the button doesn\'t work, you can copy and paste this link into your browser:</p>
                    <p><a href="{{verification_url}}">{{verification_url}}</a></p>
                    
                    <p>This verification link will expire in 24 hours.</p>
                    
                    <p>If you didn\'t create an account with us, please ignore this email.</p>
                </div>
                <div class="footer">
                    <p>Need help? Contact us at <a href="mailto:{{support_email}}">{{support_email}}</a></p>
                    <p>&copy; ' . date('Y') . ' {{site_name}}. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Welcome email template
     */
    private function getWelcomeTemplate() {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Welcome</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #5D5CDE 0%, #4BB74B 100%); color: white; text-align: center; padding: 30px; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .button { display: inline-block; background: #5D5CDE; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .feature { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #4BB74B; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Welcome to {{site_name}}!</h1>
                    <p>Your account is now verified and ready to use</p>
                </div>
                <div class="content">
                    <h2>Hello {{name}}!</h2>
                    <p>Welcome to {{site_name}}! Your email has been successfully verified and your account is now active.</p>
                    
                    <div class="feature">
                        <h3>🔐 Secure Transactions</h3>
                        <p>Your transaction history is now saved and accessible anytime from your dashboard.</p>
                    </div>
                    
                    <div class="feature">
                        <h3>⚡ Quick Exchange</h3>
                        <p>Your profile information will auto-fill during exchanges for faster transactions.</p>
                    </div>
                    
                    <div class="feature">
                        <h3>📊 Transaction History</h3>
                        <p>View all your past exchanges and track their status in real-time.</p>
                    </div>
                    
                    <center>
                        <a href="{{dashboard_url}}" class="button">Go to Dashboard</a>
                        <a href="{{login_url}}" class="button" style="background: #4BB74B;">Start Exchange</a>
                    </center>
                    
                    <p>Thank you for choosing {{site_name}} for your currency exchange needs!</p>
                </div>
                <div class="footer">
                    <p>Need help? Contact us at <a href="mailto:{{support_email}}">{{support_email}}</a></p>
                    <p>&copy; ' . date('Y') . ' {{site_name}}. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Password reset template
     */
    private function getPasswordResetTemplate() {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Password Reset</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #DC3545 0%, #FFC107 100%); color: white; text-align: center; padding: 30px; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .button { display: inline-block; background: #DC3545; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>{{site_name}}</h1>
                    <p>Password Reset Request</p>
                </div>
                <div class="content">
                    <h2>Hello {{name}}!</h2>
                    <p>We received a request to reset your password for your {{site_name}} account.</p>
                    
                    <center>
                        <a href="{{reset_url}}" class="button">Reset Password</a>
                    </center>
                    
                    <p>If the button doesn\'t work, you can copy and paste this link into your browser:</p>
                    <p><a href="{{reset_url}}">{{reset_url}}</a></p>
                    
                    <div class="warning">
                        <strong>⚠️ Security Notice:</strong>
                        <ul>
                            <li>This password reset link will expire in 1 hour</li>
                            <li>If you didn\'t request this reset, please ignore this email</li>
                            <li>Never share this reset link with anyone</li>
                        </ul>
                    </div>
                    
                    <p>For your security, we recommend using a strong password that includes:</p>
                    <ul>
                        <li>At least 8 characters</li>
                        <li>A mix of uppercase and lowercase letters</li>
                        <li>Numbers and special characters</li>
                    </ul>
                </div>
                <div class="footer">
                    <p>Need help? Contact us at <a href="mailto:{{support_email}}">{{support_email}}</a></p>
                    <p>&copy; ' . date('Y') . ' {{site_name}}. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Transaction notification template
     */
    private function getTransactionNotificationTemplate() {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Transaction Update</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #5D5CDE 0%, #4BB74B 100%); color: white; text-align: center; padding: 30px; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .transaction-details { background: white; padding: 20px; border-radius: 5px; margin: 20px 0; }
                .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
                .status { padding: 5px 15px; border-radius: 20px; color: white; font-weight: bold; }
                .status.pending { background: #FFC107; }
                .status.confirmed { background: #4BB74B; }
                .status.cancelled { background: #DC3545; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>{{site_name}}</h1>
                    <p>Transaction Update</p>
                </div>
                <div class="content">
                    <h2>Hello {{name}}!</h2>
                    <p>We have an update regarding your transaction.</p>
                    
                    <div class="transaction-details">
                        <h3>Transaction Details</h3>
                        <div class="detail-row">
                            <span>Reference ID:</span>
                            <strong>{{reference_id}}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Amount:</span>
                            <span>{{send_amount}} {{from_currency}} → {{receive_amount}} {{to_currency}}</span>
                        </div>
                        <div class="detail-row">
                            <span>Status:</span>
                            <span class="status {{status_class}}">{{status}}</span>
                        </div>
                        <div class="detail-row">
                            <span>Date:</span>
                            <span>{{date_time}}</span>
                        </div>
                    </div>
                    
                    <p>{{status_message}}</p>
                    
                    <p>You can track your transaction status anytime by visiting your dashboard.</p>
                </div>
                <div class="footer">
                    <p>Need help? Contact us at <a href="mailto:{{support_email}}">{{support_email}}</a></p>
                    <p>&copy; ' . date('Y') . ' {{site_name}}. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Test email configuration
     */
    public function testEmailConfig($testEmail = null) {
        try {
            $testEmail = $testEmail ?: getSetting('contact_email', 'test@example.com');
            $subject = 'Email Configuration Test - ' . getSetting('site_name', 'Exchange Bridge');
            $message = '<h1>Email Test Successful!</h1><p>Your PHPMailer configuration is working correctly.</p><p>Test performed at: ' . date('Y-m-d H:i:s') . '</p>';
            
            return $this->sendEmail($testEmail, $subject, $message);
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Test failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Log email activity
     */
    private function logEmail($to, $subject, $status, $error = null) {
        try {
            // You can create an email_logs table if needed
            error_log("Email {$status}: To={$to}, Subject={$subject}" . ($error ? ", Error={$error}" : ""));
        } catch (Exception $e) {
            error_log("Failed to log email: " . $e->getMessage());
        }
    }
    
    /**
     * Send transaction notification
     */
    public function sendTransactionNotification($email, $name, $transactionData) {
        $siteName = getSetting('site_name', 'Exchange Bridge');
        
        $statusMessages = [
            'pending' => 'Your transaction is being processed. We will update you once it\'s confirmed.',
            'confirmed' => 'Great news! Your transaction has been confirmed and completed successfully.',
            'cancelled' => 'Your transaction has been cancelled. If you have any questions, please contact our support.',
            'refunded' => 'Your transaction has been refunded. The amount will be credited back to your account.'
        ];
        
        $subject = "Transaction Update: {$transactionData['reference_id']} - $siteName";
        $message = $this->getEmailTemplate('transaction-notification', [
            'name' => $name ?: 'User',
            'site_name' => $siteName,
            'reference_id' => $transactionData['reference_id'],
            'send_amount' => $transactionData['send_amount'],
            'from_currency' => $transactionData['from_currency'],
            'receive_amount' => $transactionData['receive_amount'],
            'to_currency' => $transactionData['to_currency'],
            'status' => ucfirst($transactionData['status']),
            'status_class' => $transactionData['status'],
            'status_message' => $statusMessages[$transactionData['status']] ?? 'Your transaction status has been updated.',
            'date_time' => date('d M Y, h:i A', strtotime($transactionData['created_at'])),
            'support_email' => getSetting('contact_email', 'support@exchangebridge.com')
        ]);
        
        return $this->sendEmail($email, $subject, $message);
    }
}