<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require 'config.php'; // Βεβαιωθείτε ότι το αρχείο config.php περιλαμβάνει τη σύνδεση στη βάση δεδομένων.

$message = '';
$message_class = '';

// Ενημέρωση της κατάστασης του χρήστη
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);

    if ($_POST['action'] === 'jail') {
        $jail_days = intval($_POST['jail_days']);
        
        if ($jail_days > 0) {
            // Λήψη της τρέχουσας ημερομηνίας απόλυσης από τη βάση δεδομένων
            $select_query = $conn->prepare("SELECT end_date FROM users WHERE id = ?");
            $select_query->bind_param("i", $user_id);
            $select_query->execute();
            $select_query->bind_result($end_date);
            $select_query->fetch();
            $select_query->close();

            // Υπολογισμός της νέας ημερομηνίας απόλυσης
            $new_end_date = date('Y-m-d', strtotime($end_date . " + $jail_days days"));

            // Ενημέρωση των συνολικών ημερών υπηρεσίας και της ημερομηνίας απόλυσης στη βάση δεδομένων
            $update_query = $conn->prepare("UPDATE users SET total_service_days = total_service_days + ?, end_date = ? WHERE id = ?");
            $update_query->bind_param("isi", $jail_days, $new_end_date, $user_id);

            if ($update_query->execute()) {
                $message = "Οι μέρες φυλάκισης προστέθηκαν με επιτυχία.";
                $message_class = 'success';
                error_log("Jail days successfully added.");
            } else {
                $message = "Σφάλμα κατά την προσθήκη ημερών φυλάκισης: " . $update_query->error;
                $message_class = 'error';
                error_log("Error adding jail days: " . $update_query->error);
            }
            $update_query->close();
        } else {
            $message = "Οι ημέρες φυλάκισης πρέπει να είναι θετικές.";
            $message_class = 'error';
        }
    } elseif ($_POST['action'] === 'honorary_leave') {
        $leave_days = intval($_POST['leave_days']);

        if ($leave_days > 0) {
            // Προετοιμασία και εκτέλεση της ερώτησης
            $update_query = $conn->prepare("UPDATE users SET leave_days = leave_days + ?, approved_leave_days = approved_leave_days + ? WHERE id = ?");
            $update_query->bind_param("iii", $leave_days, $leave_days, $user_id);

            if ($update_query->execute()) {
                $message = "Η τιμητική άδεια προστέθηκε με επιτυχία.";
                $message_class = 'success';
                error_log("Honorary leave successfully added.");
            } else {
                $message = "Σφάλμα κατά την προσθήκη τιμητικής άδειας: " . $update_query->error;
                $message_class = 'error';
                error_log("Error adding honorary leave: " . $update_query->error);
            }
            $update_query->close();
        } else {
            $message = "Οι ημέρες άδειας πρέπει να είναι θετικές.";
            $message_class = 'error';
        }
    }
}

// Λήψη όλων των χρηστών από τη βάση δεδομένων εξαιρώντας τους admin
$users_query = $conn->prepare("SELECT id, fullname, leave_days, total_service_days FROM users WHERE role != 'admin'");
$users_query->execute();
$users_result = $users_query->get_result();
$users_query->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 20px;
        }
        .alert {
            border-radius: 0.25rem;
        }
        .form-actions {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .form-actions .form-control {
            margin-bottom: 10px;
        }
        .form-actions .btn {
            margin-top: 5px;
        }
        @media (min-width: 768px) {
            .form-actions {
                flex-direction: row;
                justify-content: center;
                align-items: flex-end;
            }
            .form-actions .form-control {
                margin-bottom: 0;
                margin-right: 10px;
                max-width: 200px; /* Περιορισμός του πλάτους των inputs */
            }
            .form-actions .btn {
                margin-top: 0;
                margin-left: 10px;
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
        <h2>Manage Users</h2>
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_class; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Leave Days</th>
                    <th>Total Service Days</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = $users_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                        <td><?php echo htmlspecialchars($user['leave_days']); ?></td>
                        <td><?php echo htmlspecialchars($user['total_service_days']); ?></td>
                        <td>
                            <div class="form-actions">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="number" name="jail_days" min="0" value="0" class="form-control" placeholder="Jail Days">
                                    <button type="submit" name="action" value="jail" class="btn btn-danger btn-sm">Jail</button>
                                </form>
                                <form method="POST" class="d-inline mt-2 mt-md-0">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="number" name="leave_days" min="0" value="0" class="form-control" placeholder="Leave Days">
                                    <button type="submit" name="action" value="honorary_leave" class="btn btn-success btn-sm">Honorary Leave</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
