<?php
/**
 * Student Dashboard
 * 
 * Overview page for students showing their borrowing stats.
 */

$allowed_roles = ['Student'];
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$userId = $_SESSION['user_id'];

// Current borrowed books count
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM issued_books WHERE user_id = ? AND status = 'Issued'");
$stmt->bind_param("i", $userId);
$stmt->execute();
$borrowedCount = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Pending requests
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM issued_books WHERE user_id = ? AND status = 'Requested'");
$stmt->bind_param("i", $userId);
$stmt->execute();
$pendingCount = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Overdue books
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM issued_books WHERE user_id = ? AND status = 'Issued' AND due_date < CURDATE()");
$stmt->bind_param("i", $userId);
$stmt->execute();
$overdueCount = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Total books read
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM issued_books WHERE user_id = ? AND status = 'Returned'");
$stmt->bind_param("i", $userId);
$stmt->execute();
$returnedCount = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Currently borrowed details
$stmt = $conn->prepare("
    SELECT ib.*, b.title as book_title, b.author
    FROM issued_books ib
    JOIN books b ON ib.book_id = b.id
    WHERE ib.user_id = ? AND ib.status = 'Issued'
    ORDER BY ib.due_date ASC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$currentBooks = $stmt->get_result();
$stmt->close();

$pageTitle = 'My Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1"><i class="bi bi-speedometer2 me-2"></i>My Dashboard</h2>
        <p class="text-muted mb-0">Welcome, <?php echo sanitize($_SESSION['name']); ?>!</p>
    </div>
    <span class="badge bg-success fs-6 px-3 py-2">
        <i class="bi bi-mortarboard me-1"></i>Student
    </span>
</div>

<!-- Stats -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Currently Borrowed</p>
                        <h3 class="fw-bold mb-0"><?php echo $borrowedCount; ?></h3>
                    </div>
                    <div class="stat-icon bg-primary-light">
                        <i class="bi bi-book text-primary"></i>
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
                        <h3 class="fw-bold mb-0"><?php echo $pendingCount; ?></h3>
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
                        <p class="text-muted small mb-1">Overdue</p>
                        <h3 class="fw-bold mb-0 <?php echo $overdueCount > 0 ? 'text-danger' : ''; ?>"><?php echo $overdueCount; ?></h3>
                    </div>
                    <div class="stat-icon bg-<?php echo $overdueCount > 0 ? 'danger' : 'warning'; ?>-light">
                        <i class="bi bi-exclamation-triangle text-<?php echo $overdueCount > 0 ? 'danger' : 'warning'; ?>"></i>
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
                        <p class="text-muted small mb-1">Books Read</p>
                        <h3 class="fw-bold mb-0"><?php echo $returnedCount; ?></h3>
                    </div>
                    <div class="stat-icon bg-success-light">
                        <i class="bi bi-check-circle text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <h6 class="fw-bold mb-3"><i class="bi bi-lightning me-2"></i>Quick Actions</h6>
        <a href="<?php echo baseUrl(); ?>/user/view_books.php" class="btn btn-outline-primary me-2 mb-2">
            <i class="bi bi-search me-1"></i>Browse Books
        </a>
        <a href="<?php echo baseUrl(); ?>/user/my_books.php" class="btn btn-outline-success me-2 mb-2">
            <i class="bi bi-bookmark-check me-1"></i>My Books
        </a>
    </div>
</div>

<!-- Current Books -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="fw-bold mb-0"><i class="bi bi-book me-2"></i>Currently Borrowed</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Book</th>
                        <th>Author</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($currentBooks->num_rows > 0): ?>
                        <?php while ($row = $currentBooks->fetch_assoc()): ?>
                            <?php $isOverdue = strtotime($row['due_date']) < time(); ?>
                            <tr class="<?php echo $isOverdue ? 'table-danger' : ''; ?>">
                                <td class="fw-semibold"><?php echo sanitize($row['book_title']); ?></td>
                                <td><?php echo sanitize($row['author']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['issue_date'])); ?></td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($row['due_date'])); ?>
                                    <?php if ($isOverdue): ?>
                                        <span class="badge bg-danger ms-1">OVERDUE</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-warning">Issued</span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                You haven't borrowed any books. 
                                <a href="<?php echo baseUrl(); ?>/user/view_books.php">Browse available books</a>.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
