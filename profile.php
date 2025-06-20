<?php
session_start();
if (!isset($_SESSION['user_name'])) {
    header('Location: login.html');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Update Profile</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(to right, #e6f0ff, #ffffff);
            margin: 0;
            padding: 0;
            text-align: center;
        }

        .container {
            max-width: 600px;
            margin: 100px auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #004080;
            margin-bottom: 10px;
        }

        p {
            font-size: 16px;
            color: #444;
            margin-bottom: 30px;
        }

        a {
            text-decoration: none;
            color: #ffffff;
            background-color: #007bff;
            padding: 10px 20px;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }

        a:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Update Profile</h2>
        <p>Profile update functionality coming soon.</p>
        <a href="lender_dashboard.php">← Back to Dashboard</a>
    </div>
</body>
</html>
