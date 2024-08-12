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
