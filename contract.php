<?php
session_start();
require 'db.php';

// Check user logged in and borrower role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'borrower') {
    header('Location: login.html');
    exit();
}

$userId = $_SESSION['user_id'];

// Get borrower info from users table
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$borrower = $stmt->fetch();

if (!$borrower) {
    die('User not found.');
}

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



$lenderName = $request['approved_by'] ?? 'Not yet assigned';
$lenderAddress = "P.O Box 648, Ruhengeri, Musanze District, Rwanda";
$lenderContact = "info@ines.ac.rw | +250 788 123 456";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Lendu - Loan Contract #<?= htmlspecialchars($request['id']); ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    body {
      font-family: 'Montserrat', sans-serif;
      max-width: 720px;
      margin: 40px auto;
      background: #fff;
      color: #222;
      padding: 40px 50px;
      border: 1px solid #ddd;
      border-radius: 14px;
      box-shadow: 0 6px 18px rgba(0,0,0,0.08);
      line-height: 1.6;
    }
    h1, h2 {
      text-align: center;
      color: #274bae;
      margin-bottom: 10px;
      font-weight: 700;
    }
    .section {
      margin-bottom: 30px;
    }
    .section strong {
      color: #274bae;
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
    }
    .details p, .terms p {
      margin: 6px 0;
      font-size: 1.1rem;
    }
    .terms {
      background: #f0f4ff;
      padding: 20px;
      border-radius: 12px;
      border: 1px solid #b2c7ff;
    }
    .signature-section {
      margin-top: 50px;
      display: flex;
      justify-content: space-between;
    }
    .signature-block {
      width: 45%;
      text-align: center;
    }
    .signature-line {
      margin-top: 60px;
      border-top: 1px solid #999;
      width: 100%;
    }
    .actions {
      text-align: center;
      margin-bottom: 40px;
    }
    button.print-btn, a.back-btn {
      display: inline-block;
      background: #407dff;
      color: #fff;
      padding: 14px 30px;
      border-radius: 10px;
      font-weight: 700;
      font-size: 16px;
      cursor: pointer;
      text-decoration: none;
      margin: 10px;
    }
    button.print-btn:hover, a.back-btn:hover {
      background: #305de0;
    }
    @media print {
      .actions {
        display: none;
      }
    }
  </style>
</head>
<body>

<h1>Lendu Loan Contract</h1>
<h2>Contract #<?= htmlspecialchars($request['id']); ?></h2>

<div class="actions">
  <button class="print-btn" onclick="window.print()">🖨️ Print Contract</button>
  <a href="borrower_dashboard.php" class="back-btn">⬅️ Back to Dashboard</a>
</div>

<div class="section">
  <strong>Borrower Details</strong>
  <p><strong>Full Name:</strong> <?= htmlspecialchars($borrower['full_name']); ?></p>
  <p><strong>Username:</strong> <?= htmlspecialchars($borrower['user_name']); ?></p>
  <p><strong>Email:</strong> <?= htmlspecialchars($borrower['email']); ?></p>
  <?php if (!empty($borrower['student_id'])): ?>
    <p><strong>Student ID:</strong> <?= htmlspecialchars($borrower['student_id']); ?></p>
  <?php endif; ?>
</div>

<div class="section">
  <strong>Lender Details</strong>
  <p><strong>Lender:</strong> <?= htmlspecialchars($lenderName); ?></p>
  <p><strong>Address:</strong> <?= htmlspecialchars($lenderAddress); ?></p>
  <p><strong>Contact:</strong> <?= htmlspecialchars($lenderContact); ?></p>
</div>

<div class="section terms">
  <strong>Loan Terms</strong>
  <p>Amount: <strong><?= number_format($request['amount']); ?> RWF</strong></p>
  <p>Purpose: <strong><?= htmlspecialchars($request['purpose']); ?></strong></p>
  <p>Repayment Duration: <strong><?= htmlspecialchars($request['duration']); ?> days</strong></p>
  <p>Status: <strong><?= ucfirst(htmlspecialchars($request['status'])); ?></strong></p>
  <p>Request Date: <strong><?= htmlspecialchars($request['request_date']); ?></strong></p>
</div>

<div class="section">
  <strong>Agreement</strong>
  <p>
    By signing below, the Borrower agrees to repay the loan amount within the specified duration.
    The Lender agrees to provide the funds under the stated terms.
    Both parties acknowledge that this contract is binding and subject to the laws governing peer-to-peer lending in Rwanda.
  </p>
</div>

<div class="signature-section">
  <div class="signature-block">
    <div class="signature-line"></div>
    <p>Borrower Signature</p>
  </div>
  <div class="signature-block">
    <div class="signature-line"></div>
    <p>Lender Signature</p>
  </div>
</div>

<div class="footer" style="text-align:center; margin-top:40px; font-size:14px; color:#666;">
  <p>Thank you for using <strong>Lendu</strong> — Platform built for INES-Ruhengeri students.</p>
</div>

</body>
</html>
