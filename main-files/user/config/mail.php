<?php
// Email configuration using PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendVerificationEmail($to_email, $to_name, $verification_token) {
    require_once '../vendor/autoload.php';
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'Exchangebridge.bd@gmail.com';
        $mail->Password   = 'kjjg feoj ijwv fwwr'; // App password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('Exchangebridge.bd@gmail.com', 'Exchange Bridge');
        $mail->addAddress($to_email, $to_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification - Exchange Bridge';
        
        $verification_link = "http://localhost/auth-system/auth/verify.php?token=" . $verification_token;
        
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #333;'>Welcome to Exchange Bridge!</h2>
            <p>Hi $to_name,</p>
            <p>Thank you for signing up! Please click the button below to verify your email address:</p>
            <div style='text-align: center; margin: 30px 0;'>
                <a href='$verification_link' 
                   style='background-color: #007bff; color: white; padding: 12px 30px; 
                          text-decoration: none; border-radius: 5px; display: inline-block;'>
                    Verify Email Address
                </a>
            </div>
            <p>Or copy and paste this link in your browser:</p>
            <p style='color: #666; word-break: break-all;'>$verification_link</p>
            <p>This link will expire in 24 hours.</p>
            <hr style='margin: 30px 0;'>
            <p style='color: #888; font-size: 12px;'>
                If you didn't create an account, please ignore this email.
            </p>
        </div>";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>