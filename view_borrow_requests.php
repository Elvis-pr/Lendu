<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lender') {
    header('Location: login.html');
    exit();
}

$lenderId = $_SESSION['user_id'];
$lenderName = $_SESSION['user_name'];

// Function to check if column exists
function columnExists($pdo, $table, $column) {
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM $table LIKE ?");
        $stmt->execute([$column]);
        return ($stmt->rowCount() > 0);
    } catch (PDOException $e) {
        error_log("Column check error: " . $e->getMessage());
        return false;
    }
}

// Handle approve/reject actions
if (isset($_GET['action'], $_GET['id'])) {
    $requestId = (int)$_GET['id'];
    $action = $_GET['action'];

    try {
        // Check if columns exist
        $hasApprovedBy = columnExists($pdo, 'borrow_requests', 'approved_by');
        $hasApprovedAt = columnExists($pdo, 'borrow_requests', 'approved_at');

        if ($action === 'approve') {
            if ($hasApprovedBy && $hasApprovedAt) {
                $update = $pdo->prepare("UPDATE borrow_requests SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
                $update->execute([$lenderName, $requestId]);
            } elseif ($hasApprovedBy) {
                $update = $pdo->prepare("UPDATE borrow_requests SET status = 'approved', approved_by = ? WHERE id = ?");
                $update->execute([$lenderName, $requestId]);
            } else {
                $update = $pdo->prepare("UPDATE borrow_requests SET status = 'approved' WHERE id = ?");
                $update->execute([$requestId]);
            }
        } elseif ($action === 'reject') {
            if ($hasApprovedBy && $hasApprovedAt) {
                $update = $pdo->prepare("UPDATE borrow_requests SET status = 'rejected', approved_by = ?, approved_at = NOW() WHERE id = ?");
                $update->execute([$lenderName, $requestId]);
            } elseif ($hasApprovedBy) {
                $update = $pdo->prepare("UPDATE borrow_requests SET status = 'rejected', approved_by = ? WHERE id = ?");
                $update->execute([$lenderName, $requestId]);
            } else {
                $update = $pdo->prepare("UPDATE borrow_requests SET status = 'rejected' WHERE id = ?");
                $update->execute([$requestId]);
            }
        }

        header("Location: view_borrow_requests.php");
        exit();
    } catch (PDOException $e) {
        die("Error processing request: " . $e->getMessage());
    }
}

// Fetch all pending requests
try {
    $stmt = $pdo->query("SELECT * FROM borrow_requests WHERE status = 'pending' ORDER BY request_date DESC");
    $pending = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching requests: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Borrow Requests - Lendu</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <style>
        /* Reset and base */
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Montserrat', sans-serif;
            background: #e9efff;
            padding: 40px 20px;
            color: #34495e;
            margin: 0;
        }
        h2 {
            color: #2c3e50;
            font-weight: 700;
            text-align: center;
            margin-bottom: 30px;
            letter-spacing: 0.05em;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            box-shadow: 0 10px 25px rgba(50, 50, 93, 0.1);
            border-radius: 12px;
            overflow: hidden;
        }
        thead tr {
            background: #407dff;
            color: white;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 0.1em;
        }
        th, td {
            padding: 16px 20px;
            border-bottom: 1px solid #e1e8ff;
            text-align: left;
            vertical-align: middle;
        }
        tbody tr:hover {
            background: #f0f5ff;
        }
        tbody tr:last-child td {
            border-bottom: none;
        }
        .action-btn {
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            min-width: 80px;
            text-align: center;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            user-select: none;
            border: none;
        }
        .approve {
            background-color: #28a745;
            color: #fff;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
            border: 1px solid #28a745;
        }
        .approve:hover {
            background-color: #218838;
            box-shadow: 0 6px 18px rgba(33, 136, 56, 0.5);
        }
        .reject {
            background-color: #dc3545;
            color: #fff;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
            border: 1px solid #dc3545;
        }
        .reject:hover {
            background-color: #bd2130;
            box-shadow: 0 6px 18px rgba(189, 33, 48, 0.5);
        }
        .back-btn {
            display: block;
            width: fit-content;
            margin: 30px auto 0 auto;
            background: #407dff;
            color: white;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            text-decoration: none;
            box-shadow: 0 5px 15px rgba(64,125,255,0.5);
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
        }
        .back-btn:hover {
            background: #305de0;
            box-shadow: 0 8px 22px rgba(48,93,224,0.6);
        }
        /* Responsive */
        @media (max-width: 700px) {
            th, td {
                padding: 12px 10px;
                font-size: 13px;
            }
            .action-btn {
                padding: 8px 12px;
                font-size: 12px;
                min-width: 70px;
            }
            .back-btn {
                padding: 12px 24px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

<h2>📄 Pending Borrow Requests</h2>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Borrower</th>
            <th>Amount</th>
            <th>Purpose</th>
            <th>Duration</th>
            <th>Requested On</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!empty($pending)): ?>
        <?php foreach ($pending as $req): ?>
        <tr>
            <td><?= $req['id'] ?></td>
            <td><?= htmlspecialchars($req['borrower_name']) ?></td>
            <td><?= number_format($req['amount']) ?> RWF</td>
            <td><?= htmlspecialchars($req['purpose']) ?></td>
            <td><?= $req['duration'] ?> days</td>
            <td><?= $req['request_date'] ?></td>
            <td>
                <a class="action-btn approve" href="?action=approve&id=<?= $req['id'] ?>">Approve</a>
                <a class="action-btn reject" href="?action=reject&id=<?= $req['id'] ?>">Reject</a>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="7" style="text-align:center; padding: 20px;">No pending requests</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<a href="lender_dashboard.php" class="back-btn">⬅️ Return to Dashboard</a>

</body>
</html>