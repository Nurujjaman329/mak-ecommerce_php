<?php
// controllers/ProductController.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../config/upload.php';

function createProduct($data, $files) {
    global $pdo;

    $name = $data['name'] ?? '';
    $description = $data['description'] ?? '';
    $variants = $data['variants'] ?? null;

    if(!$files || empty($files['images']['name'][0])) Response::error('At least one image is required', 400);
    if(!$variants) Response::error('Variants required', 400);

    $uploaded = uploadFiles($files['images']);
    $images_json = json_encode(array_map(fn($f) => "/uploads/$f", $uploaded));
    $variants_json = json_encode(json_decode($variants, true));

    $stmt = $pdo->prepare("INSERT INTO products (name, description, images, variants, created_at, updated_at) VALUES (?,?,?,?,NOW(),NOW())");
    $stmt->execute([$name, $description, $images_json, $variants_json]);

    $id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    // decode before sending
    $product['images'] = json_decode($product['images'], true);
    $product['variants'] = json_decode($product['variants'], true);

    // Convert relative image paths to full URLs
    $product['images'] = array_map(function($image) {
        return getFullImageUrl($image);
    }, $product['images']);

    Response::success($product, 'Product created successfully', 201);
}

function getProducts() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM products");
    $products = $stmt->fetchAll();

    // decode each product's images and variants
    foreach($products as &$product) {
        $product['images'] = json_decode($product['images'], true);
        $product['variants'] = json_decode($product['variants'], true);

        // Convert relative image paths to full URLs
        $product['images'] = array_map(function($image) {
            return getFullImageUrl($image);
        }, $product['images']);
    }

    Response::success($products);
}

function getProductById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if(!$product) Response::error('Product not found', 404);

    $product['images'] = json_decode($product['images'], true);
    $product['variants'] = json_decode($product['variants'], true);

    // Convert relative image paths to full URLs
    $product['images'] = array_map(function($image) {
        return getFullImageUrl($image);
    }, $product['images']);

    Response::success($product);
}

function getFullImageUrl($relativePath) {
    // Get the base URL from the request
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . '://' . $host;

    // Ensure the relative path starts with a slash
    if (substr($relativePath, 0, 1) !== '/') {
        $relativePath = '/' . $relativePath;
    }

    return $baseUrl . $relativePath;
}
