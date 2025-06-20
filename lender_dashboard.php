<?php
session_start();

// Redirect if not logged in or not a lender
if (!isset($_SESSION['user_name']) || $_SESSION['role'] !== 'lender') {
    header('Location: login.html');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Lender Dashboard - Lendu</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      margin: 0;
      font-family: 'Montserrat', sans-serif;
      background: #f5f7fa;
      color: #2c3e50;
    }

    header {
      background: #407dff;
      padding: 20px;
      color: white;
      text-align: center;
    }

    .container {
      max-width: 1000px;
      margin: 30px auto;
      background: white;
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
    }

    h2 {
      color: #2c3e50;
      margin-bottom: 30px;
    }

    .nav-links {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .nav-links a {
      display: block;
      text-decoration: none;
      padding: 15px 20px;
      background: #407dff;
      color: white;
      border-radius: 12px;
      font-weight: 600;
      text-align: center;
      transition: background 0.3s ease;
    }

    .nav-links a:hover {
      background: #2c6bed;
    }

    footer {
      margin-top: 40px;
      text-align: center;
      font-size: 14px;
      color: #888;
    }
  </style>
</head>
<body>

<header>
  <h1>Lender Dashboard</h1>
  <p>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
</header>

<div class="container">
  <h2>What would you like to do?</h2>

  <div class="nav-links">
    <a href="view_borrow_requests.php">📄 View Borrow Requests</a>
    <a href="profile.php">👤 Update Profile</a>
    <a href="logout.php">🚪 Logout</a>
  </div>

  <footer>
    &copy; <?php echo date("Y"); ?> Lendu Platform — Peer-to-Peer Student Lending
  </footer>
</div>

</body>
</html>
