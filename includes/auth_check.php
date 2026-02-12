<?php
/**
 * Authentication Check
 * 
 * Include this file at the TOP of every protected page.
 * It verifies that the user is logged in and has the correct role.
 * 
 * Usage:
 *   // Allow only Admin
 *   $allowed_roles = ['Admin'];
 *   require_once __DIR__ . '/auth_check.php';
 * 
 *   // Allow Admin and Librarian
 *   $allowed_roles = ['Admin', 'Librarian'];
 *   require_once __DIR__ . '/auth_check.php';
 * 
 *   // Allow any logged-in user (don't set $allowed_roles)
 *   require_once __DIR__ . '/auth_check.php';
 */

// Include helper functions
require_once __DIR__ . '/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    flash('error', 'Please log in to access this page.');
    redirect(baseUrl() . '/auth/login.php');
}

// Check role-based access if $allowed_roles is defined
if (isset($allowed_roles) && !empty($allowed_roles)) {
    if (!hasRole($allowed_roles)) {
        flash('error', 'You do not have permission to access this page.');
        redirect(getDashboardUrl());
    }
}
?>
