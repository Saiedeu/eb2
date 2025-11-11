<?php
/**
 * User Logout
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

// Logout user
$userAuth->logout();

// Redirect to home page
header('Location: ../index.php?logged_out=1');
exit();
?>