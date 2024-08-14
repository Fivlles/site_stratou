<?php
require 'config.php'; // Σύνδεση στη βάση δεδομένων

$current_date = new DateTime();

// Ενημέρωση των χρηστών εκτός από τους admin
$update_query = $conn->prepare("
    UPDATE users 
    SET total_service_days = GREATEST(0, total_service_days - 1), last_update = ? 
    WHERE role != 'admin' AND total_service_days > 0
");

$update_query->bind_param("s", $current_date->format('Y-m-d'));
$update_query->execute();
$update_query->close();

$conn->close();
?>