<?php
require_once __DIR__ . '/../controllers/SubcategoryController.php';
require_once __DIR__ . '/../middlewares/AdminAuth.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); // ✅ strip query string
$method = $_SERVER['REQUEST_METHOD'];

function getFormData() {
    $data = $_POST;
    $json = json_decode(file_get_contents('php://input'), true);
    return is_array($json) ? array_merge($data, $json) : $data;
}

if(str_starts_with($uri, '/api/v1/admin/subcategories')) {
    $admin = AdminAuth();

    // CREATE SUBCATEGORY
    if($method === 'POST') createSubcategory(getFormData());

    // ✅ GET ALL SUBCATEGORIES, optionally filtered by category_id
    if($method === 'GET') {
        $category_id = $_GET['category_id'] ?? null; // read from ?category_id=
        getSubcategories($category_id);
    }

    // UPDATE SUBCATEGORY
    if($method === 'PATCH' && preg_match('#/subcategories/(\d+)#', $uri, $m)) {
        updateSubcategory($m[1], getFormData());
    }

    // DELETE SUBCATEGORY
    if($method === 'DELETE' && preg_match('#/subcategories/(\d+)#', $uri, $m)) {
        deleteSubcategory($m[1]);
    }
}
