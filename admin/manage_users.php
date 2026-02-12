<?php
/**
 * Manage Users (Admin)
 * 
 * Full CRUD operations for user management.
 * Only accessible by administrators.
 * 
 * CRUD Explanation:
 * - CREATE: Add a new user with a hashed password
 * - READ:   List all users in a table
 * - UPDATE: Edit user details and role
 * - DELETE: Remove a user from the system
 * 
 * All operations use PREPARED STATEMENTS to prevent SQL injection.
 */

$allowed_roles = ['Admin'];
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

// ====== HANDLE FORM ACTIONS ======

// DELETE USER
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    
    // Prevent admin from deleting themselves
    if ($deleteId === (int) $_SESSION['user_id']) {
        flash('error', 'You cannot delete your own account.');
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $deleteId);
        if ($stmt->execute()) {
            flash('success', 'User deleted successfully.');
        } else {
            flash('error', 'Failed to delete user.');
        }
        $stmt->close();
    }
    redirect(baseUrl() . '/admin/manage_users.php');
}

// ADD or EDIT USER
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid form submission.');
        redirect(baseUrl() . '/admin/manage_users.php');
    }
    
    $action = $_POST['action'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? 'Student';
    $password = $_POST['password'] ?? '';
    
    // Validate required fields
    $errors = [];
    if (empty($name)) $errors[] = 'Name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (!in_array($role, ['Admin', 'Librarian', 'Student'])) $errors[] = 'Invalid role.';
    
    if (!empty($errors)) {
        flash('error', implode(' ', $errors));
        redirect(baseUrl() . '/admin/manage_users.php');
    }
    
    if ($action === 'add') {
        // ====== CREATE operation ======
        if (empty($password) || strlen($password) < 6) {
            flash('error', 'Password must be at least 6 characters.');
            redirect(baseUrl() . '/admin/manage_users.php');
        }
        
        // Check duplicate email
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            flash('error', 'Email already exists.');
            $stmt->close();
            redirect(baseUrl() . '/admin/manage_users.php');
        }
        $stmt->close();
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $hashedPassword, $role, $phone);
        
        if ($stmt->execute()) {
            flash('success', 'User added successfully.');
        } else {
            flash('error', 'Failed to add user.');
        }
        $stmt->close();
        
    } elseif ($action === 'edit') {
        // ====== UPDATE operation ======
        $editId = (int) ($_POST['user_id'] ?? 0);
        
        if (!empty($password)) {
            // Update with new password
            if (strlen($password) < 6) {
                flash('error', 'Password must be at least 6 characters.');
                redirect(baseUrl() . '/admin/manage_users.php');
            }
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ?, role = ?, phone = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $name, $email, $hashedPassword, $role, $phone, $editId);
        } else {
            // Update without changing password
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ?, phone = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $name, $email, $role, $phone, $editId);
        }
        
        if ($stmt->execute()) {
            flash('success', 'User updated successfully.');
        } else {
            flash('error', 'Failed to update user.');
        }
        $stmt->close();
    }
    
    redirect(baseUrl() . '/admin/manage_users.php');
}

// ====== READ: Fetch all users ======
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");

$pageTitle = 'Manage Users';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1"><i class="bi bi-people me-2"></i>Manage Users</h2>
        <p class="text-muted mb-0">Add, edit, or remove system users</p>
    </div>
    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="bi bi-person-plus me-1"></i>Add New User
    </button>
</div>

<!-- Users Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="usersTable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users->num_rows > 0): ?>
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td class="fw-semibold"><?php echo sanitize($user['name']); ?></td>
                                <td><?php echo sanitize($user['email']); ?></td>
                                <td><?php echo sanitize($user['phone'] ?? '—'); ?></td>
                                <td>
                                    <?php
                                    $roleBadge = 'secondary';
                                    if ($user['role'] === 'Admin') $roleBadge = 'danger';
                                    elseif ($user['role'] === 'Librarian') $roleBadge = 'primary';
                                    elseif ($user['role'] === 'Student') $roleBadge = 'success';
                                    ?>
                                    <span class="badge bg-<?php echo $roleBadge; ?>"><?php echo sanitize($user['role']); ?></span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <!-- Edit Button -->
                                    <button class="btn btn-sm btn-outline-primary me-1" 
                                            data-bs-toggle="modal" data-bs-target="#editUserModal"
                                            data-id="<?php echo $user['id']; ?>"
                                            data-name="<?php echo sanitize($user['name']); ?>"
                                            data-email="<?php echo sanitize($user['email']); ?>"
                                            data-phone="<?php echo sanitize($user['phone'] ?? ''); ?>"
                                            data-role="<?php echo sanitize($user['role']); ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    
                                    <!-- Delete Button -->
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="?delete=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger delete-confirm"
                                           data-name="<?php echo sanitize($user['name']); ?>">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ====== ADD USER MODAL ====== -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="add">
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="Student">Student</option>
                            <option value="Librarian">Librarian</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="bi bi-plus-circle me-1"></i>Add User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ====== EDIT USER MODAL ====== -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="editUserId">
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" id="editName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" id="editEmail" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" id="editPhone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" id="editRole" class="form-select" required>
                            <option value="Student">Student</option>
                            <option value="Librarian">Librarian</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password <small class="text-muted">(leave blank to keep current)</small></label>
                        <input type="password" name="password" class="form-control" minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="bi bi-check-lg me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Script to populate Edit Modal with user data -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editUserModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const btn = event.relatedTarget;
            document.getElementById('editUserId').value = btn.dataset.id;
            document.getElementById('editName').value = btn.dataset.name;
            document.getElementById('editEmail').value = btn.dataset.email;
            document.getElementById('editPhone').value = btn.dataset.phone;
            document.getElementById('editRole').value = btn.dataset.role;
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
