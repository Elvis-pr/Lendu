<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Connect to the database
    $conn = mysqli_connect("localhost", "root", "", "lendu");

    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    // Collect and sanitize inputs
    $full_name        = trim($_POST['full_name']);
    $student_id       = trim($_POST['student_id']);
    $email            = trim($_POST['email']);
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role             = $_POST['role'];

    // Validate inputs
    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match.'); window.history.back();</script>";
        exit();
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Prepare SQL insert
    $stmt = mysqli_prepare($conn, "INSERT INTO users (full_name, student_id, email, password, role) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssss", $full_name, $student_id, $email, $hashed_password, $role);

    if (mysqli_stmt_execute($stmt)) {
        // Registration success: alert and redirect to login page
        echo "<script>
            alert('Registration successful! You can now log in.');
            window.location.href = 'login.html';  // Update this if your login page is different
        </script>";
    } else {
        // Registration failed: show alert and go back
        echo "<script>
            alert('Registration failed. Username or email might already be taken.');
            window.history.back();
        </script>";
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}
?>
