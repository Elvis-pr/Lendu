<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.html");
  exit;
}

// Platform stats
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalLoans = $pdo->query("SELECT COUNT(*) FROM borrow_requests")->fetchColumn();
$approvedLoans = $pdo->query("SELECT COUNT(*) FROM borrow_requests WHERE status='approved'")->fetchColumn();
$pendingLoans = $pdo->query("SELECT COUNT(*) FROM borrow_requests WHERE status='pending'")->fetchColumn();

// Recent loans
$loansStmt = $pdo->query("
  SELECT br.id, br.amount, br.status, br.created_at, u.name AS borrower_name, u.email 
  FROM borrow_requests br
  JOIN users u ON br.borrower_id = u.id
  ORDER BY br.created_at DESC
  LIMIT 10
");
$recentLoans = $loansStmt->fetchAll();

// Users list
$usersStmt = $pdo->query("SELECT id, name, email, university, role, created_at FROM users ORDER BY created_at DESC");
$allUsers = $usersStmt->fetchAll();

// Chart data
$loanStatusData = $pdo->query("SELECT status, COUNT(*) AS count FROM borrow_requests GROUP BY status")->fetchAll();
$statuses = [];
$counts = [];
foreach ($loanStatusData as $row) {
  $statuses[] = ucfirst($row['status']);
  $counts[] = (int)$row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Dashboard - LendU</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      font-family: 'Montserrat', sans-serif;
      background: #f5faff;
      margin: 0;
      padding: 1rem 2rem;
      color: #00274d;
    }
    .top-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: linear-gradient(90deg, #00274d, #007acc);
      padding: 1rem 2rem;
      border-radius: 8px;
      color: white;
    }
    .top-bar h1 {
      margin: 0;
      font-size: 1.5rem;
    }
    .logout-btn {
      border: 1px solid white;
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 4px;
      text-decoration: none;
      font-weight: 600;
      transition: 0.3s;
    }
    .logout-btn:hover {
      background: rgba(255,255,255,0.15);
    }
    .container {
      max-width: 1100px;
      margin: 1rem auto;
    }
    .cards {
      display: flex;
      gap: 1.5rem;
      flex-wrap: wrap;
      margin: 2rem 0;
    }
    .card {
      flex: 1;
      min-width: 180px;
      background: white;
      border-radius: 8px;
      padding: 1.5rem;
      box-shadow: 0 3px 12px rgba(0,0,0,0.1);
      text-align: center;
    }
    .card h2 {
      margin: 0;
      font-size: 2rem;
      color: #007acc;
    }
    .card p {
      margin-top: 0.5rem;
      font-weight: 600;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 1rem;
      background: white;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 3px 12px rgba(0,0,0,0.1);
    }
    thead {
      background: #007acc;
      color: white;
    }
    th, td {
      padding: 0.75rem 1rem;
      border: 1px solid #ddd;
      text-align: left;
    }
    tr:nth-child(even) {
      background: #f1faff;
    }
    .btn {
      padding: 6px 12px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-weight: 600;
    }
    .btn-approve {
      background: #28a745;
      color: white;
    }
    .btn-reject {
      background: #dc3545;
      color: white;
    }
    .btn-export {
      background: #007acc;
      color: white;
      margin-bottom: 1rem;
    }
    h2 {
      margin-top: 3rem;
    }
  </style>
</head>
<body>

<div class="top-bar">
  <h1>Admin Dashboard - Welcome <?= htmlspecialchars($_SESSION['name']) ?></h1>
  <a href="logout.php" class="logout-btn">Logout</a>
</div>

<div class="container">

  <div class="cards">
    <div class="card"><h2><?= $totalUsers ?></h2><p>Total Users</p></div>
    <div class="card"><h2><?= $totalLoans ?></h2><p>Total Loans</p></div>
    <div class="card"><h2><?= $approvedLoans ?></h2><p>Approved Loans</p></div>
    <div class="card"><h2><?= $pendingLoans ?></h2><p>Pending Loans</p></div>
  </div>

  <h2>Loan Requests Status Chart</h2>
  <canvas id="loanStatusChart" style="max-width:600px;height:300px;"></canvas>

  <h2>Recent Loan Requests</h2>
  <button class="btn btn-export" onclick="exportTableToCSV('recent_loans.csv', 'loanRequestsTable')">Export CSV</button>
  <table id="loanRequestsTable">
    <thead>
      <tr>
        <th>ID</th>
        <th>Borrower</th>
        <th>Email</th>
        <th>Amount</th>
        <th>Status</th>
        <th>Date</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($recentLoans as $loan): ?>
        <tr>
          <td><?= $loan['id'] ?></td>
          <td><?= htmlspecialchars($loan['borrower_name']) ?></td>
          <td><?= htmlspecialchars($loan['email']) ?></td>
          <td><?= number_format($loan['amount']) ?> RWF</td>
          <td><?= ucfirst($loan['status']) ?></td>
          <td><?= $loan['created_at'] ?></td>
          <td>
            <?php if ($loan['status'] === 'pending'): ?>
              <form method="POST" action="admin_loan_action.php" style="display:inline;">
                <input type="hidden" name="loan_id" value="<?= $loan['id'] ?>">
                <input type="hidden" name="action" value="approve">
                <button class="btn btn-approve" type="submit">Approve</button>
              </form>
              <form method="POST" action="admin_loan_action.php" style="display:inline;">
                <input type="hidden" name="loan_id" value="<?= $loan['id'] ?>">
                <input type="hidden" name="action" value="reject">
                <button class="btn btn-reject" type="submit">Reject</button>
              </form>
            <?php else: ?>
              <em>Reviewed</em>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <h2>All Users</h2>
  <button class="btn btn-export" onclick="exportTableToCSV('users.csv', 'usersTable')">Export Users</button>
  <table id="usersTable">
    <thead>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>University</th>
        <th>Role</th>
        <th>Joined</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($allUsers as $user): ?>
        <tr>
          <td><?= $user['id'] ?></td>
          <td><?= htmlspecialchars($user['name']) ?></td>
          <td><?= htmlspecialchars($user['email']) ?></td>
          <td><?= htmlspecialchars($user['university']) ?></td>
          <td><?= ucfirst($user['role']) ?></td>
          <td><?= $user['created_at'] ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
  // Loan status chart with fixed color order
  const ctx = document.getElementById('loanStatusChart').getContext('2d');
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Approved', 'Pending', 'Rejected'],
      datasets: [{
        label: 'Loan Requests',
        data: [
          <?= $approvedLoans ?>,
          <?= $pendingLoans ?>,
          <?= $totalLoans - $approvedLoans - $pendingLoans ?>
        ],
        backgroundColor: [
          '#28a745', // green
          '#ffc107', // yellow
          '#dc3545'  // red
        ]
      }]
    }
  });

  function exportTableToCSV(filename, tableId) {
    const csv = [];
    const rows = document.querySelectorAll(`#${tableId} tr`);
    for (let row of rows) {
      const cols = row.querySelectorAll('th, td');
      const rowData = [];
      for (let col of cols) {
        let text = col.innerText.replace(/"/g, '""');
        if (text.includes(',') || text.includes('"')) {
          text = `"${text}"`;
        }
        rowData.push(text);
      }
      csv.push(rowData.join(','));
    }
    const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
    const downloadLink = document.createElement('a');
    downloadLink.href = URL.createObjectURL(csvFile);
    downloadLink.download = filename;
    downloadLink.click();
  }
</script>
</body>
</html>
