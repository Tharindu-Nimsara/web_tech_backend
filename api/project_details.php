<?php
require_once("cors_headers.php");
require_once("../config/db.php");

// Get project ID from URL parameter
$project_id = $_GET['id'] ?? '';

if (empty($project_id)) {
    echo json_encode(["status" => "error", "message" => "Project ID is required"]);
    exit();
}

try {
    // Get project details with author information
    $query = "
        SELECT 
            p.project_id,
            p.title,
            p.description,
            p.abstract,
            p.category,
            p.department as project_department,
            p.year as project_year,
            p.tags,
            p.github_link,
            p.demo_url,
            p.project_image,
            p.file_paths,
            p.created_at,
            s.name as author_name,
            s.email as author_email,
            s.department as author_department,
            s.study_year as author_year,
            s.photo as author_photo
        FROM projects p 
        JOIN student s ON p.student_id = s.student_id 
        WHERE p.project_id = ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $project = $result->fetch_assoc();
        
        // Convert tags string to array
        if ($project['tags']) {
            $project['tags_array'] = array_map('trim', explode(',', $project['tags']));
        } else {
            $project['tags_array'] = [];
        }
        
        // Parse file paths JSON if exists
        if ($project['file_paths']) {
            $project['file_paths'] = json_decode($project['file_paths'], true);
        } else {
            $project['file_paths'] = [];
        }

        echo json_encode([
            "status" => "success",
            "project" => $project
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Project not found"]);
    }

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>