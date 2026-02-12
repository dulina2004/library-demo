<?php
/**
 * Reports Page (Admin)
 * 
 * Shows all book issue/return transactions with filtering.
 * Only accessible by administrators.
 * 
 * CRUD: This is the READ part — reading transaction records.
 */

$allowed_roles = ['Admin'];
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

// Build query with optional filters
$query = "
    SELECT ib.*, b.title as book_title, b.isbn, u.name as user_name, u.email as user_email, 
           l.name as librarian_name
    FROM issued_books ib
    JOIN books b ON ib.book_id = b.id
    JOIN users u ON ib.user_id = u.id
    LEFT JOIN users l ON ib.issued_by = l.id
    WHERE 1=1
";
$params = [];
$types = '';

if (!empty($statusFilter) && in_array($statusFilter, ['Requested', 'Issued', 'Returned', 'Rejected'])) {
    $query .= " AND ib.status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

if (!empty($search)) {
    $searchTerm = "%$search%";
    $query .= " AND (b.title LIKE ? OR u.name LIKE ? OR b.isbn LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'sss';
}

$query .= " ORDER BY ib.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$transactions = $stmt->get_result();

$pageTitle = 'Reports';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1"><i class="bi bi-graph-up me-2"></i>Transaction Reports</h2>
        <p class="text-muted mb-0">View all book issue and return records</p>
    </div>
    <a href="<?php echo baseUrl(); ?>/admin/dashboard.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
    </a>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" 
                       placeholder="Search by book title, student name, or ISBN..."
                       value="<?php echo sanitize($search); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="Requested" <?php echo $statusFilter === 'Requested' ? 'selected' : ''; ?>>Requested</option>
                    <option value="Issued" <?php echo $statusFilter === 'Issued' ? 'selected' : ''; ?>>Issued</option>
                    <option value="Returned" <?php echo $statusFilter === 'Returned' ? 'selected' : ''; ?>>Returned</option>
                    <option value="Rejected" <?php echo $statusFilter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary-custom me-2">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="<?php echo baseUrl(); ?>/admin/reports.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Transactions Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="fw-bold mb-0">
            All Transactions
            <span class="badge bg-secondary ms-2"><?php echo $transactions->num_rows; ?> records</span>
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Book</th>
                        <th>ISBN</th>
                        <th>Student</th>
                        <th>Issued By</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($transactions->num_rows > 0): ?>
                        <?php while ($row = $transactions->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td class="fw-semibold"><?php echo sanitize($row['book_title']); ?></td>
                                <td><small class="text-muted"><?php echo sanitize($row['isbn'] ?? '—'); ?></small></td>
                                <td>
                                    <?php echo sanitize($row['user_name']); ?>
                                    <br><small class="text-muted"><?php echo sanitize($row['user_email']); ?></small>
                                </td>
                                <td><?php echo $row['librarian_name'] ? sanitize($row['librarian_name']) : '<span class="text-muted">—</span>'; ?></td>
                                <td><?php echo $row['issue_date'] ? date('M d, Y', strtotime($row['issue_date'])) : '—'; ?></td>
                                <td>
                                    <?php 
                                    if ($row['due_date']) {
                                        $dueDate = strtotime($row['due_date']);
                                        $isOverdue = ($row['status'] === 'Issued' && $dueDate < time());
                                        echo '<span class="' . ($isOverdue ? 'text-danger fw-bold' : '') . '">';
                                        echo date('M d, Y', $dueDate);
                                        if ($isOverdue) echo ' <i class="bi bi-exclamation-triangle"></i>';
                                        echo '</span>';
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                                <td><?php echo $row['return_date'] ? date('M d, Y', strtotime($row['return_date'])) : '—'; ?></td>
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
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No transactions found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
$stmt->close();
require_once __DIR__ . '/../includes/footer.php'; 
?>
