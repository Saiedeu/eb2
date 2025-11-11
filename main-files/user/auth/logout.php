<?php
require_once '../includes/session.php';

// Destroy all session data
session_unset();
session_destroy();

// Start new session for message
session_start();
require_once '../includes/functions.php';

setMessage('success', 'You have been logged out successfully');
redirect('login.php');
?>