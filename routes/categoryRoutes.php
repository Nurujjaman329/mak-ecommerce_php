<?php
require_once __DIR__ . '/../controllers/CategoryController.php';
require_once __DIR__ . '/../middlewares/AdminAuth.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];


function getFormData() {
    $data = $_POST;
    $json = json_decode(file_get_contents('php://input'), true);
    return is_array($json) ? array_merge($data, $json) : $data;
}

if(str_starts_with($uri, '/api/v1/admin/categories')) {
    $admin = AdminAuth();

    if($method === 'POST') createCategory(getFormData());

    if($method === 'GET') getCategories();

    if($method === 'PATCH' && preg_match('#/categories/(\d+)#', $uri, $m)) {
        updateCategory($m[1], getFormData());
        exit;
    }

    if($method === 'DELETE' && preg_match('#/categories/(\d+)#', $uri, $m)) {
        deleteCategory($m[1]);
        exit;
    }
}

