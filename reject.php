<?php
session_start();
require 'db.php';

// Check logged-in lender
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lender') {
    die("❌ Access denied. You're not a logged-in lender.");
}

if (!isset($_GET['id'])) {
    die("❌ Invalid request: missing loan ID.");
}

$loanId = (int)$_GET['id'];

// Check if loan exists and is pending
$stmt = $pdo->prepare("SELECT * FROM borrow_requests WHERE id = ?");
$stmt->execute([$loanId]);
$loan = $stmt->fetch();

if (!$loan) {
    die("❌ Loan request with ID $loanId not found.");
}

if ($loan['status'] !== 'pending') {
    die("⚠️ This loan request is not pending (current status: " . htmlspecialchars($loan['status']) . ").");
}

// Update loan status to rejected
$update = $pdo->prepare("UPDATE borrow_requests SET status = 'rejected' WHERE id = ?");
$success = $update->execute([$loanId]);

if ($success) {
    // Insert notification for borrower
    $notif = $pdo->prepare("INSERT INTO notifications (user_id, message, created_at, is_read) VALUES (?, ?, NOW(), 0)");
    $message = "Your loan request #$loanId has been rejected by the lender.";
    $notif->execute([$loan['borrower_id'], $message]);

    // Redirect back with success message in session
    $_SESSION['flash_message'] = "❌ Loan request #$loanId rejected successfully.";
    header("Location: lender_dashboard.php");
    exit();
} else {
    die("⚠️ Failed to update loan request status.");
}
