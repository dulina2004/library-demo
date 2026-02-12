<?php
/**
 * Registration Page
 * 
 * Allows new users to create a Student account.
 * Only the Student role is available via public registration.
 * Admins can assign other roles from the admin panel.
 * 
 * CRUD: This is the CREATE part — we insert a new user into the database.
 * 
 * Security:
 * - password_hash() to securely hash passwords
 * - Prepared statements to prevent SQL injection
 * - Server-side validation for all fields
 * - CSRF token protection
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect(getDashboardUrl());
}

$errors = [];
$name = $email = $phone = '';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        // Get and trim input
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // ====== SERVER-SIDE VALIDATION ======
        
        // Name validation
        if (empty($name)) {
            $errors[] = 'Full name is required.';
        } elseif (strlen($name) < 2 || strlen($name) > 100) {
            $errors[] = 'Name must be between 2 and 100 characters.';
        }
        
        // Email validation
        if (empty($email)) {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } else {
            // Check for duplicate email
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors[] = 'An account with this email already exists.';
            }
            $stmt->close();
        }
        
        // Phone validation (optional)
        if (!empty($phone) && !preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {
            $errors[] = 'Please enter a valid phone number.';
        }
        
        // Password validation
        if (empty($password)) {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }
        
        // Confirm password
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }
        
        // ====== INSERT USER IF NO ERRORS ======
        if (empty($errors)) {
            // Hash the password using bcrypt (default algorithm)
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Prepared statement — prevents SQL injection
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, 'Student', ?)");
            $stmt->bind_param("ssss", $name, $email, $hashedPassword, $phone);
            
            if ($stmt->execute()) {
                flash('success', 'Registration successful! Please log in.');
                redirect(baseUrl() . '/auth/login.php');
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
            $stmt->close();
        }
    }
}

$pageTitle = 'Register';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-lg border-0 mt-4">
            <div class="card-body p-4">
                <!-- Header -->
                <div class="text-center mb-4">
                    <div class="auth-icon mx-auto mb-3">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <h3 class="fw-bold">Create Account</h3>
                    <p class="text-muted">Register as a student to get started</p>
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
                
                <!-- Registration Form -->
                <form method="POST" action="" id="registerForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <!-- Full Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label">
                            <i class="bi bi-person me-1"></i>Full Name
                        </label>
                        <input type="text" class="form-control form-control-lg" id="name" name="name" 
                               value="<?php echo sanitize($name); ?>" 
                               placeholder="John Doe" required minlength="2" maxlength="100">
                    </div>
                    
                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="bi bi-envelope me-1"></i>Email Address
                        </label>
                        <input type="email" class="form-control form-control-lg" id="email" name="email" 
                               value="<?php echo sanitize($email); ?>" 
                               placeholder="you@example.com" required>
                    </div>
                    
                    <!-- Phone (Optional) -->
                    <div class="mb-3">
                        <label for="phone" class="form-label">
                            <i class="bi bi-telephone me-1"></i>Phone Number <small class="text-muted">(optional)</small>
                        </label>
                        <input type="tel" class="form-control form-control-lg" id="phone" name="phone" 
                               value="<?php echo sanitize($phone); ?>" 
                               placeholder="077 123 4567">
                    </div>
                    
                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="bi bi-lock me-1"></i>Password
                        </label>
                        <input type="password" class="form-control form-control-lg" id="password" name="password" 
                               placeholder="At least 6 characters" required minlength="6">
                    </div>
                    
                    <!-- Confirm Password -->
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">
                            <i class="bi bi-lock-fill me-1"></i>Confirm Password
                        </label>
                        <input type="password" class="form-control form-control-lg" id="confirm_password" name="confirm_password" 
                               placeholder="Re-enter your password" required>
                    </div>
                    
                    <!-- Submit -->
                    <button type="submit" class="btn btn-primary-custom btn-lg w-100 mb-3">
                        <i class="bi bi-person-plus me-2"></i>Create Account
                    </button>
                </form>
                
                <!-- Login Link -->
                <p class="text-center text-muted mb-0">
                    Already have an account? 
                    <a href="<?php echo baseUrl(); ?>/auth/login.php" class="text-decoration-none fw-semibold">Sign in</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
