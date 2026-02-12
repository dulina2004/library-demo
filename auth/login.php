<?php
/**
 * Login Page
 * 
 * Handles user authentication using PHP sessions.
 * 
 * CRUD: This is the READ part — we read user data from the database
 * and verify the password using password_verify().
 * 
 * Security:
 * - Uses prepared statements to prevent SQL injection
 * - Uses password_verify() to check hashed passwords
 * - Regenerates session ID on login to prevent session fixation
 * - CSRF token protects the form
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect(getDashboardUrl());
}

$errors = [];

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        // Get and sanitize input
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validate input
        if (empty($email)) {
            $errors[] = 'Email is required.';
        }
        if (empty($password)) {
            $errors[] = 'Password is required.';
        }
        
        // If no validation errors, attempt login
        if (empty($errors)) {
            // Prepared statement prevents SQL injection
            $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);  // "s" = string parameter
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verify password against stored hash
                if (password_verify($password, $user['password'])) {
                    // Password correct — create session
                    session_regenerate_id(true); // Prevent session fixation
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    
                    flash('success', 'Welcome back, ' . $user['name'] . '!');
                    redirect(getDashboardUrl());
                } else {
                    $errors[] = 'Invalid email or password.';
                }
            } else {
                $errors[] = 'Invalid email or password.';
            }
            
            $stmt->close();
        }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-lg border-0 mt-5">
            <div class="card-body p-4">
                <!-- Login Header -->
                <div class="text-center mb-4">
                    <div class="auth-icon mx-auto mb-3">
                        <i class="bi bi-book-half"></i>
                    </div>
                    <h3 class="fw-bold">Welcome Back</h3>
                    <p class="text-muted">Sign in to your account</p>
                </div>
                
                <!-- Error Messages -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo sanitize($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <!-- Login Form -->
                <form method="POST" action="" id="loginForm" novalidate>
                    <!-- CSRF Token (hidden field to prevent cross-site request forgery) -->
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <!-- Email Field -->
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="bi bi-envelope me-1"></i>Email Address
                        </label>
                        <input type="email" class="form-control form-control-lg" id="email" name="email" 
                               value="<?php echo sanitize($email ?? ''); ?>" 
                               placeholder="you@example.com" required>
                    </div>
                    
                    <!-- Password Field -->
                    <div class="mb-4">
                        <label for="password" class="form-label">
                            <i class="bi bi-lock me-1"></i>Password
                        </label>
                        <input type="password" class="form-control form-control-lg" id="password" name="password" 
                               placeholder="Enter your password" required>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary-custom btn-lg w-100 mb-3">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                    </button>
                </form>
                
                <!-- Register Link -->
                <p class="text-center text-muted mb-0">
                    Don't have an account? 
                    <a href="<?php echo baseUrl(); ?>/auth/register.php" class="text-decoration-none fw-semibold">Register here</a>
                </p>
            </div>
        </div>
        
        <!-- Demo Credentials -->
        <div class="card mt-3 border-0 bg-light">
            <div class="card-body py-3">
                <p class="small text-muted mb-2"><strong>Demo Accounts:</strong></p>
                <div class="small text-muted">
                    <p class="mb-1"><strong>Admin:</strong> admin@library.com / Admin@123</p>
                    <p class="mb-1"><strong>Librarian:</strong> librarian@library.com / Librarian@123</p>
                    <p class="mb-0"><strong>Student:</strong> student@library.com / Student@123</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
