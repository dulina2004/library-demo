<?php
/**
 * Admin Dashboard
 * 
 * Shows key statistics and recent activity for administrators.
 * Only accessible by users with the 'Admin' role.
 * 
 * CRUD: This is the READ part — we read aggregated data from the database.
 */

// Set allowed roles BEFORE including auth_check
$allowed_roles = ['Admin'];
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

// ====== FETCH STATISTICS ======

// Total books count
$totalBooks = $conn->query("SELECT SUM(qty_total) as total FROM books")->fetch_assoc()['total'] ?? 0;

// Currently issued books
$issuedBooks = $conn->query("SELECT COUNT(*) as total FROM issued_books WHERE status = 'Issued'")->fetch_assoc()['total'] ?? 0;

// Available books
$availableBooks = $conn->query("SELECT SUM(qty_available) as total FROM books")->fetch_assoc()['total'] ?? 0;

// Total users
$totalUsers = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'] ?? 0;

// Pending requests
$pendingRequests = $conn->query("SELECT COUNT(*) as total FROM issued_books WHERE status = 'Requested'")->fetch_assoc()['total'] ?? 0;

// Total book titles
$totalTitles = $conn->query("SELECT COUNT(*) as total FROM books")->fetch_assoc()['total'] ?? 0;

// Recent transactions (last 10)
$recentTransactions = $conn->query("
    SELECT ib.*, b.title as book_title, u.name as user_name, l.name as librarian_name
    FROM issued_books ib
    JOIN books b ON ib.book_id = b.id
    JOIN users u ON ib.user_id = u.id
    LEFT JOIN users l ON ib.issued_by = l.id
    ORDER BY ib.created_at DESC
    LIMIT 10
");

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1"><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</h2>
        <p class="text-muted mb-0">Welcome back, <?php echo sanitize($_SESSION['name']); ?>!</p>
    </div>
    <div>
        <span class="badge bg-primary-custom fs-6 px-3 py-2">
            <i class="bi bi-shield-check me-1"></i>Administrator
        </span>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <!-- Total Book Titles -->
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Total Book Titles</p>
                        <h3 class="fw-bold mb-0"><?php echo $totalTitles; ?></h3>
                        <small class="text-muted"><?php echo $totalBooks; ?> total copies</small>
                    </div>
                    <div class="stat-icon bg-primary-light">
                        <i class="bi bi-journal-bookmark text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Issued Books -->
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Books Issued</p>
                        <h3 class="fw-bold mb-0"><?php echo $issuedBooks; ?></h3>
                        <small class="text-muted">Currently out</small>
                    </div>
                    <div class="stat-icon bg-warning-light">
                        <i class="bi bi-arrow-left-right text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Available Books -->
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Available Copies</p>
                        <h3 class="fw-bold mb-0"><?php echo $availableBooks; ?></h3>
                        <small class="text-muted">Ready to issue</small>
                    </div>
                    <div class="stat-icon bg-success-light">
                        <i class="bi bi-check-circle text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Total Users -->
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Total Users</p>
                        <h3 class="fw-bold mb-0"><?php echo $totalUsers; ?></h3>
                        <small class="text-muted"><?php echo $pendingRequests; ?> pending requests</small>
                    </div>
                    <div class="stat-icon bg-info-light">
                        <i class="bi bi-people text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-4 mb-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <h6 class="fw-bold mb-3"><i class="bi bi-lightning me-2"></i>Quick Actions</h6>
                <a href="<?php echo baseUrl(); ?>/admin/manage_users.php" class="btn btn-outline-primary me-2 mb-2">
                    <i class="bi bi-person-plus me-1"></i>Add User
                </a>
                <a href="<?php echo baseUrl(); ?>/librarian/manage_books.php" class="btn btn-outline-success me-2 mb-2">
                    <i class="bi bi-plus-circle me-1"></i>Add Book
                </a>
                <a href="<?php echo baseUrl(); ?>/admin/reports.php" class="btn btn-outline-info me-2 mb-2">
                    <i class="bi bi-graph-up me-1"></i>View Reports
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="fw-bold mb-0">
            <i class="bi bi-clock-history me-2"></i>Recent Transactions
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Book</th>
                        <th>Student</th>
                        <th>Issued By</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recentTransactions->num_rows > 0): ?>
                        <?php while ($row = $recentTransactions->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo sanitize($row['book_title']); ?></td>
                                <td><?php echo sanitize($row['user_name']); ?></td>
                                <td><?php echo $row['librarian_name'] ? sanitize($row['librarian_name']) : '<span class="text-muted">—</span>'; ?></td>
                                <td><?php echo $row['issue_date'] ? date('M d, Y', strtotime($row['issue_date'])) : '—'; ?></td>
                                <td><?php echo $row['due_date'] ? date('M d, Y', strtotime($row['due_date'])) : '—'; ?></td>
                                <td>
                                    <?php
                                    $badgeClass = 'secondary';
                                    if ($row['status'] === 'Issued') $badgeClass = 'warning';
                                    elseif ($row['status'] === 'Returned') $badgeClass = 'success';
                                    elseif ($row['status'] === 'Requested') $badgeClass = 'info';
                                    elseif ($row['status'] === 'Rejected') $badgeClass = 'danger';
                                    ?>
                                    <span class="badge bg-<?php echo $badgeClass; ?>"><?php echo sanitize($row['status']); ?></span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No transactions found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
