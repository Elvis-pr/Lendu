<?php
session_start();
require 'db.php';

// Check user logged in and loan_id provided
if (!isset($_SESSION['user_id']) || !isset($_GET['loan_id'])) {
    header('Location: login.html');
    exit();
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];
$loan_id = (int)$_GET['loan_id'];

// Fetch loan request info including borrower and lender
$stmt = $pdo->prepare("SELECT br.*, l.name AS lender_name, b.name AS borrower_name FROM borrow_requests br JOIN users l ON br.approved_by = l.id JOIN users b ON br.borrower_id = b.id WHERE br.id = ?");
$stmt->execute([$loan_id]);
$loan = $stmt->fetch();

if (!$loan) {
    die('Loan not found');
}

// Check if user is borrower or lender for this loan
if (($userRole === 'borrower' && $loan['borrower_id'] !== $userId) && ($userRole === 'lender' && $loan['approved_by'] !== $userId)) {
    die('Unauthorized to sign this contract');
}

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agree'])) {
    if ($userRole === 'borrower') {
        $update = $pdo->prepare("UPDATE borrow_requests SET contract_signed_borrower = 1 WHERE id = ?");
        $update->execute([$loan_id]);
        $message = 'You have signed the contract as borrower.';
    } elseif ($userRole === 'lender') {
        $update = $pdo->prepare("UPDATE borrow_requests SET contract_signed_lender = 1 WHERE id = ?");
        $update->execute([$loan_id]);
        $message = 'You have signed the contract as lender.';
    }
}

// Check signing status
$signedByBorrower = (bool)$loan['contract_signed_borrower'];
$signedByLender = (bool)$loan['contract_signed_lender'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Sign Loan Contract</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
  body { font-family: Arial, sans-serif; max-width: 700px; margin: 30px auto; background: #f9f9f9; padding: 20px; }
  h1 { color: #274bae; }
  .message { margin: 20px 0; padding: 15px; border-radius: 10px; }
  .success { background: #d4edda; color: #155724; }
  .info { background: #cce5ff; color: #004085; }
  button { background: #407dff; color: white; border: none; padding: 12px 20px; font-size: 16px; border-radius: 8px; cursor: pointer; }
  button:hover { background: #305de0; }
  .status { margin-top: 20px; font-weight: 600; }
  pre { background: #e9ecef; padding: 15px; border-radius: 8px; overflow-x: auto; }
</style>
</head>
<body>

<h1>Loan Contract for Loan #<?php echo $loan_id; ?></h1>

<?php if ($message): ?>
  <div class="message success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<h3>Loan Details:</h3>
<ul>
  <li><strong>Borrower:</strong> <?php echo htmlspecialchars($loan['borrower_name']); ?></li>
  <li><strong>Lender:</strong> <?php echo htmlspecialchars($loan['lender_name']); ?></li>
  <li><strong>Amount:</strong> RWF <?php echo number_format($loan['amount']); ?></li>
  <li><strong>Interest Rate:</strong> <?php echo htmlspecialchars($loan['interest_rate']); ?>%</li>
  <li><strong>Duration:</strong> <?php echo htmlspecialchars($loan['duration']); ?> days</li>
  <li><strong>Purpose:</strong> <?php echo htmlspecialchars($loan['purpose']); ?></li>
</ul>

<h3>Contract Terms:</h3>
<pre>
The borrower agrees to repay the loan amount plus interest within the agreed duration.

Late payments will incur a penalty of 5% of the outstanding amount per week.

By signing this contract, both parties agree to these terms.
</pre>

<div class="status">
  <p>Signed by Borrower: <?php echo $signedByBorrower ? '✅ Yes' : '❌ No'; ?></p>
  <p>Signed by Lender: <?php echo $signedByLender ? '✅ Yes' : '❌ No'; ?></p>
</div>

<?php if (($userRole === 'borrower' && !$signedByBorrower) || ($userRole === 'lender' && !$signedByLender)): ?>
  <form method="POST" action="">
    <button type="submit" name="agree">I Agree and Sign Contract</button>
  </form>
<?php else: ?>
  <p class="info">You have already signed this contract.</p>
<?php endif; ?>

<p><a href="<?php echo $userRole === 'borrower' ? 'borrower_dashboard.php' : 'lender_dashboard.php'; ?>">← Back to Dashboard</a></p>

</body>
</html>
