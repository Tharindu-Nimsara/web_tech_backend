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
    // Get user's project statistics
    $stats_query = "SELECT COUNT(*) as project_count FROM projects WHERE student_id = ?";
    $stats_stmt = $conn->prepare($stats_query);
    $stats_stmt->bind_param("i", $student_id);
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result();
    $stats = $stats_result->fetch_assoc();

    // Get user's projects
    $projects_query = "
        SELECT 
            project_id,
            title,
            description,
            category,
            department,
            year,
            tags,
            project_image,
            github_link,
            demo_url,
            created_at
        FROM projects 
        WHERE student_id = ?
        ORDER BY created_at DESC
    ";
    
    $projects_stmt = $conn->prepare($projects_query);
    $projects_stmt->bind_param("i", $student_id);
    $projects_stmt->execute();
    $projects_result = $projects_stmt->get_result();

    $projects = [];
    while ($row = $projects_result->fetch_assoc()) {
        // Convert tags string to the array
        if ($row['tags']) {
            $row['tags_array'] = array_map('trim', explode(',', $row['tags']));
        } else {
            $row['tags_array'] = [];
        }
        $projects[] = $row;
    }

    echo json_encode([
        "status" => "success",
        "stats" => $stats,
        "projects" => $projects
    ]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}

$stats_stmt->close();
$projects_stmt->close();
$conn->close();
?>