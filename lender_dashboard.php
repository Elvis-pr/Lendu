<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db.php';

$message = ''; // Prevent "undefined variable" error

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lender') {
    header('Location: login.html');
    exit();
}

$lenderId = $_SESSION['user_id'];
$lenderName = $_SESSION['name'];

// Handle loan offer submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['offer_submit'])) {
    $amount = (float) ($_POST['amount'] ?? 0);
    $interest_rate = (float) ($_POST['interest_rate'] ?? 0);
    $duration = (int) ($_POST['duration'] ?? 0);

    if ($amount <= 0 || $interest_rate < 0 || $duration <= 0) {
        $message = ['type' => 'error', 'text' => '❌ Please provide valid offer details.'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO loan_offers (lender_id, lender_name, amount, interest_rate, duration, status, offer_date) VALUES (?, ?, ?, ?, ?, 'active', NOW())");
        $stmt->execute([$lenderId, $lenderName, $amount, $interest_rate, $duration]);
        $message = ['type' => 'success', 'text' => '✅ Loan offer posted successfully!'];
    }
}

// Fetch pending borrow requests
$stmt = $pdo->prepare("SELECT br.*, u.name AS borrower_name FROM borrow_requests br JOIN users u ON br.borrower_id = u.id WHERE br.status = 'pending' ORDER BY br.request_date DESC");
$stmt->execute();
$requests = $stmt->fetchAll();

// Fetch messages from borrowers to this lender
$msgStmt = $pdo->prepare("SELECT om.*, u.name as borrower_name FROM offer_messages om JOIN users u ON om.borrower_id = u.id WHERE om.lender_id = ? ORDER BY om.sent_at DESC");
$msgStmt->execute([$lenderId]);
$messages = $msgStmt->fetchAll();

// Fetch lender's own loan offers
$myOffersStmt = $pdo->prepare("SELECT * FROM loan_offers WHERE lender_id = ? ORDER BY offer_date DESC");
$myOffersStmt->execute([$lenderId]);
$myOffers = $myOffersStmt->fetchAll();

// Fetch distinct borrowers who messaged this lender
$borrowersStmt = $pdo->prepare("SELECT DISTINCT u.id, u.name FROM users u JOIN offer_messages om ON u.id = om.borrower_id WHERE om.lender_id = ?");
$borrowersStmt->execute([$lenderId]);
$borrowers = $borrowersStmt->fetchAll();

