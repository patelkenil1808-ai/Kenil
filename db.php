<?php
/**
 * db.php
 * Database Connection module representing PDO parameters.
 * Designed to be dropped easily into XAMPP/Apache or cPanel environments.
 */

// Define Database Configurations (Update these for your production cPanel / local XAMPP environment)
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'kenil_tech_logistics');
define('DB_USER', 'root');
define('DB_PASS', '');

// Optional: check environment variables if available
$db_host = getenv('DB_HOST') ?: DB_HOST;
$db_port = getenv('DB_PORT') ?: DB_PORT;
$db_name = getenv('DB_NAME') ?: DB_NAME;
$db_user = getenv('DB_USER') ?: DB_USER;
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : DB_PASS;

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // Return a secure error message instead of leaking passwords in default stack traces
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Database connection failed. Please ensure the Kenil Tech database (database.sql) is imported and database configurations in db.php are set correctly.',
        'details' => $e->getMessage()
    ]);
    exit();
}
