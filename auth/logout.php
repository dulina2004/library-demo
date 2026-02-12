<?php
/**
 * Logout Script
 * 
 * Destroys the current session and redirects to the login page.
 * This is a simple but important security feature.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset all session variables
$_SESSION = [];

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Start a new session for flash message
session_start();
$_SESSION['flash']['success'] = 'You have been logged out successfully.';

// Redirect to login page
header("Location: /lib/auth/login.php");
exit();
?>
