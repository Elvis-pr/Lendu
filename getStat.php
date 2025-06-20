<?php
// getStats.php
header('Content-Type: application/json');
require 'db.php'; // Include your DB connection file

$response = [
    'users' => 0,
    'loans' => 0,
    'repaymentRate' => '0%'
];

// Get total users
$result1 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
if ($result1 && $row = mysqli_fetch_assoc($result1)) {
    $response['users'] = $row['total'];
}

// Get total loans
$result2 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM loans");
if ($result2 && $row = mysqli_fetch_assoc($result2)) {
    $response['loans'] = $row['total'];
}

// Calculate repayment rate (e.g., loans marked as repaid)
$result3 = mysqli_query($conn, "SELECT 
    (SUM(CASE WHEN status = 'repaid' THEN 1 ELSE 0 END) / COUNT(*)) * 100 AS rate 
    FROM loans");

if ($result3 && $row = mysqli_fetch_assoc($result3)) {
    $response['repaymentRate'] = round($row['rate']) . '%';
}

echo json_encode($response);
?>
