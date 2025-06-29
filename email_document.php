<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Adjust path if you installed PHPMailer via composer
include('db_connection.php');

$loan_id = $_GET['loan_id'] ?? null;
if (!$loan_id) {
    die("Loan ID is required.");
}

// Fetch user email (example, adjust table/field names)
$query = $conn->prepare("SELECT b.email AS borrower_email, r.email AS lender_email FROM loans l 
    JOIN users b ON l.borrower_id = b.id 
    JOIN users r ON l.lender_id = r.id
    WHERE l.id = ?");
$query->bind_param("i", $loan_id);
$query->execute();
$result = $query->get_result();
if ($result->num_rows == 0) {
    die("No loan found with the given ID.");
}
$loan = $result->fetch_assoc();

// Generate PDFs temporarily using your existing scripts or functions
// For simplicity, assume your scripts can save PDFs to server without outputting

// Path to generated PDFs
$receipt_pdf = "temp/Lendu_Receipt_{$loan_id}.pdf";
$contract_pdf = "temp/Lendu_Contract_{$loan_id}.pdf";

// TODO: Call your PDF generation code here to save those PDFs to $receipt_pdf and $contract_pdf

// Create PHPMailer instance
$mail = new PHPMailer(true);
try {
    // SMTP settings - adjust for your mail server
    $mail->isSMTP();
    $mail->Host = 'smtp.example.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'your_email@example.com';
    $mail->Password = 'your_password';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('no-reply@yourdomain.com', 'Lendu Platform');
    $mail->addAddress($loan['borrower_email']);
    $mail->addAddress($loan['lender_email']);

    $mail->Subject = "Loan Documents for Loan ID: $loan_id";
    $mail->Body = "Dear user,\n\nPlease find attached the receipt and contract documents for your loan.\n\nRegards,\nLendu Team";

    $mail->addAttachment($receipt_pdf);
    $mail->addAttachment($contract_pdf);

    $mail->send();
    echo "Emails sent successfully.";
} catch (Exception $e) {
    echo "Mailer Error: " . $mail->ErrorInfo;
}
?>
