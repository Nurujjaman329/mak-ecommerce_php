<?php
// Enable CORS for frontend requests
header('Access-Control-Allow-Origin: *'); // Allow all origins, or replace * with your frontend URL
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/utils/JWT.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Simple router
$uri = $_SERVER['REQUEST_URI'];

if(str_starts_with($uri, '/api/v1/admin')) {
    require __DIR__ . '/routes/adminRoutes.php';
} elseif(str_starts_with($uri, '/api/v1/products')) {
    require __DIR__ . '/routes/productRoutes.php';
} elseif(str_starts_with($uri, '/api/v1/orders')) {
    require __DIR__ . '/routes/orderRoutes.php';
} elseif(str_starts_with($uri, '/api/v1/test')) {
    require __DIR__ . '/routes/testRoutes.php';
} else {
    echo json_encode(['message' => 'E-commerce API Running']);
}
