<?php
require 'config.php'; // Βεβαιωθείτε ότι το αρχείο config.php περιλαμβάνει τη σύνδεση στη βάση δεδομένων.

$current_date = new DateTime(); // Τρέχουσα ημερομηνία

// Ενημέρωση των χρηστών
$update_query = $conn->prepare("UPDATE users SET total_service_days = total_service_days - 1 WHERE end_date >= ?");
$update_query->bind_param("s", $current_date->format('Y-m-d'));
$update_query->execute();
$update_query->close();

$conn->close();
?>
