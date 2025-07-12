<?php
session_start();
require_once("cors_headers.php");
require_once("../config/db.php");

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit();
}

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit();
}

$student_id = $_SESSION['student_id'];

// Get form data
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$abstract = trim($_POST['abstract'] ?? '');
$category = trim($_POST['category'] ?? '');
$department = trim($_POST['department'] ?? '');
$year = trim($_POST['year'] ?? '');
$tags = trim($_POST['tags'] ?? '');
$github_link = trim($_POST['github_link'] ?? '');
$demo_url = trim($_POST['demo_url'] ?? '');

// Validate required fields
if (!$title || !$description || !$department || !$year) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit();
}

// Handle multiple file uploads
$file_paths = [];
$project_image_path = null;

// Handle project image upload
if (isset($_FILES['project_image']) && $_FILES['project_image']['error'] === UPLOAD_ERR_OK) {
    $image_upload_dir = '../uploads/project_images/';
    
    // Create directory if it doesn't exist
    if (!file_exists($image_upload_dir)) {
        mkdir($image_upload_dir, 0777, true);
    }
    
    $image_file = $_FILES['project_image'];
    $image_extension = strtolower(pathinfo($image_file['name'], PATHINFO_EXTENSION));
    $allowed_image_extensions = ['jpg', 'jpeg', 'png'];
    $max_image_size = 10 * 1024 * 1024; // 10MB
    
    if (in_array($image_extension, $allowed_image_extensions) && $image_file['size'] <= $max_image_size) {
        $new_image_filename = 'project_img_' . $student_id . '_' . time() . '.' . $image_extension;
        $image_upload_path = $image_upload_dir . $new_image_filename;
        
        if (move_uploaded_file($image_file['tmp_name'], $image_upload_path)) {
            $project_image_path = 'uploads/project_images/' . $new_image_filename;
        }
    }
}
if (isset($_FILES['project_files'])) {
    $upload_dir = '../uploads/projects/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $allowed_extensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'mp4'];
    $max_file_size = 50 * 1024 * 1024; // 50MB
    
    // Handle multiple files
    $files = $_FILES['project_files'];
    if (is_array($files['name'])) {
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $file_extension = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                
                if (in_array($file_extension, $allowed_extensions) && $files['size'][$i] <= $max_file_size) {
                    $new_filename = 'project_' . $student_id . '_' . time() . '_' . $i . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($files['tmp_name'][$i], $upload_path)) {
                        $file_paths[] = [
                            'filename' => $files['name'][$i],
                            'path' => 'uploads/projects/' . $new_filename,
                            'size' => $files['size'][$i],
                            'type' => $file_extension
                        ];
                    }
                }
            }
        }
    } else {
        // Single file upload
        if ($files['error'] === UPLOAD_ERR_OK) {
            $file_extension = strtolower(pathinfo($files['name'], PATHINFO_EXTENSION));
            
            if (in_array($file_extension, $allowed_extensions) && $files['size'] <= $max_file_size) {
                $new_filename = 'project_' . $student_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($files['tmp_name'], $upload_path)) {
                    $file_paths[] = [
                        'filename' => $files['name'],
                        'path' => 'uploads/projects/' . $new_filename,
                        'size' => $files['size'],
                        'type' => $file_extension
                    ];
                }
            }
        }
    }
}

try {
    // Insert project data
    $stmt = $conn->prepare("INSERT INTO projects (student_id, title, description, abstract, category, department, year, tags, github_link, demo_url, project_image, file_paths) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $file_paths_json = json_encode($file_paths);
    
    $stmt->bind_param("isssssssssss", $student_id, $title, $description, $abstract, $category, $department, $year, $tags, $github_link, $demo_url, $project_image_path, $file_paths_json);

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Project uploaded successfully",
            "project_id" => $conn->insert_id,
            "redirect" => "gallery.html"
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to upload project"]);
    }

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>