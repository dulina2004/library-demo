<?php
/**
 * Issue Books (Librarian)
 * 
 * Handles book issue requests:
 * - View pending requests → Approve or Reject
 * - View currently issued books → Mark as Returned
 * - Directly issue a book to a student
 * 
 * Status Flow: Requested → Issued → Returned
 *              Requested → Rejected
 */

$allowed_roles = ['Librarian', 'Admin'];
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

// ====== HANDLE ACTIONS ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid form submission.');
        redirect(baseUrl() . '/librarian/issue_books.php');
    }
    
    $action = $_POST['action'] ?? '';
    
    // APPROVE a request
    if ($action === 'approve') {
        $issueId = (int) ($_POST['issue_id'] ?? 0);
        $dueDate = $_POST['due_date'] ?? date('Y-m-d', strtotime('+14 days'));
        
        // Check book availability
        $stmt = $conn->prepare("
            SELECT ib.book_id, b.qty_available 
            FROM issued_books ib 
            JOIN books b ON ib.book_id = b.id 
            WHERE ib.id = ? AND ib.status = 'Requested'
        ");
        $stmt->bind_param("i", $issueId);
        $stmt->execute();
        $issue = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($issue && $issue['qty_available'] > 0) {
            $issuedBy = $_SESSION['user_id'];
            $issueDate = date('Y-m-d');
            
            // Update issue record
            $stmt = $conn->prepare("UPDATE issued_books SET status = 'Issued', issued_by = ?, issue_date = ?, due_date = ? WHERE id = ?");
            $stmt->bind_param("issi", $issuedBy, $issueDate, $dueDate, $issueId);
            $stmt->execute();
            $stmt->close();
            
            // Decrease available quantity
            $stmt = $conn->prepare("UPDATE books SET qty_available = qty_available - 1 WHERE id = ?");
            $stmt->bind_param("i", $issue['book_id']);
            $stmt->execute();
            $stmt->close();
            
            flash('success', 'Book issued successfully.');
        } else {
            flash('error', 'Book is not available or request not found.');
        }
    }
    
    // REJECT a request
    elseif ($action === 'reject') {
        $issueId = (int) ($_POST['issue_id'] ?? 0);
        $stmt = $conn->prepare("UPDATE issued_books SET status = 'Rejected' WHERE id = ? AND status = 'Requested'");
        $stmt->bind_param("i", $issueId);
        $stmt->execute();
        $stmt->close();
        flash('success', 'Request rejected.');
    }
    
    // RETURN a book
    elseif ($action === 'return') {
        $issueId = (int) ($_POST['issue_id'] ?? 0);
        
        // Get book_id for this issue
        $stmt = $conn->prepare("SELECT book_id FROM issued_books WHERE id = ? AND status = 'Issued'");
        $stmt->bind_param("i", $issueId);
        $stmt->execute();
        $issue = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($issue) {
            $returnDate = date('Y-m-d');
            
            // Update issue status
            $stmt = $conn->prepare("UPDATE issued_books SET status = 'Returned', return_date = ? WHERE id = ?");
            $stmt->bind_param("si", $returnDate, $issueId);
            $stmt->execute();
            $stmt->close();
            
            // Increase available quantity
            $stmt = $conn->prepare("UPDATE books SET qty_available = qty_available + 1 WHERE id = ?");
            $stmt->bind_param("i", $issue['book_id']);
            $stmt->execute();
            $stmt->close();
            
            flash('success', 'Book marked as returned.');
        } else {
            flash('error', 'Issue record not found.');
        }
    }
    
    // DIRECT ISSUE — librarian issues a book directly to a student
    elseif ($action === 'direct_issue') {
        $bookId = (int) ($_POST['book_id'] ?? 0);
        $userId = (int) ($_POST['user_id'] ?? 0);
        $dueDate = $_POST['due_date'] ?? date('Y-m-d', strtotime('+14 days'));
        
        // Check availability
        $stmt = $conn->prepare("SELECT qty_available FROM books WHERE id = ?");
        $stmt->bind_param("i", $bookId);
        $stmt->execute();
        $book = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($book && $book['qty_available'] > 0) {
            $issuedBy = $_SESSION['user_id'];
            $issueDate = date('Y-m-d');
            
            $stmt = $conn->prepare("INSERT INTO issued_books (book_id, user_id, issued_by, issue_date, due_date, status) VALUES (?, ?, ?, ?, ?, 'Issued')");
            $stmt->bind_param("iiiss", $bookId, $userId, $issuedBy, $issueDate, $dueDate);
            $stmt->execute();
            $stmt->close();
            
            $stmt = $conn->prepare("UPDATE books SET qty_available = qty_available - 1 WHERE id = ?");
            $stmt->bind_param("i", $bookId);
            $stmt->execute();
            $stmt->close();
            
            flash('success', 'Book issued directly to student.');
        } else {
            flash('error', 'Book not available.');
        }
    }
    
    redirect(baseUrl() . '/librarian/issue_books.php');
}

// ====== FETCH DATA ======

