<?php
/**
 * Index Page - Entry Point
 * 
 * This is the main entry point of the application.
 * - If the user is logged in, redirect to their dashboard
 * - If not logged in, redirect to the login page
 */

require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    // Redirect to role-based dashboard
    redirect(getDashboardUrl());
} else {
    // Redirect to login page
    redirect(baseUrl() . '/auth/login.php');
}
?>
