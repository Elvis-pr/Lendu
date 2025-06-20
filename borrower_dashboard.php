<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'borrower') {
    header('Location: login.html');
    exit();
}

$userName = $_SESSION['user_name'] ?? 'User';
$message = '';

// Handle borrow request form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'] ?? null;
    $purpose = $_POST['purpose'] ?? null;
    $duration = $_POST['duration'] ?? null;

    if (!empty($amount) && !empty($purpose) && !empty($duration)) {
        if ($amount > 100000) {
            $message = "❌ Amount cannot be more than 100,000 RWF.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO borrow_requests (borrower_name, amount, purpose, duration, status, request_date) VALUES (?, ?, ?, ?, 'pending', NOW())");
            $stmt->execute([$userName, $amount, $purpose, $duration]);
            $message = "✅ Borrow request submitted successfully!";
        }
    } else {
        $message = "❌ Please fill in all fields.";
    }
}

// Fetch previous borrow requests by this borrower
$stmt = $pdo->prepare("SELECT * FROM borrow_requests WHERE borrower_name = ? ORDER BY request_date DESC");
$stmt->execute([$userName]);
$requests = $stmt->fetchAll();

// Fetch lenders who have funds available (>0)
$lendersStmt = $pdo->query("SELECT id, user_name, wallet_balance FROM users WHERE role = 'lender' AND wallet_balance > 0 ORDER BY wallet_balance DESC");
$lenders = $lendersStmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
  <title>Lendu - Borrower Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      margin: 0; padding: 0; font-family: 'Montserrat', sans-serif; background: #f5f7fb; color: #333;
    }
    .container {
      max-width: 1000px; margin: auto; padding: 40px 20px;
    }
    .header { text-align: center; margin-bottom: 40px; }
    .header h1 { margin: 0; color: #2c3e50; }

    /* Message styling */
    .msg {
      margin-bottom: 20px; padding: 12px; border-radius: 8px;
      border-left: 5px solid #2c7c2c;
      background: #e3f7e1; color: #2c7c2c;
    }
    .error {
      border-left-color: #cc0000; background: #ffe0e0; color: #cc0000;
    }

    /* Sections */
    .form-section, .history-section, .lenders-section {
      background: #fff; padding: 30px; margin-bottom: 30px; border-radius: 16px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    }
    h2 {
      margin-bottom: 20px; color: #274bae;
    }

    /* Form */
    form input, form textarea {
      width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ccc;
      border-radius: 10px; font-size: 16px;
      resize: vertical;
    }
    form button {
      background: #407dff; color: white; border: none;
      padding: 12px 20px; border-radius: 10px; font-weight: 600;
      font-size: 16px; cursor: pointer; transition: background 0.3s ease;
    }
    form button:hover { background: #305de0; }

    /* Table styling */
    table {
      width: 100%; border-collapse: collapse; font-size: 14px;
    }
    th, td {
      padding: 12px; border-bottom: 1px solid #eee; text-align: left;
    }
    th {
      background: #f0f4ff; font-weight: 600;
    }
    tr:hover {
      background: #f9fbff;
    }

    /* Status badges */
    .status-badge {
      padding: 6px 12px; border-radius: 10px;
      font-weight: 600; text-transform: capitalize;
      display: inline-block;
    }
    .pending { background: #fff3cd; color: #856404; }
    .approved { background: #d4edda; color: #155724; }
    .rejected { background: #f8d7da; color: #721c24; }

    /* Request Loan button */
    a.request-btn {
      background: #28a745; color: white;
      padding: 8px 16px; border-radius: 10px;
      font-weight: 600; text-decoration: none;
      transition: background 0.3s ease;
      display: inline-block;
    }
    a.request-btn:hover {
      background: #218838;
    }

    /* Logout */
    .logout {
      display: inline-block; margin-top: 20px;
      text-decoration: none; background: #ccc;
      color: #222; padding: 10px 16px; border-radius: 8px;
      font-weight: 600;
      transition: background 0.3s ease;
    }
    .logout:hover {
      background: #bbb;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>Welcome, <?php echo htmlspecialchars($userName); ?> (Borrower)</h1>
    </div>

    <?php if (!empty($message)): ?>
      <div class="msg <?php echo strpos($message, '❌') !== false ? 'error' : ''; ?>">
        <?php echo $message; ?>
      </div>
    <?php endif; ?>

    <div class="lenders-section">
      <h2>💰 Available Lenders</h2>
      <?php if (count($lenders) > 0): ?>
        <table>
          <thead>
            <tr>
              <th>Lender Name</th>
              <th>Available Amount (RWF)</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($lenders as $lender): ?>
              <tr>
                <td><?php echo htmlspecialchars($lender['user_name']); ?></td>
                <td><?php echo number_format($lender['wallet_balance']); ?></td>
                <td>
                  <a class="request-btn" href="borrow_request_form.php?lender_id=<?php echo $lender['id']; ?>">Request Loan</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p>No lenders currently have funds available. Please check back later.</p>
      <?php endif; ?>
    </div>

    <div class="form-section">
      <h2>📤 Submit a Borrow Request</h2>
      <form method="POST" action="">
        <input type="number" name="amount" placeholder="Amount in RWF (Max 100,000)" required>
        <textarea name="purpose" rows="3" placeholder="Purpose of the loan" required></textarea>
        <input type="number" name="duration" placeholder="Repayment duration in days" required>
        <button type="submit">Submit Request</button>
      </form>
    </div>

    <div class="history-section">
      <h2>📄 Your Borrow Request History</h2>
      <table>
        <tr>
          <th>ID</th>
          <th>Amount</th>
          <th>Purpose</th>
          <th>Duration</th>
          <th>Status</th>
          <th>Requested On</th>
          <th>Contract</th>
          <th>Receipt</th>
        </tr>
        <?php if ($requests): ?>
          <?php foreach ($requests as $req): ?>
            <tr>
              <td><?= $req['id']; ?></td>
              <td><?= number_format($req['amount']); ?> RWF</td>
              <td><?= htmlspecialchars($req['purpose']); ?></td>
              <td><?= $req['duration']; ?> days</td>
              <td><span class="status-badge <?= strtolower($req['status']); ?>"><?= $req['status']; ?></span></td>
              <td><?= $req['request_date']; ?></td>
              <td>
                <?php if (strtolower($req['status']) === 'approved'): ?>
                  <a href="contract.php?id=<?= $req['id']; ?>" target="_blank">View Contract</a>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>
              <td>
                <?php if (strtolower($req['status']) === 'approved'): ?>
                  <a href="receipt.php?id=<?= $req['id']; ?>" target="_blank">View Receipt</a>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="8">No borrow requests yet.</td></tr>
        <?php endif; ?>
      </table>
    </div>

    <a href="logout.php" class="logout">🚪 Logout</a>
  </div>
</body>
</html>
