<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'borrower') {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'];

// DB connection settings — update with your credentials
$host = 'localhost';
$dbname = 'lendu_db';
$user = 'db_user';
$pass = 'db_password';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'] ?? '';
    $purpose = $_POST['purpose'] ?? '';

    // Basic validation
    if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
        $message = "Please enter a valid loan amount.";
    } elseif (empty(trim($purpose))) {
        $message = "Please provide a purpose for the loan.";
    } else {
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("INSERT INTO loans (user_id, amount, purpose) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $amount, $purpose]);

            // Redirect back to dashboard after success
            header("Location: borrower_dashboard.php");
            exit();
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Apply for a Loan | Lendu</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background: #f4f6f9;
            margin: 0; padding: 0;
        }
        header {
            background-color: #4CAF50;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        nav a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
        }
        .container {
            max-width: 500px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h2 {
            margin-bottom: 20px;
            font-weight: 600;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        input[type="number"], textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 16px;
            font-family: 'Montserrat', sans-serif;
            resize: vertical;
        }
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 14px 25px;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        button:hover {
            background-color: #45a049;
        }
        .message {
            margin-bottom: 15px;
            color: red;
            font-weight: 600;
        }
    </style>
</head>
<body>

<header>
    <h2>Lendu | Apply for a Loan</h2>
    <nav>
        <a href="borrower_dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="container">
    <h2>Hello, <?= htmlspecialchars($name) ?>! Submit Your Loan Application</h2>

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST" action="apply_loan.php">
        <label for="amount">Loan Amount (RWF):</label>
        <input type="number" step="0.01" min="1" id="amount" name="amount" required />

        <label for="purpose">Purpose of the Loan:</label>
        <textarea id="purpose" name="purpose" rows="4" required></textarea>

        <button type="submit">Submit Application</button>
    </form>
</div>

</body>
</html>
