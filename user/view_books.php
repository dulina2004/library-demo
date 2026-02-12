<?php
/**
 * View Books (Student)
 * 
 * Browse available books with search and filter.
 * Students can request to issue a book from this page.
 * 
 * CRUD: READ (browse catalogue) + CREATE (request issue)
 */

$allowed_roles = ['Student'];
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$userId = $_SESSION['user_id'];

// ====== HANDLE BOOK REQUEST ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid form submission.');
        redirect(baseUrl() . '/user/view_books.php');
    }
    
    $bookId = (int) ($_POST['book_id'] ?? 0);
    
    // Check if book is available
    $stmt = $conn->prepare("SELECT qty_available FROM books WHERE id = ?");
    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    $book = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$book || $book['qty_available'] <= 0) {
        flash('error', 'This book is currently not available.');
        redirect(baseUrl() . '/user/view_books.php');
    }
    
    // Check if student already has an active request/issue for this book
    $stmt = $conn->prepare("SELECT id FROM issued_books WHERE book_id = ? AND user_id = ? AND status IN ('Requested', 'Issued')");
    $stmt->bind_param("ii", $bookId, $userId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        flash('warning', 'You already have an active request or issue for this book.');
        $stmt->close();
        redirect(baseUrl() . '/user/view_books.php');
    }
    $stmt->close();
    
    // Create issue request
    $stmt = $conn->prepare("INSERT INTO issued_books (book_id, user_id, status) VALUES (?, ?, 'Requested')");
    $stmt->bind_param("ii", $bookId, $userId);
    
    if ($stmt->execute()) {
        flash('success', 'Book request submitted! A librarian will review it shortly.');
    } else {
        flash('error', 'Failed to submit request.');
    }
    $stmt->close();
    redirect(baseUrl() . '/user/view_books.php');
}

// ====== FETCH BOOKS ======
$search = trim($_GET['search'] ?? '');
$catFilter = $_GET['category'] ?? '';

$query = "
    SELECT b.*, c.name as category_name 
    FROM books b 
    LEFT JOIN categories c ON b.category_id = c.id 
    WHERE 1=1
";
$params = [];
$types = '';

if (!empty($search)) {
    $searchTerm = "%$search%";
    $query .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'sss';
}

if (!empty($catFilter) && is_numeric($catFilter)) {
    $query .= " AND b.category_id = ?";
    $params[] = (int) $catFilter;
    $types .= 'i';
}

$query .= " ORDER BY b.title ASC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$books = $stmt->get_result();

// Get user's active requests/issues to disable buttons
$stmtActive = $conn->prepare("SELECT book_id FROM issued_books WHERE user_id = ? AND status IN ('Requested', 'Issued')");
$stmtActive->bind_param("i", $userId);
$stmtActive->execute();
$activeResult = $stmtActive->get_result();
$activeBookIds = [];
while ($row = $activeResult->fetch_assoc()) {
    $activeBookIds[] = $row['book_id'];
}
$stmtActive->close();

$categories = $conn->query("SELECT * FROM categories ORDER BY name");

$pageTitle = 'Browse Books';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1"><i class="bi bi-search me-2"></i>Browse Books</h2>
        <p class="text-muted mb-0">Search and request books from the library</p>
    </div>
</div>

<!-- Search & Filter -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" 
                       placeholder="Search by title, author, or ISBN..."
                       value="<?php echo sanitize($search); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Category</label>
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $catFilter == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo sanitize($cat['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary-custom me-2">
                    <i class="bi bi-search me-1"></i>Search
                </button>
                <a href="<?php echo baseUrl(); ?>/user/view_books.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Books Grid -->
<div class="row g-4">
    <?php if ($books->num_rows > 0): ?>
        <?php while ($book = $books->fetch_assoc()): ?>
            <?php $hasActive = in_array($book['id'], $activeBookIds); ?>
            <div class="col-lg-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 book-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title fw-bold mb-0"><?php echo sanitize($book['title']); ?></h5>
                            <?php if ($book['qty_available'] > 0): ?>
                                <span class="badge bg-success">Available</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Unavailable</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted mb-1"><i class="bi bi-person me-1"></i><?php echo sanitize($book['author']); ?></p>
                        <?php if ($book['category_name']): ?>
                            <p class="mb-1"><span class="badge bg-light text-dark"><i class="bi bi-tag me-1"></i><?php echo sanitize($book['category_name']); ?></span></p>
                        <?php endif; ?>
                        <?php if ($book['isbn']): ?>
                            <p class="small text-muted mb-2">ISBN: <?php echo sanitize($book['isbn']); ?></p>
                        <?php endif; ?>
                        <p class="small text-muted mb-3">
                            <i class="bi bi-stack me-1"></i><?php echo $book['qty_available']; ?> of <?php echo $book['qty_total']; ?> copies available
                        </p>
                        
                        <?php if ($hasActive): ?>
                            <button class="btn btn-secondary btn-sm w-100" disabled>
                                <i class="bi bi-check me-1"></i>Already Requested
                            </button>
                        <?php elseif ($book['qty_available'] > 0): ?>
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                <input type="hidden" name="action" value="request">
                                <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                <button type="submit" class="btn btn-primary-custom btn-sm w-100">
                                    <i class="bi bi-bookmark-plus me-1"></i>Request Issue
                                </button>
                            </form>
                        <?php else: ?>
                            <button class="btn btn-outline-secondary btn-sm w-100" disabled>
                                <i class="bi bi-x-circle me-1"></i>Not Available
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5 text-muted">
                    <i class="bi bi-journal-x fs-1 d-block mb-2"></i>
                    <p>No books found matching your search.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php 
$stmt->close();
require_once __DIR__ . '/../includes/footer.php'; 
?>
