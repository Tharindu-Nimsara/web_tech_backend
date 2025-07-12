<?php
session_start();
require_once("cors_headers.php");
require_once("../config/db.php");

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit();
}

$student_id = $_SESSION['student_id'];

try {
    // Get user profile data
    $stmt = $conn->prepare("SELECT name, email, department, study_year, bio, skills, github_link, website_link, phone_number, location, photo FROM student WHERE student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode([
            "status" => "success",
            "user" => $user
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "User not found"]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Database error"]);
}

$stmt->close();
$conn->close();
