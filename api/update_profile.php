<?php
session_start();
require_once("cors_headers.php");
require_once("../config/db.php");

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit();
}

$student_id = $_SESSION['student_id'];

// Get form data
$bio = trim($_POST['bio'] ?? '');
$skills = trim($_POST['skills'] ?? '');
$github = trim($_POST['github'] ?? '');
$website = trim($_POST['website'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$location = trim($_POST['location'] ?? '');
$study_year = trim($_POST['study_year'] ?? '');

// Handle profile picture upload (optional)
$photo_path = null;
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../uploads/profiles/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_extension = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array(strtolower($file_extension), $allowed_extensions)) {
        $new_filename = 'profile_' . $student_id . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_path)) {
            $photo_path = 'uploads/profiles/' . $new_filename;
        }
    }
}

try {
    // Update profile data
    if ($photo_path) {
            // Update with photo
            $stmt = $conn->prepare("UPDATE student SET bio = ?, skills = ?, github_link = ?, website_link = ?, phone_number = ?, location = ?, study_year = ?, photo = ? WHERE student_id = ?");
            $stmt->bind_param("ssssssssi", $bio, $skills, $github, $website, $phone, $location, $study_year, $photo_path, $student_id);
        } else {
            // Update without photo
            $stmt = $conn->prepare("UPDATE student SET bio = ?, skills = ?, github_link = ?, website_link = ?, phone_number = ?, location = ?, study_year = ? WHERE student_id = ?");
            $stmt->bind_param("sssssssi", $bio, $skills, $github, $website, $phone, $location, $study_year, $student_id);
        }

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Profile updated successfully",
            "redirect" => "profile.html"
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update profile"]);
    }

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>