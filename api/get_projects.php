<?php
require_once("cors_headers.php");
require_once("../config/db.php");

// Get optional filters
$department = $_GET['department'] ?? '';
$year = $_GET['year'] ?? '';
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$count_only = isset($_GET['count_only']) && $_GET['count_only'] === 'true';

try {
    // Build query with the filters
    $where_conditions = ["p.status = 'published'"];
    $params = [];
    $param_types = "";

    if (!empty($department)) {
        $where_conditions[] = "p.department = ?";
        $params[] = $department;
        $param_types .= "s";
    }

    if (!empty($year)) {
        $where_conditions[] = "p.year = ?";
        $params[] = $year;
        $param_types .= "s";
    }

    if (!empty($category)) {
        $where_conditions[] = "p.category = ?";
        $params[] = $category;
        $param_types .= "s";
    }

    if (!empty($search)) {
        $where_conditions[] = "(p.title LIKE ? OR p.description LIKE ? OR p.tags LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $param_types .= "sss";
    }

    $where_clause = implode(" AND ", $where_conditions);

    // If only count is requested
    if ($count_only) {
        $count_query = "
            SELECT COUNT(*) as total 
            FROM projects p 
            JOIN student s ON p.student_id = s.student_id 
            WHERE $where_clause
        ";
        
        $count_stmt = $conn->prepare($count_query);
        if (!empty($param_types) && !empty($params)) {
            $count_stmt->bind_param($param_types, ...$params);
        }
        
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total_count = $count_result->fetch_assoc()['total'];

        echo json_encode([
            "status" => "success",
            "total" => $total_count
        ]);
        
        $count_stmt->close();
        $conn->close();
        exit();
    }

    // Get projects with author information
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
            s.photo as author_photo
        FROM projects p 
        JOIN student s ON p.student_id = s.student_id 
        WHERE $where_clause
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ";

    $stmt = $conn->prepare($query);
    
    // Add limit and offset parameters
    $params[] = $limit;
    $params[] = $offset;
    $param_types .= "ii";

    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $projects = [];
    while ($row = $result->fetch_assoc()) {
        // Convert tags string to array
        if ($row['tags']) {
            $row['tags_array'] = explode(',', $row['tags']);
        } else {
            $row['tags_array'] = [];
        }
        $projects[] = $row;
    }

    // Get total count for pagination
    $count_query = "
        SELECT COUNT(*) as total 
        FROM projects p 
        JOIN student s ON p.student_id = s.student_id 
        WHERE $where_clause
    ";
    
    $count_stmt = $conn->prepare($count_query);
    if (!empty($param_types) && !empty($params)) {
        // Remove limit and offset parameters for count query
        $count_params = array_slice($params, 0, -2);
        $count_param_types = substr($param_types, 0, -2);
        if (!empty($count_params)) {
            $count_stmt->bind_param($count_param_types, ...$count_params);
        }
    }
    
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_count = $count_result->fetch_assoc()['total'];

    echo json_encode([
        "status" => "success",
        "projects" => $projects,
        "total" => $total_count,
        "limit" => $limit,
        "offset" => $offset
    ]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}

$stmt->close();
if (isset($count_stmt)) $count_stmt->close();
$conn->close();
?>