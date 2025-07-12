<?php
$host = 'localhost';
$db = 'webtech_db';
$user = 'root';        // MySQL username
$pass = '';            // password

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
