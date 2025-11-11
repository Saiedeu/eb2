<?php
// Google OAuth configuration
require_once '../vendor/autoload.php';

$google_client_id = '126175981408-6s0tio3d77c2v45orrqkk9t6pbarhir4.apps.googleusercontent.com';
$google_client_secret = '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';
$google_redirect_uri = 'http://localhost/auth-system/auth/google-login.php';

function getGoogleClient() {
    global $google_client_id, $google_client_secret, $google_redirect_uri;
    
    $client = new Google_Client();
    $client->setClientId($google_client_id);
    $client->setClientSecret($google_client_secret);
    $client->setRedirectUri($google_redirect_uri);
    $client->addScope("email");
    $client->addScope("profile");
    
    return $client;
}
?>