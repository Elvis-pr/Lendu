<?php
require 'db.php';  // make sure this sets up $pdo correctly

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $name       = trim($_POST['name'] ?? '');
  $email      = trim($_POST['email'] ?? '');
  $university = trim($_POST['university'] ?? '');
  $role       = trim($_POST['role'] ?? '');
  $password   = $_POST['password'] ?? '';
  $confirm    = $_POST['confirm_password'] ?? '';

  // Simple validation
  if (empty($name) || empty($email) || empty($university) || empty($role) || empty($password) || empty($confirm)) {
    echo "❌ All fields are required.";
    exit;
  }

  if ($password !== $confirm) {
    echo "❌ Passwords do not match.";
    exit;
  }

  // Validate email format
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "❌ Invalid email address.";
    exit;
  }

  // Secure hash
  $hashed_password = password_hash($password, PASSWORD_DEFAULT);

  try {
    $stmt = $pdo->prepare("INSERT INTO users (name, email, university, role, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $email, $university, $role, $hashed_password]);

    // Return success message for AJAX
    echo "✅ Registration successful! Redirecting to login...";
    exit;

  } catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Duplicate entry (email)
      echo "❌ Email already exists.";
    } else {
      echo "❌ Error: " . htmlspecialchars($e->getMessage());
    }
    exit;
  }
} else {
  echo "❌ Invalid request.";
  exit;
}
?>
