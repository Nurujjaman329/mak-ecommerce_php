<?php
// routes/productRoutes.php
require_once __DIR__ . '/../controllers/ProductController.php';

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

if($uri === '/api/v1/products' && $method === 'GET') getProducts();

if(preg_match('#/api/v1/products/(\d+)#', $uri, $matches) && $method === 'GET') {
    getProductById($matches[1]);
}

if($uri === '/api/v1/products' && $method === 'POST') {
    createProduct($_POST, $_FILES);
}
