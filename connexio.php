<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$servername = "hl1529.dinaserver.com";
$username = "polgu_";
$password = "BNqot747$3{.";
$database = "empresacotxes"; // ✅ nombre correcto

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Error de connexió: " . $conn->connect_error);
}
?>
