<?php
session_start();
if (!isset($_SESSION['user_name']) || $_SESSION['role'] !== 'lender') {
    header('Location: login.html');
    exit();
}

require_once 'db.php';

// Check if request ID is provided
if (isset($_GET['id'])) {
    $request_id = intval($_GET['id']);

    // Update status to 'approved'
    $query = "UPDATE borrow_requests SET status = 'approved' WHERE id = $request_id";

    if (mysqli_query($conn, $query)) {
        // Redirect back to borrow requests page
        header("Location: view_borrow_requests.php?approved=success");
        exit();
    } else {
        echo "Error approving request: " . mysqli_error($conn);
    }
} else {
    echo "Invalid request ID.";
}
?>
