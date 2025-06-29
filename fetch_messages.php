<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'], $_GET['borrower_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];
$borrowerId = (int)$_GET['borrower_id'];

// Identify lender_id and borrower_id correctly
if ($role === 'lender') {
    $lenderId = $userId;
} else if ($role === 'borrower') {
    $lenderId = (int)$_GET['lender_id'];
} else {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Fetch all messages between lender and borrower ordered by date
$stmt = $pdo->prepare("SELECT sender_role, message, sent_at FROM offer_messages WHERE lender_id = ? AND borrower_id = ? ORDER BY sent_at ASC");
$stmt->execute([$lenderId, $borrowerId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['messages' => $messages]);
