<?php
/**
 * Librarian Dashboard
 * 
 * Overview page for librarians showing book and issue statistics.
 * Accessible by Librarian and Admin roles.
 */

$allowed_roles = ['Librarian', 'Admin'];
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

// Fetch statistics
$totalBooks = $conn->query("SELECT COUNT(*) as total FROM books")->fetch_assoc()['total'] ?? 0;
$issuedOut = $conn->query("SELECT COUNT(*) as total FROM issued_books WHERE status = 'Issued'")->fetch_assoc()['total'] ?? 0;
$pendingRequests = $conn->query("SELECT COUNT(*) as total FROM issued_books WHERE status = 'Requested'")->fetch_assoc()['total'] ?? 0;
$totalCategories = $conn->query("SELECT COUNT(*) as total FROM categories")->fetch_assoc()['total'] ?? 0;

// Recent activity
$recentActivity = $conn->query("
    SELECT ib.*, b.title as book_title, u.name as user_name
    FROM issued_books ib
    JOIN books b ON ib.book_id = b.id
    JOIN users u ON ib.user_id = u.id
    ORDER BY ib.updated_at DESC
    LIMIT 8
");

$pageTitle = 'Librarian Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1"><i class="bi bi-speedometer2 me-2"></i>Librarian Dashboard</h2>
        <p class="text-muted mb-0">Welcome, <?php echo sanitize($_SESSION['name']); ?>!</p>
    </div>
    <span class="badge bg-primary fs-6 px-3 py-2">
        <i class="bi bi-bookmark-star me-1"></i>Librarian
    </span>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Total Books</p>
                        <h3 class="fw-bold mb-0"><?php echo $totalBooks; ?></h3>
                    </div>
                    <div class="stat-icon bg-primary-light">
                        <i class="bi bi-journal-bookmark text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Issued Out</p>
                        <h3 class="fw-bold mb-0"><?php echo $issuedOut; ?></h3>
                    </div>
                    <div class="stat-icon bg-warning-light">
                        <i class="bi bi-arrow-left-right text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Pending Requests</p>
                        <h3 class="fw-bold mb-0"><?php echo $pendingRequests; ?></h3>
                    </div>
                    <div class="stat-icon bg-info-light">
                        <i class="bi bi-hourglass-split text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Categories</p>
                        <h3 class="fw-bold mb-0"><?php echo $totalCategories; ?></h3>
                    </div>
                    <div class="stat-icon bg-success-light">
                        <i class="bi bi-tags text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <h6 class="fw-bold mb-3"><i class="bi bi-lightning me-2"></i>Quick Actions</h6>
                <a href="<?php echo baseUrl(); ?>/librarian/manage_books.php" class="btn btn-outline-primary me-2 mb-2">
                    <i class="bi bi-plus-circle me-1"></i>Add Book
                </a>
                <a href="<?php echo baseUrl(); ?>/librarian/issue_books.php" class="btn btn-outline-warning me-2 mb-2">
                    <i class="bi bi-arrow-left-right me-1"></i>Issue Books
                </a>
                <?php if ($pendingRequests > 0): ?>
                    <a href="<?php echo baseUrl(); ?>/librarian/issue_books.php" class="btn btn-info me-2 mb-2">
                        <i class="bi bi-bell me-1"></i><?php echo $pendingRequests; ?> Pending Request(s)
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="fw-bold mb-0"><i class="bi bi-clock-history me-2"></i>Recent Activity</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Book</th>
                        <th>Student</th>
                        <th>Status</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $recentActivity->fetch_assoc()): ?>
                        <tr>
                            <td class="fw-semibold"><?php echo sanitize($row['book_title']); ?></td>
                            <td><?php echo sanitize($row['user_name']); ?></td>
                            <td>
                                <?php
                                $bc = 'secondary';
                                if ($row['status'] === 'Issued') $bc = 'warning';
                                elseif ($row['status'] === 'Returned') $bc = 'success';
                                elseif ($row['status'] === 'Requested') $bc = 'info';
                                elseif ($row['status'] === 'Rejected') $bc = 'danger';
                                ?>
                                <span class="badge bg-<?php echo $bc; ?>"><?php echo sanitize($row['status']); ?></span>
                            </td>
                            <td><small class="text-muted"><?php echo date('M d, Y H:i', strtotime($row['updated_at'])); ?></small></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
