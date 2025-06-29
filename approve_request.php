<?php
session_start();
require 'db.php';

// Check if user is logged in and is a lender
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lender') {
    header('Location: login.html');
    exit();
}

$lenderId = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    header('Location: lender_dashboard.php?msg=missing_id');
    exit();
}

$loanId = (int)$_GET['id'];

// Get loan info
$stmt = $pdo->prepare("SELECT * FROM borrow_requests WHERE id = ?");
$stmt->execute([$loanId]);
$loan = $stmt->fetch();

if (!$loan) {
    die("Loan request not found.");
}
if ($loan['status'] !== 'pending') {
    die("This loan request is not pending.");
}

$borrowerId = $loan['borrower_id'];

// 1. Approve the loan
$update = $pdo->prepare("UPDATE borrow_requests SET status = 'approved', approved_by = ? WHERE id = ?");
$update->execute([$lenderId, $loanId]);

// 2. Add notification for borrower
$message = "🎉 Your loan request of RWF " . number_format($loan['amount']) . " has been approved.";
$notif = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
$notif->execute([$borrowerId, $message]);

// 3. Redirect back
header('Location: lender_dashboard.php?msg=approved');
exit();
