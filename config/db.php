<?php
/**
 * Database Configuration
 * 
 * This file establishes the MySQL database connection using MySQLi.
 * It is included in every page that needs database access.
 * 
 * CRUD Explanation:
 * - We use MySQLi (MySQL Improved) extension for database operations
 * - All queries use PREPARED STATEMENTS to prevent SQL injection
 * - Connection uses utf8mb4 charset for full Unicode support
 */

// =====================================================
// Database Credentials
// Change these values to match your environment
// =====================================================
define('DB_HOST', 'localhost');       // Database server (localhost for XAMPP)
define('DB_USER', 'root');           // MySQL username (default: root for XAMPP)
define('DB_PASS', '1234');           // MySQL password
define('DB_NAME', 'library_management'); // Database name

// =====================================================
// Create Connection
// =====================================================
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check if connection was successful
if ($conn->connect_error) {
    // In production, log the error instead of displaying it
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to utf8mb4 for full Unicode support
$conn->set_charset("utf8mb4");

/**
 * NOTE: This connection ($conn) is available in any file that includes this config.
 * 
 * Example usage with prepared statements:
 * 
 *   $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
 *   $stmt->bind_param("s", $email);    // "s" = string type
 *   $stmt->execute();
 *   $result = $stmt->get_result();
 *   $user = $result->fetch_assoc();
 *   $stmt->close();
 */
?>
