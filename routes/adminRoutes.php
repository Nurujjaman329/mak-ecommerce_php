<?php
// routes/adminRoutes.php
require_once __DIR__ . '/../controllers/AdminController.php';
require_once __DIR__ . '/../controllers/ProductController.php';
require_once __DIR__ . '/../middlewares/AdminAuth.php';

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

if($uri === '/api/v1/admin/login' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    login($data);
}

// Helper: Merge $_POST + JSON body for PATCH/POST
function getFormData() {
    $data = [];

    // $_POST fields
    foreach ($_POST as $k => $v) {
        $data[$k] = $v;
    }

    // JSON body fallback
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) {
        $data = array_merge($data, $json);
    }

    return $data;
}

if (str_starts_with($uri, '/api/v1/admin/products')) {
    $admin = AdminAuth(); // Validate admin for all product routes

    // CREATE PRODUCT
    if ($method === 'POST' && $uri === '/api/v1/admin/products') {
        createProduct($_POST, $_FILES);
    }

    // GET ALL PRODUCTS (admin view)
    if ($method === 'GET' && $uri === '/api/v1/admin/products') {
        getProducts();
    }

    // UPDATE PRODUCT (PATCH or POST override)
    if (($method === 'PATCH' || ($method === 'POST' && ($_POST['_method'] ?? '') === 'PATCH')) 
        && preg_match('#/products/(\d+)#', $uri, $m)) {

        // Merge $_POST + JSON body
        $data = getFormData();

        // Debug logs (optional)
        error_log("ADMIN UPDATE data: " . print_r($data, true));
        error_log("ADMIN UPDATE files: " . print_r($_FILES, true));

        // Call updateProduct from ProductController
        updateProduct($m[1], $data, $_FILES);
    }

    // DELETE PRODUCT
    if ($method === 'DELETE' && preg_match('#/products/(\d+)#', $uri, $m)) {
        deleteProduct($m[1]);
    }
}

// ADMIN ORDERS
if($uri === '/api/v1/admin/orders') {
    $admin = AdminAuth();
    getOrders();
}

if(preg_match('#/api/v1/admin/orders/(\d+)#', $uri, $matches)) {
    $admin = AdminAuth();
    getOrderById($matches[1]);
}
