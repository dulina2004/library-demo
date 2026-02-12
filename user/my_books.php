<?php
/**
 * My Books (Student)
 * 
 * Shows the student's currently borrowed books and complete issue history.
 * 
 * CRUD: READ — reading the student's issue records
 */

$allowed_roles = ['Student'];
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$userId = $_SESSION['user_id'];

// Current borrowed + pending
$stmt = $conn->prepare("
    SELECT ib.*, b.title as book_title, b.author, l.name as librarian_name
    FROM issued_books ib
    JOIN books b ON ib.book_id = b.id
    LEFT JOIN users l ON ib.issued_by = l.id
    WHERE ib.user_id = ? AND ib.status IN ('Issued', 'Requested')
    ORDER BY ib.created_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$currentBooks = $stmt->get_result();
$stmt->close();

// History (returned + rejected)
$stmt = $conn->prepare("
    SELECT ib.*, b.title as book_title, b.author, l.name as librarian_name
    FROM issued_books ib
    JOIN books b ON ib.book_id = b.id
    LEFT JOIN users l ON ib.issued_by = l.id
    WHERE ib.user_id = ? AND ib.status IN ('Returned', 'Rejected')
    ORDER BY ib.updated_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$history = $stmt->get_result();
$stmt->close();

$pageTitle = 'My Books';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1"><i class="bi bi-bookmark-check me-2"></i>My Books</h2>
        <p class="text-muted mb-0">View your current books and issue history</p>
    </div>
    <a href="<?php echo baseUrl(); ?>/user/view_books.php" class="btn btn-primary-custom">
        <i class="bi bi-search me-1"></i>Browse More Books
    </a>
</div>

<!-- Active Books -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="fw-bold mb-0">
            <i class="bi bi-book me-2 text-primary"></i>Active Books
            <span class="badge bg-primary ms-2"><?php echo $currentBooks->num_rows; ?></span>
        </h5>
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
                            <?php $isOverdue = ($row['status'] === 'Issued' && strtotime($row['due_date']) < time()); ?>
                            <tr class="<?php echo $isOverdue ? 'table-danger' : ''; ?>">
                                <td class="fw-semibold"><?php echo sanitize($row['book_title']); ?></td>
                                <td><?php echo sanitize($row['author']); ?></td>
                                <td><?php echo $row['issue_date'] ? date('M d, Y', strtotime($row['issue_date'])) : '—'; ?></td>
                                <td>
                                    <?php if ($row['due_date']): ?>
                                        <?php echo date('M d, Y', strtotime($row['due_date'])); ?>
                                        <?php if ($isOverdue): ?>
                                            <span class="badge bg-danger ms-1">OVERDUE</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Pending approval</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $bc = $row['status'] === 'Issued' ? 'warning' : 'info';
                                    ?>
                                    <span class="badge bg-<?php echo $bc; ?>"><?php echo sanitize($row['status']); ?></span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                No active books. <a href="<?php echo baseUrl(); ?>/user/view_books.php">Browse the catalogue</a>.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- History -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="fw-bold mb-0">
            <i class="bi bi-clock-history me-2 text-secondary"></i>Issue History
            <span class="badge bg-secondary ms-2"><?php echo $history->num_rows; ?></span>
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Book</th>
                        <th>Author</th>
                        <th>Issue Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($history->num_rows > 0): ?>
                        <?php while ($row = $history->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-semibold"><?php echo sanitize($row['book_title']); ?></td>
                                <td><?php echo sanitize($row['author']); ?></td>
                                <td><?php echo $row['issue_date'] ? date('M d, Y', strtotime($row['issue_date'])) : '—'; ?></td>
                                <td><?php echo $row['return_date'] ? date('M d, Y', strtotime($row['return_date'])) : '—'; ?></td>
                                <td>
                                    <?php
                                    $bc = $row['status'] === 'Returned' ? 'success' : 'danger';
                                    ?>
                                    <span class="badge bg-<?php echo $bc; ?>"><?php echo sanitize($row['status']); ?></span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No history yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
