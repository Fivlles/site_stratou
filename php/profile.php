<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

require 'config.php';

// Πληροφορίες χρήστη
$user_id = $_SESSION['user_id'];

// Υπολειπόμενες μέρες άδειας
$stmt = $conn->prepare("SELECT leave_days FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($leave_days);
$stmt->fetch();
$stmt->close();

// Αιτήματα άδειας
$stmt = $conn->prepare("SELECT start_date, end_date, status FROM leave_requests WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$leave_requests = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Υπολογισμός εγκεκριμένων μερών άδειας
$approved_leave_days = 0;
foreach ($leave_requests as $request) {
    if ($request['status'] === 'approved' || $request['status'] === 'pending') {
        $start = new DateTime($request['start_date']);
        $end = new DateTime($request['end_date']);
        $interval = $start->diff($end);
        $approved_leave_days += $interval->days + 1; // προσθέτουμε 1 για την ημέρα
    }
}

$remaining_leave_days = $leave_days - $approved_leave_days;

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 20px;
        }
        .profile-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .approved {
            color: green;
        }
        .rejected {
            color: red;
        }
        .table-container {
            margin-top: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 15px;
        }
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            .profile-card {
                padding: 15px;
                box-shadow: none;
            }
            .table-container {
                padding: 10px;
            }
            table {
                font-size: 14px;
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
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_panel.php">
                            <i class="fas fa-cog"></i> Admin Panel
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="request_leave.php">
                            <i class="fas fa-envelope"></i> Request Leave
                        </a>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </nav>
    <div class="container profile-card">
        <h1>Profile</h1>
        <p>Leave days: <?php echo htmlspecialchars($leave_days); ?></p>
        <p>Remaining leave days: <?php echo htmlspecialchars($remaining_leave_days); ?></p>
        <div class="table-container">
            <h3>Leave Requests</h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leave_requests as $request): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['start_date']); ?></td>
                            <td><?php echo htmlspecialchars($request['end_date']); ?></td>
                            <td class="<?php echo $request['status'] === 'approved' ? 'approved' : ($request['status'] === 'rejected' ? 'rejected' : ''); ?>">
                                <?php echo ucfirst($request['status']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
