<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'borrower') {
    header('Location: login.html');
    exit();
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['name'] ?? 'Borrower';
$message = '';

// Handle borrow request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $amount = (float) ($_POST['amount'] ?? 0);
    $purpose = trim($_POST['purpose'] ?? '');
    $duration = (int) ($_POST['duration'] ?? 0);

    if ($amount <= 0 || $purpose === '' || $duration <= 0) {
        $message = ['type' => 'error', 'text' => 'Please fill in all fields with valid values.'];
    } elseif ($amount > 100000) {
        $message = ['type' => 'error', 'text' => 'Amount cannot exceed 100,000 RWF.'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO borrow_requests (borrower_id, borrower_name, amount, purpose, duration, status, request_date) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->execute([$userId, $userName, $amount, $purpose, $duration]);
        $message = ['type' => 'success', 'text' => 'Borrow request submitted successfully!'];
    }
}

// Handle "Mark all notifications as read"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $updateStmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $updateStmt->execute([$userId]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch unread notifications
$notifStmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
$notifStmt->execute([$userId]);
$notifications = $notifStmt->fetchAll();

// Fetch borrow requests and stats
$stmt = $pdo->prepare("SELECT * FROM borrow_requests WHERE borrower_id = ? ORDER BY request_date DESC");
$stmt->execute([$userId]);
$requests = $stmt->fetchAll();

$stmtCount = $pdo->prepare("
    SELECT 
      COUNT(*) as total_requests,
      SUM(status = 'pending') as pending_count,
      SUM(status = 'approved') as approved_count,
      SUM(status = 'rejected') as rejected_count,
      SUM(amount) as total_borrowed
    FROM borrow_requests WHERE borrower_id = ?
");
$stmtCount->execute([$userId]);
$stats = $stmtCount->fetch();
// Fetch active loan offers (add lender_id!)
$offersStmt = $pdo->prepare("
    SELECT id, lender_id, lender_name, amount, interest_rate, duration, offer_date
    FROM loan_offers
    WHERE status = 'active'
    ORDER BY offer_date DESC
");
$offersStmt->execute();
$loanOffers = $offersStmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Borrower Dashboard - Lendu</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet"/>

  <style>
    /* Basic reset & font */
   <style>
  body {
    font-family: 'Montserrat', sans-serif;
    background: #f5faff;
    color: #00274d;
    margin: 0;
    padding: 0;
  }

  .container {
    display: flex;
    min-height: 100vh;
  }

  nav.sidebar {
    width: 220px;
    background: linear-gradient(180deg, #00274d, #007acc);
    color: white;
    display: flex;
    flex-direction: column;
    padding: 24px 0;
  }

  nav.sidebar h2 {
    margin: 0 0 20px 20px;
    font-weight: 700;
  }

  nav.sidebar a {
    color: white;
    padding: 12px 24px;
    text-decoration: none;
    font-weight: 600;
    transition: background 0.3s;
  }

  nav.sidebar a:hover, nav.sidebar a.active {
    background: rgba(255, 255, 255, 0.1);
  }

  main {
    flex-grow: 1;
    padding: 30px 40px;
    overflow-y: auto;
  }

  header h1 {
    margin-top: 0;
    font-size: 1.8rem;
  }

  .message {
    padding: 0.75rem 1rem;
    margin-bottom: 1.5rem;
    border-radius: 6px;
    font-weight: 600;
  }

  .message.success {
    background: #d4edda;
    color: #155724;
  }

  .message.error {
    background: #f8d7da;
    color: #721c24;
  }

  .stats {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
    margin-bottom: 2rem;
  }

  .stat-card {
    flex: 1 1 150px;
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    text-align: center;
  }

  .stat-card h3 {
    margin: 0;
    font-size: 1rem;
    color: #007acc;
  }

  .stat-card p {
    font-size: 1.6rem;
    font-weight: 700;
    margin-top: 0.5rem;
  }

  form {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    margin-bottom: 2rem;
  }

  label {
    display: block;
    margin: 1rem 0 0.5rem;
    font-weight: 600;
  }

  input[type="number"], textarea {
    width: 100%;
    padding: 0.75rem;
    font-size: 1rem;
    border-radius: 8px;
    border: 1px solid #ccc;
    resize: vertical;
  }

  button {
    background: #007acc;
    color: white;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    margin-top: 1rem;
    cursor: pointer;
    transition: background 0.3s ease;
  }

  button:hover {
    background: #005f99;
  }

  .btn-request {
    background-color: #28a745;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    font-weight: 600;
    border-radius: 6px;
    cursor: pointer;
  }

  .btn-request:hover {
    background-color: #218838;
  }

  .btn-mark-read {
    background-color: #407dff;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    margin-top: 12px;
  }

  .btn-mark-read:hover {
    background-color: #305de0;
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
    vertical-align: middle;
  }

  tr:nth-child(even) {
    background: #f1faff;
  }

  .status {
    padding: 5px 12px;
    border-radius: 12px;
    font-weight: 600;
    display: inline-block;
  }

  .pending {
    background-color: #fff3cd;
    color: #856404;
  }

  .approved {
    background-color: #d4edda;
    color: #155724;
  }

  .rejected {
    background-color: #f8d7da;
    color: #721c24;
  }

  .notifications {
    background: #e7f3ff;
    border: 1px solid #a0c8ff;
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 25px;
  }

  .notifications h2 {
    margin-top: 0;
    color: #274bae;
  }

  .notifications ul {
    list-style: none;
    padding-left: 0;
    margin: 0;
  }

  .notifications li {
    padding: 10px;
    border-bottom: 1px solid #cde1ff;
    font-size: 15px;
    color: #1e2e50;
  }

  .notifications li:last-child {
    border-bottom: none;
  }

  .notifications small {
    color: #666;
    font-size: 12px;
    display: block;
    margin-top: 4px;
  }

  .nav-actions {
    margin-top: 2rem;
    display: flex;
    gap: 1rem;
  }

  @media (max-width: 768px) {
    .container {
      flex-direction: column;
    }

    nav.sidebar {
      width: 100%;
      flex-direction: row;
      overflow-x: auto;
    }

    nav.sidebar a {
      flex: 1;
      text-align: center;
    }

    main {
      padding: 20px;
    }

    .stats {
      flex-direction: column;
    }
  }
</style>

</head>
<body>
<div class="container">
  <nav class="sidebar">
    <h2>Lendu</h2>
    <a href="#" class="active">Dashboard</a>
    <a href="profile.php">Profile</a>
    <a href="logout.php">Logout</a>
  </nav>

  <main>
    <header>
      <h1>Welcome, <?php echo htmlspecialchars($userName); ?> (Borrower)</h1>
    </header>

    <!-- Notifications -->
    <section class="notifications" aria-label="Notifications">
      <h2>Notifications</h2>
      <?php if (empty($notifications)): ?>
        <p>No new notifications.</p>
      <?php else: ?>
        <ul>
          <?php foreach ($notifications as $note): ?>
            <li>
              <?php echo htmlspecialchars($note['message']); ?>
              <small><?php echo date('Y-m-d H:i', strtotime($note['created_at'])); ?></small>
            </li>
          <?php endforeach; ?>
        </ul>
        <form method="POST" style="margin-top: 10px;">
          <button type="submit" name="mark_read" class="btn-mark-read">Mark all as read</button>
        </form>
      <?php endif; ?>
    </section>

    <?php if ($message): ?>
      <div class="message <?php echo $message['type'] === 'success' ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars($message['text']); ?>
      </div>
    <?php endif; ?>

    <!-- Summary stats -->
    <section class="stats" aria-label="Borrower statistics summary">
      <div class="stat-card" title="Total requests submitted">
        <h3>Total Requests</h3>
        <p><?php echo (int)$stats['total_requests']; ?></p>
      </div>
      <div class="stat-card" title="Pending requests">
        <h3>Pending</h3>
        <p><?php echo (int)$stats['pending_count']; ?></p>
      </div>
      <div class="stat-card" title="Approved requests">
        <h3>Approved</h3>
        <p><?php echo (int)$stats['approved_count']; ?></p>
      </div>
      <div class="stat-card" title="Rejected requests">
        <h3>Rejected</h3>
        <p><?php echo (int)$stats['rejected_count']; ?></p>
      </div>
      <div class="stat-card" title="Total amount borrowed">
        <h3>Total Borrowed (RWF)</h3>
        <p><?php echo number_format((float)$stats['total_borrowed']); ?></p>
      </div>
    </section>

    <section aria-label="Submit borrow request">
      <h2>Submit a Borrow Request</h2>
      <form method="POST" action="">
        <label for="amount">Amount (Max 100,000 RWF)</label>
        <input type="number" id="amount" name="amount" min="1" max="100000" required />

        <label for="purpose">Purpose of Loan</label>
        <textarea id="purpose" name="purpose" required></textarea>

        <label for="duration">Repayment Duration (days)</label>
        <input type="number" id="duration" name="duration" min="1" required />

        <button type="submit" name="submit_request">Submit Request</button>
      </form>
    </section>

    <section aria-label="Borrow request history">
      <h2>Your Borrow Request History</h2>
      <?php if (count($requests) === 0): ?>
        <p>You have no borrow requests yet.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Amount (RWF)</th>
              <th>Purpose</th>
              <th>Duration (days)</th>
              <th>Status</th>
              <th>Requested On</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($requests as $req): ?>
              <tr>
                <td><?php echo $req['id']; ?></td>
                <td><?php echo number_format($req['amount']); ?></td>
                <td><?php echo htmlspecialchars($req['purpose']); ?></td>
                <td><?php echo $req['duration']; ?></td>
                <td><span class="status <?php echo strtolower($req['status']); ?>"><?php echo htmlspecialchars($req['status']); ?></span></td>
                <td><?php echo $req['request_date']; ?></td>
              </tr>
              <?php if ($req['status'] === 'approved'): ?>
                <tr>
                  <td colspan="6">
                    <a href="contract.php?loan_id=<?php echo $req['id']; ?>" target="_blank">📄 View Contract</a>

                    <?php if (!$req['contract_signed_borrower']): ?>
                      <form method="POST" action="sign_contract.php" style="display:inline;">
                        <input type="hidden" name="loan_id" value="<?php echo $req['id']; ?>">
                        <input type="hidden" name="who" value="borrower">
                        <button type="submit">✅ I Agree</button>
                      </form>
                    <?php else: ?>
                      <span>✅ You Signed</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endif; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>

    <!-- Loan offers from lenders -->
    <section aria-label="Available loan offers">
      <h2>Available Loan Offers from Lenders</h2>
      <?php if (count($loanOffers) === 0): ?>
        <p>No loan offers available at the moment. Please check back later.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Lender</th>
              <th>Amount (RWF)</th>
              <th>Interest Rate (%)</th>
              <th>Duration (days)</th>
              <th>Offered On</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($loanOffers as $offer): ?>
              <tr>
                <td><?php echo htmlspecialchars($offer['lender_name']); ?></td>
                <td><?php echo number_format($offer['amount']); ?></td>
                <td><?php echo htmlspecialchars($offer['interest_rate']); ?></td>
                <td><?php echo htmlspecialchars($offer['duration']); ?></td>
                <td><?php echo $offer['offer_date']; ?></td>
               <td>
       <form method="POST" action="send_message.php" style="margin-bottom: 5px;">
  <input type="hidden" name="borrower_id" value="<?= $_SESSION['user_id'] ?>">
  <input type="hidden" name="lender_id" value="<?= htmlspecialchars($offer['lender_id']) ?>">
  <textarea name="message" placeholder="Ask lender to reduce interest..." rows="2" style="width:100%; border-radius:6px;" required></textarea>
  <button type="submit" style="margin-top:5px;">💬 Send Message</button>
</form>


  <form method="POST" action="request_loan.php" style="margin:0;">
    <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>" />
    <button type="submit" class="btn-request">Request Loan</button>
  </form>
</td>

              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>
  </main>
</div>
</body>
</html>
