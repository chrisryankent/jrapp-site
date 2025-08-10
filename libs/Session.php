<?php
/**
 * Session Management - Plain PHP
 */

// Start the session
if (version_compare(phpversion(), '5.4.0', '<')) {
    if (session_id() == '') {
        session_start();
    }
} else {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Example: Set a session variable
$_SESSION['key'] = 'value'; // Replace 'key' and 'value' with your session key and value

// Example: Get a session variable
if (isset($_SESSION['key'])) {
    $value = $_SESSION['key'];
} else {
    $value = false;
}

// Example: Check if the session is valid
if (!isset($_SESSION['userlogin']) || $_SESSION['userlogin'] == false) {
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session
    header("Location: signin.php"); // Redirect to the sign-in page
    exit();
}

// Example: Check if the user is already logged in
if (isset($_SESSION['userlogin']) && $_SESSION['userlogin'] == true) {
    header("Location: dashboard.php"); // Redirect to the dashboard if already logged in
    exit();
}
?>