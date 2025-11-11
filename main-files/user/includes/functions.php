<?php
// Helper functions for authentication

function generateToken() {
    return bin2hex(random_bytes(32));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePassword($password) {
    // At least 6 characters
    return strlen($password) >= 6;
}

function setMessage($type, $message) {
    $_SESSION['message'] = [
        'type' => $type, // success, error, warning, info
        'text' => $message
    ];
}

function displayMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $alertClass = '';
        
        switch($message['type']) {
            case 'success':
                $alertClass = 'alert-success';
                break;
            case 'error':
                $alertClass = 'alert-error';
                break;
            case 'warning':
                $alertClass = 'alert-warning';
                break;
            default:
                $alertClass = 'alert-info';
        }
        
        echo "<div class='alert $alertClass'>" . htmlspecialchars($message['text']) . "</div>";
        unset($_SESSION['message']);
    }
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('../auth/login.php');
    }
}
?>