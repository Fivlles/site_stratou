<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

require 'config.php'; // Βεβαιωθείτε ότι το αρχείο config.php περιλαμβάνει τη σύνδεση στη βάση δεδομένων.

$user_id = $_SESSION['user_id'];
$user_query = $conn->prepare("SELECT total_service_days, end_date, last_update FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_query->bind_result($total_service_days, $end_date, $last_update);
$user_query->fetch();
$user_query->close();

$current_date = new DateTime();
$end_date_obj = new DateTime($end_date);

if ($end_date_obj > $current_date) {
    $days_left = $end_date_obj->diff($current_date)->days;
} else {
    $days_left = 0;
}

// Αφαιρούμε τον έλεγχο για το πέρασμα 24 ωρών, αφού αυτό το διαχειρίζεται το cron job.

$conn->close();
?>
