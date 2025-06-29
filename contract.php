<?php
require 'db.php';
session_start();
$allowed_roles = ['borrower', 'lender', 'admin']; // add roles as needed

if (!isset($_SESSION['user_id']) || !isset($_GET['loan_id']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die('Unauthorized access');
}

$loan_id = (int)$_GET['loan_id'];

// Fetch loan details
$stmt = $pdo->prepare("SELECT br.*, l.name AS lender_name FROM borrow_requests br JOIN users l ON br.approved_by = l.id WHERE br.id = ?");
$stmt->execute([$loan_id]);
$loan = $stmt->fetch();

if (!$loan) {
    die('Loan not found or not approved.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Loan Contract - Lendu</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Montserrat', sans-serif;
      background: #f7f9fc;
      padding: 40px;
      color: #2c3e50;
      max-width: 800px;
      margin: auto;
      border: 1px solid #ccc;
      border-radius: 12px;
      background-color: #ffffff;
    }
    h1 {
      text-align: center;
      color: #274bae;
    }
    p {
      font-size: 16px;
      line-height: 1.6;
    }
    strong {
      color: #000;
    }
    table {
      margin-top: 30px;
      width: 100%;
    }
    td {
      padding: 20px;
      font-size: 16px;
    }
    .center {
      text-align: center;
      margin-top: 40px;
    }
    button {
      background-color: #407dff;
      color: white;
      padding: 14px 28px;
      font-size: 16px;
      font-weight: 600;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    button:hover {
      background-color: #305de0;
    }
    .back-button {
      margin-top: 20px;
      background-color: #407dff;
      color: white;
      padding: 14px 28px;
      font-size: 16px;
      font-weight: 600;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      transition: background-color 0.3s ease;
      display: inline-block;
      text-decoration: none;
    }
    .back-button:hover {
      background-color: #305de0;
    }
  </style>
</head>
<body>

  <h1>Loan Agreement</h1>

  <p>This Loan Agreement is made between <strong><?php echo htmlspecialchars($loan['borrower_name']); ?></strong> (Borrower) and <strong><?php echo htmlspecialchars($loan['lender_name']); ?></strong> (Lender).</p>

  <p><strong>Loan Amount:</strong> RWF <?php echo number_format($loan['amount']); ?></p>
  <p><strong>Interest Rate:</strong> <?php echo $loan['interest_rate']; ?>%</p>
  <p><strong>Repayment Duration:</strong> <?php echo $loan['duration']; ?> days</p>
  <p><strong>Purpose:</strong> <?php echo htmlspecialchars($loan['purpose']); ?></p>

  <p><strong>Repayment Schedule:</strong> The borrower agrees to repay the loan in full within <?php echo $loan['duration']; ?> days from the date of this agreement.</p>

  <p><strong>Late Payment Penalties:</strong> Any payment received after the due date will incur a penalty of 5% of the outstanding amount per week.</p>

  <p>Signed electronically on <strong><?php echo date('Y-m-d'); ?></strong>.</p>

  <table>
    <tr>
      <td><strong>Borrower Signature:</strong> ____________________</td>
      <td><strong>Lender Signature:</strong> ____________________</td>
    </tr>
  </table>

  <div class="center">
    <button onclick="window.print()">🖨️ Download or Print</button>
    <br>
    <a href="borrower_dashboard.php" class="back-button">⬅️ Back to Dashboard</a>
  </div>

</body>
</html>
