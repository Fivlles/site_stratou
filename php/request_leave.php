<?php
session_start();
require 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$message = "";

if (isset($_POST['submit'])) {
    $user_id = $_SESSION['user_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // Εκτέλεση ερωτήματος SQL για τις εγκεκριμένες ημέρες άδειας
    $sql = "SELECT approved_leave_days FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $approved_leave_days);
    mysqli_stmt_fetch($stmt);

    // Υπολογισμός των ημερών άδειας που ζητάει ο χρήστης
    $total_seconds = strtotime($end_date) - strtotime($start_date);
    $total_days = floor($total_seconds / (60 * 60 * 24)) + 1; // Προσθέτουμε 1 για να συμπεριληφθεί και η τελευταία ημέρα
    $remaining_days = $approved_leave_days - $total_days;

    mysqli_stmt_close($stmt);

    // Έλεγχος αν οι υπόλοιπες ημέρες άδειας είναι αρνητικές
    if ($remaining_days >= 0) {
        // Προσθήκη αίτησης άδειας σε εκκρεμότητα
        $stmt2 = mysqli_prepare($conn, "INSERT INTO leave_requests (user_id, start_date, end_date, status) VALUES (?, ?, ?, 'pending')");
        mysqli_stmt_bind_param($stmt2, "iss", $user_id, $start_date, $end_date);

        if (mysqli_stmt_execute($stmt2)) {
            // Ενημέρωση του εγκεκριμένων ημερών άδειας
            $stmt3 = mysqli_prepare($conn, "UPDATE users SET approved_leave_days = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt3, "ii", $remaining_days, $user_id);
            mysqli_stmt_execute($stmt3);
            mysqli_stmt_close($stmt3);

            $message = "<div class='alert alert-success'>Leave request submitted successfully.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error submitting leave request: " . mysqli_error($conn) . "</div>";
        }
        mysqli_stmt_close($stmt2);
    } else {
        $message = "<div class='alert alert-danger'>Your remaining leave days are not enough.</div>";
    }

    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Leave</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 20px;
        }
        .form-control {
            border-radius: 0.25rem;
        }
        .alert {
            border-radius: 0.25rem;
        }
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            .form-control {
                font-size: 14px;
            }
            .alert {
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
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Request Leave</h1>
        <div id="message">
            <?php if (!empty($message)) echo $message; ?>
        </div>
        <form id="leaveForm" method="POST" action="" onsubmit="return validateDates();">
            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="date" name="start_date" class="form-control" id="start_date" required>
            </div>
            <div class="form-group">
                <label for="end_date">End Date</label>
                <input type="date" name="end_date" class="form-control" id="end_date" required>
            </div>
            <button type="submit" class="btn btn-primary" name="submit">Submit</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var today = new Date().toISOString().split('T')[0];
            document.getElementById('start_date').setAttribute('min', today);
            document.getElementById('end_date').setAttribute('min', today);
        });

        function validateDates() {
            var startDate = document.getElementById('start_date').value;
            var endDate = document.getElementById('end_date').value;
            var messageDiv = document.getElementById('message');

            // Clear previous message
            messageDiv.innerHTML = '';

            if (endDate < startDate) {
                var errorMessage = document.createElement('div');
                errorMessage.className = 'alert alert-danger';
                errorMessage.textContent = 'End date cannot be before start date.';
                messageDiv.appendChild(errorMessage);
                return false;
            }
            return true;
        }
    </script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
