<?php
/**
 * Google OAuth Callback
 */

// Start session and define access
session_start();
define('ALLOW_ACCESS', true);

// Include required files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/user-auth.php';

$userAuth = UserAuth::getInstance();

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

// Check for authorization code
$code = $_GET['code'] ?? '';
if (empty($code)) {
    $_SESSION['auth_error'] = 'Authorization code not received from Google';
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
    
    // Exchange authorization code for access token
    $token = $client->fetchAccessTokenWithAuthCode($code);
    
    if (isset($token['error'])) {
        throw new Exception('Error fetching access token: ' . $token['error']);
    }
    
    // Set access token
    $client->setAccessToken($token);
    
    // Get user info from Google
    $oauth2 = new Google_Service_Oauth2($client);
    $userInfo = $oauth2->userinfo->get();
    
    $googleId = $userInfo->getId();
    $email = $userInfo->getEmail();
    $name = $userInfo->getName();
    $verified = $userInfo->getVerifiedEmail();
    
    if (!$verified) {
        $_SESSION['auth_error'] = 'Please use a verified Google account';
        header('Location: login.php');
        exit();
    }
    
    // Get action from session
    $action = $_SESSION['google_auth_action'] ?? 'login';
    unset($_SESSION['google_auth_action']);
    
    if ($action === 'signup') {
        // Try to register
        $result = $userAuth->register($email, null, $name, $googleId);
        
        if ($result['success']) {
            if (!$result['needs_verification']) {
                // Auto-login after successful registration
                $loginResult = $userAuth->login($email, null, $googleId);
                if ($loginResult['success']) {
                    header('Location: dashboard.php?registered=1');
                    exit();
                }
            }
            header('Location: login.php?registered=1');
            exit();
        } else {
            // If registration fails, try to login (user might already exist)
            $loginResult = $userAuth->login($email, null, $googleId);
            
            if ($loginResult['success']) {
                header('Location: dashboard.php');
                exit();
            } else {
                $_SESSION['auth_error'] = $result['message'];
                header('Location: register.php');
                exit();
            }
        }
    } else {
        // Try to login
        $result = $userAuth->login($email, null, $googleId);
        
        if ($result['success']) {
            header('Location: dashboard.php');
            exit();
        } else {
            $_SESSION['auth_error'] = $result['message'];
            header('Location: login.php');
            exit();
        }
    }
    
} catch (Exception $e) {
    error_log('Google OAuth callback error: ' . $e->getMessage());
    $_SESSION['auth_error'] = 'Authentication failed. Please try again.';
    header('Location: login.php');
    exit();
}
?>