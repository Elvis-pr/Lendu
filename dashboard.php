<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'lendu');
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Get user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT fullname, email, university, student_id FROM users WHERE id = $user_id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    // User not found - destroy session
    session_destroy();
    header("Location: login.html");
    exit();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - LendU</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            background: #f5faff;
            color: #222;
        }
        header {
            background: linear-gradient(90deg, #00274d, #007acc);
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }
        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            user-select: none;
        }
        nav {
            display: flex;
            gap: 1rem;
        }
        nav a {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
        }
        .dashboard-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .user-info {
            margin-bottom: 2rem;
        }
        .user-info h2 {
            color: #00274d;
            margin-bottom: 1rem;
        }
        .info-card {
            background: #e6f0fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .info-row {
            display: flex;
            margin-bottom: 0.5rem;
        }
        .info-label {
            font-weight: 600;
            width: 120px;
            color: #007acc;
        }
        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        footer {
            text-align: center;
            padding: 1rem;
            background: #00274d;
            color: #fff;
            position: fixed;
            bottom: 0;
            width: 100%;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">Lend<span style="color:#00d4ff;">U</span></div>
        <nav>
            <form action="logout.php" method="post">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </nav>
    </header>

    <div class="dashboard-container">
        <div class="user-info">
            <h2>Welcome, <?php echo htmlspecialchars($user['fullname']); ?>!</h2>
            <div class="info-card">
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">University:</span>
                    <span><?php echo htmlspecialchars($user['university']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Student ID:</span>
                    <span><?php echo htmlspecialchars($user['student_id']); ?></span>
                </div>
            </div>
        </div>

        <!-- Add your dashboard content here -->
        <div class="dashboard-content">
            <h3>Your Account Overview</h3>
            <p>This is your personal dashboard. More features coming soon!</p>
        </div>
    </div>

    <footer>
        &copy; 2025 LendU. All rights reserved.
    </footer>
</body>
</html>