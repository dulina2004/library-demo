# 📚 BookFlow — Library Management System

A complete web-based Library Management System (**BookFlow**) built with **PHP**, **MySQL**, and **Bootstrap 5** for a university group assignment.

---

## 🛠️ Tech Stack

| Layer     | Technology                          |
|-----------|-------------------------------------|
| Frontend  | HTML5, CSS3, Bootstrap 5, Vanilla JS |
| Backend   | PHP 8+ (Procedural + MVC-like)      |
| Database  | MySQL 5.7+                          |
| Server    | Apache (XAMPP)                      |

---

## 👥 User Roles & Permissions

| Feature                | Admin | Librarian | Student |
|------------------------|:-----:|:---------:|:-------:|
| Dashboard              |  ✅   |    ✅     |   ✅    |
| Manage Users (CRUD)    |  ✅   |    ❌     |   ❌    |
| Manage Books (CRUD)    |  ✅   |    ✅     |   ❌    |
| Issue/Return Books     |  ✅   |    ✅     |   ❌    |
| View Reports           |  ✅   |    ❌     |   ❌    |
| Browse Books           |  ❌   |    ❌     |   ✅    |
| Request Book Issue     |  ❌   |    ❌     |   ✅    |
| View My Books          |  ❌   |    ❌     |   ✅    |
| Register Account       |  ❌   |    ❌     |   ✅    |

---

## ⚙️ Setup Instructions

### Prerequisites
- **XAMPP** installed (Apache + MySQL)
- **PHP 8.0+**
- A web browser

### Step 1: Clone / Copy the Project
Place this folder inside your XAMPP `htdocs` directory:
```
C:\xampp\htdocs\lib\
```

### Step 2: Start XAMPP
1. Open XAMPP Control Panel
2. Start **Apache** and **MySQL**

### Step 3: Create the Database
1. Open **phpMyAdmin**: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Click **Import** tab
3. Choose the file: `database/library_management.sql`
4. Click **Go** to import

Alternatively, you can:
1. Create a new database named `library_management`
2. Select it, then go to **Import** and load the SQL file

