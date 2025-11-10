<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$servername = "localhost";
$username = "root";
$password = "";
$database = "empresacotxes"; // ✅ nombre correcto

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Error de connexió: " . $conn->connect_error);
}
?>
