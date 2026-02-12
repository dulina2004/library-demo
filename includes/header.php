<?php
/**
 * Header Include
 * 
 * This file is included at the top of every page. It contains:
 * - HTML head with Bootstrap 5 CDN
 * - Navigation bar with role-based menus
 * - Flash message display area
 */

// Ensure functions are available
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Library Management System - University Project">
    <title><?php echo isset($pageTitle) ? sanitize($pageTitle) . ' | ' : ''; ?>Library Management System</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo baseUrl(); ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary-custom sticky-top shadow-sm">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand fw-bold" href="<?php echo baseUrl(); ?>/index.php">
            <i class="bi bi-book-half me-2"></i>LibraryMS
        </a>
        
        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto">
                <?php if (isLoggedIn()): ?>
                    
                    <?php if (hasRole('Admin')): ?>
                        <!-- ========== ADMIN MENU ========== -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo baseUrl(); ?>/admin/dashboard.php">
                                <i class="bi bi-speedometer2 me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo baseUrl(); ?>/admin/manage_users.php">
                                <i class="bi bi-people me-1"></i>Manage Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo baseUrl(); ?>/librarian/manage_books.php">
                                <i class="bi bi-journal-bookmark me-1"></i>Manage Books
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo baseUrl(); ?>/admin/reports.php">
                                <i class="bi bi-graph-up me-1"></i>Reports
                            </a>
                        </li>
                    
                    <?php elseif (hasRole('Librarian')): ?>
                        <!-- ========== LIBRARIAN MENU ========== -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo baseUrl(); ?>/librarian/dashboard.php">
                                <i class="bi bi-speedometer2 me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo baseUrl(); ?>/librarian/manage_books.php">
                                <i class="bi bi-journal-bookmark me-1"></i>Manage Books
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo baseUrl(); ?>/librarian/issue_books.php">
                                <i class="bi bi-arrow-left-right me-1"></i>Issue Books
                            </a>
                        </li>
                    
                    <?php elseif (hasRole('Student')): ?>
                        <!-- ========== STUDENT MENU ========== -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo baseUrl(); ?>/user/dashboard.php">
                                <i class="bi bi-speedometer2 me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo baseUrl(); ?>/user/view_books.php">
                                <i class="bi bi-search me-1"></i>Browse Books
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo baseUrl(); ?>/user/my_books.php">
                                <i class="bi bi-bookmark-check me-1"></i>My Books
                            </a>
                        </li>
                    <?php endif; ?>
                    
                <?php endif; ?>
            </ul>
            
            <!-- Right Side - User Info & Logout -->
            <ul class="navbar-nav ms-auto">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            <?php echo sanitize($_SESSION['name']); ?>
                            <span class="badge bg-light text-dark ms-1"><?php echo sanitize($_SESSION['role']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo getDashboardUrl(); ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo baseUrl(); ?>/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo baseUrl(); ?>/auth/login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo baseUrl(); ?>/auth/register.php">
                            <i class="bi bi-person-plus me-1"></i>Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content Container -->
<main class="container py-4">
    <!-- Flash Messages -->
    <?php echo flash('success'); ?>
    <?php echo flash('error'); ?>
    <?php echo flash('warning'); ?>
    <?php echo flash('info'); ?>
