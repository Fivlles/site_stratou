<?php
require 'config.php';

$message = "";  // Δημιουργία μεταβλητής για τα μηνύματα

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $fullname = $_POST['fullname'];
    $months = intval($_POST['months']);  // Διασφαλίστε ότι ο αριθμός μηνών είναι ακέραιος
    $start_date = $_POST['start_date'];

    // Κρυπτογραφούμε τον κωδικό πριν τον αποθηκεύσουμε στη βάση δεδομένων
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Υπολογισμός της ημερομηνίας λήξης
    $end_date = date('Y-m-d', strtotime("+$months months", strtotime($start_date)));

    // Υπολογισμός των συνολικών ημερών θητείας
    $total_service_days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
    $total_service_days = round($total_service_days);  // Στρογγυλοποίηση των ημερών

    // Υπολογισμός των ημερών άδειας (2 μέρες άδειας για κάθε μήνα υπηρεσίας)
    $leave_days = $months * 2;

    $last_update = date('Y-m-d'); 

    // Προετοιμασία ερωτήματος για εισαγωγή νέου χρήστη
    $stmt = $conn->prepare("INSERT INTO users (username, password, total_service_days, start_date, end_date, leave_days, approved_leave_days, last_update, fullname) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssissisis", $username, $password_hash, $total_service_days, $start_date, $end_date, $leave_days, $leave_days, $last_update, $fullname);

    // Εκτέλεση του ερωτήματος
    if ($stmt->execute()) {
        $message = "<div class='alert alert-success mt-3'>Εγγραφή επιτυχής!<br>Ημερομηνία Έναρξης: $start_date<br>Ημερομηνία Λήξης: $end_date<br>Συνολικές Ημέρες Υπηρεσίας: $total_service_days<br>Ημέρες Άδειας: $leave_days</div>";
    } else {
        $message = "<div class='alert alert-danger mt-3'>Υπήρξε κάποιο σφάλμα κατά την εγγραφή.</div>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
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
                <li class="nav-item">
                    <a class="nav-link" href="login.php">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                </li>
            </ul>
        </div>
    </nav>
    <div class="container mt-4">
        <h2>Register</h2>
        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" class="form-control" id="username" placeholder="Username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" class="form-control" id="password" placeholder="Password" required>
            </div>
            <div class="form-group">
                <label for="fullname">Full Name</label>
                <input type="text" name="fullname" class="form-control" id="fullname" placeholder="Full Name" required>
            </div>
            <div class="form-group">
                <label for="months">Months of Service</label>
                <select name="months" class="form-control" id="months" required>
                    <option value="6">6</option>
                    <option value="9">9</option>
                    <option value="12">12</option>
                </select>
            </div>
            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="date" name="start_date" class="form-control" id="start_date" placeholder="Start Date" required>
            </div>
            <button type="submit" class="btn btn-primary">Register</button>
            <?php if (!empty($message)) echo $message; ?>  <!-- Εμφάνιση μηνύματος κάτω από το κουμπί -->
        </form>
    </div>

    <!-- Προσθήκη των αρχείων jQuery και Bootstrap JavaScript για το navbar -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
