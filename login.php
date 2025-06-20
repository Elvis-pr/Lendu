<?php 
session_start();

// Database connection
$host = 'localhost';
$db   = 'lendu';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
  $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
  die("Database connection failed: " . $e->getMessage());
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $email = $_POST['email'];
  $password = $_POST['password'];

  $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
  $stmt->execute([$email]);
  $user = $stmt->fetch();

  if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['user_name'] = $user['full_name']; // ✅ Use correct column

    // ✅ Redirect based on role
    if ($user['role'] === 'lender') {
      header('Location: lender_dashboard.php');
    } elseif ($user['role'] === 'borrower') {
      header('Location: borrower_dashboard.php');
    } else {
      echo "Invalid role assigned to user.";
    }

    exit();
  } else {
    echo "<script>alert('Invalid email or password.'); window.location.href = 'login.html';</script>";
  }
}
?>
