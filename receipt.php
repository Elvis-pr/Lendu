<?php
session_start();
require 'db.php';

// Check user logged in and borrower role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'borrower') {
    header('Location: login.html');
    exit();
}

$userName = $_SESSION['user_name'] ?? 'User';

// Validate borrow request ID from GET
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid borrow request ID.');
}

$borrowRequestId = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM borrow_requests WHERE id = ? AND borrower_id = ?");
$stmt->execute([$borrowRequestId, $_SESSION['user_id']]);
$request = $stmt->fetch();

if (!$request) {
    die('Borrow request not found or access denied.');
}

if (strtolower($request['status']) !== 'approved') {
    die('This loan has not been approved yet.');
}

// Fetch lender info based on approved_by username
$lenderName = 'N/A';
$lenderAddress = 'N/A';
$lenderContact = 'N/A';

if (!empty($request['approved_by'])) {
    $lenderStmt = $pdo->prepare("SELECT user_name, address, contact FROM users WHERE user_name = ?");
    $lenderStmt->execute([$request['approved_by']]);
    $lender = $lenderStmt->fetch();

    if ($lender) {
        $lenderName = $lender['user_name'];
        $lenderAddress = $lender['address'] ?? 'Address not available';
        $lenderContact = $lender['contact'] ?? 'Contact not available';
    }
}
// Add to line 50 in receipt.php
if ($request['status'] === 'approved') {
    $payment = json_decode(file_get_contents('http://localhost/mobile_money_simulator.php', false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode(['amount' => $request['amount']])
        ]
    ]));
    echo "<p><strong>Payment Method:</strong> Mobile Money ({$payment->transaction_id})</p>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Lendu - Loan Receipt #<?= htmlspecialchars($request['id']); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet" />
<style>
  body {
    font-family: 'Montserrat', sans-serif;
    max-width: 720px;
    margin: 40px auto;
    background: #fefefe;
    color: #222;
    padding: 40px 50px;
    border: 1px solid #ddd;
    border-radius: 14px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
  }
  h1, h2 {
    text-align: center;
    color: #274bae;
    margin-bottom: 10px;
    font-weight: 700;
  }
  h2 {
    font-weight: 600;
    font-size: 1.6rem;
  }
  .btn {
    display: inline-block;
    margin: 20px 10px 40px 10px;
    background: #407dff;
    color: #fff;
    border: none;
    padding: 14px 30px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 16px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(64,125,255,0.4);
    transition: background-color 0.3s ease;
    text-decoration: none;
    text-align: center;
  }
  .btn:hover {
    background: #305de0;
  }
  @media print {
    .btn {
      display: none;
    }
    body {
      margin: 0;
      box-shadow: none;
      border: none;
      padding: 0;
    }
  }
  .receipt-info {
    font-size: 1.1rem;
    line-height: 1.7;
    margin-bottom: 40px;
  }
  .receipt-info p {
    margin: 12px 0;
  }
  .receipt-info strong {
    color: #274bae;
    width: 180px;
    display: inline-block;
  }
  .footer {
    text-align: center;
    font-size: 14px;
    color: #666;
    border-top: 1px solid #eee;
    padding-top: 25px;
  }
</style>
</head>
<body>

  <h1>Lendu Loan Receipt</h1>
  <h2>Receipt #<?= htmlspecialchars($request['id']); ?></h2>

  <div style="text-align:center;">
    <button class="btn" onclick="window.print()">🖨️ Print Receipt</button>
    <a href="borrower_dashboard.php" class="btn">⬅️ Back to Dashboard</a>
  </div>

  <div class="receipt-info">
    <p><strong>Borrower Name:</strong> <?= htmlspecialchars($request['borrower_name']); ?></p>
    <p><strong>Approved By:</strong> <?= htmlspecialchars($lenderName); ?></p>
    <p><strong>Lender Address:</strong> <?= htmlspecialchars($lenderAddress); ?></p>
    <p><strong>Lender Contact:</strong> <?= htmlspecialchars($lenderContact); ?></p>
    <p><strong>Amount Borrowed:</strong> <?= number_format($request['amount']); ?> RWF</p>
    <p><strong>Purpose:</strong> <?= htmlspecialchars($request['purpose']); ?></p>
    <p><strong>Repayment Duration:</strong> <?= htmlspecialchars($request['duration']); ?> days</p>
    <p><strong>Status:</strong> <?= ucfirst(htmlspecialchars($request['status'])); ?></p>
    <p><strong>Request Date:</strong> <?= htmlspecialchars($request['request_date']); ?></p>
  </div>

  <div class="footer">
    <p>Thank you for choosing <strong>Lendu</strong> — Your trusted student lending platform.</p>
  </div>

</body>
</html>
