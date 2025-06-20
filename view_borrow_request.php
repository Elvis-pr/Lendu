<?php
session_start();

// Only lenders can access this page
if (!isset($_SESSION['user_name']) || $_SESSION['role'] !== 'lender') {
    header('Location: login.html');
    exit();
}

require_once 'db.php'; // Make sure db.php connects to lend-u DB

// Fetch pending borrow requests
$query = "SELECT * FROM borrow_requests WHERE status = 'pending' ORDER BY request_date DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Borrow Requests - Lendu</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    body {
      font-family: 'Montserrat', sans-serif;
      background: #f5f7fa;
      margin: 0;
      padding: 30px;
      color: #2c3e50;
    }

    h1 {
      text-align: center;
      margin-bottom: 30px;
      color: #407dff;
    }

    table {
      border-collapse: collapse;
      width: 100%;
      max-width: 900px;
      margin: auto;
      background: white;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.05);
      overflow: hidden;
    }

    th, td {
      padding: 15px 20px;
      text-align: left;
      border-bottom: 1px solid #eee;
    }

    th {
      background-color: #407dff;
      color: white;
      font-weight: 600;
    }

    tr:hover {
      background-color: #f0f8ff;
    }

    a.approve-btn {
      background-color: #28a745;
      color: white;
      padding: 8px 15px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      transition: background-color 0.3s ease;
      display: inline-block;
    }

    a.approve-btn:hover {
      background-color: #218838;
    }

    .no-requests {
      text-align: center;
      font-size: 18px;
      color: #777;
      margin-top: 50px;
    }

    .back-link {
      display: block;
      max-width: 900px;
      margin: 30px auto 0;
      text-align: center;
      font-weight: 600;
      color: #407dff;
      text-decoration: none;
      cursor: pointer;
    }

    .back-link:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

<h1>Pending Borrow Requests</h1>

<?php if (mysqli_num_rows($result) > 0): ?>
  <table>
    <thead>
      <tr>
        <th>Borrower Name</th>
        <th>Amount (RWF)</th>
        <th>Purpose</th>
        <th>Request Date</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <tr>
          <td><?php echo htmlspecialchars($row['borrower_name']); ?></td>
          <td><?php echo number_format($row['amount']); ?></td>
          <td><?php echo htmlspecialchars($row['purpose']); ?></td>
          <td><?php echo htmlspecialchars(date('d M Y', strtotime($row['request_date']))); ?></td>
          <td>
            <a class="approve-btn" href="approve_request.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Approve this request?');">Approve</a>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
<?php else: ?>
  <p class="no-requests">No pending borrow requests found.</p>
<?php endif; ?>

<a class="back-link" href="lender_dashboard.php">&larr; Back to Dashboard</a>

</body>
</html>
