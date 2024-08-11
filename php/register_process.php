<?php
// Σύνδεση με τη βάση δεδομένων
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "leave_system";

$conn = new mysqli($servername, $username, $password, $dbname);

// Έλεγχος σύνδεσης
if ($conn->connect_error) {
    die("Σφάλμα σύνδεσης με τη βάση δεδομένων: " . $conn->connect_error);
}

// Λήψη δεδομένων από τη φόρμα
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Προετοιμασία εντολής SQL για εισαγωγή δεδομένων
    $sql = "INSERT INTO users (username, password) VALUES ('$username', '$password')";

    if ($conn->query($sql) === TRUE) {
    } else {
        echo "Σφάλμα κατά την εγγραφή στη βάση δεδομένων: " . $conn->error;
    }
} else {
    echo "Σφάλμα: Μη έγκυρη πρόσβαση στη σελίδα.";
}

// Κλείσιμο σύνδεσης με τη βάση δεδομένων
$conn->close();
?>
