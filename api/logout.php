<?php
session_start();
require_once("cors_headers.php");
session_destroy();
echo json_encode(["status" => "success", "message" => "Logged out"]);
?>
