<?php
// db.php - database connection file

$host = 'localhost';
$db   = 'lendu';      // your database name
$user = 'root';       // your database user
$pass = '';           // your database password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // fetch assoc arrays by default
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Show error and exit if DB connection fails
    echo "Database connection failed: " . htmlspecialchars($e->getMessage());
    exit;
}
?>
