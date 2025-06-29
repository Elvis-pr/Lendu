<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = trim($_POST['email']);
  $password = $_POST['password'];

  if (empty($email) || empty($password)) {
    $_SESSION['error'] = "❌ Email and password are required.";
    header("Location: login.html");
    exit();
  }

  $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
  $stmt->execute([$email]);
  $user = $stmt->fetch();

  if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = $user['role'];

    if ($user['role'] === 'admin') {
      header("Location: admin_dashboard.php");
    } elseif ($user['role'] === 'lender') {
      header("Location: lender_dashboard.php");
    } elseif ($user['role'] === 'borrower') {
      header("Location: borrower_dashboard.php");
    } else {
      $_SESSION['error'] = "❌ Unknown user role.";
      header("Location: login.html");
    }
    exit();
  } else {
    $_SESSION['error'] = "❌ Invalid email or password.";
    header("Location: login.html");
    exit();
  }
} else {
  $_SESSION['error'] = "❌ Invalid request.";
  header("Location: login.html");
  exit();
}
