<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "leave_system"; // Η βάση δεδομένων που δημιουργήσαμε

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
