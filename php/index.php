<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

require 'config.php'; // Βεβαιωθείτε ότι το αρχείο config.php περιλαμβάνει τη σύνδεση στη βάση δεδομένων.

$user_id = $_SESSION['user_id'];
$user_query = $conn->prepare("SELECT total_service_days, end_date, last_update, leave_days, approved_leave_days FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_query->bind_result($total_service_days, $end_date, $last_update, $leave_days, $approved_leave_days);
$user_query->fetch();
$user_query->close();

$current_date = new DateTime();
$end_date_obj = new DateTime($end_date);

if ($end_date_obj > $current_date) {
    $days_left = $end_date_obj->diff($current_date)->days;
} else {
    $days_left = 0;
}

$last_update_date = new DateTime($last_update);
$interval = $last_update_date->diff($current_date);
$hours_passed = $interval->days * 24 + $interval->h;

if ($hours_passed >= 24) {
    $new_total_service_days = max(0, $total_service_days - 1);
    $update_query = $conn->prepare("UPDATE users SET total_service_days = ?, last_update = ? WHERE id = ?");
    $update_query->bind_param("isi", $new_total_service_days, $current_date->format('Y-m-d'), $user_id);
    $update_query->execute();
    $update_query->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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
                <?php if (isset($_SESSION['username']) && $_SESSION['role'] == 'user'): ?>
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
                <?php if (isset($_SESSION['username']) && $_SESSION['role'] == 'admin'): ?>
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
                <?php endif; ?>
                <?php if (isset($_SESSION['username'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    <div class="container mt-4">
        <div class="jumbotron">
            <h2 class="display-4">Welcome to the Leave System</h2>
            <?php if (isset($_SESSION['username'])): ?>
                <?php if ($_SESSION['role'] == 'user'): ?>
                    <hr class="my-4">
                    <p>Total Service Days: <strong><?php echo htmlspecialchars($total_service_days); ?></strong></p>
                    <p>End Date: <strong><?php echo htmlspecialchars($end_date); ?></strong></p>
                    <p>Leave Days: <strong><?php echo htmlspecialchars($leave_days); ?></strong></p>
                    <p>Remaining Days: <strong><?php echo htmlspecialchars($approved_leave_days); ?></strong></p>
                <?php endif; ?>
            <?php else: ?>
                <p>Please log in or register to use the system.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap και jQuery αρχεία για την λειτουργία του Navbar -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