// Pending requests
$pendingRequests = $conn->query("
    SELECT ib.*, b.title as book_title, b.qty_available, u.name as user_name, u.email as user_email
    FROM issued_books ib
    JOIN books b ON ib.book_id = b.id
    JOIN users u ON ib.user_id = u.id
    WHERE ib.status = 'Requested'
    ORDER BY ib.created_at ASC
");

// Currently issued books
$issuedBooks = $conn->query("
    SELECT ib.*, b.title as book_title, u.name as user_name, u.email as user_email
    FROM issued_books ib
    JOIN books b ON ib.book_id = b.id
    JOIN users u ON ib.user_id = u.id
    WHERE ib.status = 'Issued'
    ORDER BY ib.due_date ASC
");

// Available books for direct issue
$availableBooks = $conn->query("SELECT id, title, qty_available FROM books WHERE qty_available > 0 ORDER BY title");

// Students for direct issue
$students = $conn->query("SELECT id, name, email FROM users WHERE role = 'Student' ORDER BY name");

$pageTitle = 'Issue Books';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1"><i class="bi bi-arrow-left-right me-2"></i>Issue Books</h2>
        <p class="text-muted mb-0">Approve requests, issue books, and process returns</p>
    </div>
    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#directIssueModal">
        <i class="bi bi-plus-circle me-1"></i>Direct Issue
    </button>
</div>

<!-- Pending Requests -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="fw-bold mb-0">
            <i class="bi bi-hourglass-split me-2 text-info"></i>Pending Requests
            <?php if ($pendingRequests->num_rows > 0): ?>
                <span class="badge bg-info ms-2"><?php echo $pendingRequests->num_rows; ?></span>
            <?php endif; ?>
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Book</th>
                        <th>Student</th>
                        <th>Requested On</th>
                        <th>Available</th>
                        <th>Due Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pendingRequests->num_rows > 0): ?>
                        <?php while ($req = $pendingRequests->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-semibold"><?php echo sanitize($req['book_title']); ?></td>
                                <td>
                                    <?php echo sanitize($req['user_name']); ?>
                                    <br><small class="text-muted"><?php echo sanitize($req['user_email']); ?></small>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($req['created_at'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $req['qty_available'] > 0 ? 'success' : 'danger'; ?>">
                                        <?php echo $req['qty_available']; ?> copies
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="issue_id" value="<?php echo $req['id']; ?>">
                                        <input type="date" name="due_date" class="form-control form-control-sm" 
                                               value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>" 
                                               min="<?php echo date('Y-m-d'); ?>" style="width: 150px;">
                                </td>
                                <td>
                                        <button type="submit" class="btn btn-sm btn-success me-1" 
                                                <?php echo $req['qty_available'] <= 0 ? 'disabled' : ''; ?>>
                                            <i class="bi bi-check-lg me-1"></i>Approve
                                        </button>
                                    </form>
                                    <form method="POST" action="" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="issue_id" value="<?php echo $req['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-x-lg me-1"></i>Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No pending requests.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Currently Issued Books -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="fw-bold mb-0">
            <i class="bi bi-arrow-repeat me-2 text-warning"></i>Currently Issued
            <span class="badge bg-warning text-dark ms-2"><?php echo $issuedBooks->num_rows; ?></span>
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Book</th>
                        <th>Student</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($issuedBooks->num_rows > 0): ?>
                        <?php while ($issue = $issuedBooks->fetch_assoc()): ?>
                            <?php $isOverdue = strtotime($issue['due_date']) < time(); ?>
                            <tr class="<?php echo $isOverdue ? 'table-danger' : ''; ?>">
                                <td class="fw-semibold"><?php echo sanitize($issue['book_title']); ?></td>
                                <td>
                                    <?php echo sanitize($issue['user_name']); ?>
                                    <br><small class="text-muted"><?php echo sanitize($issue['user_email']); ?></small>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($issue['issue_date'])); ?></td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($issue['due_date'])); ?>
                                    <?php if ($isOverdue): ?>
                                        <br><span class="badge bg-danger">OVERDUE</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-warning">Issued</span></td>
                                <td>
                                    <form method="POST" action="">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="return">
                                        <input type="hidden" name="issue_id" value="<?php echo $issue['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="bi bi-check2-circle me-1"></i>Return
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No books currently issued.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- DIRECT ISSUE MODAL -->
<div class="modal fade" id="directIssueModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="direct_issue">
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Direct Issue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Book</label>
                        <select name="book_id" class="form-select" required>
                            <option value="">Choose a book...</option>
                            <?php while ($b = $availableBooks->fetch_assoc()): ?>
                                <option value="<?php echo $b['id']; ?>">
                                    <?php echo sanitize($b['title']); ?> (<?php echo $b['qty_available']; ?> available)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select Student</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">Choose a student...</option>
                            <?php while ($s = $students->fetch_assoc()): ?>
                                <option value="<?php echo $s['id']; ?>">
                                    <?php echo sanitize($s['name']); ?> (<?php echo sanitize($s['email']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control" 
                               value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>" 
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="bi bi-check-lg me-1"></i>Issue Book
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
