<?php
// routes/adminRoutes.php
require_once __DIR__ . '/../controllers/AdminController.php';
require_once __DIR__ . '/../middlewares/AdminAuth.php';

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

if($uri === '/api/v1/admin/login' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    login($data);
}

if(str_starts_with($uri, '/api/v1/admin/products')) {
    $admin = AdminAuth(); // verify JWT
    if($method === 'POST') createProduct($_POST, $_FILES);
}

if($uri === '/api/v1/admin/orders') {
    $admin = AdminAuth();
    getOrders();
}

if(preg_match('#/api/v1/admin/orders/(\d+)#', $uri, $matches)) {
    $admin = AdminAuth();
    getOrderById($matches[1]);
}
