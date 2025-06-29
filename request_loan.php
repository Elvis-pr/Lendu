<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'borrower') {
    header('Location: login.html');
    exit();
}

$borrowerId = $_SESSION['user_id'];
$borrowerName = $_SESSION['name'] ?? 'Borrower';

// Ensure the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['offer_id'])) {
    $offerId = (int) $_POST['offer_id'];

    // Fetch the loan offer
    $offerStmt = $pdo->prepare("SELECT * FROM loan_offers WHERE id = ? AND status = 'active'");
    $offerStmt->execute([$offerId]);
    $offer = $offerStmt->fetch();

    if (!$offer) {
        $_SESSION['error'] = '❌ The selected loan offer is no longer available.';
        header("Location: borrower_dashboard.php");
        exit();
    }

    // Check if the borrower has already requested this offer
    $check = $pdo->prepare("SELECT COUNT(*) FROM borrow_requests WHERE borrower_id = ? AND offer_id = ? AND status = 'pending'");
    $check->execute([$borrowerId, $offerId]);
    $exists = $check->fetchColumn();

    if ($exists) {
        $_SESSION['error'] = '⚠️ You have already requested this loan offer. Please wait for a response.';
        header("Location: borrower_dashboard.php");
        exit();
    }

    // Insert the new borrow request
    $stmt = $pdo->prepare("INSERT INTO borrow_requests (borrower_id, borrower_name, amount, purpose, duration, status, request_date, offer_id) VALUES (?, ?, ?, ?, ?, 'pending', NOW(), ?)");
    $stmt->execute([
        $borrowerId,
        $borrowerName,
        $offer['amount'],
        'Requested from lender offer',
        $offer['duration'],
        $offerId
    ]);

    // Optional: Add notification for lender
    $notif = $pdo->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
    $notif->execute([
        $offer['lender_id'],
        "📬 $borrowerName has requested your loan offer of " . number_format($offer['amount']) . " RWF."
    ]);

    $_SESSION['success'] = '✅ Loan request submitted successfully!';
    header("Location: borrower_dashboard.php");
    exit();
} else {
    $_SESSION['error'] = '⚠️ Invalid request method.';
    header("Location: borrower_dashboard.php");
    exit();
}
