<?php
/**
 * Manage Books (Librarian)
 * 
 * Full CRUD for book management.
 * Accessible by Librarian and Admin roles.
 * 
 * CRUD Explanation:
 * - CREATE: Add a new book to the catalogue
 * - READ:   List all books with search/filter
 * - UPDATE: Edit book details (title, author, ISBN, etc.)
 * - DELETE: Remove a book from the system
 */

$allowed_roles = ['Librarian', 'Admin'];
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

// ====== HANDLE DELETE ======
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    
    // Check if book has active issues
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM issued_books WHERE book_id = ? AND status IN ('Requested', 'Issued')");
    $stmt->bind_param("i", $deleteId);
    $stmt->execute();
    $activeIssues = $stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();
    
    if ($activeIssues > 0) {
        flash('error', 'Cannot delete this book — it has active issues or pending requests.');
    } else {
        $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
        $stmt->bind_param("i", $deleteId);
        if ($stmt->execute()) {
            flash('success', 'Book deleted successfully.');
        } else {
            flash('error', 'Failed to delete book.');
        }
        $stmt->close();
    }
    redirect(baseUrl() . '/librarian/manage_books.php');
}

// ====== HANDLE ADD / EDIT ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid form submission.');
        redirect(baseUrl() . '/librarian/manage_books.php');
    }
    
    $action = $_POST['action'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $publisher = trim($_POST['publisher'] ?? '');
    $category_id = !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null;
    $qty_total = max(1, (int) ($_POST['qty_total'] ?? 1));
    
    // Validate
    $errors = [];
    if (empty($title)) $errors[] = 'Title is required.';
    if (empty($author)) $errors[] = 'Author is required.';
    
    if (!empty($errors)) {
        flash('error', implode(' ', $errors));
        redirect(baseUrl() . '/librarian/manage_books.php');
    }
    
    if ($action === 'add') {
        // CREATE
        $qty_available = $qty_total;
        $added_by = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("INSERT INTO books (title, author, isbn, publisher, category_id, qty_total, qty_available, added_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssiiii", $title, $author, $isbn, $publisher, $category_id, $qty_total, $qty_available, $added_by);
        
        if ($stmt->execute()) {
            flash('success', 'Book added successfully.');
        } else {
            flash('error', 'Failed to add book. ISBN might already exist.');
        }
        $stmt->close();
        
    } elseif ($action === 'edit') {
        // UPDATE
        $bookId = (int) ($_POST['book_id'] ?? 0);
        
        // Get current qty_total to calculate new qty_available
        $stmt = $conn->prepare("SELECT qty_total, qty_available FROM books WHERE id = ?");
        $stmt->bind_param("i", $bookId);
        $stmt->execute();
        $current = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($current) {
            $issued_count = $current['qty_total'] - $current['qty_available'];
            $new_available = max(0, $qty_total - $issued_count);
            
            $stmt = $conn->prepare("UPDATE books SET title = ?, author = ?, isbn = ?, publisher = ?, category_id = ?, qty_total = ?, qty_available = ? WHERE id = ?");
            $stmt->bind_param("ssssiiii", $title, $author, $isbn, $publisher, $category_id, $qty_total, $new_available, $bookId);
            
            if ($stmt->execute()) {
                flash('success', 'Book updated successfully.');
            } else {
                flash('error', 'Failed to update book.');
            }
            $stmt->close();
        }
    }
    
    redirect(baseUrl() . '/librarian/manage_books.php');
}

// ====== READ: Fetch all books with category names ======
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

$query .= " ORDER BY b.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$books = $stmt->get_result();

// Fetch categories for dropdown
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