// Risk scoring
function getRiskScore($amount) {
  if ($amount <= 30000) return ['label' => 'Low', 'color' => '#28a745'];
  if ($amount <= 70000) return ['label' => 'Medium', 'color' => '#ffc107'];
  return ['label' => 'High', 'color' => '#dc3545'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Lender Dashboard - Lendu</title>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet"/>
  <style>
    body {
      font-family: 'Montserrat', sans-serif;
      background: #f5faff;
      color: #00274d;
      margin: 0;
      padding: 0;
    }

    header {
      background: linear-gradient(90deg, #00274d, #007acc);
      color: white;
      padding: 1.5rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    header h1 {
      margin: 0;
      font-size: 1.8rem;
    }

    .welcome {
      font-size: 1rem;
      font-weight: 500;
    }

    .container {
      max-width: 1000px;
      margin: 2rem auto;
      padding: 1rem;
    }

    h2 {
      color: #007acc;
      margin-top: 2rem;
    }

    .message {
      padding: 0.75rem;
      margin-bottom: 1rem;
      border-radius: 5px;
      font-weight: 600;
    }

    .message.success {
      background-color: #d4edda;
      color: #155724;
    }

    .message.error {
      background-color: #f8d7da;
      color: #721c24;
    }

    form input, form select {
      display: block;
      width: 100%;
      padding: 0.6rem;
      margin-bottom: 1rem;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-family: inherit;
    }

    button {
      background: #007acc;
      color: white;
      padding: 0.7rem 1.2rem;
      border: none;
      border-radius: 6px;
      font-size: 1rem;
      cursor: pointer;
    }

    button:hover {
      background: #005f99;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 1rem;
      background: white;
      border-radius: 8px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.1);
      overflow: hidden;
    }

    th, td {
      padding: 0.75rem 1rem;
      border: 1px solid #ddd;
      text-align: left;
    }

    thead {
      background: #007acc;
      color: white;
    }

    tr:nth-child(even) {
      background: #f1faff;
    }

    .risk-badge {
      padding: 5px 10px;
      color: white;
      font-weight: 600;
      border-radius: 5px;
      display: inline-block;
    }

    .nav-links {
      margin-top: 2rem;
      display: flex;
      gap: 1rem;
    }

    .nav-links a {
      text-decoration: none;
      font-weight: 600;
      color: #007acc;
      background: #e6f0fa;
      padding: 0.5rem 1rem;
      border-radius: 6px;
    }

    .nav-links a:hover {
      background: #d0e7fa;
    }

    footer {
      text-align: center;
      padding: 2rem 1rem;
      background: #00274d;
      color: #fff;
      font-size: 0.9rem;
      margin-top: 3rem;
    }

    @media (max-width: 768px) {
      table, thead, tbody, th, td, tr {
        display: block;
      }

      th {
        background: #007acc;
        color: white;
        font-weight: bold;
      }

      td {
        padding-left: 50%;
        position: relative;
      }

      td::before {
        position: absolute;
        left: 1rem;
        top: 0.75rem;
        font-weight: bold;
        color: #007acc;
        content: attr(data-label);
      }

      .nav-links {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>

<header>
  <h1>Lender Dashboard</h1>
  <p class="welcome">Welcome, <?= htmlspecialchars($lenderName); ?>!</p>
</header>

<div class="container">

  <?php if (!empty($message)): ?>
    <div class="message <?= $message['type']; ?>">
      <?= htmlspecialchars($message['text']); ?>
    </div>
  <?php endif; ?>

  <h2>Create New Loan Offer</h2>
  <form method="POST">
    <label for="amount">Amount to Lend (RWF)</label>
    <input type="number" id="amount" name="amount" min="1000" max="100000" required>

    <label for="interest_rate">Interest Rate (%)</label>
    <input type="number" id="interest_rate" name="interest_rate" step="0.1" min="0" max="100" required>

    <label for="duration">Repayment Duration (days)</label>
    <input type="number" id="duration" name="duration" min="1" max="180" required>

    <button type="submit" name="offer_submit">Post Offer</button>
  </form>

  <h2>Your Active Loan Offers</h2>
  <?php if (count($myOffers) === 0): ?>
    <p>No offers posted yet.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr><th>Amount</th><th>Interest</th><th>Duration</th><th>Posted</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php foreach ($myOffers as $offer): ?>
        <tr>
          <td><?= number_format($offer['amount']) ?> RWF</td>
          <td><?= $offer['interest_rate'] ?>%</td>
          <td><?= $offer['duration'] ?> days</td>
          <td><?= $offer['offer_date'] ?></td>
          <td><?= ucfirst($offer['status']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
<h2>Messages from Borrowers</h2>
<?php if (count($messages) === 0): ?>
  <p>No messages yet.</p>
<?php else: ?>
  <?php
  $grouped = [];
  foreach ($messages as $msg) {
      $key = $msg['borrower_id'] . '_' . $msg['offer_id'];
      if (!isset($grouped[$key])) {
          $grouped[$key] = [
              'borrower_id' => $msg['borrower_id'],
              'offer_id' => $msg['offer_id'],
              'borrower_name' => $msg['borrower_name'],
              'messages' => [],
          ];
      }
      $grouped[$key]['messages'][] = $msg;
  }
  ?>

  <?php foreach ($grouped as $entry): ?>
    <div style="border: 1px solid #ccc; padding: 1rem; margin-bottom: 1.5rem; border-radius: 10px;">
      <h3>💬 Chat with <?= htmlspecialchars($entry['borrower_name']) ?> (Offer ID: <?= $entry['offer_id'] ?>)</h3>
      <div style="max-height: 200px; overflow-y: auto; background: #f9f9f9; padding: 10px; margin-bottom: 10px;">
        <?php foreach ($entry['messages'] as $msg): ?>
          <p><strong><?= $msg['sender_role'] === 'borrower' ? '👤 Borrower' : '🧑‍💼 You' ?>:</strong>
             <?= htmlspecialchars($msg['message']) ?><br>
             <small><?= $msg['sent_at'] ?></small></p>
        <?php endforeach; ?>
      </div>
<form method="POST" action="send_message.php" style="margin-bottom: 5px;">
  <input type="hidden" name="lender_id" value="<?= $_SESSION['user_id'] ?>">
  <input type="hidden" name="borrower_id" value="<?= $request['borrower_id'] ?>">
  <input type="hidden" name="offer_id" value="<?= $request['offer_id'] ?>">
  <textarea name="message" placeholder="Message borrower..." rows="2" style="width:100%; border-radius:6px;" required></textarea>
  <button type="submit" style="margin-top:5px;">💬 Send Message</button>
</form>


    </div>
  <?php endforeach; ?>
<?php endif; ?>


  <h2>Pending Borrow Requests with AI Risk Score</h2>
  <?php if (count($requests) === 0): ?>
    <p>No pending borrow requests.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr><th>Borrower</th><th>Amount</th><th>Purpose</th><th>Requested</th><th>Risk</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($requests as $req): 
          $risk = getRiskScore($req['amount']);
        ?>
        <tr>
          <td><?= $req['borrower_name'] ?></td>
          <td><?= number_format($req['amount']) ?> RWF</td>
          <td><?= htmlspecialchars($req['purpose']) ?></td>
          <td><?= $req['request_date'] ?></td>
          <td><span class="risk-badge" style="background:<?= $risk['color'] ?>"><?= $risk['label'] ?></span></td>
          <td>
            <a href="approve_request.php?id=<?= $req['id'] ?>" style="color:green;">Approve</a> |
            <a href="reject.php?id=<?= $req['id'] ?>" style="color:red;">Reject</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <div class="nav-links">
    <a href="profile.php">👤 Profile</a>
    <a href="logout.php">🚪 Logout</a>
  </div>
</div>

<footer>
  &copy; <?= date('Y') ?> Lendu Platform — Peer-to-Peer Student Lending
</footer>

</body>
</html>