### Step 4: Configure Database Connection
Open `config/db.php` and verify the credentials match your XAMPP:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');           // Empty by default in XAMPP
define('DB_NAME', 'library_management');
```

### Step 5: Access the Application
Open your browser and navigate to:
```
http://localhost/lib/
```

---

## 🔐 Default Login Credentials

| Role      | Email                    | Password       |
|-----------|--------------------------|----------------|
| Admin     | admin@library.com        | Admin@123      |
| Librarian | librarian@library.com    | Librarian@123  |
| Student   | student@library.com      | Student@123    |

> ⚠️ **Change these passwords after first login for security!**

---

## 📁 Project Structure

```
/lib
├── /assets
│   ├── /css
│   │   └── style.css              # Custom styles
│   ├── /js
│   │   └── main.js                # Client-side JavaScript
│   └── /images                    # (placeholder for images)
│
├── /config
│   └── db.php                     # Database connection
│
├── /database
│   └── library_management.sql     # SQL schema + seed data
│
├── /auth
│   ├── login.php                  # Login form + session handling
│   ├── register.php               # Student registration
│   └── logout.php                 # Session destruction
│
├── /admin
│   ├── dashboard.php              # Admin statistics
│   ├── manage_users.php           # CRUD users
│   └── reports.php                # Transaction reports
│
├── /librarian
│   ├── dashboard.php              # Librarian overview
│   ├── manage_books.php           # CRUD books
│   └── issue_books.php            # Approve/return books
│
├── /user
│   ├── dashboard.php              # Student overview
│   ├── view_books.php             # Browse + search books
│   └── my_books.php               # Issued books + history
│
├── /includes
│   ├── header.php                 # HTML head + navbar
│   ├── footer.php                 # Footer + JS
│   ├── auth_check.php             # Session guard
│   └── functions.php              # Helper functions
│
├── index.php                      # Entry point / redirect
└── README.md                      # This file
```

---

## 🔄 How CRUD Works (Viva Explanation)

### What is CRUD?
CRUD stands for **Create, Read, Update, Delete** — the four basic database operations.

### Example: Managing Books (`librarian/manage_books.php`)

#### CREATE — Adding a new book
```php
// Prepared statement prevents SQL injection
$stmt = $conn->prepare("INSERT INTO books (title, author, isbn) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $title, $author, $isbn);  // "sss" = 3 string params
$stmt->execute();
```

#### READ — Fetching all books
```php
$stmt = $conn->prepare("SELECT * FROM books WHERE title LIKE ?");
$stmt->bind_param("s", $searchTerm);
$stmt->execute();
$result = $stmt->get_result();
while ($book = $result->fetch_assoc()) {
    echo $book['title'];
}
```

#### UPDATE — Editing a book
```php
$stmt = $conn->prepare("UPDATE books SET title = ?, author = ? WHERE id = ?");
$stmt->bind_param("ssi", $title, $author, $bookId);  // "ssi" = 2 strings + 1 integer
$stmt->execute();
```

#### DELETE — Removing a book
```php
$stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
$stmt->bind_param("i", $bookId);  // "i" = integer param
$stmt->execute();
```

### Why Prepared Statements?
- **Prevents SQL Injection**: User input is separated from the SQL query
- **Performance**: MySQL can optimize repeated queries
- **Type Safety**: Parameters are bound with types (s=string, i=integer, d=double)

---

## 🔒 Security Features

| Feature | Implementation |
|---------|---------------|
| SQL Injection Prevention | Prepared statements (MySQLi) |
| XSS Prevention | `htmlspecialchars()` on all output |
| Password Security | `password_hash()` + `password_verify()` |
| Session Security | `session_regenerate_id()` on login |
| CSRF Protection | Token-based form validation |
| Access Control | Role-based `auth_check.php` on every page |

---

## 🗄️ Database Schema

### users
| Column     | Type         | Description        |
|------------|--------------|--------------------|
| id         | INT (PK)     | Auto-increment ID  |
| name       | VARCHAR(100) | Full name          |
| email      | VARCHAR(150) | Unique email       |
| password   | VARCHAR(255) | Bcrypt hash        |
| role       | ENUM         | Admin/Librarian/Student |
| phone      | VARCHAR(20)  | Phone number       |
| created_at | TIMESTAMP    | Account creation   |

### books
| Column        | Type         | Description        |
|---------------|--------------|--------------------|
| id            | INT (PK)     | Auto-increment ID  |
| title         | VARCHAR(255) | Book title         |
| author        | VARCHAR(150) | Author name        |
| isbn          | VARCHAR(20)  | Unique ISBN        |
| category_id   | INT (FK)     | References categories |
| qty_total     | INT          | Total copies       |
| qty_available | INT          | Available copies   |

### categories
| Column      | Type         | Description        |
|-------------|--------------|--------------------|
| id          | INT (PK)     | Auto-increment ID  |
| name        | VARCHAR(100) | Category name      |
| description | TEXT         | Category description |

### issued_books
| Column      | Type     | Description              |
|-------------|----------|--------------------------|
| id          | INT (PK) | Auto-increment ID        |
| book_id     | INT (FK) | References books         |
| user_id     | INT (FK) | Student who borrowed     |
| issued_by   | INT (FK) | Librarian who approved   |
| issue_date  | DATE     | Date issued              |
| due_date    | DATE     | Return deadline          |
| return_date | DATE     | Actual return date       |
| status      | ENUM     | Requested/Issued/Returned/Rejected |

---

## 🎓 Tips for Viva & Presentation

1. **Explain the flow**: Registration → Login → Dashboard → Browse → Request → Issue → Return
2. **Know your SQL**: Be ready to explain any query in the code
3. **Security**: Emphasize prepared statements and password hashing
4. **MVC concept**: Config (Model layer), Includes (shared View), Page files (Controller + View)
5. **Session management**: Explain how `$_SESSION` tracks logged-in users
6. **Role-based access**: Show how `auth_check.php` restricts pages by role

---

## 📄 License

This project is created for educational purposes as a university group assignment.

---

## 👨‍💻 Team Members

- _Add your team members here_

---

*Built with ❤️ using PHP, MySQL, and Bootstrap 5*
