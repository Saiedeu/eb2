<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    setMessage('error', 'Invalid verification link');
    redirect('login.php');
}

// Find user with this token
$stmt = $pdo->prepare("SELECT * FROM users WHERE verification_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    setMessage('error', 'Invalid or expired verification link');
    redirect('login.php');
}

if ($user['email_verified'] == 1) {
    setMessage('info', 'Your email is already verified. You can login now.');
    redirect('login.php');
}

// Verify the email
$stmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?");

if ($stmt->execute([$user['id']])) {
    setMessage('success', 'Email verified successfully! You can now login to your account.');
} else {
    setMessage('error', 'Failed to verify email. Please try again.');
}

redirect('login.php');
?>