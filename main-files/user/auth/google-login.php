<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../config/google.php';
require_once '../includes/functions.php';

$client = getGoogleClient();

if (!isset($_GET['code'])) {
    // Redirect to Google OAuth
    $auth_url = $client->createAuthUrl();
    redirect($auth_url);
} else {
    // Handle Google callback
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        if (isset($token['error'])) {
            setMessage('error', 'Google authentication failed');
            redirect('login.php');
        }
        
        $client->setAccessToken($token['access_token']);
        
        // Get user profile
        $google_oauth = new Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();
        
        $google_id = $google_account_info->id;
        $name = $google_account_info->name;
        $email = $google_account_info->email;
        $profile_image = $google_account_info->picture;
        
        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR google_id = ?");
        $stmt->execute([$email, $google_id]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Update Google ID if not set
            if (empty($user['google_id'])) {
                $stmt = $pdo->prepare("UPDATE users SET google_id = ?, profile_image = ? WHERE id = ?");
                $stmt->execute([$google_id, $profile_image, $user['id']]);
            }
            
            // Login user
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['login_type'] = 'google';
            
        } else {
            // Create new user
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, google_id, profile_image, email_verified, login_type) 
                VALUES (?, ?, ?, ?, 1, 'google')
            ");
            
            if ($stmt->execute([$name, $email, $google_id, $profile_image])) {
                $user_id = $pdo->lastInsertId();
                
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['login_type'] = 'google';
            } else {
                setMessage('error', 'Failed to create account');
                redirect('login.php');
            }
        }
        
        redirect('../dashboard.php');
        
    } catch (Exception $e) {
        setMessage('error', 'Google authentication failed: ' . $e->getMessage());
        redirect('login.php');
    }
}
?>