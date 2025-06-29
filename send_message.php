<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.html");
    exit();
}

$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];
$messageText = trim($_POST['message'] ?? '');
$offerId = (int) ($_POST['offer_id'] ?? 0);

if ($messageText === '' || $offerId <= 0) {
    die('❌ Message and offer ID are required.');
}

if ($role === 'borrower') {
    $lenderId = (int) ($_POST['lender_id'] ?? 0);
    if ($lenderId <= 0) {
        die('❌ Invalid lender ID.');
    }

    $stmt = $pdo->prepare("INSERT INTO offer_messages (offer_id, borrower_id, lender_id, message, sender_role, sent_at)
                           VALUES (?, ?, ?, ?, 'borrower', NOW())");
    $stmt->execute([$offerId, $userId, $lenderId, $messageText]);

    header("Location: borrower_dashboard.php");
    exit();

} elseif ($role === 'lender') {
    $borrowerId = (int) ($_POST['borrower_id'] ?? 0);
    if ($borrowerId <= 0) {
        die('❌ Invalid borrower ID.');
    }

    $stmt = $pdo->prepare("INSERT INTO offer_messages (offer_id, borrower_id, lender_id, message, sender_role, sent_at)
                           VALUES (?, ?, ?, ?, 'lender', NOW())");
    $stmt->execute([$offerId, $borrowerId, $userId, $messageText]);

    header("Location: lender_dashboard.php");
    exit();

} else {
    die('❌ Unknown role.');
}
?>
