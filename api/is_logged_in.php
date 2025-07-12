<?php
session_start();
require_once("cors_headers.php");

if (isset($_SESSION['student_id'])) {
    echo json_encode([
        "status" => "success",
        "student_id" => $_SESSION['student_id'],
        "name" => $_SESSION['name']
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
}
?>