$pageTitle = 'Manage Books';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1"><i class="bi bi-journal-bookmark me-2"></i>Manage Books</h2>
        <p class="text-muted mb-0">Add, edit, search, or remove books from the catalogue</p>
    </div>
    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addBookModal">
        <i class="bi bi-plus-circle me-1"></i>Add New Book
    </button>
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
                    <?php 
                    $categories->data_seek(0);
                    while ($cat = $categories->fetch_assoc()): 
                    ?>
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
                <a href="<?php echo baseUrl(); ?>/librarian/manage_books.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Books Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="fw-bold mb-0">
            Book Catalogue
            <span class="badge bg-secondary ms-2"><?php echo $books->num_rows; ?> books</span>
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>ISBN</th>
                        <th>Category</th>
                        <th>Total</th>
                        <th>Available</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($books->num_rows > 0): ?>
                        <?php while ($book = $books->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $book['id']; ?></td>
                                <td class="fw-semibold"><?php echo sanitize($book['title']); ?></td>
                                <td><?php echo sanitize($book['author']); ?></td>
                                <td><small class="text-muted"><?php echo sanitize($book['isbn'] ?? '—'); ?></small></td>
                                <td>
                                    <?php if ($book['category_name']): ?>
                                        <span class="badge bg-light text-dark"><?php echo sanitize($book['category_name']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $book['qty_total']; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $book['qty_available'] > 0 ? 'success' : 'danger'; ?>">
                                        <?php echo $book['qty_available']; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-1" 
                                            data-bs-toggle="modal" data-bs-target="#editBookModal"
                                            data-id="<?php echo $book['id']; ?>"
                                            data-title="<?php echo sanitize($book['title']); ?>"
                                            data-author="<?php echo sanitize($book['author']); ?>"
                                            data-isbn="<?php echo sanitize($book['isbn'] ?? ''); ?>"
                                            data-publisher="<?php echo sanitize($book['publisher'] ?? ''); ?>"
                                            data-category="<?php echo $book['category_id'] ?? ''; ?>"
                                            data-qty="<?php echo $book['qty_total']; ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="?delete=<?php echo $book['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger delete-confirm"
                                       data-name="<?php echo sanitize($book['title']); ?>">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-journal-x fs-1 d-block mb-2"></i>No books found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ADD BOOK MODAL -->
<div class="modal fade" id="addBookModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="add">
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add New Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Author <span class="text-danger">*</span></label>
                        <input type="text" name="author" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ISBN</label>
                            <input type="text" name="isbn" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Publisher</label>
                            <input type="text" name="publisher" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select">
                                <option value="">Select Category</option>
                                <?php 
                                $categories->data_seek(0);
                                while ($cat = $categories->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo sanitize($cat['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="qty_total" class="form-control" value="1" min="1" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="bi bi-plus-circle me-1"></i>Add Book
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT BOOK MODAL -->
<div class="modal fade" id="editBookModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="book_id" id="editBookId">
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="editBookTitle" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Author <span class="text-danger">*</span></label>
                        <input type="text" name="author" id="editBookAuthor" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ISBN</label>
                            <input type="text" name="isbn" id="editBookIsbn" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Publisher</label>
                            <input type="text" name="publisher" id="editBookPublisher" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" id="editBookCategory" class="form-select">
                                <option value="">Select Category</option>
                                <?php 
                                $categories->data_seek(0);
                                while ($cat = $categories->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo sanitize($cat['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Total Quantity</label>
                            <input type="number" name="qty_total" id="editBookQty" class="form-control" min="1" required>
                        </div>
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

<!-- Populate Edit Modal -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editBookModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const btn = event.relatedTarget;
            document.getElementById('editBookId').value = btn.dataset.id;
            document.getElementById('editBookTitle').value = btn.dataset.title;
            document.getElementById('editBookAuthor').value = btn.dataset.author;
            document.getElementById('editBookIsbn').value = btn.dataset.isbn;
            document.getElementById('editBookPublisher').value = btn.dataset.publisher;
            document.getElementById('editBookCategory').value = btn.dataset.category;
            document.getElementById('editBookQty').value = btn.dataset.qty;
        });
    }
});
</script>

<?php 
$stmt->close();
require_once __DIR__ . '/../includes/footer.php'; 
?>
