<?php
// controllers/ProductController.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../config/upload.php';

/**
 * Get full URL for uploaded images
 */
function getFullImageUrl($relativePath) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . '://' . $host;

    if (substr($relativePath, 0, 1) !== '/') {
        $relativePath = '/' . $relativePath;
    }

    return $baseUrl . $relativePath;
}

/**
 * CREATE PRODUCT
 */
function createProduct($data, $files) {
    global $pdo;

    $name             = $data['name'] ?? '';
    $description      = $data['description'] ?? '';
    $long_description = $data['long_description'] ?? null;
    $variants         = $data['variants'] ?? null;

    if (!$files || empty($files['images']['name'][0])) {
        Response::error('At least one image is required', 400);
    }

    if (!$variants) {
        Response::error('Variants required', 400);
    }

    // Decode variants JSON
    $variantsArray = json_decode($variants, true);

    if (!is_array($variantsArray) || count($variantsArray) === 0) {
        Response::error('Variants must be a non-empty array', 400);
    }

    // Set main product price from first variant
    $price = $variantsArray[0]['price'] ?? 0;

    // Encode variants for DB
    $variants_json = json_encode($variantsArray);

    // Upload images
    $uploaded = uploadFiles($files['images']);
    $images_json = json_encode(array_map(fn($f) => "/uploads/$f", $uploaded));

    // Insert into DB
    $stmt = $pdo->prepare(
        "INSERT INTO products 
        (name, description, long_description, price, images, variants, created_at, updated_at)
        VALUES (?,?,?,?,?,?,NOW(),NOW())"
    );

    $stmt->execute([
        $name,
        $description,
        $long_description,
        $price,
        $images_json,
        $variants_json
    ]);

    // Fetch inserted product
    $id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if ($product) {
        // Decode JSON fields
        $product['images']   = json_decode($product['images'], true) ?? [];
        $product['variants'] = json_decode($product['variants'], true) ?? [];
        $product['long_description'] = $product['long_description'] ?? null;

        // Convert relative paths to full URLs
        $product['images'] = array_map(fn($img) => getFullImageUrl($img), $product['images']);
    }

    Response::success($product, 'Product created successfully', 201);
}


/**
 * GET ALL PRODUCTS
 */
function getProducts() {
    global $pdo;

    $stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
    $products = $stmt->fetchAll();

    foreach ($products as &$product) {
        $product['images']   = json_decode($product['images'], true) ?? [];
        $product['variants'] = json_decode($product['variants'], true) ?? [];
        $product['long_description'] = $product['long_description'] ?? null;

        $product['images'] = array_map(fn($img) => getFullImageUrl($img), $product['images']);
    }

    Response::success($products, 'Success');
}

/**
 * GET PRODUCT BY ID
 */
function getProductById($id) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if (!$product) Response::error('Product not found', 404);

    $product['images']   = json_decode($product['images'], true) ?? [];
    $product['variants'] = json_decode($product['variants'], true) ?? [];
    $product['long_description'] = $product['long_description'] ?? null;

    $product['images'] = array_map(fn($img) => getFullImageUrl($img), $product['images']);

    Response::success($product, 'Success');
}
