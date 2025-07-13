<?php
require_once("cors_headers.php");
require_once("../config/db.php");

try {
    // Get latest 3 projects for the featured section
    $query = "
        SELECT 
            p.project_id,
            p.title,
            p.description,
            p.category,
            p.department,
            p.year,
            p.tags,
            p.project_image,
            p.created_at,
            s.name as author_name,
            s.study_year as author_year,
            s.department as author_department
        FROM projects p 
        JOIN student s ON p.student_id = s.student_id 
        ORDER BY p.created_at DESC
        LIMIT 3
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    $projects = [];
    while ($row = $result->fetch_assoc()) {
        // Convert tags string to array
        if ($row['tags']) {
            $row['tags_array'] = array_map('trim', explode(',', $row['tags']));
        } else {
            $row['tags_array'] = [];
        }
        $projects[] = $row;
    }

    echo json_encode([
        "status" => "success",
        "projects" => $projects
    ]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>