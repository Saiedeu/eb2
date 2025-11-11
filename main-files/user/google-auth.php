<?php
/**
 * Google OAuth Authentication
 */

// Start session and define access
session_start();
define('ALLOW_ACCESS', true);

// Include required files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if Google OAuth is enabled
if (getSetting('google_oauth_enabled', 'no') !== 'yes') {
    header('Location: login.php');
    exit();
}

// Get Google OAuth settings
$clientId = getSetting('google_oauth_client_id', '');
$clientSecret = getSetting('google_oauth_client_secret', '');
$redirectUri = getSetting('google_oauth_redirect_uri', SITE_URL . '/users/google-callback.php');

if (empty($clientId) || empty($clientSecret)) {
    $_SESSION['auth_error'] = 'Google OAuth is not properly configured';
    header('Location: login.php');
    exit();
}

// Include Google API Client
require_once '../vendor/autoload.php';

try {
    $client = new Google_Client();
    $client->setClientId($clientId);
    $client->setClientSecret($clientSecret);
    $client->setRedirectUri($redirectUri);
    $client->addScope('email');
    $client->addScope('profile');
    
    // Store action (login or signup) in session
    $action = $_GET['action'] ?? 'login';
    $_SESSION['google_auth_action'] = $action;
    
    // Generate authentication URL
    $authUrl = $client->createAuthUrl();
    
    // Redirect to Google OAuth
    header('Location: ' . $authUrl);
    exit();
    
} catch (Exception $e) {
    error_log('Google OAuth error: ' . $e->getMessage());
    $_SESSION['auth_error'] = 'Failed to initialize Google authentication';
    header('Location: login.php');
    exit();
}
?>