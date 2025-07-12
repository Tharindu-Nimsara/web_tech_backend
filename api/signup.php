<?php
require_once("cors_headers.php");

// DEBUGGING: Show all errors (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include DB connection
require_once("../config/db.php");

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit();
}

// Get and sanitize input
$name        = trim($_POST['name'] ?? '');
$email       = trim($_POST['email'] ?? '');
$department  = trim($_POST['department'] ?? '');
$study_year  = trim($_POST['study_year'] ?? '');
$password    = $_POST['password'] ?? '';

// Validate required fields
if (empty($name) || empty($email) || empty($department) || empty($study_year) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "All fields are required"]);
    exit();
}

// Check if email already exists
$check = $conn->prepare("SELECT student_id FROM student WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$checkResult = $check->get_result();

if ($checkResult && $checkResult->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Email is already registered"]);
    exit();
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$stmt = $conn->prepare("
    INSERT INTO student (name, email, department, study_year, password_hash)
    VALUES (?, ?, ?, ?, ?)
");

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Prepare failed: " . $conn->error]);
    exit();
}

$stmt->bind_param("sssss", $name, $email, $department, $study_year, $hashedPassword);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Account created successfully"]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Signup failed: " . $stmt->error
    ]);
}

// Close connections
$stmt->close();
$check->close();
$conn->close();
?>
