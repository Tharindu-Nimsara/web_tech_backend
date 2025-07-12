<?php
// advanced_cors.php - Comprehensive CORS handling

// Configuration constants
define('DEBUG_CORS', false); // Set to true for debugging CORS issues
define('IS_DEVELOPMENT', true); // Set to false for production

// Define allowed origins for different environments
$allowedOrigins = [
    'http://127.0.0.1:5501',  // VS Code Live Server
    'http://127.0.0.1:5500',  // Alternative Live Server port
    'http://localhost:3000',   // React dev server
    'http://localhost:8080',   // Vue dev server
    'https://yourdomain.com',  // Production domain
];

// Get the origin of the request
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Check if the origin is allowed
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    // For development, you might want to allow all origins
    // Comment out the next line for production
    header("Access-Control-Allow-Origin: *");
}

// Common CORS headers
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Content-Type: application/json");

// Security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// Handle preflight OPTIONS request
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    // Respond to preflight request
    header("Access-Control-Max-Age: 86400"); // Cache preflight for 24 hours
    http_response_code(200);
    exit();
}

// Optional: Log CORS requests for debugging (only if enabled)
if (DEBUG_CORS) {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? 'No origin';
    $method = $_SERVER["REQUEST_METHOD"] ?? 'No method';
    error_log("CORS Debug - Origin: $origin, Method: $method");
}
?>