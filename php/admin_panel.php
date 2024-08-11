<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

require 'config.php';

$message = "";

// Αν έχει πατηθεί έγκριση ή απόρριψη αιτήματος
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['request_id'])) {
    $action = $_POST['action'];
    $request_id = $_POST['request_id'];

    // Αναζήτηση του αιτήματος για να λάβουμε τις ημερομηνίες και το user_id
    $stmt = $conn->prepare("SELECT user_id, start_date, end_date FROM leave_requests WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $stmt->bind_result($user_id, $start_date, $end_date);
    $stmt->fetch();
    $stmt->close();

    // Υπολογισμός των ημερών άδειας που ζητήθηκαν
    $total_seconds = strtotime($end_date) - strtotime($start_date);
    $total_days = floor($total_seconds / (60 * 60 * 24)) + 1; // +1 για να περιλάβουμε και την τελική ημέρα

    // Εκτέλεση της ενέργειας
    if ($action === 'approve') {
        $status = 'approved';
    } elseif ($action === 'reject') {
        $status = 'rejected';

        // Επαναφορά των εγκεκριμένων ημερών άδειας του χρήστη
        $stmt = $conn->prepare("UPDATE users SET approved_leave_days = approved_leave_days + ? WHERE id = ?");
        $stmt->bind_param("ii", $total_days, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    // Ενημέρωση της κατάστασης της αίτησης
    $stmt = $conn->prepare("UPDATE leave_requests SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $request_id);
    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>Request successfully " . ($action === 'approve' ? "approved" : "rejected") . ".</div>";
    } else {
        $message = "<div class='alert alert-danger'>Error updating request: " . $conn->error . "</div>";
    }
    $stmt->close();
}

// Επιλογή όλων των αιτημάτων άδειας που είναι σε κατάσταση 'pending' με το fullname του χρήστη
$stmt = $conn->prepare("
    SELECT l.id, u.fullname, l.start_date, l.end_date, l.status
    FROM leave_requests l
    JOIN users u ON l.user_id = u.id
    WHERE l.status = 'pending'
");
$stmt->execute();
$result = $stmt->get_result();
$leave_requests = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 20px;
        }
        .table {
            margin-top: 20px;
        }
        .alert {
            border-radius: 0.25rem;
        }
        .action-buttons {
            display: flex;
            gap: 10px; /* Επαρκής απόσταση μεταξύ των κουμπιών */
            flex-wrap: nowrap; /* Διατήρηση των κουμπιών σε μία γραμμή */
        }
        .action-buttons button {
            flex: 1; /* Κάνει τα κουμπιά να γεμίζουν το διαθέσιμο πλάτος */
        }
        .action-row {
            display: table-row; /* Εμφάνιση των κουμπιών σε μικρές οθόνες */
        }
        @media (max-width: 768px) {
            .action-cell {
                display: none; /* Κρύψε το κελί δράσης σε μικρές οθόνες */
            }
            .action-buttons {
                flex-wrap: wrap; /* Επέτρεψε στα κουμπιά να τυλίγονται σε νέα γραμμή αν χρειάζεται */
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">Leave System</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-home"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_panel.php">
                        <i class="fas fa-cog"></i> Admin Panel
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_users.php">
                        <i class="fas fa-users"></i> Manage Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="all_leave_requests.php">
                        <i class="fas fa-envelope"></i> All Approved Requests
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </nav>
    <div class="container mt-4">
        <h3>Pending Leave Requests</h3>
        <div id="message">
            <?php if (!empty($message)) echo $message; ?>
        </div>
        <?php if (empty($leave_requests)): ?>
            <p>No pending leave requests.</p>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leave_requests as $request): ?>
                        <tr>
                            <td class="user-info">
                                <?php echo htmlspecialchars($request['fullname']); ?>
                            </td>
                            <td class="user-info">
                                <?php echo htmlspecialchars($request['start_date']); ?>
                            </td>
                            <td class="user-info">
                                <?php echo htmlspecialchars($request['end_date']); ?>
                            </td>
                            <td class="user-info">
                                <?php echo ucfirst(htmlspecialchars($request['status'])); ?>
                            </td>
                            <td class="action-cell">
                                <!-- Κελί δράσης κρυμμένο σε μικρές οθόνες -->
                            </td>
                        </tr>
                        <tr class="action-row">
                            <td colspan="5">
                                <form method="POST" action="admin_panel.php" class="action-buttons">
                                    <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['id']); ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-success">Approve</button>
                                    <button type="submit" name="action" value="reject" class="btn btn-danger">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- JavaScript includes -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
