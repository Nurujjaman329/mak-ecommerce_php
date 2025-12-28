<?php
// routes/orderRoutes.php
require_once __DIR__ . '/../controllers/OrderController.php';

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

if($uri === '/api/v1/orders' && $method === 'GET') getOrders();
if($uri === '/api/v1/orders' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    createOrder($data);
}

if(preg_match('#/api/v1/orders/(\d+)#', $uri, $matches) && $method === 'GET') {
    getOrderById($matches[1]);
}
