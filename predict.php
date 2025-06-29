<?php
header('Content-Type: application/json');
require '../db.php';

$input = json_decode(file_get_contents('php://input'), true);

// Validate input
$required = ['student_id', 'gpa', 'attendance', 'previous_loans'];
foreach ($required as $field) {
    if (!isset($input[$field])) {
        http_response_code(400);
        die(json_encode(['error' => "Missing $field"]));
    }
}

// Prepare data for Python
$data = [
    'gpa' => (float)$input['gpa'],
    'attendance' => (int)$input['attendance'],
    'previous_loans' => (int)$input['previous_loans']
];

// Execute Python script
$command = escapeshellcmd("python3 ../ai/risk_assessment.py '" . json_encode($data) . "'");
$output = shell_exec($command);

if ($output) {
    echo $output;
} else {
    http_response_code(500);
    echo json_encode(['error' => 'AI prediction failed']);
}