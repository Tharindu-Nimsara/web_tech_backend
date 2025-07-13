<?php
require_once("cors_headers.php");
require_once("../config/db.php");

try {
    $statistics = [];

    // 1. Total Projects
    $stmt = $conn->prepare("SELECT COUNT(*) as total_projects FROM projects");
    $stmt->execute();
    $result = $stmt->get_result();
    $statistics['total_projects'] = $result->fetch_assoc()['total_projects'];

    // 2. Active Students (students who have uploaded projects)
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT student_id) as active_students FROM projects");
    $stmt->execute();
    $result = $stmt->get_result();
    $statistics['active_students'] = $result->fetch_assoc()['active_students'];

    // 3. Departments (unique departments from projects)
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT department) as departments FROM projects WHERE department IS NOT NULL AND department != ''");
    $stmt->execute();
    $result = $stmt->get_result();
    $statistics['departments'] = $result->fetch_assoc()['departments'];

    // 4. This Month's Projects
    $stmt = $conn->prepare("SELECT COUNT(*) as this_month FROM projects WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $stmt->execute();
    $result = $stmt->get_result();
    $statistics['this_month'] = $result->fetch_assoc()['this_month'];

    echo json_encode([
        "status" => "success",
        "statistics" => $statistics
    ]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>