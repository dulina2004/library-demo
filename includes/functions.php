<?php
/**
 * Helper Functions
 * 
 * This file contains reusable utility functions used throughout the application.
 * Include this file in pages that need these helpers.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Sanitize output to prevent XSS attacks
 * Wraps htmlspecialchars() for convenience
 * 
 * @param string $data - The string to sanitize
 * @return string - Sanitized string safe for HTML output
 */
function sanitize($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a given URL
 * 
 * @param string $url - The URL to redirect to
 */
function redirect($url) {
    header("Location: $url");
    exit(); // Always exit after redirect to prevent further code execution
}

/**
 * Flash message system
 * Set a message in one request, display it in the next
 * 
 * Usage:
 *   flash('success', 'Book added successfully!');  // Set
 *   flash('success');                                // Get & clear
 * 
 * @param string $key   - Message type (success, error, warning, info)
 * @param string $value - Message text (omit to retrieve)
 * @return string|void  - Returns message HTML if retrieving
 */
function flash($key, $value = null) {
    if ($value !== null) {
        // SET the flash message
        $_SESSION['flash'][$key] = $value;
    } else {
        // GET and CLEAR the flash message
        if (isset($_SESSION['flash'][$key])) {
            $msg = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            
            // Map key to Bootstrap alert class
            $alertClass = 'info';
            if ($key === 'success') $alertClass = 'success';
            elseif ($key === 'error') $alertClass = 'danger';
            elseif ($key === 'warning') $alertClass = 'warning';
            
            return '<div class="alert alert-' . $alertClass . ' alert-dismissible fade show" role="alert">'
                 . sanitize($msg)
                 . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>'
                 . '</div>';
        }
        return '';
    }
}

/**
 * Check if a user is currently logged in
 * 
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Check if the logged-in user has a specific role
 * 
 * @param string|array $roles - Role name(s) to check
 * @return bool
 */
function hasRole($roles) {
    if (!isLoggedIn()) return false;
    
    // Accept single role or array of roles
    if (is_string($roles)) {
        $roles = [$roles];
    }
    
    return in_array($_SESSION['role'], $roles);
}

/**
 * Get the base URL path for the application
 * Useful for generating links that work in any deployment
 * 
 * @return string
 */
function baseUrl() {
    return '/lib';
}

/**
 * Generate a CSRF token and store it in the session
 * 
 * @return string - The CSRF token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a CSRF token from a form submission
 * 
 * @param string $token - Token from the form
 * @return bool
 */
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get the dashboard URL for the current user's role
 * 
 * @return string
 */
function getDashboardUrl() {
    $base = baseUrl();
    switch ($_SESSION['role'] ?? '') {
        case 'Admin':
            return "$base/admin/dashboard.php";
        case 'Librarian':
            return "$base/librarian/dashboard.php";
        case 'Student':
            return "$base/user/dashboard.php";
        default:
            // Invalid/missing role — destroy corrupt session to prevent redirect loop
            session_destroy();
            session_start();
            return "$base/auth/login.php";
    }
}
?>
