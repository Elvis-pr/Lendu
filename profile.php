<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require 'db.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

$userId = $_SESSION['user_id'];
$message = '';

// Fetch user data
$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['update_info'])) {
        // Update profile info
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($name === '' || $email === '') {
            $message = ['type' => 'error', 'text' => 'Name and email cannot be empty.'];
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = ['type' => 'error', 'text' => 'Invalid email address.'];
        } else {
            $updateStmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $updateStmt->execute([$name, $email, $userId]);
            $_SESSION['name'] = $name;
            $message = ['type' => 'success', 'text' => 'Profile updated successfully.'];
            $user['name'] = $name;
            $user['email'] = $email;
        }
    }

    if (isset($_POST['change_password'])) {
        // Change password
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $message = ['type' => 'error', 'text' => 'Please fill all password fields.'];
        } elseif ($newPassword !== $confirmPassword) {
            $message = ['type' => 'error', 'text' => 'New passwords do not match.'];
        } elseif (strlen($newPassword) < 6) {
            $message = ['type' => 'error', 'text' => 'New password must be at least 6 characters.'];
        } else {
            $passStmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $passStmt->execute([$userId]);
            $row = $passStmt->fetch();

            if (!$row || !password_verify($currentPassword, $row['password'])) {
                $message = ['type' => 'error', 'text' => 'Current password incorrect.'];
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updatePass = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updatePass->execute([$hashedPassword, $userId]);
                $message = ['type' => 'success', 'text' => 'Password changed successfully.'];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Profile - Lendu</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet" />
<style>
  body {
    font-family: 'Montserrat', sans-serif;
    background: #f0f4ff;
    color: #2c3e50;
    margin: 0; padding: 0;
  }
  main {
    max-width: 600px;
    margin: 40px auto;
    background: white;
    padding: 30px 40px;
    border-radius: 16px;
    box-shadow: 0 8px 20px rgb(0 0 0 / 0.08);
  }
  h1 {
    margin-bottom: 24px;
    font-weight: 600;
    color: #274bae;
  }
  form {
    margin-bottom: 40px;
  }
  label {
    display: block;
    margin: 12px 0 6px;
    font-weight: 600;
  }
  input[type="text"],
  input[type="email"],
  input[type="password"] {
    width: 100%;
    padding: 12px;
    font-size: 15px;
    border-radius: 10px;
    border: 1px solid #ccc;
  }
  button {
    margin-top: 16px;
    background-color: #407dff;
    color: white;
    border: none;
    padding: 14px 28px;
    font-weight: 600;
    font-size: 16px;
    border-radius: 12px;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }
  button:hover {
    background-color: #305de0;
  }
  .message {
    margin-bottom: 20px;
    padding: 14px 20px;
    border-radius: 12px;
    font-weight: 600;
  }
  .message.success {
    background-color: #d4edda;
    color: #155724;
    border-left: 5px solid #155724;
  }
  .message.error {
    background-color: #f8d7da;
    color: #721c24;
    border-left: 5px solid #721c24;
  }
  nav {
    text-align: center;
    margin-top: 20px;
  }
  nav a {
    color: #407dff;
    font-weight: 600;
    text-decoration: none;
    border: 2px solid #407dff;
    padding: 8px 18px;
    border-radius: 12px;
    transition: background-color 0.3s ease, color 0.3s ease;
  }
  nav a:hover {
    background-color: #407dff;
    color: white;
  }
</style>
</head>
<body>
<main>
  <h1>Profile Settings</h1>

  <?php if ($message): ?>
    <div class="message <?php echo $message['type'] === 'success' ? 'success' : 'error'; ?>">
      <?php echo htmlspecialchars($message['text']); ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="">
    <h2>Update Information</h2>
    <label for="name">Full Name</label>
    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required />

    <label for="email">Email Address</label>
    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required />

    <button type="submit" name="update_info">Save Changes</button>
  </form>

  <form method="POST" action="">
    <h2>Change Password</h2>
    <label for="current_password">Current Password</label>
    <input type="password" id="current_password" name="current_password" required />

    <label for="new_password">New Password</label>
    <input type="password" id="new_password" name="new_password" required />

    <label for="confirm_password">Confirm New Password</label>
    <input type="password" id="confirm_password" name="confirm_password" required />

    <button type="submit" name="change_password">Change Password</button>
  </form>

  <nav>
    <a href="borrower_dashboard.php">← Back to Dashboard</a>
    <a href="logout.php">Logout</a>
  </nav>
</main>
</body>
</html>
